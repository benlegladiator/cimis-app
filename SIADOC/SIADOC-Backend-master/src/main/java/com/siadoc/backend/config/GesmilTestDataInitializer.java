package com.siadoc.backend.config;

import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;
import lombok.RequiredArgsConstructor;
import org.springframework.boot.CommandLineRunner;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;

// @Component
@RequiredArgsConstructor
public class GesmilTestDataInitializer implements CommandLineRunner {

    private final MilitaireRepository militaireRepository;
    private final DossierAdministratifRepository dossierRepository;

    @Override
    @Transactional
    public void run(String... args) throws Exception {
        if (militaireRepository.findByMatriculeMilitaire("TEST-GESMIL-01").isPresent()) {
            return;
        }
        if (militaireRepository.findByMatriculeSolde("S-99999").isPresent()) {
            return;
        }

        // 1. IDENTITE DU MILITAIRE
        Militaire m = new Militaire();
        m.setNom("SIADOC-PRO");
        m.setPrenom("Test Integration");
        m.setMatriculeMilitaire("TEST-GESMIL-01");
        m.setMatriculeSolde("S-99999");
        m.setGrade("CAPITAINE");
        m.setEchelon(5);
        m.setDateGrade(LocalDate.now().minusYears(2));
        m.setDateService(LocalDate.now().minusYears(10));
        m.setSexe("M");
        m.setAptitudeOps("APTE");
        m.setLieuNaissance("YAOUNDE");
        m.setDateNaissance(LocalDate.of(1990, 5, 20));
        m.setStatutValidation(StatutValidation.VALIDE);
        m.setEtat(EtatMilitaire.ACTIF);
        
        m = militaireRepository.save(m);

        // 2. DOSSIER ADMINISTRATIF
        DossierAdministratif d = new DossierAdministratif();
        d.setMilitaire(m);
        d.setStatut(StatutDossier.ADMINISTRATIF);
        d.setDateCreation(LocalDateTime.now());
        
        d = dossierRepository.save(d);

        // 3. CARRIERE
        Carriere car = new Carriere();
        car.setDossier(d);
        car.setCorps(CorpsArmee.AT); // Armée de Terre
        car.setArme("INFANTERIE");
        car.setNomCompagnie("BATAILLON TEST");
        d.setCarriere(car);

        // 4. ETAT CIVIL (Détails via InformationsPersonnelles)
        EtatCivil ec = new EtatCivil();
        ec.setDossier(d);
        
        InformationsPersonnelles ip = new InformationsPersonnelles();
        ip.setEtatCivil(ec);
        ip.setNom(m.getNom());
        ip.setPrenom(m.getPrenom());
        ip.setSexe(m.getSexe());
        ip.setSituationMatrimoniale("MARIE");
        ip.setTelephone("670000000");
        ip.setRegionOrigine("EXT-NORD");
        ec.setInformationsPersonnelles(ip);
        
        d.setEtatCivil(ec);

        // 5. MODULES (Liste des éléments)
        
        // Diplômes
        DiplomeModule dm = new DiplomeModule();
        dm.setDossier(d);
        dm.setItems(new ArrayList<>());
        DiplomeItem di = new DiplomeItem();
        di.setModule(dm);
        di.setDesignation("MASTER GESTION");
        di.setEcole("ECOLE MILITAIRE INTERARMES");
        di.setDateObtention(LocalDate.now().minusYears(3));
        dm.getItems().add(di);
        d.setDiplomeModule(dm);

        // Mutations
        MutationsModule mm = new MutationsModule();
        mm.setDossier(d);
        mm.setItems(new ArrayList<>());
        MutationItem mut = new MutationItem();
        mut.setModule(mm);
        mut.setType(TypeMutation.AFFECTATION);
        mut.setEmploi("CHEF DE SECTION");
        mut.setNumeroTexte("TXT-2024-001");
        mut.setDateTexte(LocalDate.now().minusMonths(6));
        mm.getItems().add(mut);
        d.setMutationsModule(mm);

        // Punitions
        PunitionModule pm = new PunitionModule();
        pm.setDossier(d);
        pm.setItems(new ArrayList<>());
        PunitionItem pun = new PunitionItem();
        pun.setModule(pm);
        pun.setDesignation("Arrêts de rigueur");
        pun.setTexte("Manque de discipline");
        pun.setDateEffet(LocalDate.now().minusMonths(2));
        pm.getItems().add(pun);
        d.setPunitionModule(pm);

        // Recompenses
        RecompenseModule rm = new RecompenseModule();
        rm.setDossier(d);
        rm.setItems(new ArrayList<>());
        RecompenseItem rec = new RecompenseItem();
        rec.setModule(rm);
        rec.setDesignation("VALEUR MILITAIRE");
        rec.setTexte("Décret N°2024-123");
        rec.setDateEffet(LocalDate.now().minusYears(1));
        rm.getItems().add(rec);
        d.setRecompenseModule(rm);

        // Medical
        MedicalModule med = new MedicalModule();
        med.setDossier(d);
        med.setBlessures(new ArrayList<>());
        Blessure mi = new Blessure();
        mi.setModule(med);
        mi.setNature("BLESSURE DE GUERRE");
        mi.setLieu("ZONE OPERATIONNELLE EST");
        mi.setDateEffet(LocalDate.now().minusYears(5));
        med.getBlessures().add(mi);
        d.setMedicalModule(med);

        dossierRepository.save(d);
        System.out.println(">>> [GESMIL SETUP] Dossier de TEST-GESMIL-01 créé avec succès (Exhaustif).");
    }
}
