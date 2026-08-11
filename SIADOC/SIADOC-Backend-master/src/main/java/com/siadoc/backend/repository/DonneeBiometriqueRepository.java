package com.siadoc.backend.repository;

import com.siadoc.backend.model.DonneeBiometrique;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;
import java.util.UUID;

/**
 * Repository JPA pour l'entité DonneeBiometrique.
 * Permet de récupérer ou de vérifier l'existence d'une entrée
 * biométrique liée à un militaire.
 */
public interface DonneeBiometriqueRepository extends JpaRepository<DonneeBiometrique, UUID> {

    /**
     * Retourne le dernier enregistrement biométrique pour un militaire donné.
     * Utile pour l'upsert : si une entrée existe déjà, on la met à jour.
     */
    Optional<DonneeBiometrique> findByMilitaireId(UUID militaireId);

    /**
     * Vérifie si des données biométriques existent déjà pour un militaire.
     */
    boolean existsByMilitaireId(UUID militaireId);
}
