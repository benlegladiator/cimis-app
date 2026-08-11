package com.siadoc.backend.repository;

import com.siadoc.backend.model.AdmissionSoc;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.UUID;

public interface AdmissionSocRepository extends JpaRepository<AdmissionSoc, UUID> {

}
