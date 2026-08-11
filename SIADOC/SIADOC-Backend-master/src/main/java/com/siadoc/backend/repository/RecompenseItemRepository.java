package com.siadoc.backend.repository;

import com.siadoc.backend.model.RecompenseItem;
import org.springframework.data.jpa.repository.JpaRepository;
import com.siadoc.backend.dto.search.ResultRechercheRecompenseDTO;
import org.springframework.data.repository.query.Param;
import java.util.List;
import org.springframework.data.jpa.repository.Query;
import java.util.UUID;

public interface RecompenseItemRepository extends JpaRepository<RecompenseItem, UUID> {
    // Tri par Date Croissante (Ascending)
    List<RecompenseItem> findByModuleIdOrderByDateEffetAsc(UUID moduleId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheRecompenseDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    r.designation,
    r.texte,
    r.dateEffet
)
FROM RecompenseItem r
JOIN r.module rm
JOIN rm.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:designation IS NULL OR r.designation ILIKE :designation)
AND (:texte IS NULL OR r.texte ILIKE :texte)
AND (:annee IS NULL OR EXTRACT(YEAR FROM r.dateEffet) = :annee)
""")
    List<ResultRechercheRecompenseDTO> rechercherRecompenses(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("designation") String designation,
            @Param("texte") String texte,
            @Param("annee") Integer annee
    );
}
