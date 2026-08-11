package com.siadoc.backend.repository;

import com.siadoc.backend.model.StageItem;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheStageDTO;

import java.util.List;
import java.util.UUID;

public interface StageItemRepository extends JpaRepository<StageItem, UUID> {
    // Tri Ascendant : Du plus vieux au plus récent
    List<StageItem> findByModuleIdOrderByDateObtentionAsc(UUID moduleId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheStageDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    s.designation,
    s.ville,
    s.dateObtention
)
FROM StageItem s
JOIN s.module sm
JOIN sm.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:designation IS NULL OR s.designation ILIKE :designation)
AND (:lieu IS NULL OR (s.ville ILIKE :lieu OR s.pays ILIKE :lieu))
AND (:annee IS NULL OR EXTRACT(YEAR FROM s.dateObtention) = :annee)
""")
    List<ResultRechercheStageDTO> rechercherStages(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("designation") String designation,
            @Param("lieu") String lieu,
            @Param("annee") Integer annee
    );
}