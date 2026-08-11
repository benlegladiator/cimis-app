package com.siadoc.backend.repository;

import com.siadoc.backend.model.ApiKey;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.Optional;
import java.util.UUID;

public interface ApiKeyRepository extends JpaRepository<ApiKey, UUID> {
    Optional<ApiKey> findByKeyValueAndActiveTrue(String keyValue);
    boolean existsByAppName(String appName);
}
