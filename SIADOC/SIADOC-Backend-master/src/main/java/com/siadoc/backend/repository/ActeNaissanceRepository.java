package com.siadoc.backend.repository;

import com.siadoc.backend.model.ActeNaissance;
import com.siadoc.backend.dto.search.ResultRechercheEtatCivilDTO;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;
import java.util.UUID;

public interface ActeNaissanceRepository extends JpaRepository<ActeNaissance, UUID> {

    List<ActeNaissance> findByEtatCivilId(UUID etatCivilId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheEtatCivilDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    a.lieuEtablissement,
    a.dateEtablissement
)
FROM ActeNaissance a
JOIN a.etatCivil ec
JOIN ec.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:annee IS NULL OR EXTRACT(YEAR FROM a.dateEtablissement) = :annee)
AND (:lieu IS NULL OR a.lieuEtablissement ILIKE :lieu)
""")
    List<ResultRechercheEtatCivilDTO> rechercherActeNaissance(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("annee") Integer annee,
            @Param("lieu") String lieu
    );
}