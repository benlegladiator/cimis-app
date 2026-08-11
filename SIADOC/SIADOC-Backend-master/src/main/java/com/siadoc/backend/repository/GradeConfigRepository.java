package com.siadoc.backend.repository;

import com.siadoc.backend.model.GradeConfig;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;
import java.util.UUID;

public interface GradeConfigRepository extends JpaRepository<GradeConfig, UUID> {
    List<GradeConfig> findByArmeeOrderByOrdreAsc(String armee);
    List<GradeConfig> findAllByOrderByOrdreAsc();
}
