package com.siadoc.backend.repository;

import com.siadoc.backend.model.ActeMariage;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import com.siadoc.backend.dto.search.ResultRechercheMariageDTO;

import java.util.List;
import java.util.UUID;

public interface ActeMariageRepository extends
        JpaRepository<ActeMariage, UUID> {

    List<ActeMariage> findByEtatCivilId(UUID etatCivilId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheMariageDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    a.numeroActe,
    a.dateMariage,
    a.lieuMariage,
    a.nomConjoint
)
FROM ActeMariage a
JOIN a.etatCivil ec
JOIN ec.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:annee IS NULL OR EXTRACT(YEAR FROM a.dateMariage) = :annee)
AND (:lieu IS NULL OR a.lieuMariage ILIKE :lieu)
AND (:nomConjoint IS NULL OR a.nomConjoint ILIKE :nomConjoint)
""")
    List<ResultRechercheMariageDTO> rechercherActeMariage(
            String nom,
            String prenom,
            String grade,
            String arme,
            Integer annee,
            String lieu,
            String nomConjoint
    );

}
