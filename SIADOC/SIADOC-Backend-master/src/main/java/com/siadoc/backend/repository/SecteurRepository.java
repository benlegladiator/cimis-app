package com.siadoc.backend.repository;

import com.siadoc.backend.model.SecteurMilitaire;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.UUID;

public interface SecteurRepository extends JpaRepository<SecteurMilitaire, UUID> {
}
