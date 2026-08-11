package com.siadoc.backend.repository;

import com.siadoc.backend.model.Pension;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheMedicalDTO;

import java.util.List;
import java.util.UUID;

public interface PensionRepository extends JpaRepository<Pension, UUID> {
    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheMedicalDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    p.typeInvalidite,
    'Pension',
    p.datePriseEffet
)
FROM Pension p
JOIN p.module pm
JOIN pm.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:taux IS NULL OR p.taux = :taux)
AND (:annee IS NULL OR EXTRACT(YEAR FROM p.datePriseEffet) = :annee)
""")
    List<ResultRechercheMedicalDTO> rechercherPensions(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("taux") Integer taux,
            @Param("annee") Integer annee
    );
}
