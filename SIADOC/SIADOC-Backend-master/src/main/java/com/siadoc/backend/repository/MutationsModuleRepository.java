package com.siadoc.backend.repository;

import com.siadoc.backend.model.MutationsModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface MutationsModuleRepository extends JpaRepository<MutationsModule, UUID> {
    Optional<MutationsModule> findByDossierId(UUID dossierId);
}
