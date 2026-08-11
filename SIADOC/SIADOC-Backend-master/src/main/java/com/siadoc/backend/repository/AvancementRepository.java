package com.siadoc.backend.repository;

import com.siadoc.backend.model.Avancement;
import com.siadoc.backend.model.TypeAvancement;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheAvancementDTO;

import java.util.List;
import java.util.UUID;

public interface AvancementRepository extends JpaRepository<Avancement, UUID> {

    List<Avancement> findByModuleIdOrderByDateEffetDesc(UUID moduleId);

    // NOUVELLE METHODE : Somme de toutes les prolongations d'un module
    @Query(value = "SELECT COALESCE(SUM(a.duree_annees), 0) FROM avancement a " +
            "JOIN avancement_module am ON a.module_id = am.id " +
            "JOIN dossier_administratif da ON am.dossier_id = da.id " +
            "WHERE da.militaire_id = :militaireId " +
            "AND a.type_avancement = 'PROLONGATION_SERVICE'",
            nativeQuery = true)
    Integer sumProlongationsByMilitaireId(@Param("militaireId") UUID militaireId);

    // Alternative si tu veux les objets complets pour affichage
    List<Avancement> findByModuleIdAndTypeAvancement(UUID moduleId, TypeAvancement type);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheAvancementDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    a.avancement,
    a.dateEffet,
    CAST(a.typeAvancement AS string)
)
FROM Avancement a
JOIN a.module am
JOIN am.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:nouveauGrade IS NULL OR a.avancement ILIKE :nouveauGrade)
AND (:annee IS NULL OR EXTRACT(YEAR FROM a.dateEffet) = :annee)
""")
    List<ResultRechercheAvancementDTO> rechercherAvancements(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("nouveauGrade") String nouveauGrade,
            @Param("annee") Integer annee
    );
}
