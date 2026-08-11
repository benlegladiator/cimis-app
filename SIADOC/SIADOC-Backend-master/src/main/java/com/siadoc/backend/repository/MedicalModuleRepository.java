package com.siadoc.backend.repository;

import com.siadoc.backend.model.MedicalModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface MedicalModuleRepository extends JpaRepository<MedicalModule, UUID> {
    Optional<MedicalModule> findByDossierId(UUID dossierId);
}