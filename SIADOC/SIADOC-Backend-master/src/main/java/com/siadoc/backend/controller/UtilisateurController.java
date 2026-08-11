package com.siadoc.backend.controller;

import com.siadoc.backend.model.*;
import com.siadoc.backend.dto.*;
import com.siadoc.backend.service.UtilisateurService;
import com.siadoc.backend.repository.UtilisateurRepository;

import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/utilisateurs")
public class UtilisateurController {

    private final UtilisateurService service;
    private final UtilisateurRepository utilisateurRepository;

    // ✅ CONSTRUCTEUR UNIQUE
    public UtilisateurController(
            UtilisateurService service,
            UtilisateurRepository utilisateurRepository
    ) {
        this.service = service;
        this.utilisateurRepository = utilisateurRepository;
    }

    // =========================
    // CREER UTILISATEUR
    // =========================
    @PostMapping
    public Utilisateur creer(@RequestBody CreateUtilisateurRequest request) {
        return service.creerUtilisateur(
                request.getUsername(),
                request.getPassword(),
                request.getRole(),
                request.getSecteurId(),
                request.getRegionId(),
                request.getBrigadeId(),
                request.getBataillonId(),
                request.getCompagnieId()
        );
    }

    @DeleteMapping("/{id}")
    public void delete(@PathVariable UUID id) {
        service.deleteUtilisateur(id);
    }

    // =========================
    // LISTE UTILISATEURS
    // =========================
    @GetMapping
    public List<Utilisateur> getAll() {
        return utilisateurRepository.findAll();
    }
}
