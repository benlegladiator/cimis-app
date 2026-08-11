package com.siadoc.backend.repository;

import com.siadoc.backend.model.AvancementModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.UUID;

public interface AvancementModuleRepository extends JpaRepository<AvancementModule, UUID> {
}
