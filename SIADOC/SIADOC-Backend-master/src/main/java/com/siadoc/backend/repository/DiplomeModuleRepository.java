package com.siadoc.backend.repository;

import com.siadoc.backend.model.DiplomeModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface DiplomeModuleRepository extends JpaRepository<DiplomeModule, UUID> {
    Optional<DiplomeModule> findByDossierId(UUID dossierId);
}
