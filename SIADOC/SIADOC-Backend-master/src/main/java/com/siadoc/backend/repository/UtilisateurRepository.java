package com.siadoc.backend.repository;

import com.siadoc.backend.model.Utilisateur;
import com.siadoc.backend.model.Role;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.Optional;
import java.util.UUID;

public interface UtilisateurRepository extends JpaRepository<Utilisateur, UUID> {

    @Query("SELECT u FROM Utilisateur u " +
           "LEFT JOIN FETCH u.region " +
           "LEFT JOIN FETCH u.brigade " +
           "LEFT JOIN FETCH u.bataillon " +
           "LEFT JOIN FETCH u.compagnie " +
           "WHERE u.username = :username")
    Optional<Utilisateur> findByUsername(@org.springframework.data.repository.query.Param("username") String username);

    Optional<Utilisateur> findByUsernameAndPassword(String username, String password);
    boolean existsByRole(Role role);
    boolean existsByUsername(String username);
}