package com.siadoc.backend.repository;

import com.siadoc.backend.model.ActeDeces;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.UUID;

public interface ActeDecesRepository
        extends JpaRepository<ActeDeces, UUID> {

    List<ActeDeces> findByEtatCivilId(UUID etatCivilId);
}
