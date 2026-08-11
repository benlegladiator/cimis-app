package com.siadoc.backend.repository;

import com.siadoc.backend.model.ArchiveDecede;
import com.siadoc.backend.dto.ArchiveResultDTO;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.UUID;
import java.util.List;

public interface ArchiveDecedeRepository extends JpaRepository<ArchiveDecede, UUID> {

    boolean existsByMilitaireId(UUID militaireId);

    @Query("""
        SELECT new com.siadoc.backend.dto.ArchiveResultDTO(
            a.militaireId, a.nom, a.prenom, a.matricule, a.numeroCase, a.armee, a.anneeContingent, a.grade, CAST(a.categorie AS string)
        )
        FROM ArchiveDecede a
        WHERE (:search IS NULL 
           OR UPPER(a.nom) LIKE UPPER(CONCAT('%', :search, '%'))
           OR UPPER(a.prenom) LIKE UPPER(CONCAT('%', :search, '%'))
           OR UPPER(a.matricule) LIKE UPPER(CONCAT('%', :search, '%')))
    """)
    List<ArchiveResultDTO> search(@Param("search") String search);

    @Query("""
        SELECT new com.siadoc.backend.dto.ArchiveResultDTO(
            a.militaireId, a.nom, a.prenom, a.matricule, a.numeroCase, a.armee, a.anneeContingent, a.grade, CAST(a.categorie AS string)
        )
        FROM ArchiveDecede a ORDER BY a.nom
    """)
    List<ArchiveResultDTO> findAllDTO();
}
