package com.siadoc.backend.repository;

import com.siadoc.backend.model.CompagnieMappingGesmil;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.Optional;
import java.util.UUID;

public interface CompagnieMappingGesmilRepository extends JpaRepository<CompagnieMappingGesmil, UUID> {
    Optional<CompagnieMappingGesmil> findByCodeGesmilIgnoreCase(String codeGesmil);
    boolean existsByCodeGesmil(String codeGesmil);
}
