package com.siadoc.backend.repository;

import com.siadoc.backend.model.Brigade;
import com.siadoc.backend.model.RegionMilitaire;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.stereotype.Repository;

import java.util.Optional;
import java.util.UUID;

@Repository
public interface BrigadeRepository extends JpaRepository<Brigade, UUID> {
    Optional<Brigade> findByNom(String nom);
    Optional<Brigade> findByNomIgnoreCase(String nom);
    Optional<Brigade> findByNomAndRegion(String nom, RegionMilitaire region);
    Optional<Brigade> findByNomIgnoreCaseAndRegion(String nom, RegionMilitaire region);
    @Query("SELECT b FROM Brigade b LEFT JOIN FETCH b.region r WHERE r.id = :regionId")
    java.util.List<Brigade> findByRegionId(@org.springframework.data.repository.query.Param("regionId") UUID regionId);
}
