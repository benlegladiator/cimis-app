package com.siadoc.backend.service;

import com.siadoc.backend.dto.TeecLigneDTO;
import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.security.UserSession;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.ArrayList;
import java.util.List;
import java.util.Set;
import java.util.UUID;
import java.util.stream.Collectors;

@Service
public class TeecService {

    private static final Logger log = LoggerFactory.getLogger(TeecService.class);
    private final DossierAdministratifRepository dossierRepository;
    private final UserSession userSession;

    public TeecService(DossierAdministratifRepository dossierRepository, UserSession userSession) {
        this.dossierRepository = dossierRepository;
        this.userSession = userSession;
    }

    private static final Set<String> GRADES_OFFICIER = Set.of(
        "ASPIRANT", "SOUS-LIEUTENANT", "LIEUTENANT", "CAPITAINE",
        "COMMANDANT", "CHEF DE BATAILLON", "CHEF D'ESCADRON",
        "LIEUTENANT-COLONEL", "COLONEL", "GENERAL"
    );

    @Transactional(readOnly = true)
    public List<TeecLigneDTO> genererTeec(UUID compagnieId) {
        log.info(">>> GENERER TEEC - Compagnie ID: {}", compagnieId);

        Utilisateur user = userSession.getCurrentUser();
        List<DossierAdministratif> dossiers;

        if (compagnieId != null) {
            dossiers = dossierRepository.findByCompagnieId(compagnieId);
        } else {
            if (user == null || user.getRole() == Role.DRH || user.getRole() == Role.SUPER_ADMIN) {
                dossiers = dossierRepository.findAll();
            } else if (user.getRole() == Role.ETAT_MAJOR_TERRE) {
                dossiers = dossierRepository.findByArmeService("TERRE", "AT");
            } else if (user.getRole() == Role.ETAT_MAJOR_AIR) {
                dossiers = dossierRepository.findByArmeService("AIR", "AA");
            } else if (user.getRole() == Role.ETAT_MAJOR_MARINE) {
                dossiers = dossierRepository.findByArmeService("MARINE", "AM");
            } else if (user.getRegion() != null) {
                dossiers = dossierRepository.findByRegionId(user.getRegion().getId());
            } else if (user.getBrigade() != null) {
                dossiers = dossierRepository.findByBrigadeId(user.getBrigade().getId());
            } else if (user.getBataillon() != null) {
                dossiers = dossierRepository.findByBataillonId(user.getBataillon().getId());
            } else if (user.getCompagnie() != null) {
                dossiers = dossierRepository.findByCompagnieId(user.getCompagnie().getId());
            } else {
                dossiers = new ArrayList<>();
            }
        }

        log.info(">>> Dossiers trouves: {}", dossiers.size());
        
        // TRI: On priorise CCS/CCT/COMMANDEMENT, puis le nom de la compagnie, puis Commandant, puis le numéro, puis ordre alphabétique
        List<TeecLigneDTO> dtos = dossiers.stream()
            .filter(d -> d.getMilitaire() != null)
            .map(this::mapperToDTO)
            .sorted((d1, d2) -> {
                String c1 = d1.getNomCompagnie() != null ? d1.getNomCompagnie().toUpperCase() : "ZZZ";
                String c2 = d2.getNomCompagnie() != null ? d2.getNomCompagnie().toUpperCase() : "ZZZ";

                // 1. Groupement par compagnie (priorité CCS)
                if (!c1.equals(c2)) {
                    boolean isC1Cmd = c1.contains("CCS") || c1.contains("CCT") || c1.contains("COMMANDEMENT");
                    boolean isC2Cmd = c2.contains("CCS") || c2.contains("CCT") || c2.contains("COMMANDEMENT");
                    if (isC1Cmd && !isC2Cmd) return -1;
                    if (!isC1Cmd && isC2Cmd) return 1;
                    return c1.compareTo(c2);
                }

                // 2. Priorité au Commandant de Compagnie au sein de la même unité
                String p1 = d1.getEmploiPoste() != null ? d1.getEmploiPoste().toUpperCase() : "";
                String p2 = d2.getEmploiPoste() != null ? d2.getEmploiPoste().toUpperCase() : "";
                boolean isCmd1 = p1.contains("COMMANDANT DE COMPAGNIE") || p1.contains("CHEF DE CORPS");
                boolean isCmd2 = p2.contains("COMMANDANT DE COMPAGNIE") || p2.contains("CHEF DE CORPS");
                if (isCmd1 && !isCmd2) return -1;
                if (!isCmd1 && isCmd2) return 1;

                // 3. Tri par Numéro existant
                String n1 = d1.getNumero() != null ? d1.getNumero() : "ZZZ";
                String n2 = d2.getNumero() != null ? d2.getNumero() : "ZZZ";
                int compNum = n1.compareTo(n2);
                if (compNum != 0) return compNum;

                // 4. Tri par ordre alphabétique
                return d1.getNomPrenom().compareToIgnoreCase(d2.getNomPrenom());
            })
            .collect(Collectors.toList());

        // Trouver le numéro maximum pre-existant pour que le compteur s'ajuste (ex: commence après 4.1 -> 5)
        int maxAssigned = 0;
        for (TeecLigneDTO dto : dtos) {
            if (dto.getNumero() != null && !dto.getNumero().equals("ZZZ")) {
                try {
                    String cleanNum = dto.getNumero().split("\\.")[0].replaceAll("[^0-9]", "");
                    if (!cleanNum.isEmpty()) {
                        int n = Integer.parseInt(cleanNum);
                        if (n > maxAssigned) maxAssigned = n;
                    }
                } catch (Exception e) {}
            }
        }

        // Le compteur global continue à partir du dernier grand numéro assigné
        int currentNum = (maxAssigned > 0) ? maxAssigned + 1 : 2;
        
        for (TeecLigneDTO dto : dtos) {
            if (dto.getNumero() == null) {
                dto.setNumero(String.valueOf(currentNum++));
            }
        }
        
        return dtos;
    }

    private TeecLigneDTO mapperToDTO(DossierAdministratif dossier) {
        Militaire m = dossier.getMilitaire();
        TeecLigneDTO dto = new TeecLigneDTO();
        dto.setNomPrenom((m.getNom() != null ? m.getNom() : "") + " " + (m.getPrenom() != null ? m.getPrenom() : ""));
        dto.setGrade(m.getGrade());
        dto.setEchelon(m.getEchelon());
        dto.setDateEntreeService(m.getDateService());
        dto.setAptitudeOps(determinerAptitude(dossier));

        // 1. Calcul Catégories (X, Y, Z, C)
        determinerCategorie(dto, m);

        // 2. Région et Langues (InformationsPersonnelles)
        if (dossier.getEtatCivil() != null && dossier.getEtatCivil().getInformationsPersonnelles() != null) {
            InformationsPersonnelles ip = dossier.getEtatCivil().getInformationsPersonnelles();
            dto.setRegionOrigine(ip.getRegionOrigine());
            dto.setLanguesParlees(ip.getLanguesParlees());
        }

        // 3. Observation sur l'emploi (Carriere)
        if (dossier.getCarriere() != null) {
            dto.setObservationEmploi(dossier.getCarriere().getObservationEmploi());
        }

        // 4. Emploi / Poste (Dernière Mutation)
        if (dossier.getMutationsModule() != null && dossier.getMutationsModule().getItems() != null && !dossier.getMutationsModule().getItems().isEmpty()) {
            MutationItem last = dossier.getMutationsModule().getItems().get(dossier.getMutationsModule().getItems().size() - 1);
            String rawEmploi = last.getEmploi();
            if (rawEmploi != null && rawEmploi.contains(" - ")) {
                String[] parts = rawEmploi.split(" - ", 2);
                dto.setNumero(parts[0]);
                dto.setEmploiPoste(parts[1]);
            } else {
                dto.setEmploiPoste(rawEmploi);
            }
            dto.setDatePriseFonction(last.getDateTexte());
        }

        // 5. Qualifications (Diplômes et Stages)
        StringBuilder qMil = new StringBuilder();
        StringBuilder qCiv = new StringBuilder();

        if (dossier.getDiplomeModule() != null && dossier.getDiplomeModule().getItems() != null) {
            for (DiplomeItem item : dossier.getDiplomeModule().getItems()) {
                if (qCiv.length() > 0) qCiv.append(", ");
                qCiv.append(item.getDesignation());
            }
        }
        if (dossier.getStageModule() != null && dossier.getStageModule().getItems() != null) {
            for (StageItem item : dossier.getStageModule().getItems()) {
                if (qMil.length() > 0) qMil.append(", ");
                qMil.append(item.getDesignation());
            }
        }
        dto.setQualifCivile(qCiv.toString());
        dto.setQualifMilitaire(qMil.toString());

        if (dossier.getCompagnie() != null) {
            dto.setNomCompagnie(dossier.getCompagnie().getNom());
        } else {
            dto.setNomCompagnie("HORS UNITÉ");
        }

        return dto;
    }

    private String determinerAptitude(DossierAdministratif dossier) {
        // Vérifier s'il y a des enregistrements dans le dossier médical
        if (dossier.getMedicalModule() != null) {
            MedicalModule medical = dossier.getMedicalModule();
            
            // Vérifier les blessures
            boolean hasBlessures = (medical.getBlessures() != null && !medical.getBlessures().isEmpty());
            
            // Vérifier les pensions d'invalidité
            boolean hasPensions = (medical.getPensions() != null && !medical.getPensions().isEmpty());
            
            // Vérifier les arrêts de travail
            boolean hasArretsTravail = (medical.getArretsTravail() != null && !medical.getArretsTravail().isEmpty());
            
            // Si blessure OU pension d'invalidité OU arrêt de travail -> "APTITUDE À EXAMINER"
            if (hasBlessures || hasPensions || hasArretsTravail) {
                return "APTITUDE À EXAMINER";
            }
        }
        
        // Si aucun enregistrement médical trouvé -> "APTE"
        return "APTE";
    }

    private void determinerCategorie(TeecLigneDTO ligne, Militaire mil) {
        String grade = mil.getGrade();
        String arme = mil.getArmeService();
        String ech = (mil.getEchelon() != null) ? mil.getEchelon().toString() : "X"; // "X" par défaut si pas d'échelon

        CategorieMilitaire cat = GradeService.determinerCategorie(grade, arme);

        if (cat == CategorieMilitaire.OFFICIER) {
            ligne.setCategorieX(ech);
        } else if (cat == CategorieMilitaire.SOUS_OFFICIER) {
            ligne.setCategorieY(ech);
        } else {
            // Pour le TEEC, on distingue souvent les Gendarmes (C) des autres MDR (Z)
            if (grade != null && grade.toUpperCase().contains("GENDARME") && !grade.toUpperCase().contains("MAJOR")) {
                ligne.setCategorieC(ech);
            } else {
                ligne.setCategorieZ(ech);
            }
        }
    }
}
