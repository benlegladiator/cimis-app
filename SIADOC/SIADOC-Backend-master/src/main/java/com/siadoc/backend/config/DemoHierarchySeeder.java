package com.siadoc.backend.config;

import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;
import lombok.RequiredArgsConstructor;
import org.springframework.boot.CommandLineRunner;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import java.util.UUID;

// @Component
@RequiredArgsConstructor
public class DemoHierarchySeeder implements CommandLineRunner {

    private final RegionMilitaireRepository regionRepository;
    private final BrigadeRepository brigadeRepository;
    private final BataillonRepository bataillonRepository;
    private final CompagnieRepository compagnieRepository;
    private final MilitaireRepository militaireRepository;
    private final DossierAdministratifRepository dossierRepository;
    private final UtilisateurRepository utilisateurRepository;

    @Override
    @Transactional
    public void run(String... args) throws Exception {
        // On vérifie si les données de test existent déjà pour ne pas faire de doublons
        if (utilisateurRepository.findByUsername("user_rmia1").isPresent()) {
            return;
        }

        System.out.println(">>> [TEST SEEDER] Création des données de test demandées...");

        // 1. STRUCTURE
        RegionMilitaire rmia1 = regionRepository.findByNom("RMIA1")
                .orElseGet(() -> {
                    RegionMilitaire r = new RegionMilitaire();
                    r.setNom("RMIA1");
                    return regionRepository.save(r);
                });

        Brigade bqg = brigadeRepository.findByNomAndRegion("BQG", rmia1)
                .orElseGet(() -> {
                    Brigade b = new Brigade();
                    b.setNom("BQG");
                    b.setRegion(rmia1);
                    return brigadeRepository.save(b);
                });

        Bataillon bcs = bataillonRepository.findByNomAndBrigade("BCS", bqg)
                .orElseGet(() -> {
                    Bataillon bat = new Bataillon();
                    bat.setNom("BCS");
                    bat.setBrigade(bqg);
                    return bataillonRepository.save(bat);
                });

        Compagnie ccs = compagnieRepository.findByNomAndBataillon("CCS", bcs)
                .orElseGet(() -> {
                    Compagnie c = new Compagnie();
                    c.setNom("CCS");
                    c.setBataillon(bcs);
                    return compagnieRepository.save(c);
                });

        Compagnie ema = compagnieRepository.findByNomAndBataillon("CIE/EMA", bcs)
                .orElseGet(() -> {
                    Compagnie c = new Compagnie();
                    c.setNom("CIE/EMA");
                    c.setBataillon(bcs);
                    return compagnieRepository.save(c);
                });

        // 2. MILITAIRES (3 dans chaque compagnie)
        // Compagnie CCS
        createTestMilitaire("NGUENA", "Jean", "MAT-001", ccs, CorpsArmee.AT);
        createTestMilitaire("ONANA", "Paul", "MAT-002", ccs, CorpsArmee.AT);
        createTestMilitaire("BELLA", "Luc", "MAT-003", ccs, CorpsArmee.AT);

        // Compagnie CIE/EMA
        createTestMilitaire("EKO", "Samuel", "MAT-004", ema, CorpsArmee.AA);
        createTestMilitaire("SALI", "Hamidou", "MAT-005", ema, CorpsArmee.AA);
        createTestMilitaire("MENGUE", "Thérèse", "MAT-006", ema, CorpsArmee.AA);

        // 3. PROFILS UTILISATEURS
        createTestUser("user_rmia1", Role.RMIA, rmia1, null, null, null);
        createTestUser("user_bqg", Role.BRIGADE, null, bqg, null, null);
        createTestUser("user_bcs", Role.BATAILLON, null, null, bcs, null);
        createTestUser("user_ccs", Role.COMMANDANT_COMPAGNIE, null, null, null, ccs);
        createTestUser("user_ema", Role.COMMANDANT_COMPAGNIE, null, null, null, ema);

        // 4. ETATS-MAJORS
        if (utilisateurRepository.findByUsername("em_terre").isEmpty()) {
            createTestUser("em_terre", Role.ETAT_MAJOR_TERRE, null, null, null, null);
        }
        if (utilisateurRepository.findByUsername("em_air").isEmpty()) {
            createTestUser("em_air", Role.ETAT_MAJOR_AIR, null, null, null, null);
        }
        if (utilisateurRepository.findByUsername("em_marine").isEmpty()) {
            createTestUser("em_marine", Role.ETAT_MAJOR_MARINE, null, null, null, null);
        }

        System.out.println("<<< [TEST SEEDER] Données de test créées avec succès.");
    }

    private void createTestMilitaire(String nom, String prenom, String matricule, Compagnie cie, CorpsArmee corps) {
        Militaire m = new Militaire();
        m.setNom(nom);
        m.setPrenom(prenom);
        m.setMatriculeMilitaire(matricule);
        m.setMatriculeSolde("S-" + matricule);
        m.setGrade("Sergent");
        m.setArmeService(corps.name());
        m.setStatutValidation(StatutValidation.VALIDE);
        m.setEtat(EtatMilitaire.ACTIF);
        m = militaireRepository.save(m);

        DossierAdministratif d = new DossierAdministratif();
        d.setMilitaire(m);
        d.setCompagnie(cie);
        d.setStatut(StatutDossier.ADMINISTRATIF);
        
        Carriere car = new Carriere();
        car.setDossier(d);
        car.setCorps(corps);
        car.setArme("INFANTERIE");
        car.setNomCompagnie(cie.getNom());
        d.setCarriere(car);
        
        dossierRepository.save(d);
    }

    private void createTestUser(String username, Role role, RegionMilitaire reg, Brigade bri, Bataillon bat, Compagnie cie) {
        Utilisateur user = new Utilisateur();
        user.setUsername(username);
        user.setPassword("password"); // Password par défaut pour vos tests
        user.setRole(role);
        user.setRegion(reg);
        user.setBrigade(bri);
        user.setBataillon(bat);
        user.setCompagnie(cie);
        utilisateurRepository.save(user);
    }
}
