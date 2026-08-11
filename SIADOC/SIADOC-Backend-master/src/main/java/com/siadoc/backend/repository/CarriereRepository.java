package com.siadoc.backend.repository;

import com.siadoc.backend.model.Carriere;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheCarriereDTO;

import java.util.List;
import java.util.Optional;
import java.util.UUID;

public interface CarriereRepository extends JpaRepository<Carriere, UUID> {
    // Permet de trouver le module directement via l'ID du dossier
    Optional<Carriere> findByDossierId(UUID dossierId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheCarriereDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    c.cnim,
    c.nomCompagnie,
    m.dateGrade
)
FROM Carriere c
JOIN c.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:poste IS NULL OR c.cnim ILIKE :poste)
AND (:unite IS NULL OR c.nomCompagnie ILIKE :unite)
""")
    List<ResultRechercheCarriereDTO> rechercherCarriere(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("poste") String poste,
            @Param("unite") String unite
    );
}