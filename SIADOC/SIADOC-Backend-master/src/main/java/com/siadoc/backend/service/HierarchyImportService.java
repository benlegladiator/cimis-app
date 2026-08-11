package com.siadoc.backend.service;

import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.core.io.ClassPathResource;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.List;
import java.util.Optional;

@Service
public class HierarchyImportService {

    private static final org.slf4j.Logger log = org.slf4j.LoggerFactory.getLogger(HierarchyImportService.class);

    private final RegionMilitaireRepository regionRepository;
    private final BrigadeRepository brigadeRepository;
    private final BataillonRepository bataillonRepository;
    private final CompagnieRepository compagnieRepository;
    private final UtilisateurRepository utilisateurRepository;
    private final DossierAdministratifRepository dossierRepository;
    private final NotificationRepository notificationRepository;
    
    @jakarta.persistence.PersistenceContext
    private jakarta.persistence.EntityManager entityManager;

    public HierarchyImportService(RegionMilitaireRepository regionRepository,
                                  BrigadeRepository brigadeRepository,
                                  BataillonRepository bataillonRepository,
                                  CompagnieRepository compagnieRepository,
                                  UtilisateurRepository utilisateurRepository,
                                  DossierAdministratifRepository dossierRepository,
                                  NotificationRepository notificationRepository) {
        this.regionRepository = regionRepository;
        this.brigadeRepository = brigadeRepository;
        this.bataillonRepository = bataillonRepository;
        this.compagnieRepository = compagnieRepository;
        this.utilisateurRepository = utilisateurRepository;
        this.dossierRepository = dossierRepository;
        this.notificationRepository = notificationRepository;
    }

    @Transactional(propagation = org.springframework.transaction.annotation.Propagation.REQUIRES_NEW)
    public void clearHierarchy() {
        log.info("Aggressive clearing of existing hierarchy data using Native SQL...");
        
        try {
            // 1. Detach all references in other tables
            int u = entityManager.createNativeQuery("UPDATE \"utilisateur\" SET \"compagnie_id\" = NULL, \"bataillon_id\" = NULL, \"brigade_id\" = NULL, \"region_id\" = NULL").executeUpdate();
            int d = entityManager.createNativeQuery("UPDATE \"dossier_administratif\" SET \"compagnie_id\" = NULL").executeUpdate();
            int n = entityManager.createNativeQuery("DELETE FROM \"notification\"").executeUpdate();
            int m = entityManager.createNativeQuery("UPDATE \"mutation_item\" SET \"compagnie_id\" = NULL").executeUpdate();
            
            log.info("Detached/Deleted: {} users, {} dossiers, {} notifications, {} mutation items.", u, d, n, m);

            // 2. Delete mapping tables
            entityManager.createNativeQuery("DELETE FROM \"compagnie_mapping_gesmil\"").executeUpdate();
            
            // 3. Delete hierarchy in reverse order
            int c = entityManager.createNativeQuery("DELETE FROM \"compagnie\"").executeUpdate();
            int b = entityManager.createNativeQuery("DELETE FROM \"bataillon\"").executeUpdate();
            int br = entityManager.createNativeQuery("DELETE FROM \"brigade\"").executeUpdate();
            int r = entityManager.createNativeQuery("DELETE FROM \"region_militaire\"").executeUpdate();
            
            log.info("Deleted from DB: {} compagnies, {} bataillons, {} brigades, {} regions.", c, b, br, r);
            log.info("Hierarchy cleared successfully via Native SQL.");
        } catch (Exception e) {
            log.error("CRITICAL: Error during native SQL cleanup: {}", e.getMessage());
            // Log full stack trace
            e.printStackTrace();
            throw new RuntimeException("Could not clear hierarchy. Import aborted to avoid duplicates.", e);
        }
    }

    @Transactional
    public void importHierarchy() {
        log.info("Starting hierarchy import...");
        
        // Clear first to avoid duplicates in DB from previous failed imports
        // This is now MANDATORY. If it fails, the import will fail.
        clearHierarchy();

        try (BufferedReader br = new BufferedReader(new InputStreamReader(
                new ClassPathResource("data/hierarchy.csv").getInputStream(), StandardCharsets.UTF_8))) {
            
            String line;
            boolean firstLine = true;
            
            // Caches pour éviter les lookups répétitifs et les conflits de session
            java.util.Map<String, RegionMilitaire> regionCache = new java.util.HashMap<>();
            java.util.Map<String, Brigade> brigadeCache = new java.util.HashMap<>();
            java.util.Map<String, Bataillon> bataillonCache = new java.util.HashMap<>();

            while ((line = br.readLine()) != null) {
                if (firstLine) {
                    firstLine = false;
                    continue;
                }
                
                String[] data = line.split(";");
                if (data.length < 5) continue;
                
                // Normalisation : Trim + Uppercase pour éviter les doublons invisibles
                String rmiaNom = data[0].trim().toUpperCase();
                String armee = data[1].trim();
                String brigadeNom = data[2].trim().toUpperCase();
                String bataillonNom = data[3].trim().toUpperCase();
                String compagniesList = data[4].trim();
                
                try {
                    // 1. RMIA
                    RegionMilitaire region = regionCache.get(rmiaNom);
                    if (region == null) {
                        region = regionRepository.findByNomIgnoreCase(rmiaNom)
                                .orElseGet(() -> {
                                    RegionMilitaire r = new RegionMilitaire();
                                    r.setNom(rmiaNom);
                                    return regionRepository.saveAndFlush(r);
                                });
                        regionCache.put(rmiaNom, region);
                    }
                    
                    // 2. Brigade
                    String bKey = brigadeNom; // Recherche par nom uniquement pour satisfaire la contrainte unique de la DB
                    Brigade brigade = brigadeCache.get(bKey);
                    if (brigade == null) {
                        final RegionMilitaire finalRegion = region;
                        brigade = brigadeRepository.findByNomIgnoreCase(brigadeNom)
                                .orElseGet(() -> {
                                    Brigade b = new Brigade();
                                    b.setNom(brigadeNom);
                                    b.setRegion(finalRegion);
                                    return brigadeRepository.saveAndFlush(b);
                                });
                        brigadeCache.put(bKey, brigade);
                    }
                    
                    // 3. Bataillon
                    String batKey = bataillonNom; // Recherche par nom uniquement
                    Bataillon bataillon = bataillonCache.get(batKey);
                    if (bataillon == null) {
                        final Brigade finalBrigade = brigade;
                        bataillon = bataillonRepository.findByNomIgnoreCase(bataillonNom)
                                .orElseGet(() -> {
                                    Bataillon bat = new Bataillon();
                                    bat.setNom(bataillonNom);
                                    bat.setBrigade(finalBrigade);
                                    return bataillonRepository.saveAndFlush(bat);
                                });
                        bataillonCache.put(batKey, bataillon);
                    }
                    
                    // 4. Compagnies
                    if (!compagniesList.equals("None") && !compagniesList.isEmpty()) {
                        final Bataillon finalBataillon = bataillon;
                        String[] compagnies = compagniesList.split(",");
                        for (String cNomRaw : compagnies) {
                            String cNom = cNomRaw.trim().toUpperCase();
                            if (cNom.isEmpty()) continue;
                            
                            compagnieRepository.findByNomIgnoreCase(cNom)
                                    .ifPresentOrElse(
                                        c -> {},
                                        () -> {
                                            try {
                                                Compagnie c = new Compagnie();
                                                c.setNom(cNom);
                                                c.setBataillon(finalBataillon);
                                                compagnieRepository.saveAndFlush(c);
                                            } catch (Exception e) {
                                                log.warn("Could not save company {}: {}", cNom, e.getMessage());
                                            }
                                        }
                                    );
                        }
                    }
                } catch (Exception e) {
                    log.error("Error processing line: " + line, e);
                    // On continue le traitement des autres lignes
                }
            }
            log.info("Hierarchy import completed.");
        } catch (Exception e) {
            log.error("Critical error during hierarchy import", e);
        }
    }
}
