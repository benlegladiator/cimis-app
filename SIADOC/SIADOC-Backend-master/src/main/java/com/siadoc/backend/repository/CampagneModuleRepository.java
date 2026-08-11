package com.siadoc.backend.repository;

import com.siadoc.backend.model.CampagneMilitaireModule;
import com.siadoc.backend.model.StageModule;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

public interface CampagneModuleRepository  extends JpaRepository<CampagneMilitaireModule, UUID> {
    Optional<StageModule> findByDossierId(UUID dossierId);
}
