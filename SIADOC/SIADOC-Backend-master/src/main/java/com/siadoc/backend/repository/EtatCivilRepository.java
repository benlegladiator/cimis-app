package com.siadoc.backend.repository;

import com.siadoc.backend.model.EtatCivil;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.UUID;

public interface EtatCivilRepository extends JpaRepository<EtatCivil, UUID> {
}

