package com.siadoc.backend.repository;

import com.siadoc.backend.model.StageModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface StageModuleRepository extends JpaRepository<StageModule, UUID> {
    Optional<StageModule> findByDossierId(UUID dossierId);
}
