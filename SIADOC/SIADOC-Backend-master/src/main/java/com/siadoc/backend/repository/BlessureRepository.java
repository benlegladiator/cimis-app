package com.siadoc.backend.repository;

import com.siadoc.backend.model.Blessure;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheMedicalDTO;

import java.util.List;
import java.util.UUID;

public interface BlessureRepository extends JpaRepository<Blessure, UUID> {
    @Query("""
            SELECT new com.siadoc.backend.dto.search.ResultRechercheMedicalDTO(
                m.nom,
                m.prenom,
                m.grade,
                m.armeService,
                b.nature,
                b.lieu,
                b.dateEffet
            )
            FROM Blessure b
            JOIN b.module bm
            JOIN bm.dossier d
            JOIN d.militaire m
            WHERE
            (:nom IS NULL OR m.nom ILIKE :nom)
            AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
            AND (:grade IS NULL OR m.grade = :grade)
            AND (:arme IS NULL OR m.armeService = :arme)
            AND (:nature IS NULL OR b.nature ILIKE :nature)
            AND (:lieu IS NULL OR b.lieu ILIKE :lieu)
            AND (:annee IS NULL OR EXTRACT(YEAR FROM b.dateEffet) = :annee)
            """)
    List<ResultRechercheMedicalDTO> rechercherBlessures(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("nature") String nature,
            @Param("lieu") String lieu,
            @Param("annee") Integer annee);
}