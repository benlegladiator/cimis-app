package com.siadoc.backend.repository;

import com.siadoc.backend.model.DocumentCivil;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.UUID;
import java.util.List;

public interface DocumentCivilRepository extends JpaRepository<DocumentCivil, UUID> {
    List<DocumentCivil> findByPersonnelCivilId(UUID personnelId);
}
