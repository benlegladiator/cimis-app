package com.siadoc.backend.repository;

import com.siadoc.backend.model.JugementSuppletif;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.UUID;


public interface JugementSuppletifRepository
        extends JpaRepository<JugementSuppletif, UUID> {

    List<JugementSuppletif> findByEtatCivilId(UUID moduleId);
}
