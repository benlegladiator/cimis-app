package com.siadoc.backend.repository;

import org.springframework.data.jpa.repository.JpaRepository;
import com.siadoc.backend.model.SecteurMilitaire;
import java.util.Optional;
import java.util.UUID;

public interface SecteurMilitaireRepository
        extends JpaRepository<SecteurMilitaire, UUID> {

    Optional<SecteurMilitaire> findByNom(String nom);
}