package com.siadoc.backend.repository;

import com.siadoc.backend.model.PersonnelCivil;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.UUID;

public interface PersonnelCivilRepository extends JpaRepository<PersonnelCivil, UUID> {
    boolean existsByMatricule(String matricule);
}
