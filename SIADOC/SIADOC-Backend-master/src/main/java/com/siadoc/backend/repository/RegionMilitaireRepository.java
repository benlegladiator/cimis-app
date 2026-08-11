package com.siadoc.backend.repository;

import com.siadoc.backend.model.RegionMilitaire;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface RegionMilitaireRepository extends JpaRepository<RegionMilitaire, UUID> {

    Optional<RegionMilitaire> findByNom(String nom);
    Optional<RegionMilitaire> findByNomIgnoreCase(String nom);

}