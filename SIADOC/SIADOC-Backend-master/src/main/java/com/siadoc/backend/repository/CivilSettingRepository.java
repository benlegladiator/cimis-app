package com.siadoc.backend.repository;

import com.siadoc.backend.model.CivilSetting;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;
import java.util.UUID;

public interface CivilSettingRepository extends JpaRepository<CivilSetting, UUID> {
    List<CivilSetting> findByType(String type);
}
