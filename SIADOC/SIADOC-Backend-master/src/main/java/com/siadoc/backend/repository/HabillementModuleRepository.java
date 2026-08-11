package com.siadoc.backend.repository;

import com.siadoc.backend.model.HabillementModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;
import java.util.UUID;

public interface HabillementModuleRepository extends JpaRepository<HabillementModule, UUID> {
    Optional<HabillementModule> findByDossierId(UUID dossierId);
}
