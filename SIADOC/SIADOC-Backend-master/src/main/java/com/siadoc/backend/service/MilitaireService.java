package com.siadoc.backend.service;

import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import com.siadoc.backend.model.*;
import com.siadoc.backend.dto.AffectationRequestDTO;
import com.siadoc.backend.repository.*;
import com.siadoc.backend.security.UserSession;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import lombok.extern.slf4j.Slf4j;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Service
public class MilitaireService {

    private static final org.slf4j.Logger log = org.slf4j.LoggerFactory.getLogger(MilitaireService.class);

    private final MilitaireRepository militaireRepository;
    private final DossierAdministratifRepository dossierRepository;
    private final DossierAdministratifService dossierService;
    private final CompagnieRepository compagnieRepository;
    private final SecteurMilitaireRepository secteurRepository;
    private final NotificationRepository notificationRepository;
    private final HistoriqueMilitaireRepository historiqueRepository;
    private final UserSession userSession;

    @PersistenceContext
    private EntityManager entityManager;

    public MilitaireService(MilitaireRepository militaireRepository,
                            DossierAdministratifRepository dossierRepository,
                            DossierAdministratifService dossierService,
                            CompagnieRepository compagnieRepository,
                            SecteurMilitaireRepository secteurRepository,
                            NotificationRepository notificationRepository,
                            HistoriqueMilitaireRepository historiqueRepository,
                            UserSession userSession) {
        this.militaireRepository = militaireRepository;
        this.dossierRepository = dossierRepository;
        this.dossierService = dossierService;
        this.compagnieRepository = compagnieRepository;
        this.secteurRepository = secteurRepository;
        this.notificationRepository = notificationRepository;
        this.historiqueRepository = historiqueRepository;
        this.userSession = userSession;
    }

    private void logHistorique(Militaire militaire, Utilisateur acteur, String action, String details) {
        HistoriqueMilitaire historique = HistoriqueMilitaire.builder()
                .militaire(militaire)
                .acteur(acteur)
                .action(action)
                .details(details)
                .dateAction(LocalDateTime.now())
                .build();
        historiqueRepository.save(historique);
    }

    @Transactional
    public Militaire creerMilitaire(Militaire militaire, Utilisateur utilisateur, UUID compagnieId) {
        if (utilisateur == null) throw new RuntimeException("Utilisateur obligatoire");
        
        // Autoriser Commandant, DRH, Commandant de Compagnie, RMIA, BRIGADE, BATAILLON
        boolean authorized = utilisateur.getRole() == Role.DRH || utilisateur.getRole() == Role.SUPER_ADMIN || 
                             utilisateur.getRole() == Role.COMMANDANT || utilisateur.getRole() == Role.COMMANDANT_COMPAGNIE ||
                             utilisateur.getRole() == Role.RMIA || utilisateur.getRole() == Role.BRIGADE || utilisateur.getRole() == Role.BATAILLON;

        if (!authorized) {
            throw new RuntimeException("Non autorisé à créer un militaire");
        }

        // Auto-affectation pour le commandant de compagnie
        if (utilisateur.getRole() == Role.COMMANDANT_COMPAGNIE && utilisateur.getCompagnie() != null) {
            compagnieId = utilisateur.getCompagnie().getId();
        }

        militaire.setStatutValidation(StatutValidation.VALIDE);
        militaire.setEtat(EtatMilitaire.ACTIF);

        Militaire saved = militaireRepository.save(militaire);
        DossierAdministratif dossier = dossierService.initialiserDossier(saved, utilisateur.getSecteur(), StatutDossier.ADMINISTRATIF);
        
        logHistorique(saved, utilisateur, "Création du dossier", "Créé par " + utilisateur.getRole().name());
        
        if (compagnieId != null) {
            Compagnie comp = compagnieRepository.findById(compagnieId).orElse(null);
            if (comp != null) {
                MutationItem affect = new MutationItem();
                affect.setType(TypeMutation.AFFECTATION);
                affect.setCompagnie(comp);
                affect.setModule(dossier.getMutationsModule());
                affect.setDateTexte(LocalDate.now());

                if (dossier.getMutationsModule().getItems() == null) {
                    dossier.getMutationsModule().setItems(new java.util.ArrayList<>());
                }
                dossier.getMutationsModule().getItems().add(affect);
                dossier.setCompagnie(comp);
                
                // Notifier la compagnie (seulement si le créateur n'est pas déjà de cette compagnie)
                if (utilisateur.getCompagnie() == null || !utilisateur.getCompagnie().getId().equals(comp.getId())) {
                    Notification notif = Notification.builder()
                        .message("Nouveau dossier : " + saved.getNom() + " " + saved.getPrenom() + " a été affecté à votre unité.")
                        .dateCreation(LocalDateTime.now())
                        .compagnieConcernee(comp)
                        .militaire(saved)
                        .lu(false)
                        .build();
                    notificationRepository.save(notif);
                }
                dossierRepository.save(dossier);
            }
        }
        return saved;
    }

    public Militaire getById(UUID id) {
        return militaireRepository.findById(id).orElseThrow(() -> new RuntimeException("Militaire introuvable"));
    }

    public List<Militaire> lister() {
        return militaireRepository.findActifs();
    }

    public List<Militaire> listerParRole(Utilisateur user) {
        if (user == null) {
            log.warn("Tentative de liste sans utilisateur connecté");
            return new ArrayList<>();
        }
        
        log.info("Lister militaires pour l'utilisateur: {} avec le rôle: {}", user.getUsername(), user.getRole());
        
        switch (user.getRole()) {
            case SUPER_ADMIN:
            case DRH:
                return lister();
            case RMIA:
                if (user.getRegion() == null) { log.warn("Utilisateur RMIA sans région"); return new ArrayList<>(); }
                return militaireRepository.findByDossierCompagnieBataillonBrigadeRegionId(user.getRegion().getId());
            case BRIGADE:
                if (user.getBrigade() == null) { log.warn("Utilisateur BRIGADE sans brigade"); return new ArrayList<>(); }
                return militaireRepository.findByDossierCompagnieBataillonBrigadeId(user.getBrigade().getId());
            case BATAILLON:
                if (user.getBataillon() == null) { log.warn("Utilisateur BATAILLON sans bataillon"); return new ArrayList<>(); }
                return militaireRepository.findByDossierCompagnieBataillonId(user.getBataillon().getId());
            case COMMANDANT_COMPAGNIE:
                if (user.getCompagnie() == null) {
                    log.error("ERREUR CRITIQUE: Utilisateur {} a le rôle COMMANDANT_COMPAGNIE mais getCompagnie() est NULL", user.getUsername());
                    return new ArrayList<>();
                }
                log.info("Chargement des militaires pour la compagnie ID: {} ({})", user.getCompagnie().getId(), user.getCompagnie().getNom());
                return militaireRepository.findByDossierCompagnieId(user.getCompagnie().getId());
            case ETAT_MAJOR_TERRE:
                return militaireRepository.findByArmeService("TERRE", "AT");
            case ETAT_MAJOR_AIR:
                return militaireRepository.findByArmeService("AIR", "AA");
            case ETAT_MAJOR_MARINE:
                return militaireRepository.findByArmeService("MARINE", "AM");
            default:
                return new ArrayList<>();
        }
    }

    public List<Militaire> listerEnAttente() {
        return militaireRepository.findByStatutValidation(StatutValidation.EN_ATTENTE_DRH);
    }

    public List<Militaire> getByCompagnieNom(String nom) {
        return militaireRepository.findByCompagnieNom(nom);
    }

    public List<Militaire> getByUniteNom(String nom) {
        return militaireRepository.findByUniteOrganisationnelleNom(nom);
    }

    @Transactional
    public Militaire creerParDRH(Militaire militaire){
        militaire.setStatutValidation(StatutValidation.VALIDE);
        militaire.setEtat(EtatMilitaire.ACTIF);
        Militaire saved = militaireRepository.save(militaire);
        dossierService.initialiserDossier(saved, null, StatutDossier.ADMINISTRATIF);
        return saved;
    }

    @Transactional
    public Militaire creerParCompagnie(Militaire militaire){
        militaire.setStatutValidation(StatutValidation.VALIDE);
        militaire.setEtat(EtatMilitaire.ACTIF);
        Militaire saved = militaireRepository.save(militaire);
        dossierService.initialiserDossier(saved, null, StatutDossier.ADMINISTRATIF);
        return saved;
    }

    @Transactional
    public void validerDossier(UUID militaireId){
        Militaire militaire = militaireRepository.findById(militaireId)
                .orElseThrow(() -> new RuntimeException("Militaire introuvable"));
        DossierAdministratif dossier = militaire.getDossier();
        if (dossier == null) throw new RuntimeException("Dossier introuvable");

        dossier.setStatut(StatutDossier.ADMINISTRATIF);
        militaire.setStatutValidation(StatutValidation.VALIDE);
        
        if (militaire.getEtat() == EtatMilitaire.NOUVELLE_RECRUE) {
            militaire.setEtat(EtatMilitaire.ACTIF);
            if (militaire.getMatriculeMilitaire() == null || militaire.getMatriculeMilitaire().isEmpty()) {
                militaire.setMatriculeMilitaire("MAT-" + LocalDate.now().getYear() + "-" + militaire.getMatriculeSolde());
            }
        }
        militaireRepository.save(militaire);
        dossierRepository.save(dossier);

        // Nettoyer les notifications de validation en attente pour ce militaire
        nettoyerNotificationsValidation(militaire);

        // Notifier les échelons supérieurs (Brigade et RMIA)
        if (dossier.getCompagnie() != null && dossier.getCompagnie().getBataillon() != null && dossier.getCompagnie().getBataillon().getBrigade() != null) {
            
            // Notification Brigade
            Notification notifBrigade = Notification.builder()
                    .titre("Dossier validé : " + militaire.getNom())
                    .message("Le bataillon " + dossier.getCompagnie().getBataillon().getNom() + " a validé le dossier de " + militaire.getNom())
                    .type(TypeNotification.INFO)
                    .brigadeConcernee(dossier.getCompagnie().getBataillon().getBrigade())
                    .militaire(militaire)
                    .dossierConcerne(dossier)
                    .dateCreation(LocalDateTime.now())
                    .lu(false)
                    .build();
            notificationRepository.save(notifBrigade);

            // Notification Région (RMIA)
            if (dossier.getCompagnie().getBataillon().getBrigade().getRegion() != null) {
                Notification notifRegion = Notification.builder()
                        .titre("Dossier validé : " + militaire.getNom())
                        .message("Validation effectuée au niveau du Bataillon pour un militaire de la " + dossier.getCompagnie().getNom())
                        .type(TypeNotification.INFO)
                        .regionConcernee(dossier.getCompagnie().getBataillon().getBrigade().getRegion())
                        .militaire(militaire)
                        .dossierConcerne(dossier)
                        .dateCreation(LocalDateTime.now())
                        .lu(false)
                        .build();
                notificationRepository.save(notifRegion);
            }
        }
    }

    @Transactional
    public void rejeterDossier(UUID militaireId, String motif) {
        if (motif == null || motif.trim().isEmpty()) {
            throw new RuntimeException("Le motif du rejet est obligatoire");
        }
        Militaire militaire = militaireRepository.findById(militaireId).orElseThrow();
        militaire.setStatutValidation(StatutValidation.REJETE);
        
        if (militaire.getDossier() != null) {
            militaire.getDossier().setMotifRefus(motif);
            dossierRepository.save(militaire.getDossier());

            // Notifier la compagnie UNIQUEMENT
            if (militaire.getDossier().getCompagnie() != null) {
                notificationRepository.save(Notification.builder()
                        .titre("Dossier REJETÉ")
                        .message("Le dossier de " + militaire.getNom() + " a été rejeté. Motif : " + motif)
                        .type(TypeNotification.REFUS_VALIDATION)
                        .compagnieConcernee(militaire.getDossier().getCompagnie())
                        .militaire(militaire)
                        .dossierConcerne(militaire.getDossier())
                        .build());
            }
            // Nettoyer les notifications de validation
            nettoyerNotificationsValidation(militaire);
        }
        militaireRepository.save(militaire);
    }

    private void nettoyerNotificationsValidation(Militaire militaire) {
        List<Notification> pendingNotifs = notificationRepository.findByMilitaireAndTypeAndLuFalse(
                militaire, TypeNotification.VALIDATION_REQUISE);
        pendingNotifs.forEach(n -> {
            n.setLu(true);
            notificationRepository.save(n);
        });
    }

    @Transactional
    public Militaire creerNouvelleRecrue(Militaire militaire, UUID compagnieAffectationId) {
        militaire.setStatutValidation(StatutValidation.VALIDE);
        militaire.setEtat(EtatMilitaire.NOUVELLE_RECRUE);
        Militaire saved = militaireRepository.save(militaire);
        
        SecteurMilitaire secteur = secteurRepository.findAll().stream().findFirst().orElse(null);
        DossierAdministratif dossier = dossierService.initialiserDossier(saved, secteur, StatutDossier.ADMINISTRATIF);
        
        if (compagnieAffectationId != null) {
            Compagnie comp = compagnieRepository.findById(compagnieAffectationId).orElse(null);
            if (comp != null) {
                MutationItem affect = new MutationItem();
                affect.setType(TypeMutation.AFFECTATION);
                affect.setCompagnie(comp);
                affect.setModule(dossier.getMutationsModule());
                affect.setDateTexte(LocalDate.now());
                if (dossier.getMutationsModule().getItems() == null) dossier.getMutationsModule().setItems(new ArrayList<>());
                dossier.getMutationsModule().getItems().add(affect);
                dossier.setCompagnie(comp);
                
                notificationRepository.save(Notification.builder()
                    .message("Nouveau dossier arrivé : " + saved.getNom())
                    .dateCreation(LocalDateTime.now())
                    .compagnieConcernee(comp)
                    .militaire(saved)
                    .lu(false)
                    .build());
            }
        }
        return saved;
    }

    @Transactional
    public Militaire effectuerMutationAffectation(UUID militaireId, AffectationRequestDTO request) {
        Militaire militaire = militaireRepository.findById(militaireId).orElseThrow();
        Compagnie compagnie = compagnieRepository.findById(request.getCompagnieId()).orElseThrow();
        DossierAdministratif dossier = militaire.getDossier();
        
        MutationItem mutation = new MutationItem();
        mutation.setType(TypeMutation.AFFECTATION);
        mutation.setCompagnie(compagnie);
        mutation.setModule(dossier.getMutationsModule());
        mutation.setNumeroTexte(request.getNumeroTexte());
        mutation.setDateTexte(request.getDateTexte() != null ? request.getDateTexte() : LocalDate.now());
        mutation.setEmploi(request.getEmploi());
        
        if (dossier.getMutationsModule().getItems() == null) dossier.getMutationsModule().setItems(new ArrayList<>());
        dossier.getMutationsModule().getItems().add(mutation);
        
        dossier.setCompagnie(compagnie);
        dossier.setStatut(StatutDossier.EN_ATTENTE_VALIDATION);
        
        notificationRepository.save(Notification.builder()
            .message("Mutation : Le dossier de " + militaire.getNom() + " est arrivé.")
            .dateCreation(LocalDateTime.now())
            .compagnieConcernee(compagnie)
            .militaire(militaire)
            .lu(false)
            .build());
        
        dossierRepository.save(dossier);
        return militaireRepository.save(militaire);
    }

    @Transactional
    public void confirmerReceptionDossier(UUID militaireId) {
        Militaire militaire = militaireRepository.findById(militaireId).orElseThrow();
        Compagnie comp = militaire.getDossier().getCompagnie();
        if (comp != null) {
            notificationRepository.findByCompagnieConcerneeAndLuFalseOrderByDateCreationDesc(comp).stream()
                .filter(n -> n.getMilitaire().getId().equals(militaireId))
                .forEach(n -> { n.setLu(true); notificationRepository.save(n); });
        }
        DossierAdministratif dossier = militaire.getDossier();
        if (dossier != null) {
            dossier.setStatut(StatutDossier.ADMINISTRATIF);
            dossierRepository.save(dossier);
        }
    }

    public List<Militaire> getMilitairesMaCompagnie(UUID compagnieId) {
        return militaireRepository.findByDossierCompagnieId(compagnieId).stream()
                .filter(m -> m.getStatutValidation() == StatutValidation.VALIDE)
                .toList();
    }

    public List<Militaire> searchComplex(String nom, String matricule, String grade) {
        Utilisateur user = userSession.getCurrentUser();
        List<Militaire> results = militaireRepository.search(nom, matricule, grade);
        
        if (user == null || user.getRole() == Role.DRH || user.getRole() == Role.SUPER_ADMIN) {
            return results;
        }

        // Filter results based on visibility
        return results.stream().filter(m -> {
            if (m.getDossier() == null || m.getDossier().getCompagnie() == null) return false;
            Compagnie c = m.getDossier().getCompagnie();
            
            switch (user.getRole()) {
                case RMIA:
                    return user.getRegion() != null && c.getBataillon().getBrigade().getRegion().getId().equals(user.getRegion().getId());
                case BRIGADE:
                    return user.getBrigade() != null && c.getBataillon().getBrigade().getId().equals(user.getBrigade().getId());
                case BATAILLON:
                    return user.getBataillon() != null && c.getBataillon().getId().equals(user.getBataillon().getId());
                case COMMANDANT_COMPAGNIE:
                    return user.getCompagnie() != null && c.getId().equals(user.getCompagnie().getId());
                case ETAT_MAJOR_TERRE:
                    return m.getArmeService() != null && (m.getArmeService().toUpperCase().contains("TERRE") || m.getArmeService().toUpperCase().contains("AT"));
                case ETAT_MAJOR_AIR:
                    return m.getArmeService() != null && (m.getArmeService().toUpperCase().contains("AIR") || m.getArmeService().toUpperCase().contains("AA"));
                case ETAT_MAJOR_MARINE:
                    return m.getArmeService() != null && (m.getArmeService().toUpperCase().contains("MARINE") || m.getArmeService().toUpperCase().contains("AM"));
                default:
                    return false;
            }
        }).toList();
    }

    public List<HistoriqueMilitaire> getHistorique(UUID militaireId) {
        return historiqueRepository.findByMilitaireIdOrderByDateActionDesc(militaireId);
    }

    public List<Militaire> getNouvellesIntegrations() {
        return militaireRepository.findNouvellesIntegrations(LocalDate.now().minusDays(30));
    }

    public List<Militaire> getNouvellesIntegrationsByBataillon(UUID bataillonId) {
        return militaireRepository.findNouvellesIntegrationsByBataillon(bataillonId, LocalDate.now().minusDays(30));
    }

    public List<Militaire> getNouvellesIntegrationsByArme(String arme) {
        String code = getArmeCode(arme);
        return militaireRepository.findNouvellesIntegrationsByArme(arme, code, LocalDate.now().minusDays(30));
    }

    private boolean estProcheRetraite(Militaire m) {
        if (m.getDateNaissance() == null || m.getGrade() == null) return false;
        
        int ageRetraite = 49; // MDR par défaut (Caporal, Soldat, Quartier Maître, Gendarme, Matelot, etc.)
        String grade = m.getGrade().toUpperCase();
        
        if (grade.contains("LIEUTENANT DE VAISSEAU") || grade.contains("LV")) {
            ageRetraite = 55;
        } else if (grade.contains("CAPITAINE DE VAISSEAU") || grade.contains("CV") || grade.contains("COLONEL") || grade.equals("COL")) {
            ageRetraite = 58;
        } else if (grade.contains("LIEUTENANT-COLONEL") || grade.contains("LT-COL") || grade.contains("LCL") || grade.contains("FRÉGATE") || grade.contains("FREGATE") || grade.contains("CF")) {
            ageRetraite = 57;
        } else if (grade.contains("COMMANDANT") || grade.contains("CDT") || grade.contains("CHEF DE BATAILLON") || grade.contains("CBA") || grade.contains("CHEF D'ESCADRON") || grade.contains("CE") || grade.contains("CORVETTE") || grade.contains("CC")) {
            ageRetraite = 56;
        } else if (grade.contains("CAPITAINE") || grade.contains("CNE") || grade.contains("ADJUDANT-CHEF MAJOR") || grade.contains("ADJUDANT CHEF MAJOR") || grade.contains("ACM") || grade.contains("MAÎTRE PRINCIPAL MAJOR") || grade.contains("MAITRE PRINCIPAL MAJOR") || grade.contains("MPM")) {
            ageRetraite = 55;
        } else if (grade.contains("LIEUTENANT") || grade.contains("LT") || grade.contains("SOUS-OFFICIER") || grade.contains("ADJUDANT") || grade.contains("SERGENT") || grade.contains("MARECHAL") || grade.contains("S/OFF") 
                || grade.contains("ENSEIGNE") || grade.contains("EV1") || grade.contains("EV2") || grade.contains("ASPIRANT") || grade.contains("ASP")
                || grade.contains("MAÎTRE PRINCIPAL") || grade.contains("MAITRE PRINCIPAL") || grade.contains("PREMIER MAÎTRE") || grade.contains("PREMIER MAITRE") || grade.contains("PM") || grade.contains("SECOND MAÎTRE") || grade.contains("SECOND MAITRE") || grade.contains("SM") || grade.contains("MAÎTRE") || grade.contains("MAITRE") || grade.contains("MTRE")
                || grade.contains("AC") || grade.contains("ADJT") || grade.contains("MDLC") || grade.contains("MDL") || grade.contains("SC") || grade.contains("SGT") || grade.contains("MAJOR")) {
            ageRetraite = 54;
        }
        
        LocalDate dateRetraite = m.getDateNaissance().plusYears(ageRetraite);
        LocalDate dateAlerte = LocalDate.now().plusYears(1); // Alerte 1 an à l'avance
        
        return dateRetraite.isBefore(dateAlerte) || dateRetraite.isEqual(dateAlerte);
    }

    public List<Militaire> getRetraitesProches() {
        return militaireRepository.findActifs().stream()
                .filter(this::estProcheRetraite)
                .toList();
    }

    public List<Militaire> getRetraitesProchesByBataillon(UUID bataillonId) {
        return militaireRepository.findByDossierCompagnieBataillonId(bataillonId).stream()
                .filter(m -> m.getStatutValidation() == StatutValidation.VALIDE && (m.getEtat() == null || m.getEtat() != EtatMilitaire.DECEDE))
                .filter(m -> m.getDossier() == null || m.getDossier().getStatut() != StatutDossier.ARCHIVE)
                .filter(this::estProcheRetraite)
                .toList();
    }

    public List<Militaire> getRetraitesProchesByArme(String arme) {
        String code = getArmeCode(arme);
        return militaireRepository.findByArmeService(arme, code).stream()
                .filter(this::estProcheRetraite)
                .toList();
    }

    @Transactional
    public void mettreEnRetraite(UUID militaireId) {
        Militaire militaire = militaireRepository.findById(militaireId)
                .orElseThrow(() -> new RuntimeException("Militaire introuvable"));
        
        DossierAdministratif dossier = militaire.getDossier();
        if (dossier == null) throw new RuntimeException("Dossier introuvable");

        // Changement des états
        militaire.setEtat(EtatMilitaire.RETRAITE);
        dossier.setStatut(StatutDossier.CAMPAGNE);
        dossier.setDateArchivage(LocalDateTime.now());

        militaireRepository.save(militaire);
        dossierRepository.save(dossier);

        // Historique
        Utilisateur current = userSession.getCurrentUser();
        logHistorique(militaire, current, "MISE EN RETRAITE", "Le militaire a été mis en retraite officiellement.");
        
        log.info(">>> Militaire {} {} mis en retraite", militaire.getNom(), militaire.getPrenom());
    }

    public List<Militaire> listerRetraites() {
        return militaireRepository.findRetraites();
    }

    private String getArmeCode(String arme) {
        if (arme == null) return "";
        if (arme.toUpperCase().contains("TERRE")) return "AT";
        if (arme.toUpperCase().contains("AIR")) return "AA";
        if (arme.toUpperCase().contains("MARINE")) return "AM";
        if (arme.toUpperCase().contains("GENDARMERIE") || arme.toUpperCase().contains("GN")) return "GN";
        return "";
    }
}