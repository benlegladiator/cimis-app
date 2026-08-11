package com.siadoc.backend.service;

import com.siadoc.backend.dto.*;
import com.siadoc.backend.dto.export.FullDossierExportDTO;
import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.MilitaireRepository;
import com.siadoc.backend.repository.CompagnieMappingGesmilRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.Optional;
import java.util.List;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class DossierExportService {

    private final DossierAdministratifRepository dossierRepository;
    private final MilitaireRepository militaireRepository;
    private final DossierAdministratifService dossierService;
    private final com.siadoc.backend.repository.CompagnieRepository compagnieRepository;
    private final CompagnieMappingGesmilRepository mappingRepository;

    // -------------------------------------------------------------------------
    // EXPORT
    // -------------------------------------------------------------------------

    @Transactional(readOnly = true)
    public FullDossierExportDTO exportByAnyMatricule(String matricule) {
        if (matricule == null) throw new RuntimeException("Matricule non fourni");

        String cleanMatricule = matricule.trim().toUpperCase();
        Optional<Militaire> oMil = findMilitaireFlexible(cleanMatricule);

        if (oMil.isEmpty() && cleanMatricule.endsWith(".")) {
            oMil = findMilitaireFlexible(cleanMatricule.substring(0, cleanMatricule.length() - 1));
        }

        Militaire militaire = oMil.orElseThrow(() ->
            new RuntimeException("Militaire non trouve avec le matricule: " + matricule));

        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaire.getId())
                .orElseThrow(() -> new RuntimeException("Dossier administratif non trouve pour ce militaire"));

        return mapToFullDTO(dossier);
    }

    private Optional<Militaire> findMilitaireFlexible(String m) {
        // PRIORITE : Matricule Militaire, puis Solde
        Optional<Militaire> res = militaireRepository.findByMatriculeMilitaire(m);
        if (res.isEmpty()) {
            res = militaireRepository.findByMatriculeSolde(m);
        }
        return res;
    }

    private FullDossierExportDTO mapToFullDTO(DossierAdministratif d) {
        FullDossierExportDTO dto = new FullDossierExportDTO();
        Militaire m = d.getMilitaire();

        dto.setDossierId(d.getId());
        dto.setMatriculeMilitaire(m.getMatriculeMilitaire());
        dto.setMatriculeSolde(m.getMatriculeSolde());
        dto.setNom(m.getNom());
        dto.setPrenom(m.getPrenom());
        dto.setDateNaissance(m.getDateNaissance() != null ? m.getDateNaissance().toString() : null);
        dto.setLieuNaissance(m.getLieuNaissance());
        
        // Enrichissement Identité
        dto.setGrade(m.getGrade());
        dto.setDateGrade(m.getDateGrade() != null ? m.getDateGrade().toString() : null);
        dto.setEchelon(m.getEchelon());
        dto.setDateEchelon(m.getDateEchelon() != null ? m.getDateEchelon().toString() : null);
        dto.setDateService(m.getDateService() != null ? m.getDateService().toString() : null);
        dto.setArmeService(m.getArmeService());
        dto.setSexe(m.getSexe());
        dto.setAptitudeOps(m.getAptitudeOps());
        dto.setStatut(m.getStatut());
        dto.setDateEnregistrement(m.getDateEnregistrement() != null ? m.getDateEnregistrement().toString() : null);
        dto.setDateMiseAJour(m.getDateMiseAJour() != null ? m.getDateMiseAJour().toString() : null);
        
        dto.setCategorie(m.getCategorie() != null ? m.getCategorie().name() : null);
        if (m.getDateService() != null) {
            dto.setAnneeContingent(m.getDateService().getYear());
        }

        // Unités
        if (d.getCompagnie() != null) {
            Compagnie comp = d.getCompagnie();
            dto.setCompagnie(comp.getNom());
            if (comp.getBataillon() != null) {
                Bataillon bat = comp.getBataillon();
                dto.setBataillon(bat.getNom());
                if (bat.getBrigade() != null) {
                    Brigade bri = bat.getBrigade();
                    dto.setBrigade(bri.getNom());
                    if (bri.getRegion() != null) {
                        dto.setRegion(bri.getRegion().getNom());
                    }
                }
            }
        }

        if (m.getPhoto() != null) {
            dto.setPhotoBase64(java.util.Base64.getEncoder().encodeToString(m.getPhoto()));
        }

        if (d.getCarriere() != null) {
            Carriere c = d.getCarriere();
            CarriereDTO cDto = new CarriereDTO();
            cDto.setCorps(c.getCorps());
            cDto.setArme(c.getArme());
            cDto.setOrigine(c.getOrigine());
            cDto.setCompagnie(c.getNomCompagnie());
            cDto.setObservationEmploi(c.getObservationEmploi());

            // Reengagements
            if (c.getReengagements() != null) {
                cDto.setReengagements(c.getReengagements().stream().map(r -> {
                    ReengagementDTO rDto = new ReengagementDTO();
                    rDto.setId(r.getId());
                    rDto.setDesignation(r.getDesignation());
                    rDto.setLieu(r.getLieu());
                    rDto.setDate(r.getDate());
                    return rDto;
                }).collect(Collectors.toList()));
            }

            // Admissions SOC
            if (c.getAdmissionSocs() != null) {
                cDto.setAdmissionSocs(c.getAdmissionSocs().stream().map(a -> {
                    AdmissionSocDTO aDto = new AdmissionSocDTO();
                    aDto.setId(a.getId());
                    aDto.setDesignation(a.getDesignation());
                    aDto.setLieu(a.getLieu());
                    aDto.setDate(a.getDate());
                    return aDto;
                }).collect(Collectors.toList()));
            }

            dto.setCarriere(cDto);
        }

        if (d.getMedicalModule() != null) {
            MedicalGlobalDTO mDto = new MedicalGlobalDTO();
            MedicalModule mm = d.getMedicalModule();
            
            if (mm.getBlessures() != null) {
                mDto.setBlessures(mm.getBlessures().stream().map(b -> {
                    BlessureDTO bDto = new BlessureDTO();
                    bDto.setId(b.getId());
                    bDto.setNature(b.getNature());
                    bDto.setDateEffet(b.getDateEffet());
                    bDto.setLieu(b.getLieu());
                    bDto.setAutorite(b.getAutorite());
                    bDto.setDocument(b.getDocumentNom());
                    return bDto;
                }).collect(Collectors.toList()));
            }

            if (mm.getPensions() != null) {
                mDto.setPensions(mm.getPensions().stream().map(p -> {
                    PensionDTO pDto = new PensionDTO();
                    pDto.setId(p.getId());
                    pDto.setTypeInvalidite(p.getTypeInvalidite());
                    pDto.setDatePriseEffet(p.getDatePriseEffet());
                    pDto.setReference(p.getReference());
                    pDto.setTaux(p.getTaux());
                    pDto.setDocument(p.getDocumentNom());
                    return pDto;
                }).collect(Collectors.toList()));
            }
            dto.setMedical(mDto);
        }

        if (d.getEtatCivil() != null) {
            EtatCivil ec = d.getEtatCivil();
            EtatCivilDTO ecDto = new EtatCivilDTO();
            
            if (ec.getInformationsPersonnelles() != null) {
                InformationsPersonnelles ip = ec.getInformationsPersonnelles();
                InfoPersonnellesDTO ipDto = new InfoPersonnellesDTO();
                ipDto.setSexe(ip.getSexe());
                ipDto.setNumeroCNI(ip.getNumeroCNI());
                ipDto.setSituationMatrimoniale(ip.getSituationMatrimoniale());
                ipDto.setRegime(ip.getRegime());
                ipDto.setNombreConjoints(ip.getNombreConjoints());
                ipDto.setNombreEnfants(ip.getNombreEnfants());
                ipDto.setTelephone(ip.getTelephone());
                ipDto.setPpcaNom(ip.getPpcaNom());
                ipDto.setPpcaTelephone(ip.getPpcaTelephone());
                ipDto.setPpcaLien(ip.getPpcaLien());
                ipDto.setAdresseComplete(ip.getAdresseComplete());
                ipDto.setRegionOrigine(ip.getRegionOrigine());
                ipDto.setLanguesParlees(ip.getLanguesParlees());
                ecDto.setInformationsPersonnelles(ipDto);
            }

            if (ec.getCnis() != null) {
                ecDto.setCnis(ec.getCnis().stream().map(cni -> {
                    CniDTO cniDto = new CniDTO();
                    cniDto.setNumero(cni.getNumero());
                    cniDto.setDateDelivrance(cni.getDateDelivrance());
                    cniDto.setDateExpiration(cni.getDateExpiration());
                    cniDto.setLieuDelivrance(cni.getLieuDelivrance());
                    cniDto.setFichierNom(cni.getFichierNom());
                    return cniDto;
                }).collect(Collectors.toList()));
            }

            if (ec.getActesNaissance() != null) {
                ecDto.setActesNaissance(ec.getActesNaissance().stream().map(a -> {
                    ActeDTO aDto = new ActeDTO();
                    aDto.setNumeroActe(a.getNumeroActe());
                    aDto.setDateEtablissement(a.getDateEtablissement());
                    aDto.setLieuEtablissement(a.getLieuEtablissement());
                    aDto.setFichierNom(a.getFichierNom());
                    return aDto;
                }).collect(Collectors.toList()));
            }

            if (ec.getActesMariage() != null) {
                ecDto.setActesMariage(ec.getActesMariage().stream().map(a -> {
                    ActeDTO aDto = new ActeDTO();
                    aDto.setNumeroActe(a.getNumeroActe());
                    aDto.setDateMariage(a.getDateMariage());
                    aDto.setLieuMariage(a.getLieuMariage());
                    aDto.setNomConjoint(a.getNomConjoint());
                    aDto.setFichierNom(a.getFichierNom());
                    return aDto;
                }).collect(Collectors.toList()));
            }

            if (ec.getActesDeces() != null) {
                ecDto.setActesDeces(ec.getActesDeces().stream().map(a -> {
                    ActeDTO aDto = new ActeDTO();
                    aDto.setNumeroActe(a.getNumeroActe());
                    aDto.setDateDeces(a.getDateDeces());
                    aDto.setLieuDeces(a.getLieuDeces());
                    aDto.setFichierNom(a.getDocumentNom());
                    return aDto;
                }).collect(Collectors.toList()));
            }

            if (ec.getActesDivorce() != null) {
                ecDto.setActesDivorce(ec.getActesDivorce().stream().map(a -> {
                    ActeDTO aDto = new ActeDTO();
                    aDto.setNumeroJugement(a.getNumeroJugement());
                    aDto.setDateJugement(a.getDateJugement());
                    aDto.setTribunal(a.getTribunal());
                    aDto.setFichierNom(a.getDocumentNom());
                    return aDto;
                }).collect(Collectors.toList()));
            }

            if (ec.getJugementsSuppletifs() != null) {
                ecDto.setJugementsSuppletifs(ec.getJugementsSuppletifs().stream().map(a -> {
                    ActeDTO aDto = new ActeDTO();
                    aDto.setNumeroJugement(a.getNumeroJugement());
                    aDto.setDateJugement(a.getDateJugement());
                    aDto.setTribunal(a.getTribunal());
                    aDto.setFichierNom(a.getDocumentNom());
                    return aDto;
                }).collect(Collectors.toList()));
            }

            dto.setEtatCivil(ecDto);
        }

        if (d.getDiplomeModule() != null && d.getDiplomeModule().getItems() != null) {
            dto.setDiplomes(d.getDiplomeModule().getItems().stream().map(item -> {
                DiplomeDTO itemDto = new DiplomeDTO();
                itemDto.setId(item.getId());
                itemDto.setDesignation(item.getDesignation());
                itemDto.setEcole(item.getEcole());
                itemDto.setDateObtention(item.getDateObtention());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        if (d.getStageModule() != null && d.getStageModule().getItems() != null) {
            dto.setStages(d.getStageModule().getItems().stream().map(item -> {
                StageDTO itemDto = new StageDTO();
                itemDto.setId(item.getId());
                itemDto.setDesignation(item.getDesignation());
                itemDto.setDiplome(item.getDiplome());
                itemDto.setVille(item.getVille());
                itemDto.setPays(item.getPays());
                itemDto.setDateObtention(item.getDateObtention());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        if (d.getMutationsModule() != null && d.getMutationsModule().getItems() != null) {
            dto.setMutations(d.getMutationsModule().getItems().stream().map(item -> {
                MutationItemDTO itemDto = new MutationItemDTO();
                itemDto.setId(item.getId());
                itemDto.setEmploi(item.getEmploi());
                itemDto.setType(item.getType() != null ? item.getType().name() : null);
                itemDto.setNumeroTexte(item.getNumeroTexte());
                itemDto.setDateTexte(item.getDateTexte() != null ? item.getDateTexte().toString() : null);
                if (item.getCompagnie() != null) itemDto.setCompagnieNom(item.getCompagnie().getNom());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        if (d.getAvancementModule() != null && d.getAvancementModule().getAvancements() != null) {
            dto.setAvancements(d.getAvancementModule().getAvancements().stream().map(item -> {
                AvancementDTO itemDto = new AvancementDTO();
                itemDto.setId(item.getId());
                itemDto.setAvancement(item.getAvancement());
                itemDto.setNumeroTexte(item.getNumeroTexte());
                itemDto.setSignataire(item.getSignataire());
                itemDto.setDateEffet(item.getDateEffet());
                itemDto.setTypeAvancement(item.getTypeAvancement());
                itemDto.setDureeAnnees(item.getDureeAnnees());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        if (d.getPunitionModule() != null && d.getPunitionModule().getItems() != null) {
            dto.setPunitions(d.getPunitionModule().getItems().stream().map(item -> {
                PunitionDTO itemDto = new PunitionDTO();
                itemDto.setId(item.getId());
                itemDto.setDesignation(item.getDesignation());
                itemDto.setTexte(item.getTexte());
                itemDto.setDateEffet(item.getDateEffet());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        if (d.getNotationModule() != null && d.getNotationModule().getItems() != null) {
            dto.setNotations(d.getNotationModule().getItems().stream().map(item -> {
                NotationDTO itemDto = new NotationDTO();
                itemDto.setId(item.getId());
                itemDto.setPeriodeDu(item.getPeriodeDu());
                itemDto.setPeriodeAu(item.getPeriodeAu());
                itemDto.setAppreciationGenerale(item.getAppreciationGenerale());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        if (d.getRecompenseModule() != null && d.getRecompenseModule().getItems() != null) {
            dto.setRecompenses(d.getRecompenseModule().getItems().stream().map(item -> {
                RecompenseDTO itemDto = new RecompenseDTO();
                itemDto.setId(item.getId());
                itemDto.setDesignation(item.getDesignation());
                itemDto.setTexte(item.getTexte());
                itemDto.setDateEffet(item.getDateEffet());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        if (d.getCampagneMilitaireModule() != null && d.getCampagneMilitaireModule().getItems() != null) {
            dto.setCampagnes(d.getCampagneMilitaireModule().getItems().stream().map(item -> {
                CampagneDTO itemDto = new CampagneDTO();
                itemDto.setId(item.getId());
                itemDto.setDesignation(item.getDesignation());
                itemDto.setSignataire(item.getSignataire());
                itemDto.setDate(item.getDate());
                return itemDto;
            }).collect(Collectors.toList()));
        }

        return dto;
    }

    // -------------------------------------------------------------------------
    // IMPORT UNITAIRE
    // -------------------------------------------------------------------------

    @Transactional
    public ImportResultDTO importFullDossier(FullDossierExportDTO dto) {
        return doImport(dto);
    }

    // -------------------------------------------------------------------------
    // IMPORT EN LOT
    // -------------------------------------------------------------------------

    @Transactional
    public BulkImportResultDTO importBulkDossiers(List<FullDossierExportDTO> dtos) {
        int success = 0, skipped = 0;
        List<String> errors = new java.util.ArrayList<>();
        for (FullDossierExportDTO dto : dtos) {
            try {
                doImport(dto);
                success++;
            } catch (Exception e) {
                skipped++;
                String id = dto.getMatriculeMilitaire() != null ? dto.getMatriculeMilitaire() : (dto.getMatriculeSolde() != null ? dto.getMatriculeSolde() : "?");
                errors.add(id + " : " + e.getMessage());
            }
        }
        return new BulkImportResultDTO(dtos.size(), success, skipped, errors);
    }

    // -------------------------------------------------------------------------
    // POOL NON AFFECTES
    // -------------------------------------------------------------------------

    public List<Militaire> getMilitairesSansCompagnie() {
        return militaireRepository.findSansCompagnie();
    }

    @Transactional(readOnly = true)
    public List<FullDossierExportDTO> exportAllMilitaires() {
        return militaireRepository.findActifs().stream()
                .filter(m -> m.getDossier() != null)
                .map(this::mapToFullDTOFromMilitaire)
                .collect(Collectors.toList());
    }

    @Transactional(readOnly = true)
    public List<FullDossierExportDTO> exportByRegistrationDateRange(java.time.LocalDateTime start, java.time.LocalDateTime end) {
        return militaireRepository.findByDateEnregistrementBetween(start, end).stream()
                .filter(m -> m.getDossier() != null)
                .map(this::mapToFullDTOFromMilitaire)
                .collect(Collectors.toList());
    }

    @Transactional(readOnly = true)
    public List<FullDossierExportDTO> exportByUpdateDateRange(java.time.LocalDateTime start, java.time.LocalDateTime end) {
        return militaireRepository.findByDateMiseAJourBetween(start, end).stream()
                .filter(m -> m.getDossier() != null)
                .map(this::mapToFullDTOFromMilitaire)
                .collect(Collectors.toList());
    }

    private FullDossierExportDTO mapToFullDTOFromMilitaire(Militaire m) {
        if (m.getDossier() == null) return null;
        return mapToFullDTO(m.getDossier());
    }

    @Transactional
    public void assignMilitairesToCompagnie(java.util.List<java.util.UUID> militaireIds, java.util.UUID compagnieId) {
        Compagnie compagnie = compagnieRepository.findById(compagnieId)
                .orElseThrow(() -> new RuntimeException("Compagnie introuvable"));

        for (java.util.UUID mId : militaireIds) {
            DossierAdministratif dossier = dossierRepository.findByMilitaireId(mId)
                    .orElseThrow(() -> new RuntimeException("Dossier introuvable pour le militaire " + mId));
            
            dossier.setCompagnie(compagnie);
            
            // On s'assure que la carrière reflète aussi ce changement de nom pour cohérence visuelle
            if (dossier.getCarriere() != null) {
                dossier.getCarriere().setNomCompagnie(compagnie.getNom());
            }
            
            dossierRepository.save(dossier);
        }
    }

    // -------------------------------------------------------------------------
    // LOGIQUE INTERNE D'IMPORT
    // -------------------------------------------------------------------------

    private ImportResultDTO doImport(FullDossierExportDTO dto) {
        if (dto == null || (dto.getMatriculeMilitaire() == null && dto.getMatriculeSolde() == null)) {
            throw new RuntimeException("Donnees d'importation invalides (Matricule manquant)");
        }

        String targetMatricule = dto.getMatriculeMilitaire() != null ? dto.getMatriculeMilitaire() : dto.getMatriculeSolde();
        boolean isNouveau = findMilitaireFlexible(targetMatricule).isEmpty();

        Militaire militaire = findMilitaireFlexible(targetMatricule)
                .orElseGet(() -> {
                    Militaire m = new Militaire();
                    m.setMatriculeMilitaire(dto.getMatriculeMilitaire());
                    m.setMatriculeSolde(dto.getMatriculeSolde());
                    m.setStatutValidation(StatutValidation.VALIDE);
                    m.setEtat(EtatMilitaire.ACTIF);
                    return m;
                });

        militaire.setNom(dto.getNom());
        militaire.setPrenom(dto.getPrenom());
        // Mise à jour des deux matricules si présents
        if (dto.getMatriculeMilitaire() != null) militaire.setMatriculeMilitaire(dto.getMatriculeMilitaire());
        if (dto.getMatriculeSolde() != null) militaire.setMatriculeSolde(dto.getMatriculeSolde());

        if (dto.getDateNaissance() != null) {
            try { militaire.setDateNaissance(java.time.LocalDate.parse(dto.getDateNaissance())); }
            catch (Exception ignored) {}
        }
        militaire.setLieuNaissance(dto.getLieuNaissance());
        militaire = militaireRepository.save(militaire);

        final Militaire mSaved = militaire;
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaire.getId())
                .orElseGet(() -> dossierService.initialiserDossier(mSaved, null, StatutDossier.ADMINISTRATIF));

        String codeCompagnieGesmil = null;
        if (dto.getCarriere() != null) {
            Carriere c = dossier.getCarriere() != null ? dossier.getCarriere() : new Carriere();
            c.setDossier(dossier);
            c.setCorps(dto.getCarriere().getCorps());
            c.setArme(dto.getCarriere().getArme());
            c.setNomCompagnie(dto.getCarriere().getCompagnie());
            c.setObservationEmploi(dto.getCarriere().getObservationEmploi());
            dossier.setCarriere(c);
            codeCompagnieGesmil = dto.getCarriere().getCompagnie();
        }

        boolean compagnieTrouvee = false;
        if (codeCompagnieGesmil != null && !codeCompagnieGesmil.isBlank()) {
            final String codeGesmil = codeCompagnieGesmil;
            compagnieTrouvee = mappingRepository.findByCodeGesmilIgnoreCase(codeGesmil)
                    .map(mapping -> {
                        dossier.setCompagnie(mapping.getCompagnie());
                        return true;
                    }).orElse(false);
        }

        if (dto.getDiplomes() != null) {
            if (dossier.getDiplomeModule().getItems() == null)
                dossier.getDiplomeModule().setItems(new java.util.ArrayList<>());
            for (DiplomeDTO dDto : dto.getDiplomes()) {
                boolean exists = dossier.getDiplomeModule().getItems().stream()
                        .anyMatch(item -> item.getDesignation() != null &&
                                item.getDesignation().equalsIgnoreCase(dDto.getDesignation()));
                if (!exists) {
                    DiplomeItem item = new DiplomeItem();
                    item.setDesignation(dDto.getDesignation());
                    item.setEcole(dDto.getEcole());
                    item.setDateObtention(dDto.getDateObtention());
                    item.setModule(dossier.getDiplomeModule());
                    dossier.getDiplomeModule().getItems().add(item);
                }
            }
        }

        dossierRepository.save(dossier);

        String statut = isNouveau ? "CREE" : "MIS_A_JOUR";
        String compagnieInfo = compagnieTrouvee ? dossier.getCompagnie().getNom() : "Non affecte";
        return new ImportResultDTO(militaire.getMatriculeMilitaire(), dto.getNom(), dto.getPrenom(), statut, compagnieInfo);
    }

    // -------------------------------------------------------------------------
    // DTOs de resultat
    // -------------------------------------------------------------------------

    public record ImportResultDTO(String matricule, String nom, String prenom, String statut, String compagnie) {}
    public record BulkImportResultDTO(int total, int success, int echecs, List<String> details) {}
}