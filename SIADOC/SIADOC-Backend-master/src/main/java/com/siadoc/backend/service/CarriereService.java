package com.siadoc.backend.service;

import com.siadoc.backend.dto.AdmissionSocDTO;
import com.siadoc.backend.dto.CarriereDTO;
import com.siadoc.backend.dto.ReengagementDTO;
import com.siadoc.backend.dto.search.RechercheCarriereDTO;
import com.siadoc.backend.dto.search.ResultRechercheCarriereDTO;
import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;
import jakarta.transaction.Transactional;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.time.LocalDate;
import java.time.Period;
import java.time.temporal.ChronoUnit;
import java.util.UUID;
import java.util.stream.Collectors;

@Service
@Transactional
@RequiredArgsConstructor // Lombok génère le constructeur
public class CarriereService {

    private final CarriereRepository carriereRepository;
    private final DossierAdministratifRepository dossierRepository;
    private final ReengagementRepository reengagementRepository;
    private final AdmissionSocRepository admissionSocRepository;
    private final AvancementRepository avancementRepository;


    // ============================================================
    // CLASSE INTERNE pour retourner les anciennetés calculées
    // ============================================================
    public record AncienneteCalculee(
            String ancienneteServiceFormatee,  // ex: "15 ans, 3 mois, 12 jours"
            String ancienneteGradeFormatee,    // ex: "5 ans, 2 mois"
            int totalAnneesProlongation,       // ex: 3 (pour info)
            LocalDate dateReference            // Date utilisée pour le calcul (now ou archivage)
    ) {}

    // ============================================================
    // METHODE PRINCIPALE DE CALCUL
    // ============================================================
    public AncienneteCalculee calculerAnciennetes(UUID militaireId) {

        // 1. Récupération du dossier et du militaire
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier non trouvé"));

        Militaire militaire = dossier.getMilitaire();

        // 2. Détermination de la date butoir (logique métier cruciale)
        LocalDate dateButoir;
        boolean estArchive = dossier.getStatut() == StatutDossier.ARCHIVE;

        if (estArchive && dossier.getDateArchivage() != null) {
            // Si archivé, on fige le calcul à la date d'archivage
            dateButoir = dossier.getDateArchivage().toLocalDate();
        } else {
            // Sinon, calcul jusqu'à aujourd'hui
            dateButoir = LocalDate.now();
        }

        // 3. Récupération des dates du militaire
        LocalDate dateService = militaire.getDateService();
        LocalDate dateGrade = militaire.getDateGrade();

        if (dateService == null || dateGrade == null) {
            throw new RuntimeException("Dates de service ou de grade manquantes pour le calcul");
        }

        // 4. Calcul des anciennetés BRUTES (sans prolongation)
        long joursServiceBrut = ChronoUnit.DAYS.between(dateService, dateButoir);
        long joursGrade = ChronoUnit.DAYS.between(dateGrade, dateButoir);

        // 5. Calcul des PROLONGATIONS (partie complexe)
        int totalAnneesProlongation = 0;

        if (dossier.getAvancementModule() != null) {
            // On récupère la somme de toutes les prolongations enregistrées
            Integer somme = avancementRepository.sumProlongationsByMilitaireId(
                    dossier.getAvancementModule().getId()
            );
            totalAnneesProlongation = (somme != null) ? somme : 0;
        }

        // 6. AJOUT des prolongations à l'ancienneté de service
        // On ajoute les jours correspondant aux années de prolongation
        long joursProlongation = totalAnneesProlongation * 365L; // Approximation simple
        long joursServiceTotal = joursServiceBrut + joursProlongation;

        // 7. Formatage en "X ans, Y mois, Z jours"
        String ancienneteService = formaterDuree(joursServiceTotal);
        String ancienneteGrade = formaterDuree(joursGrade);

        return new AncienneteCalculee(
                ancienneteService,
                ancienneteGrade,
                totalAnneesProlongation,
                dateButoir
        );
    }

    // ============================================================
    // METHODE PRIVEE : Formatage lisible humain
    // ============================================================
    private String formaterDuree(long totalJours) {
        if (totalJours < 0) return "Date future non valide";

        LocalDate startDate = LocalDate.now();
        LocalDate endDate = startDate.plusDays(totalJours);

        // Calcul précis avec Period (gère les années bissextiles)
        Period period = Period.between(startDate, endDate);

        StringBuilder sb = new StringBuilder();
        if (period.getYears() > 0) {
            sb.append(period.getYears()).append(" an");
            if (period.getYears() > 1) sb.append("s");
        }
        if (period.getMonths() > 0) {
            if (sb.length() > 0) sb.append(", ");
            sb.append(period.getMonths()).append(" mois");
        }
        if (period.getDays() > 0 || sb.length() == 0) {
            if (sb.length() > 0) sb.append(", ");
            sb.append(period.getDays()).append(" jour");
            if (period.getDays() > 1) sb.append("s");
        }

        return sb.toString();
    }

    // 1. RECUPERER (GET)
    public CarriereDTO getCarriereByMilitaire(UUID militaireId) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier non trouvé"));

        Carriere module = dossier.getCarriere(); // Supposons que la relation existe dans Dossier

        // Si le module n'existe pas encore (cas rare), on le crée
        if (module == null) {
            module = new Carriere();
            module.setDossier(dossier);
            module = carriereRepository.save(module);
        }

        // NOUVEAU : Calcul des anciennetés
        AncienneteCalculee anciennetes = calculerAnciennetes(militaireId);

        return mapToDTO(module, anciennetes, dossier.getMilitaire(),dossier);
    }

    // 2. METTRE A JOUR (PUT)
    public CarriereDTO updateCarriere(UUID militaireId, CarriereDTO dto) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier non trouvé"));

        Carriere module = dossier.getCarriere();

        // Mise à jour des champs
        module.setCorps(dto.getCorps());
        module.setArme(dto.getArme());
        module.setOrigine(dto.getOrigine());
        module.setCnim(dto.getCnim());
        module.setTypeStructure(dto.getFormationStructure());
        module.setNomCompagnie(dto.getCompagnie());
        module.setObservationEmploi(dto.getObservationEmploi());

        // Update Militaire for aptitudeOps
        Militaire m = dossier.getMilitaire();
        m.setAptitudeOps(dto.getAptitudeOps());

        Carriere saved = carriereRepository.save(module);
        AncienneteCalculee anciennetes = calculerAnciennetes(militaireId);
        return mapToDTO(saved, anciennetes, dossier.getMilitaire(),dossier);
    }

    // 3. AJOUTER UN RÉENGAGEMENT
    public ReengagementDTO addReengagement(UUID militaireId, ReengagementDTO dto) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier non trouvé"));

        Reengagement entity = new Reengagement();
        entity.setDesignation(dto.getDesignation());
        entity.setLieu(dto.getLieu());
        entity.setDate(dto.getDate());
        entity.setCarriere(dossier.getCarriere());

        Reengagement saved = reengagementRepository.save(entity);

        // Mapping manuel pour le retour
        ReengagementDTO response = new ReengagementDTO();
        response.setId(saved.getId());
        response.setDesignation(saved.getDesignation());
        response.setLieu(saved.getLieu());
        response.setDate(saved.getDate());
        return response;
    }

    public AdmissionSocDTO addAdmission(UUID militaireId, AdmissionSocDTO dto) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier non trouvé"));

        AdmissionSoc entity = new AdmissionSoc();
        entity.setDesignation(dto.getDesignation());
        entity.setLieu(dto.getLieu());
        entity.setDate(dto.getDate());
        entity.setCarriere(dossier.getCarriere());

        AdmissionSoc saved = admissionSocRepository.save(entity);

        // Mapping manuel pour le retour
        AdmissionSocDTO response = new AdmissionSocDTO();
        response.setId(saved.getId());
        response.setDesignation(saved.getDesignation());
        response.setLieu(saved.getLieu());
        response.setDate(saved.getDate());
        return response;
    }

    // 4. SUPPRIMER UN RÉENGAGEMENT
    public void deleteReengagement(UUID reengagementId) {
        reengagementRepository.deleteById(reengagementId);
    }
    public void deleteAdmission(UUID admissionId) {
        admissionSocRepository.deleteById(admissionId);
    }

    // 5. UPLOAD FICHIER
    public void uploadFichier(UUID militaireId, MultipartFile file) throws IOException {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId).orElseThrow();
        Carriere module = dossier.getCarriere();

        module.setDocumentScan(file.getBytes());
        module.setDocumentNom(file.getOriginalFilename());
        module.setDocumentType(file.getContentType());
        carriereRepository.save(module);
    }

    public Carriere getDoc(UUID id) {
        return carriereRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Diplôme introuvable"));
    }

    // --- MAPPER PRIVÉ ---
    private CarriereDTO mapToDTO(Carriere module,AncienneteCalculee anciennetes,  Militaire militaire,DossierAdministratif dossier) {
        CarriereDTO dto = new CarriereDTO();

        // Champs éditables
        dto.setCorps(module.getCorps());
        dto.setArme(module.getArme());
        dto.setOrigine(module.getOrigine());
        dto.setCnim(module.getCnim());
        dto.setFormationStructure(module.getTypeStructure());
        dto.setCompagnie(module.getNomCompagnie());
        dto.setObservationEmploi(module.getObservationEmploi());
        dto.setAptitudeOps(militaire.getAptitudeOps());
        dto.setNomFichier(module.getDocumentNom());

        // Champs calculés depuis le Militaire
        dto.setStatut(militaire.getStatut() != null ? militaire.getStatut() : "");
        dto.setMatriculeSolde(militaire.getMatriculeSolde());
        dto.setMatriculeMilitaire(militaire.getMatriculeMilitaire());

        // Calcul des durées (Logique simple pour l'exemple)
//        if (militaire.getDateGrade() != null) {
//            dto.setAnneeGrade(militaire.getDateGrade().toString());
//            // Tu peux utiliser ChronoUnit.YEARS.between(...) ici pour avoir "5 ans".
//        }

        // NOUVEAUX CHAMPS Section 1
        dto.setAncienneteService(anciennetes.ancienneteServiceFormatee());
        dto.setAncienneteGrade(anciennetes.ancienneteGradeFormatee());
        dto.setAnneesProlongation(anciennetes.totalAnneesProlongation());
        dto.setEstArchive(dossier.getStatut() == StatutDossier.ARCHIVE);
        dto.setDateCalculReference(anciennetes.dateReference());

        // Récupération des prolongations détaillées pour affichage liste
        if (dossier.getAvancementModule() != null) {
            var prolongations = avancementRepository.findByModuleIdAndTypeAvancement(
                    dossier.getAvancementModule().getId(),
                    TypeAvancement.PROLONGATION_SERVICE
            );
            dto.setProlongationsDetails(prolongations); // Nécessite ajout dans DTO
        }

        // Mapping de la liste des réengagements
        if (module.getReengagements() != null) {
            dto.setReengagements(module.getReengagements().stream().map(r -> {
                ReengagementDTO rd = new ReengagementDTO();
                rd.setId(r.getId());
                rd.setDesignation(r.getDesignation());
                rd.setLieu(r.getLieu());
                rd.setDate(r.getDate());
                return rd;
            }).collect(Collectors.toList()));
        }

        if (module.getAdmissionSocs() != null) {
            dto.setAdmissionSocs(module.getAdmissionSocs().stream().map(r -> {
                AdmissionSocDTO rd = new AdmissionSocDTO();
                rd.setId(r.getId());
                rd.setDesignation(r.getDesignation());
                rd.setLieu(r.getLieu());
                rd.setDate(r.getDate());
                return rd;
            }).collect(Collectors.toList()));
        }

        return dto;
    }

    private String like(String v) {
        if (v == null || v.trim().isEmpty()) return null;
        return "%" + v.trim() + "%";
    }

    public java.util.List<ResultRechercheCarriereDTO> rechercher(RechercheCarriereDTO dto) {
        return carriereRepository.rechercherCarriere(
                like(dto.getNom()),
                like(dto.getPrenom()),
                dto.getGrade(),
                dto.getArme(),
                like(dto.getPoste()),
                like(dto.getUnite())
        );
    }
}
