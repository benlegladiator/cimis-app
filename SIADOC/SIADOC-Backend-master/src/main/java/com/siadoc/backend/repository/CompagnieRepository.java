package com.siadoc.backend.repository;

import com.siadoc.backend.model.Compagnie;
import com.siadoc.backend.model.Bataillon;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.Optional;
import java.util.UUID;
import java.util.List;

public interface CompagnieRepository extends JpaRepository<Compagnie, UUID> {

    Optional<Compagnie> findByNomAndBataillon(String nom, Bataillon bataillon);
    Optional<Compagnie> findByNomIgnoreCaseAndBataillon(String nom, Bataillon bataillon);
    Optional<Compagnie> findByNomIgnoreCase(String nom);
    List<Compagnie> findByNom(String nom);

    @Query("SELECT c FROM Compagnie c LEFT JOIN FETCH c.bataillon b LEFT JOIN FETCH b.brigade br LEFT JOIN FETCH br.region r")
    List<Compagnie> findAllHierarchical();

    @Query("SELECT c FROM Compagnie c LEFT JOIN FETCH c.bataillon b WHERE b.id = :bataillonId")
    List<Compagnie> findByBataillonId(@org.springframework.data.repository.query.Param("bataillonId") UUID bataillonId);

    @Query("SELECT c FROM Compagnie c LEFT JOIN FETCH c.bataillon b LEFT JOIN FETCH b.brigade br WHERE br.id = :brigadeId")
    List<Compagnie> findByBrigadeId(@org.springframework.data.repository.query.Param("brigadeId") UUID brigadeId);

    @Query("SELECT c FROM Compagnie c LEFT JOIN FETCH c.bataillon b LEFT JOIN FETCH b.brigade br LEFT JOIN FETCH br.region r WHERE r.id = :regionId")
    List<Compagnie> findByRegionId(@org.springframework.data.repository.query.Param("regionId") UUID regionId);

}