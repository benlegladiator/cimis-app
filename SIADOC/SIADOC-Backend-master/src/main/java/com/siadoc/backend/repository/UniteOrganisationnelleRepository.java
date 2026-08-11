package com.siadoc.backend.repository;

import com.siadoc.backend.model.UniteOrganisationnelle;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.UUID;

@Repository
public interface UniteOrganisationnelleRepository extends JpaRepository<UniteOrganisationnelle, UUID> {
    List<UniteOrganisationnelle> findByType(String type);

    List<UniteOrganisationnelle> findByParentId(UUID parentId);
}
