package com.siadoc.backend.repository;

import com.siadoc.backend.model.MutationItem;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheMutationDTO;

import java.util.List;
import java.util.UUID;

public interface MutationItemRepository extends JpaRepository<MutationItem, UUID> {
    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheMutationDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    mi.emploi,
    CAST(mi.unite AS string),
    CAST(mi.ville AS string),
    mi.dateTexte
)
FROM MutationItem mi
JOIN mi.module mod
JOIN mod.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:provenance IS NULL OR CAST(mi.unite AS string) ILIKE :provenance OR CAST(mi.ville AS string) ILIKE :provenance)
AND (:destination IS NULL OR CAST(mi.unite AS string) ILIKE :destination OR CAST(mi.ville AS string) ILIKE :destination)
AND (:annee IS NULL OR EXTRACT(YEAR FROM mi.dateTexte) = :annee)
""")
    List<ResultRechercheMutationDTO> rechercherMutations(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("provenance") String provenance,
            @Param("destination") String destination,
            @Param("annee") Integer annee
    );

    @Query(value = """
        SELECT m.matricule_militaire, m.nom, m.prenom, c.nom as destination, mi.date_texte,
               c_source.nom as provenance
        FROM mutation_item mi
        JOIN mutations_module mm ON mi.module_id = mm.id
        JOIN dossier_administratif d ON mm.dossier_id = d.id
        JOIN militaire m ON d.militaire_id = m.id
        JOIN compagnie c ON mi.compagnie_id = c.id
        LEFT JOIN compagnie c_source ON d.compagnie_id = c_source.id
        JOIN bataillon b ON c.bataillon_id = b.id
        JOIN brigade br ON b.brigade_id = br.id
        WHERE mi.type = 'AFFECTATION'
          AND br.region_id = :regionId
          AND mi.date_texte >= CURRENT_DATE - INTERVAL '30 days'
        ORDER BY mi.date_texte DESC
    """, nativeQuery = true)
    List<Object[]> findRecentTransfersByRegion(@Param("regionId") UUID regionId);

    @Query(value = """
        SELECT m.matricule_militaire, m.nom, m.prenom, c.nom as destination, mi.date_texte,
               c_source.nom as provenance
        FROM mutation_item mi
        JOIN mutations_module mm ON mi.module_id = mm.id
        JOIN dossier_administratif d ON mm.dossier_id = d.id
        JOIN militaire m ON d.militaire_id = m.id
        JOIN compagnie c ON mi.compagnie_id = c.id
        LEFT JOIN compagnie c_source ON d.compagnie_id = c_source.id
        JOIN bataillon b ON c.bataillon_id = b.id
        WHERE mi.type = 'AFFECTATION'
          AND b.brigade_id = :brigadeId
          AND mi.date_texte >= CURRENT_DATE - INTERVAL '30 days'
        ORDER BY mi.date_texte DESC
    """, nativeQuery = true)
    List<Object[]> findRecentTransfersByBrigade(@Param("brigadeId") UUID brigadeId);

    @Query(value = """
        SELECT m.matricule_militaire, m.nom, m.prenom, c.nom as destination, mi.date_texte,
               c_source.nom as provenance
        FROM mutation_item mi
        JOIN mutations_module mm ON mi.module_id = mm.id
        JOIN dossier_administratif d ON mm.dossier_id = d.id
        JOIN militaire m ON d.militaire_id = m.id
        JOIN compagnie c ON mi.compagnie_id = c.id
        LEFT JOIN compagnie c_source ON d.compagnie_id = c_source.id
        WHERE mi.type = 'AFFECTATION'
          AND c.bataillon_id = :bataillonId
          AND mi.date_texte >= CURRENT_DATE - INTERVAL '30 days'
        ORDER BY mi.date_texte DESC
    """, nativeQuery = true)
    List<Object[]> findRecentTransfersByBataillon(@Param("bataillonId") UUID bataillonId);
}