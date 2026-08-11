package com.siadoc.backend.repository;

import com.siadoc.backend.model.Bataillon;
import com.siadoc.backend.model.Brigade;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.stereotype.Repository;

import java.util.Optional;
import java.util.UUID;

@Repository
public interface BataillonRepository extends JpaRepository<Bataillon, UUID> {
    Optional<Bataillon> findByNom(String nom);
    Optional<Bataillon> findByNomIgnoreCase(String nom);
    Optional<Bataillon> findByNomAndBrigade(String nom, Brigade brigade);
    Optional<Bataillon> findByNomIgnoreCaseAndBrigade(String nom, Brigade brigade);
    @Query("SELECT b FROM Bataillon b LEFT JOIN FETCH b.brigade br WHERE br.id = :brigadeId")
    java.util.List<Bataillon> findByBrigadeId(@org.springframework.data.repository.query.Param("brigadeId") UUID brigadeId);
}
