package com.siadoc.backend.repository;

import com.siadoc.backend.model.Reengagement;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.UUID;

public interface ReengagementRepository extends JpaRepository<Reengagement, UUID> {
}