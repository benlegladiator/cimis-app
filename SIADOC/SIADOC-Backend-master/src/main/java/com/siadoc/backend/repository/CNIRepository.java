package com.siadoc.backend.repository;

import com.siadoc.backend.model.CNI;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import com.siadoc.backend.dto.search.ResultRechercheCniDTO;
import java.util.UUID;
import java.util.List;

public interface CNIRepository extends JpaRepository<CNI, UUID> {

    List<CNI> findByEtatCivilId(UUID etatCivilId);

    @Query("""
SELECT new com.siadoc.backend.dto.search.ResultRechercheCniDTO(
    m.nom,
    m.prenom,
    m.grade,
    m.armeService,
    c.numero,
    c.dateExpiration
)
FROM CNI c
JOIN c.etatCivil ec
JOIN ec.dossier d
JOIN d.militaire m
WHERE
(:nom IS NULL OR LOWER(m.nom) LIKE LOWER(:nom))
AND (:prenom IS NULL OR LOWER(m.prenom) LIKE LOWER(:prenom))
AND (:grade IS NULL OR m.grade = :grade)
AND (:arme IS NULL OR m.armeService = :arme)
AND (:numero IS NULL OR LOWER(c.numero) LIKE LOWER(:numero))
""")
    List<ResultRechercheCniDTO> rechercherCni(
            @Param("nom") String nom,
            @Param("prenom") String prenom,
            @Param("grade") String grade,
            @Param("arme") String arme,
            @Param("numero") String numero
    );
}
