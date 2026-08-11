package com.siadoc.backend.repository;

import com.siadoc.backend.model.NotationModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface NotationModuleRepository extends JpaRepository<NotationModule, UUID> {
    Optional<NotationModule> findByDossierId(UUID dossierId);
}
