package com.siadoc.backend.service;

import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import java.time.LocalDateTime;
import com.siadoc.backend.model.*;
import java.util.List;
import java.util.UUID;

@Service
public class DossierAdministratifService {

    private final DossierAdministratifRepository repository;
    private final com.siadoc.backend.repository.NotificationRepository notificationRepository;

    public DossierAdministratifService(
            DossierAdministratifRepository repository,
            com.siadoc.backend.repository.NotificationRepository notificationRepository
    ) {
        this.repository = repository;
        this.notificationRepository = notificationRepository;
    }

    @Transactional
    public void notifierModification(DossierAdministratif dossier, String moduleName) {
        if (dossier == null || moduleName == null) return;
        
        String current = dossier.getModulesModifies();
        if (current == null || current.trim().isEmpty()) {
            dossier.setModulesModifies(moduleName);
        } else if (!current.contains(moduleName)) {
            dossier.setModulesModifies(current + ", " + moduleName);
        }
        repository.save(dossier);
    }


    @Transactional
    public void soumettreValidation(UUID dossierId, String modulesList) {
        DossierAdministratif dossier = getById(dossierId);
        dossier.setStatutValidation(StatutValidation.EN_ATTENTE_VALIDATION);
        dossier.setModulesModifies(modulesList);
        repository.save(dossier);

        // Notifier le bataillon (uniquement le bataillon pour éviter l'auto-notification de la compagnie)
        if (dossier.getCompagnie() != null && dossier.getCompagnie().getBataillon() != null) {
            Notification notif = Notification.builder()
                    .titre("Validation requise : " + dossier.getMilitaire().getNom())
                    .message("La compagnie " + dossier.getCompagnie().getNom() + " a soumis des modifications pour validation.")
                    .type(TypeNotification.VALIDATION_REQUISE)
                    .bataillonConcerne(dossier.getCompagnie().getBataillon())
                    .dossierConcerne(dossier)
                    .militaire(dossier.getMilitaire())
                    .build();
            notificationRepository.save(notif);
        }
    }

    @Transactional
    public void approuverModifications(UUID dossierId) {
        DossierAdministratif dossier = getById(dossierId);
        dossier.setStatutValidation(StatutValidation.VALIDE);
        dossier.setMotifRefus(null);
        dossier.setModulesModifies(null);
        repository.save(dossier);

        // 0. Marquer les notifications de validation comme lues pour ce dossier
        nettoyerNotificationsValidation(dossier);

        // 1. Notifier la compagnie
        if (dossier.getCompagnie() != null) {
            Notification notif = Notification.builder()
                    .titre("Modifications approuvées")
                    .message("Les modifications du dossier " + dossier.getMilitaire().getNom() + " ont été validées par le bataillon.")
                    .type(TypeNotification.SUCCES)
                    .compagnieConcernee(dossier.getCompagnie())
                    .dossierConcerne(dossier)
                    .militaire(dossier.getMilitaire())
                    .build();
            notificationRepository.save(notif);
        }

        // 2. Notifier la brigade (Informer que le bataillon a validé)
        if (dossier.getCompagnie() != null && dossier.getCompagnie().getBataillon() != null && dossier.getCompagnie().getBataillon().getBrigade() != null) {
             Notification notifBrigade = Notification.builder()
                    .titre("Dossier validé : " + dossier.getMilitaire().getNom())
                    .message("Le bataillon " + dossier.getCompagnie().getBataillon().getNom() + " a validé les modifications soumises par la compagnie " + dossier.getCompagnie().getNom())
                    .type(TypeNotification.INFO)
                    .brigadeConcernee(dossier.getCompagnie().getBataillon().getBrigade())
                    .dossierConcerne(dossier)
                    .militaire(dossier.getMilitaire())
                    .build();
            notificationRepository.save(notifBrigade);

            // 3. Notifier la région (RMIA)
            if (dossier.getCompagnie().getBataillon().getBrigade().getRegion() != null) {
                Notification notifRegion = Notification.builder()
                        .titre("Dossier validé : " + dossier.getMilitaire().getNom())
                        .message("Validation effectuée au niveau du Bataillon pour un militaire de la " + dossier.getCompagnie().getNom())
                        .type(TypeNotification.INFO)
                        .regionConcernee(dossier.getCompagnie().getBataillon().getBrigade().getRegion())
                        .dossierConcerne(dossier)
                        .militaire(dossier.getMilitaire())
                        .build();
                notificationRepository.save(notifRegion);
            }
        }
    }

    @Transactional
    public void rejeterModifications(UUID dossierId, String motif) {
        if (motif == null || motif.trim().isEmpty()) {
            throw new RuntimeException("Le motif du rejet est obligatoire");
        }
        DossierAdministratif dossier = getById(dossierId);
        dossier.setStatutValidation(StatutValidation.REJETE);
        dossier.setMotifRefus(motif);
        repository.save(dossier);

        // 0. Marquer les notifications de validation comme lues
        nettoyerNotificationsValidation(dossier);

        // Notifier la compagnie UNIQUEMENT
        if (dossier.getCompagnie() != null) {
            Notification notif = Notification.builder()
                    .titre("Modifications REJETÉES")
                    .message("Les modifications du dossier " + dossier.getMilitaire().getNom() + " ont été rejetées. Motif : " + motif)
                    .type(TypeNotification.REFUS_VALIDATION)
                    .compagnieConcernee(dossier.getCompagnie())
                    .dossierConcerne(dossier)
                    .militaire(dossier.getMilitaire())
                    .build();
            notificationRepository.save(notif);
        }
    }

    private void nettoyerNotificationsValidation(DossierAdministratif dossier) {
        List<Notification> pendingNotifs = notificationRepository.findByDossierConcerneAndTypeAndLuFalse(
                dossier, TypeNotification.VALIDATION_REQUISE);
        pendingNotifs.forEach(n -> {
            n.setLu(true);
            notificationRepository.save(n);
        });
    }

    public DossierAdministratif getById(UUID id) {
        DossierAdministratif dossier = repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));
        
        return verifierEtInitialiserModules(dossier);
    }

    public DossierAdministratif archiver(UUID dossierId) {

        // on récupère d'abord le dossier
        DossierAdministratif dossier = getById(dossierId);

        // sécurité
        if (dossier.getStatut() == StatutDossier.ARCHIVE) {
            throw new RuntimeException("Dossier déjà archivé");
        }

        dossier.setStatut(StatutDossier.ARCHIVE);
        dossier.setDateArchivage(LocalDateTime.now());

        return repository.save(dossier);
    }

    @Transactional
    public DossierAdministratif getByMilitaireId(UUID militaireId) {
        DossierAdministratif dossier = repository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));
        
        return verifierEtInitialiserModules(dossier);
    }

    public List<DossierAdministratif> getActifs() {
        return repository.findByStatutNot(StatutDossier.ARCHIVE);
    }

    public void archiverParMilitaire(UUID militaireId) {

        DossierAdministratif dossier =
                repository.findByMilitaireId(militaireId)
                        .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        dossier.setStatut(StatutDossier.ARCHIVE);
        dossier.setDateArchivage(LocalDateTime.now());

        repository.save(dossier);
    }

    public List<Militaire> getMilitairesActifs() {

        return repository.findByStatutNot(StatutDossier.ARCHIVE)
                .stream()
                .map(DossierAdministratif::getMilitaire)
                .toList();
    }

    @Transactional
    public DossierAdministratif getArchiveComplet(UUID militaireId) {

        DossierAdministratif dossier =
                repository.findByMilitaireId(militaireId)
                        .orElseThrow(() -> new RuntimeException("Archive introuvable"));

        if (dossier.getEtatCivil() != null) {
            dossier.getEtatCivil().getActesNaissance().size();
            dossier.getEtatCivil().getCnis().size();
            dossier.getEtatCivil().getActesMariage().size();
            dossier.getEtatCivil().getActesDeces().size();
            dossier.getEtatCivil().getActesDivorce().size();
            dossier.getEtatCivil().getJugementsSuppletifs().size();
        }

        if (dossier.getAvancementModule() != null) {
            dossier.getAvancementModule().getAvancements().size();
        }

        if (dossier.getCarriere() != null) {
            dossier.getCarriere().getReengagements().size();
            dossier.getCarriere().getAdmissionSocs().size();
        }

        if (dossier.getMutationsModule() != null) {
            dossier.getMutationsModule().getItems().size();
        }

        if (dossier.getDiplomeModule() != null) {
            dossier.getDiplomeModule().getItems().size();
        }

        if (dossier.getNotationModule() != null) {
            dossier.getNotationModule().getItems().size();
        }

        if (dossier.getPunitionModule() != null) {
            dossier.getPunitionModule().getItems().size();
        }

        if (dossier.getMedicalModule() != null) {
            dossier.getMedicalModule().getBlessures().size();
            dossier.getMedicalModule().getPensions().size();
        }

        if (dossier.getRecompenseModule() != null) {
            dossier.getRecompenseModule().getItems().size();
        }

        if (dossier.getStageModule() != null) {
            dossier.getStageModule().getItems().size();
        }

        if (dossier.getCampagneMilitaireModule() != null) {
            dossier.getCampagneMilitaireModule().getItems().size();
        }

        if (dossier.getHabillementModule() != null) {
            dossier.getHabillementModule().getPerceptions().size();
        }

        return dossier;
    }

    @Transactional
    public DossierAdministratif initialiserDossier(Militaire militaire, SecteurMilitaire secteur, StatutDossier statut) {
        DossierAdministratif dossier = new DossierAdministratif();
        dossier.setMilitaire(militaire);
        dossier.setDateCreation(LocalDateTime.now());
        dossier.setSecteur(secteur);
        dossier.setStatut(statut);

        // On sauvegarde le dossier d'abord pour avoir son ID en base avant les modules (Owning side FK nullable=false)
        DossierAdministratif saved = repository.save(dossier);
        repository.flush(); // Force l'ID en base

        // Initialisation des modules avec lien bidirectionnel
        saved.setEtatCivil(new EtatCivil()); saved.getEtatCivil().setDossier(saved);
        saved.setAvancementModule(new AvancementModule()); saved.getAvancementModule().setDossier(saved);
        
        Carriere carriere = new Carriere();
        carriere.setDossier(saved);
        carriere.setOrigine(OrigineRecrutement.ORDINAIRE);
        
        // Déterminer le corps à partir de l'armeService
        String arme = militaire.getArmeService();
        if (arme != null) {
            String up = arme.toUpperCase();
            if (up.contains("TERRE") || up.equals("AT")) carriere.setCorps(CorpsArmee.AT);
            else if (up.contains("AIR") || up.equals("AA")) carriere.setCorps(CorpsArmee.AA);
            else if (up.contains("MARINE") || up.equals("AM")) carriere.setCorps(CorpsArmee.AM);
            else if (up.contains("GENDARME") || up.contains("GN")) carriere.setCorps(CorpsArmee.GN);
            else carriere.setCorps(CorpsArmee.AT);
        } else {
            carriere.setCorps(CorpsArmee.AT);
        }
        
        carriere.setTypeStructure(TypeStructure.COMPAGNIE);
        saved.setCarriere(carriere);

        saved.setMutationsModule(new MutationsModule()); saved.getMutationsModule().setDossier(saved);
        saved.setNotationModule(new NotationModule()); saved.getNotationModule().setDossier(saved);
        saved.setDiplomeModule(new DiplomeModule()); saved.getDiplomeModule().setDossier(saved);
        saved.setPunitionModule(new PunitionModule()); saved.getPunitionModule().setDossier(saved);
        saved.setMedicalModule(new MedicalModule()); saved.getMedicalModule().setDossier(saved);
        saved.setRecompenseModule(new RecompenseModule()); saved.getRecompenseModule().setDossier(saved);
        saved.setStageModule(new StageModule()); saved.getStageModule().setDossier(saved);
        saved.setCampagneMilitaireModule(new CampagneMilitaireModule()); saved.getCampagneMilitaireModule().setDossier(saved);
        saved.setHabillementModule(new HabillementModule()); saved.getHabillementModule().setDossier(saved);

        // Pas besoin de save(saved) à nouveau car on est dans une transaction
        militaire.setDossier(saved);
        return saved;
    }

    public List<DossierAdministratif> getAll() {
        return repository.findAll();
    }

    @Transactional
    public DossierAdministratif verifierEtInitialiserModules(DossierAdministratif dossier) {
        boolean updated = false;

        if (dossier.getEtatCivil() == null) {
            EtatCivil ec = new EtatCivil(); ec.setDossier(dossier);
            dossier.setEtatCivil(ec);
            updated = true;
        }
        if (dossier.getAvancementModule() == null) {
            AvancementModule am = new AvancementModule(); am.setDossier(dossier);
            dossier.setAvancementModule(am);
            updated = true;
        }
        if (dossier.getCarriere() == null) {
            Carriere c = new Carriere(); c.setDossier(dossier);
            c.setOrigine(OrigineRecrutement.ORDINAIRE);
            
            String arme = dossier.getMilitaire().getArmeService();
            if (arme != null) {
                String up = arme.toUpperCase();
                if (up.contains("TERRE") || up.equals("AT")) c.setCorps(CorpsArmee.AT);
                else if (up.contains("AIR") || up.equals("AA")) c.setCorps(CorpsArmee.AA);
                else if (up.contains("MARINE") || up.equals("AM")) c.setCorps(CorpsArmee.AM);
                else if (up.contains("GENDARME") || up.contains("GN")) c.setCorps(CorpsArmee.GN);
                else c.setCorps(CorpsArmee.AT);
            } else {
                c.setCorps(CorpsArmee.AT);
            }
            
            c.setTypeStructure(TypeStructure.COMPAGNIE);
            dossier.setCarriere(c);
            updated = true;
        }
        if (dossier.getMutationsModule() == null) {
            MutationsModule m = new MutationsModule(); m.setDossier(dossier);
            dossier.setMutationsModule(m);
            updated = true;
        }
        if (dossier.getNotationModule() == null) {
            NotationModule n = new NotationModule(); n.setDossier(dossier);
            dossier.setNotationModule(n);
            updated = true;
        }
        if (dossier.getDiplomeModule() == null) {
            DiplomeModule d = new DiplomeModule(); d.setDossier(dossier);
            dossier.setDiplomeModule(d);
            updated = true;
        }
        if (dossier.getPunitionModule() == null) {
            PunitionModule p = new PunitionModule(); p.setDossier(dossier);
            dossier.setPunitionModule(p);
            updated = true;
        }
        if (dossier.getMedicalModule() == null) {
            MedicalModule med = new MedicalModule(); med.setDossier(dossier);
            dossier.setMedicalModule(med);
            updated = true;
        }
        if (dossier.getRecompenseModule() == null) {
            RecompenseModule r = new RecompenseModule(); r.setDossier(dossier);
            dossier.setRecompenseModule(r);
            updated = true;
        }
        if (dossier.getStageModule() == null) {
            StageModule s = new StageModule(); s.setDossier(dossier);
            dossier.setStageModule(s);
            updated = true;
        }
        if (dossier.getCampagneMilitaireModule() == null) {
            CampagneMilitaireModule cam = new CampagneMilitaireModule(); cam.setDossier(dossier);
            dossier.setCampagneMilitaireModule(cam);
            updated = true;
        }
        if (dossier.getHabillementModule() == null) {
            HabillementModule h = new HabillementModule(); h.setDossier(dossier);
            dossier.setHabillementModule(h);
            updated = true;
        }

        if (updated) {
            DossierAdministratif saved = repository.save(dossier);
            repository.flush();
            return saved;
        }
        return dossier;
    }
}
