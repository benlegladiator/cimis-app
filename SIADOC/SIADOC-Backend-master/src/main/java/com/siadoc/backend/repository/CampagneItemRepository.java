package com.siadoc.backend.repository;

import com.siadoc.backend.model.CampagneMilitaireItem;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheCampagneMilitaireDTO;

import java.util.List;
import java.util.UUID;

public interface CampagneItemRepository extends JpaRepository<CampagneMilitaireItem, UUID> {

    List<CampagneMilitaireItem> findByModuleIdOrderByDateAsc(UUID moduleId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheCampagneMilitaireDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    ci.designation,
    ci.date
)
FROM CampagneMilitaireItem ci
JOIN ci.module mod
JOIN mod.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:campagne IS NULL OR ci.designation ILIKE :campagne)
AND (:annee IS NULL OR EXTRACT(YEAR FROM ci.date) = :annee)
""")
    List<ResultRechercheCampagneMilitaireDTO> rechercherCampagnes(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("campagne") String campagne,
            @Param("annee") Integer annee
    );
}
