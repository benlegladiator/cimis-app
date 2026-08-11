package com.siadoc.backend.repository;

import com.siadoc.backend.model.DiplomeItem;
import com.siadoc.backend.dto.search.ResultRechercheDiplomeDTO;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import java.util.List;
import java.util.UUID;

public interface DiplomeItemRepository extends JpaRepository<DiplomeItem, UUID> {
    // Tri Ascendant : Du plus vieux au plus récent
    List<DiplomeItem> findByModuleIdOrderByDateObtentionAsc(UUID moduleId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheDiplomeDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    di.designation,
    di.ecole,
    di.dateObtention
)
FROM DiplomeItem di
JOIN di.module dm
JOIN dm.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR m.nom ILIKE :nom)
AND (:prenom IS NULL OR m.prenom ILIKE :prenom)
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:designation IS NULL OR di.designation ILIKE :designation)
AND (:ecole IS NULL OR di.ecole ILIKE :ecole)
AND (:annee IS NULL OR EXTRACT(YEAR FROM di.dateObtention) = :annee)
""")
    List<ResultRechercheDiplomeDTO> rechercherDiplome(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("designation") String designation,
            @Param("ecole") String ecole,
            @Param("annee") Integer annee
    );
}
