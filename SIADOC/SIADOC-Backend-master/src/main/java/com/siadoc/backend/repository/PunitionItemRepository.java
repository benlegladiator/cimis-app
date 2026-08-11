package com.siadoc.backend.repository;

import com.siadoc.backend.model.PunitionItem;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRecherchePunitionDTO;

import java.util.List;
import java.util.UUID;

public interface PunitionItemRepository extends JpaRepository<PunitionItem, UUID> {
    // Tri Ascendant : Les plus anciennes dates en premier
    List<PunitionItem> findByModuleIdOrderByDateEffetAsc(UUID moduleId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRecherchePunitionDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    p.designation,
    p.texte,
    p.dateEffet
)
FROM PunitionItem p
JOIN p.module pm
JOIN pm.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR LOWER(m.nom) LIKE LOWER(:nom))
AND (:prenom IS NULL OR LOWER(m.prenom) LIKE LOWER(:prenom))
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:designation IS NULL OR LOWER(p.designation) LIKE LOWER(:designation))
AND (:texte IS NULL OR LOWER(p.texte) LIKE LOWER(:texte))
AND (:annee IS NULL OR EXTRACT(YEAR FROM p.dateEffet) = :annee)
""")
    List<ResultRecherchePunitionDTO> rechercherPunition(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("designation") String designation,
            @Param("texte") String texte,
            @Param("annee") Integer annee
    );
}
