package com.siadoc.backend.repository;

import com.siadoc.backend.model.HistoriqueMilitaire;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.UUID;

@Repository
public interface HistoriqueMilitaireRepository extends JpaRepository<HistoriqueMilitaire, UUID> {
    List<HistoriqueMilitaire> findByMilitaireIdOrderByDateActionDesc(UUID militaireId);
}
