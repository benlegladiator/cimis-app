package com.siadoc.backend.repository;

import com.siadoc.backend.model.RecompenseModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface RecompenseModuleRepository extends JpaRepository<RecompenseModule, UUID> {
    Optional<RecompenseModule> findByDossierId(UUID dossierId);
}
