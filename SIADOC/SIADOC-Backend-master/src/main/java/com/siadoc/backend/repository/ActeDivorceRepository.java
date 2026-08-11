package com.siadoc.backend.repository;

import com.siadoc.backend.model.ActeDivorce;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.UUID;


public interface ActeDivorceRepository
        extends JpaRepository<ActeDivorce, UUID> {

    List<ActeDivorce> findByEtatCivilId(UUID moduleId);


}
