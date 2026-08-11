package com.siadoc.backend.repository;

import com.siadoc.backend.model.SystemSetting;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;
import java.util.UUID;
import java.util.Optional;

@Repository
public interface SystemSettingRepository extends JpaRepository<SystemSetting, UUID> {
    // There is usually only one row in this table
    Optional<SystemSetting> findFirstBy();
}
