package com.siadoc.backend.repository;

import com.siadoc.backend.model.Militaire;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.model.StatutValidation;

import java.util.UUID;
import java.util.List;
import org.springframework.data.repository.query.Param;

public interface MilitaireRepository extends JpaRepository<Militaire, UUID> {

    java.util.Optional<Militaire> findByMatriculeSolde(String matriculeSolde);
    java.util.Optional<Militaire> findByMatriculeMilitaire(String matriculeMilitaire);

    List<Militaire> findByStatutValidation(StatutValidation statutValidation);

    @Query("SELECT m FROM Militaire m JOIN m.dossier d WHERE d.compagnie.id = :compagnieId")
    List<Militaire> findByDossierCompagnieId(@Param("compagnieId") UUID compagnieId);

    @Query("SELECT m FROM Militaire m JOIN m.dossier d WHERE d.compagnie IS NOT NULL AND d.compagnie.nom = :nom AND m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE")
    List<Militaire> findByCompagnieNom(@Param("nom") String nom);

    @Query("SELECT m FROM Militaire m JOIN m.dossier d WHERE d.uniteOrganisationnelle IS NOT NULL AND d.uniteOrganisationnelle.nom = :nom AND m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE")
    List<Militaire> findByUniteOrganisationnelleNom(@Param("nom") String nom);
    
    @Query("SELECT m FROM Militaire m JOIN m.dossier d JOIN d.compagnie c JOIN c.bataillon bat JOIN bat.brigade bri JOIN bri.region r WHERE r.id = :regionId")
    List<Militaire> findByDossierCompagnieBataillonBrigadeRegionId(@Param("regionId") UUID regionId);

    @Query("SELECT m FROM Militaire m JOIN m.dossier d JOIN d.compagnie c JOIN c.bataillon bat JOIN bat.brigade bri WHERE bri.id = :brigadeId")
    List<Militaire> findByDossierCompagnieBataillonBrigadeId(@Param("brigadeId") UUID brigadeId);

    @Query("SELECT m FROM Militaire m JOIN m.dossier d JOIN d.compagnie c JOIN c.bataillon bat WHERE bat.id = :bataillonId")
    List<Militaire> findByDossierCompagnieBataillonId(@Param("bataillonId") UUID bataillonId);
    
    @Query("""
        SELECT m FROM Militaire m 
        WHERE (:nom IS NULL OR UPPER(m.nom) LIKE UPPER(CONCAT('%', :nom, '%')))
          AND (:matricule IS NULL OR UPPER(m.matriculeMilitaire) LIKE UPPER(CONCAT('%', :matricule, '%')))
          AND (:grade IS NULL OR UPPER(m.grade) LIKE UPPER(CONCAT('%', :grade, '%')))
          AND m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE
    """)
    List<Militaire> search(
        @Param("nom") String nom, 
        @Param("matricule") String matricule, 
        @Param("grade") String grade
    );

    // Liste des militaires actifs uniquement (exclut les dÃ©cÃ©dÃ©s et ceux dont le dossier est archivÃ©)
    @Query("""
        SELECT m FROM Militaire m
        WHERE (m.etat IS NULL OR m.etat <> com.siadoc.backend.model.EtatMilitaire.DECEDE)
          AND m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE
          AND (m.dossier IS NULL OR m.dossier.statut <> com.siadoc.backend.model.StatutDossier.ARCHIVE)
    """)
    List<Militaire> findActifs();

    @Query(value = "SELECT TRIM(UPPER(m.arme_service)), COUNT(m.id) FROM militaire m GROUP BY TRIM(UPPER(m.arme_service))", nativeQuery = true)
    List<Object[]> countByArme();

    @Query("SELECT d.compagnie.nom, COUNT(m.id) FROM Militaire m JOIN m.dossier d WHERE d.compagnie IS NOT NULL GROUP BY d.compagnie.nom")
    List<Object[]> countMilitairesByCompagnie();

    @Query("SELECT d.uniteOrganisationnelle.nom, COUNT(m.id) FROM Militaire m JOIN m.dossier d WHERE d.uniteOrganisationnelle IS NOT NULL GROUP BY d.uniteOrganisationnelle.nom")
    List<Object[]> countMilitairesByUnite();

    @Query(value = """
        SELECT TO_CHAR(m.date_grade, 'YYYY-MM'), COUNT(m.id)
        FROM militaire m
        GROUP BY TO_CHAR(m.date_grade, 'YYYY-MM')
        ORDER BY 1
    """, nativeQuery = true)
    List<Object[]> evolutionMensuelle();

    // -- Dashboard DRH Queries --
    @Query("SELECT m FROM Militaire m WHERE m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE AND m.dateService >= :dateLimite ORDER BY m.dateService DESC")
    List<Militaire> findNouvellesIntegrations(@Param("dateLimite") java.time.LocalDate dateLimite);

    @Query("""
        SELECT m FROM Militaire m
        WHERE m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE
          AND (m.etat IS NULL OR m.etat <> com.siadoc.backend.model.EtatMilitaire.DECEDE)
          AND (m.dossier IS NULL OR m.dossier.statut <> com.siadoc.backend.model.StatutDossier.ARCHIVE)
          AND (m.dateNaissance <= :dateNaissanceLimite OR m.dateService <= :dateServiceLimite)
    """)
    List<Militaire> findRetraitesProches(
        @Param("dateNaissanceLimite") java.time.LocalDate dateNaissanceLimite, 
        @Param("dateServiceLimite") java.time.LocalDate dateServiceLimite
    );

    @Query("SELECT m FROM Militaire m WHERE m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE AND m.dateService >= :dateLimite AND m.dossier.compagnie.bataillon.id = :bataillonId ORDER BY m.dateService DESC")
    List<Militaire> findNouvellesIntegrationsByBataillon(@Param("bataillonId") UUID bataillonId, @Param("dateLimite") java.time.LocalDate dateLimite);

    @Query("""
        SELECT m FROM Militaire m
        WHERE m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE
          AND (m.etat IS NULL OR m.etat <> com.siadoc.backend.model.EtatMilitaire.DECEDE)
          AND (m.dossier IS NULL OR m.dossier.statut <> com.siadoc.backend.model.StatutDossier.ARCHIVE)
          AND (m.dateNaissance <= :dateNaissanceLimite OR m.dateService <= :dateServiceLimite)
          AND m.dossier.compagnie.bataillon.id = :bataillonId
    """)
    List<Militaire> findRetraitesProchesByBataillon(
        @Param("bataillonId") UUID bataillonId,
        @Param("dateNaissanceLimite") java.time.LocalDate dateNaissanceLimite, 
        @Param("dateServiceLimite") java.time.LocalDate dateServiceLimite
    );

    @Query("""
        SELECT m FROM Militaire m
        WHERE m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE
          AND (m.dossier IS NULL OR m.dossier.compagnie IS NULL)
    """)
    List<Militaire> findSansCompagnie();

    List<Militaire> findByDateEnregistrementBetween(java.time.LocalDateTime start, java.time.LocalDateTime end);
    List<Militaire> findByDateMiseAJourBetween(java.time.LocalDateTime start, java.time.LocalDateTime end);

    @Query("""
        SELECT m FROM Militaire m
        WHERE (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code)
          AND m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE
          AND (m.dossier IS NULL OR m.dossier.statut <> com.siadoc.backend.model.StatutDossier.ARCHIVE)
    """)
    List<Militaire> findByArmeService(@Param("arme") String arme, @Param("code") String code);

    @Query("SELECT m FROM Militaire m WHERE m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE AND m.dateService >= :dateLimite AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code) ORDER BY m.dateService DESC")
    List<Militaire> findNouvellesIntegrationsByArme(@Param("arme") String arme, @Param("code") String code, @Param("dateLimite") java.time.LocalDate dateLimite);

    @Query("""
        SELECT m FROM Militaire m
        WHERE m.statutValidation = com.siadoc.backend.model.StatutValidation.VALIDE
          AND (m.etat IS NULL OR m.etat <> com.siadoc.backend.model.EtatMilitaire.DECEDE)
          AND (m.dossier IS NULL OR m.dossier.statut <> com.siadoc.backend.model.StatutDossier.ARCHIVE)
          AND (m.dateNaissance <= :dateNaissanceLimite OR m.dateService <= :dateServiceLimite)
          AND (UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code)
    """)
    List<Militaire> findRetraitesProchesByArme(
        @Param("arme") String arme,
        @Param("code") String code,
        @Param("dateNaissanceLimite") java.time.LocalDate dateNaissanceLimite, 
        @Param("dateServiceLimite") java.time.LocalDate dateServiceLimite
    );

    @Query("""
        SELECT m FROM Militaire m
        WHERE m.etat = com.siadoc.backend.model.EtatMilitaire.RETRAITE
           OR m.dossier.statut = com.siadoc.backend.model.StatutDossier.CAMPAGNE
    """)
    List<Militaire> findRetraites();
}
