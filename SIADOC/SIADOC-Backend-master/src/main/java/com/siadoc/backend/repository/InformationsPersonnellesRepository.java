package com.siadoc.backend.repository;

import com.siadoc.backend.model.InformationsPersonnelles;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.UUID;
import java.util.Optional;

public interface InformationsPersonnellesRepository
        extends JpaRepository<InformationsPersonnelles, UUID> {

    Optional<InformationsPersonnelles> findByEtatCivilId(UUID etatCivilId);
}