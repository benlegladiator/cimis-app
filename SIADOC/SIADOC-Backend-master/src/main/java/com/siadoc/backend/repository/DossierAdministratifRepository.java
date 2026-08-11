package com.siadoc.backend.repository;

import com.siadoc.backend.dto.ArchiveResultDTO;
import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.model.StatutDossier;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.Optional;
import java.util.List;
import java.util.UUID;

public interface DossierAdministratifRepository extends JpaRepository<DossierAdministratif, UUID> {

    Optional<DossierAdministratif> findByMilitaireId(UUID militaireId);
    List<DossierAdministratif> findByStatutNot(StatutDossier statut);

    long countByStatut(StatutDossier statut);

    @Query("""
        SELECT new com.siadoc.backend.dto.ArchiveResultDTO(
            m.id, m.nom, m.prenom, COALESCE(m.matriculeMilitaire, m.matriculeSolde), null
        )
        FROM DossierAdministratif d
        JOIN d.militaire m
        WHERE d.statut = :statut
          AND (m.etat IS NULL OR m.etat <> com.siadoc.backend.model.EtatMilitaire.DECEDE)
          AND (:search IS NULL OR LOWER(m.nom) LIKE LOWER(CONCAT('%', :search, '%'))
                             OR LOWER(m.prenom) LIKE LOWER(CONCAT('%', :search, '%'))
                             OR LOWER(m.matriculeMilitaire) LIKE LOWER(CONCAT('%', :search, '%'))
                             OR LOWER(m.matriculeSolde) LIKE LOWER(CONCAT('%', :search, '%')))
    """)
    List<ArchiveResultDTO> searchArchives(@Param("statut") StatutDossier statut, @Param("search") String search);

    @Query("SELECT d FROM DossierAdministratif d WHERE d.compagnie.id = :compagnieId")
    List<DossierAdministratif> findByCompagnieId(@Param("compagnieId") UUID compagnieId);

    @Query("SELECT d FROM DossierAdministratif d WHERE d.compagnie.bataillon.id = :bataillonId")
    List<DossierAdministratif> findByBataillonId(@Param("bataillonId") UUID bataillonId);

    @Query("SELECT d FROM DossierAdministratif d WHERE d.compagnie.bataillon.brigade.id = :brigadeId")
    List<DossierAdministratif> findByBrigadeId(@Param("brigadeId") UUID brigadeId);

    @Query("SELECT d FROM DossierAdministratif d WHERE d.compagnie.bataillon.brigade.region.id = :regionId")
    List<DossierAdministratif> findByRegionId(@Param("regionId") UUID regionId);

    // Dashboard Aggregations (Hierarchical)
    @Query("SELECT COUNT(d) FROM DossierAdministratif d " +
           "LEFT JOIN d.compagnie comp " +
           "LEFT JOIN comp.bataillon bat " +
           "LEFT JOIN bat.brigade bri " +
           "WHERE bri.region.id = :regionId")
    long countStatsByRegionId(@Param("regionId") UUID regionId);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d LEFT JOIN d.compagnie comp LEFT JOIN comp.bataillon bat LEFT JOIN bat.brigade bri WHERE bri.id = :brigadeId")
    long countStatsByBrigadeId(@Param("brigadeId") UUID brigadeId);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d LEFT JOIN d.compagnie comp LEFT JOIN comp.bataillon bat WHERE bat.id = :bataillonId")
    long countStatsByBataillonId(@Param("bataillonId") UUID bataillonId);

    long countByCompagnieId(UUID compagnieId);

    long countByStatutAndCompagnieId(com.siadoc.backend.model.StatutDossier statut, UUID compagnieId);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d WHERE d.statut = :statut AND d.compagnie.bataillon.id = :bataillonId")
    long countByStatutAndBataillonId(@Param("statut") com.siadoc.backend.model.StatutDossier statut, @Param("bataillonId") UUID bataillonId);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d WHERE d.statut = :statut AND d.compagnie.bataillon.brigade.id = :brigadeId")
    long countByStatutAndBrigadeId(@Param("statut") com.siadoc.backend.model.StatutDossier statut, @Param("brigadeId") UUID brigadeId);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d " +
           "LEFT JOIN d.compagnie comp " +
           "LEFT JOIN comp.bataillon bat " +
           "LEFT JOIN bat.brigade bri " +
           "WHERE d.statut = :statut AND bri.region.id = :regionId")
    long countByStatutAndRegionId(@Param("statut") com.siadoc.backend.model.StatutDossier statut, @Param("regionId") UUID regionId);

    @Query(value = "SELECT COALESCE(m.arme_service, c.corps, 'INCONNU') AS arme, COUNT(d.id) " +
           "FROM dossier_administratif d " +
           "JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "JOIN compagnie comp ON comp.id = d.compagnie_id " +
           "JOIN bataillon bat ON bat.id = comp.bataillon_id " +
           "JOIN brigade bri ON bri.id = bat.brigade_id " +
           "WHERE bri.region_id = :regionId " +
           "GROUP BY COALESCE(m.arme_service, c.corps, 'INCONNU')",
           nativeQuery = true)
    List<Object[]> countByRegionAndGroupByCorps(@Param("regionId") UUID regionId);

    @Query(value = "SELECT COALESCE(m.arme_service, c.corps, 'INCONNU') AS arme, COUNT(d.id) " +
           "FROM dossier_administratif d " +
           "JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "JOIN compagnie comp ON comp.id = d.compagnie_id " +
           "JOIN bataillon bat ON bat.id = comp.bataillon_id " +
           "WHERE bat.brigade_id = :brigadeId " +
           "GROUP BY COALESCE(m.arme_service, c.corps, 'INCONNU')",
           nativeQuery = true)
    List<Object[]> countByBrigadeAndGroupByCorps(@Param("brigadeId") UUID brigadeId);

    @Query(value = "SELECT COALESCE(m.arme_service, c.corps, 'INCONNU') AS arme, COUNT(d.id) " +
           "FROM dossier_administratif d " +
           "JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "JOIN compagnie comp ON comp.id = d.compagnie_id " +
           "WHERE comp.bataillon_id = :bataillonId " +
           "GROUP BY COALESCE(m.arme_service, c.corps, 'INCONNU')",
           nativeQuery = true)
    List<Object[]> countByBataillonAndGroupByCorps(@Param("bataillonId") UUID bataillonId);

    @Query(value = "SELECT COALESCE(m.arme_service, c.corps, 'INCONNU') AS arme, COUNT(d.id) " +
           "FROM dossier_administratif d " +
           "JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "GROUP BY COALESCE(m.arme_service, c.corps, 'INCONNU')",
           nativeQuery = true)
    List<Object[]> countAllAndGroupByCorps();
 
    @Query("SELECT d.militaire.armeService, COUNT(d) FROM DossierAdministratif d GROUP BY d.militaire.armeService")
    List<Object[]> countAllAndGroupByArme();

    @Query("SELECT COALESCE(bat.nom, 'Unité Inconnue'), COALESCE(comp.nom, 'Sans Compagnie'), d.carriere.corps, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "LEFT JOIN d.compagnie comp " +
           "LEFT JOIN comp.bataillon bat " +
           "GROUP BY bat.nom, comp.nom, d.carriere.corps")
    List<Object[]> getFullStatsGlobal();

    @Query("SELECT COALESCE(bat.nom, 'Unité Inconnue'), COALESCE(comp.nom, 'Sans Compagnie'), d.carriere.corps, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "LEFT JOIN d.compagnie comp " +
           "LEFT JOIN comp.bataillon bat " +
           "LEFT JOIN bat.brigade bri " +
           "WHERE bri.region.id = :regionId " +
           "GROUP BY bat.nom, comp.nom, d.carriere.corps")
    List<Object[]> getFullStatsForRMIA(@Param("regionId") UUID regionId);

    @Query(value = "SELECT COALESCE(m.arme_service, c.corps, 'INCONNU') AS arme, COUNT(d.id) " +
           "FROM dossier_administratif d " +
           "JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "WHERE d.compagnie_id = :compagnieId " +
           "GROUP BY COALESCE(m.arme_service, c.corps, 'INCONNU')",
           nativeQuery = true)
    List<Object[]> countByCompagnieAndGroupByCorps(@Param("compagnieId") UUID compagnieId);

    @Query(value = "SELECT comp.id, comp.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM compagnie comp " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "WHERE comp.bataillon_id = :bataillonId " +
           "GROUP BY comp.id, comp.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByCompagnieAndCorpsForBataillon(@Param("bataillonId") UUID bId);

    @Query("SELECT m.categorie, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "JOIN d.militaire m " +
           "JOIN d.compagnie comp " +
           "WHERE comp.bataillon.id = :bataillonId " +
           "GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForBataillon(@Param("bataillonId") UUID bId2);

    // ==================================
    // NEW EXTENDED HIERARCHY QUERIES
    // ==================================

    @Query(value = "SELECT bri.id, bri.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM brigade bri " +
           "JOIN region_militaire r ON bri.region_id = r.id " +
           "LEFT JOIN bataillon bat ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "WHERE r.id = :regionId " +
           "GROUP BY bri.id, bri.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByBrigadeAndCorpsForRMIA(@Param("regionId") UUID regionId);
 
    @Query(value = "SELECT bri.id, bri.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM brigade bri " +
           "LEFT JOIN bataillon bat ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "GROUP BY bri.id, bri.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByBrigadeAndCorpsGlobal();

    @Query(value = "SELECT r.id, r.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM region_militaire r " +
           "LEFT JOIN brigade bri ON bri.region_id = r.id " +
           "LEFT JOIN bataillon bat ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "GROUP BY r.id, r.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByRegionAndCorpsGlobal();

    @Query(value = "SELECT bat.id, bat.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM bataillon bat " +
           "JOIN brigade bri ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "WHERE bri.region_id = :regionId " +
           "GROUP BY bat.id, bat.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByBataillonAndCorpsForRMIA(@Param("regionId") UUID regionId);

    @Query(value = "SELECT comp.id, comp.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM compagnie comp " +
           "JOIN bataillon bat ON comp.bataillon_id = bat.id " +
           "JOIN brigade bri ON bat.brigade_id = bri.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "WHERE bri.region_id = :regionId " +
           "GROUP BY comp.id, comp.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByCompagnieAndCorpsForRMIA(@Param("regionId") UUID regionId);

    @Query(value = "SELECT bat.id, bat.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM bataillon bat " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "WHERE bat.brigade_id = :brigadeId " +
           "GROUP BY bat.id, bat.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByBataillonAndCorpsForBrigade(@Param("brigadeId") UUID brigadeId);

    @Query(value = "SELECT comp.id, comp.nom, COALESCE(m.arme_service, c.corps, 'INCONNU'), COUNT(d.id) " +
           "FROM compagnie comp " +
           "JOIN bataillon bat ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "LEFT JOIN carriere c ON c.dossier_id = d.id " +
           "WHERE bat.brigade_id = :brigadeId " +
           "GROUP BY comp.id, comp.nom, COALESCE(m.arme_service, c.corps, 'INCONNU')", nativeQuery = true)
    List<Object[]> getStatsByCompagnieAndCorpsForBrigade(@Param("brigadeId") UUID brigadeId);

    @Query("SELECT m.categorie, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "JOIN d.militaire m " +
           "LEFT JOIN d.compagnie comp " +
           "LEFT JOIN comp.bataillon bat " +
           "LEFT JOIN bat.brigade bri " +
           "WHERE bri.region.id = :regionId " +
           "GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForRMIA(@Param("regionId") UUID regionId);

    @Query("SELECT m.categorie, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "JOIN d.militaire m " +
           "LEFT JOIN d.compagnie comp " +
           "LEFT JOIN comp.bataillon bat " +
           "LEFT JOIN bat.brigade bri " +
           "WHERE bri.id = :brigadeId " +
           "GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForBrigade(@Param("brigadeId") UUID brigadeId);

    @Query("SELECT m.categorie, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "JOIN d.militaire m " +
           "GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieGlobal();

    @Query("SELECT m.sexe, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "JOIN d.militaire m " +
           "GROUP BY m.sexe")
    List<Object[]> getStatsBySexeGlobal();

    @Query("SELECT m.categorie, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m WHERE d.compagnie.id = :compId GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForCompagnie(@Param("compId") UUID compId);

    @Query("SELECT m.sexe, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m WHERE d.compagnie.id = :compId GROUP BY m.sexe")
    List<Object[]> getStatsBySexeForCompagnie(@Param("compId") UUID compId);

    @Query("SELECT m.sexe, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m WHERE d.compagnie.bataillon.id = :batId GROUP BY m.sexe")
    List<Object[]> getStatsBySexeForBataillon(@Param("batId") UUID batId);

    @Query("SELECT m.sexe, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m WHERE d.compagnie.bataillon.brigade.id = :briId GROUP BY m.sexe")
    List<Object[]> getStatsBySexeForBrigade(@Param("briId") UUID briId);

    @Query("SELECT m.sexe, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m WHERE d.compagnie.bataillon.brigade.region.id = :regId GROUP BY m.sexe")
    List<Object[]> getStatsBySexeForRMIA(@Param("regId") UUID regId);

    // -- STATS POUR ETAT-MAJOR --
    @Query("SELECT COUNT(d) FROM DossierAdministratif d WHERE (UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code)")
    long countByArme(@Param("arme") String arme, @Param("code") String code);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d WHERE d.statut = :statut AND (UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code)")
    long countByStatutAndArme(@Param("statut") com.siadoc.backend.model.StatutDossier statut, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT m.categorie, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "JOIN d.militaire m " +
           "WHERE (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) " +
           "GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForArme(@Param("arme") String arme, @Param("code") String code);

    @Query("SELECT COALESCE(bat.nom, 'Unité Inconnue'), COALESCE(comp.nom, 'Sans Compagnie'), d.carriere.corps, COUNT(d) " +
           "FROM DossierAdministratif d " +
           "LEFT JOIN d.compagnie comp " +
           "LEFT JOIN comp.bataillon bat " +
           "WHERE (UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code) " +
           "GROUP BY bat.nom, comp.nom, d.carriere.corps")
    List<Object[]> getFullStatsForArme(@Param("arme") String arme, @Param("code") String code);

    @Query("SELECT d FROM DossierAdministratif d WHERE (UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code)")
    List<DossierAdministratif> findByArmeService(@Param("arme") String arme, @Param("code") String code);

    // -- HIERARCHICAL FILTERS FOR ETAT-MAJOR --
    @Query("SELECT COUNT(d) FROM DossierAdministratif d JOIN d.compagnie c JOIN c.bataillon bat JOIN bat.brigade bri WHERE bri.region.id = :id AND (UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code)")
    long countByRegionAndArme(@Param("id") UUID id, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d JOIN d.compagnie c JOIN c.bataillon bat WHERE bat.brigade.id = :id AND (UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code)")
    long countByBrigadeAndArme(@Param("id") UUID id, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT COUNT(d) FROM DossierAdministratif d JOIN d.compagnie c WHERE c.bataillon.id = :id AND (UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code)")
    long countByBataillonAndArme(@Param("id") UUID id, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT m.categorie, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m JOIN d.compagnie c JOIN c.bataillon bat JOIN bat.brigade bri WHERE bri.region.id = :id AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForRegionAndArme(@Param("id") UUID id, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT m.categorie, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m JOIN d.compagnie c JOIN c.bataillon bat WHERE bat.brigade.id = :id AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForBrigadeAndArme(@Param("id") UUID id, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT m.categorie, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m JOIN d.compagnie c WHERE c.bataillon.id = :id AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY m.categorie")
    List<Object[]> getStatsByCategorieForBataillonAndArme(@Param("id") UUID id, @Param("arme") String arme, @Param("code") String code);

    // -- DETAILED HIERARCHY AGGREGATIONS FOR ETAT-MAJOR --
    @Query("SELECT bri.id, bri.nom, COALESCE(m.armeService, c.corps, 'INCONNU'), COUNT(d) FROM DossierAdministratif d JOIN d.militaire m LEFT JOIN d.carriere c JOIN d.compagnie comp JOIN comp.bataillon bat JOIN bat.brigade bri WHERE bri.region.id = :regionId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY bri.id, bri.nom, c.corps, m.armeService")
    List<Object[]> getStatsByBrigadeAndCorpsForRMIAAndArme(@Param("regionId") UUID regionId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT bat.id, bat.nom, COALESCE(m.armeService, c.corps, 'INCONNU'), COUNT(d) FROM DossierAdministratif d JOIN d.militaire m LEFT JOIN d.carriere c JOIN d.compagnie comp JOIN comp.bataillon bat JOIN bat.brigade bri WHERE bri.region.id = :regionId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY bat.id, bat.nom, c.corps, m.armeService")
    List<Object[]> getStatsByBataillonAndCorpsForRMIAAndArme(@Param("regionId") UUID regionId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT comp.id, comp.nom, COALESCE(m.armeService, c.corps, 'INCONNU'), COUNT(d) FROM DossierAdministratif d JOIN d.militaire m LEFT JOIN d.carriere c JOIN d.compagnie comp JOIN comp.bataillon bat JOIN bat.brigade bri WHERE bri.region.id = :regionId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY comp.id, comp.nom, c.corps, m.armeService")
    List<Object[]> getStatsByCompagnieAndCorpsForRMIAAndArme(@Param("regionId") UUID regionId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT bat.id, bat.nom, COALESCE(m.armeService, c.corps, 'INCONNU'), COUNT(d) FROM DossierAdministratif d JOIN d.militaire m LEFT JOIN d.carriere c JOIN d.compagnie comp JOIN comp.bataillon bat WHERE bat.brigade.id = :brigadeId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY bat.id, bat.nom, c.corps, m.armeService")
    List<Object[]> getStatsByBataillonAndCorpsForBrigadeAndArme(@Param("brigadeId") UUID brigadeId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT comp.id, comp.nom, COALESCE(m.armeService, c.corps, 'INCONNU'), COUNT(d) FROM DossierAdministratif d JOIN d.militaire m LEFT JOIN d.carriere c JOIN d.compagnie comp JOIN comp.bataillon bat WHERE bat.brigade.id = :brigadeId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY comp.id, comp.nom, c.corps, m.armeService")
    List<Object[]> getStatsByCompagnieAndCorpsForBrigadeAndArme(@Param("brigadeId") UUID brigadeId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT bri.id, bri.nom, COALESCE(m.armeService, c.corps, 'INCONNU'), COUNT(d) FROM DossierAdministratif d JOIN d.militaire m LEFT JOIN d.carriere c JOIN d.compagnie comp JOIN comp.bataillon bat JOIN bat.brigade bri WHERE (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY bri.id, bri.nom, c.corps, m.armeService")
    List<Object[]> getStatsByBrigadeAndCorpsForArme(@Param("arme") String arme, @Param("code") String code);

    @Query("SELECT comp.id, comp.nom, COALESCE(m.armeService, c.corps, 'INCONNU'), COUNT(d) FROM DossierAdministratif d JOIN d.militaire m LEFT JOIN d.carriere c JOIN d.compagnie comp WHERE comp.bataillon.id = :bataillonId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY comp.id, comp.nom, c.corps, m.armeService")
    List<Object[]> getStatsByCompagnieAndCorpsForBataillonAndArme(@Param("bataillonId") UUID bataillonId, @Param("arme") String arme, @Param("code") String code);

    // -- CATEGORY BREAKDOWN FOR ETAT-MAJOR RECAP TABLE --
    @Query(value = "SELECT bri.id, bri.nom, m.categorie, COUNT(d.id) " +
           "FROM brigade bri " +
           "LEFT JOIN bataillon bat ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "WHERE bri.region_id = :regionId AND (m.id IS NULL OR (UPPER(m.arme_service) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.arme_service) = :code)) " +
           "GROUP BY bri.id, bri.nom, m.categorie", nativeQuery = true)
    List<Object[]> getStatsByBrigadeAndCategoryForRMIAAndArme(@Param("regionId") UUID regionId, @Param("arme") String arme, @Param("code") String code);

    @Query(value = "SELECT bat.id, bat.nom, m.categorie, COUNT(d.id) " +
           "FROM bataillon bat " +
           "JOIN brigade bri ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "WHERE bri.region_id = :regionId AND (m.id IS NULL OR (UPPER(m.arme_service) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.arme_service) = :code)) " +
           "GROUP BY bat.id, bat.nom, m.categorie", nativeQuery = true)
    List<Object[]> getStatsByBataillonAndCategoryForRMIAAndArme(@Param("regionId") UUID regionId, @Param("arme") String arme, @Param("code") String code);

    @Query(value = "SELECT comp.id, comp.nom, m.categorie, COUNT(d.id) " +
           "FROM compagnie comp " +
           "JOIN bataillon bat ON comp.bataillon_id = bat.id " +
           "JOIN brigade bri ON bat.brigade_id = bri.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "WHERE bri.region_id = :regionId AND (m.id IS NULL OR (UPPER(m.arme_service) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.arme_service) = :code)) " +
           "GROUP BY comp.id, comp.nom, m.categorie", nativeQuery = true)
    List<Object[]> getStatsByCompagnieAndCategoryForRMIAAndArme(@Param("regionId") UUID regionId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT bat.id, bat.nom, m.categorie, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m JOIN d.compagnie comp JOIN comp.bataillon bat WHERE bat.brigade.id = :brigadeId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY bat.id, bat.nom, m.categorie")
    List<Object[]> getStatsByBataillonAndCategoryForBrigadeAndArme(@Param("brigadeId") UUID brigadeId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT comp.id, comp.nom, m.categorie, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m JOIN d.compagnie comp JOIN comp.bataillon bat WHERE bat.brigade.id = :brigadeId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY comp.id, comp.nom, m.categorie")
    List<Object[]> getStatsByCompagnieAndCategoryForBrigadeAndArme(@Param("brigadeId") UUID brigadeId, @Param("arme") String arme, @Param("code") String code);

    @Query("SELECT comp.id, comp.nom, m.categorie, COUNT(d) FROM DossierAdministratif d JOIN d.militaire m JOIN d.compagnie comp WHERE comp.bataillon.id = :bataillonId AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) GROUP BY comp.id, comp.nom, m.categorie")
    List<Object[]> getStatsByCompagnieAndCategoryForBataillonAndArme(@Param("bataillonId") UUID bataillonId, @Param("arme") String arme, @Param("code") String code);

    @Query(value = "SELECT bri.id, bri.nom, m.categorie, COUNT(d.id) " +
           "FROM brigade bri " +
           "LEFT JOIN bataillon bat ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "WHERE (m.id IS NULL OR (UPPER(m.arme_service) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.arme_service) = :code)) " +
           "GROUP BY bri.id, bri.nom, m.categorie", nativeQuery = true)
    List<Object[]> getStatsByBrigadeAndCategoryForArme(@Param("arme") String arme, @Param("code") String code);

    @Query(value = "SELECT r.id, r.nom, m.categorie, COUNT(d.id) " +
           "FROM region_militaire r " +
           "LEFT JOIN brigade bri ON bri.region_id = r.id " +
           "LEFT JOIN bataillon bat ON bat.brigade_id = bri.id " +
           "LEFT JOIN compagnie comp ON comp.bataillon_id = bat.id " +
           "LEFT JOIN dossier_administratif d ON d.compagnie_id = comp.id " +
           "LEFT JOIN militaire m ON m.id = d.militaire_id " +
           "WHERE (m.id IS NULL OR (UPPER(m.arme_service) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.arme_service) = :code)) " +
           "GROUP BY r.id, r.nom, m.categorie", nativeQuery = true)
    List<Object[]> getStatsByRegionAndCategoryForArme(@Param("arme") String arme, @Param("code") String code);
}