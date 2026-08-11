package com.siadoc.backend.repository;

import com.siadoc.backend.model.NotationItem;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheNotationDTO;

import java.util.List;
import java.util.UUID;

public interface NotationItemRepository extends JpaRepository<NotationItem, UUID> {
    // Tri Descendant : Du plus récent au plus vieux
    List<NotationItem> findByModuleIdOrderByPeriodeAuDesc(UUID moduleId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheNotationDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    CAST(EXTRACT(YEAR FROM ni.periodeAu) AS integer),
    ni.appreciationGenerale,
    0.0
)
FROM NotationItem ni
JOIN ni.module mod
JOIN mod.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:appreciation IS NULL OR ni.appreciationGenerale ILIKE :appreciation)
AND (:annee IS NULL OR EXTRACT(YEAR FROM ni.periodeAu) = :annee)
""")
    List<ResultRechercheNotationDTO> rechercherNotations(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("appreciation") String appreciation,
            @Param("annee") Integer annee
    );
}