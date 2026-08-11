package com.siadoc.backend.config;

import com.siadoc.backend.model.Role;
import com.siadoc.backend.model.Utilisateur;
import com.siadoc.backend.repository.UtilisateurRepository;
import org.springframework.boot.CommandLineRunner;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

@Configuration
public class DataInitializer {

    @Bean
    CommandLineRunner initDatabase(org.springframework.jdbc.core.JdbcTemplate jdbcTemplate, UtilisateurRepository repository, com.siadoc.backend.repository.MilitaireRepository militaireRepository, com.siadoc.backend.service.SampleDataService sampleDataService) {
        return args -> {
            // FIX CONSTRAINT FOR GENDARMERIE (GN)
            try {
                jdbcTemplate.execute("ALTER TABLE carriere DROP CONSTRAINT IF EXISTS carriere_corps_check");
                System.out.println("Contrainte carriere_corps_check supprimée ou déjà absente.");
            } catch (Exception e) {
                System.out.println("Note: Impossible de supprimer la contrainte carriere_corps_check (déjà absente ou erreur non critique).");
            }

            // Création du compte DRH si absent
            if (repository.findByUsername("drh").isEmpty()) {
                Utilisateur drh = new Utilisateur();
                drh.setUsername("drh");
                drh.setPassword("drh123"); 
                drh.setRole(Role.DRH);
                repository.save(drh);
                System.out.println("Compte DRH créé.");
            }

            // DONNÉES DE TEST DÉSACTIVÉES - La hiérarchie est gérée par le script migrate_rmia.py
            // sampleDataService.cleanupTestData();
            // sampleDataService.populateSampleData();
            System.out.println(">>> Données de test désactivées - Hiérarchie gérée par migration Python <<<");

            /* 
            // CORRECTION DES CATÉGORIES POUR LES DONNÉES EXISTANTES
            System.out.println(">>> DÉBUT SYNCHRONISATION DES CATÉGORIES <<<");
            militaireRepository.findAll().forEach(m -> {
                com.siadoc.backend.model.CategorieMilitaire ancienne = m.getCategorie();
                String grade = m.getGrade();
                String arme = m.getArmeService();
                
                com.siadoc.backend.model.CategorieMilitaire nouvelle = com.siadoc.backend.service.GradeService.determinerCategorie(grade, arme);
                
                System.out.println("Analyse de : " + m.getNom() + " " + m.getPrenom());
                System.out.println(" - Grade: [" + grade + "], Arme: [" + arme + "]");
                System.out.println(" - Catégorie actuelle: " + ancienne);
                System.out.println(" - Catégorie détectée: " + nouvelle);

                if (ancienne != nouvelle) {
                    m.setCategorie(nouvelle);
                    militaireRepository.save(m);
                    System.out.println(" [!] MISE À JOUR EFFECTUÉE : " + ancienne + " -> " + nouvelle);
                } else {
                    System.out.println(" [OK] Déjà correct.");
                }
            });
            System.out.println(">>> FIN SYNCHRONISATION <<<");
            */
        };
    }
}
