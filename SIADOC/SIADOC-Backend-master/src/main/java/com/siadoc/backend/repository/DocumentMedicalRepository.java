package com.siadoc.backend.repository;

import com.siadoc.backend.model.DocumentMedical;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.UUID;

@Repository
public interface DocumentMedicalRepository extends JpaRepository<DocumentMedical, UUID> {
}
