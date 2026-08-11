package com.siadoc.backend.controller;

import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.service.DossierAdministratifService;
import com.siadoc.backend.dto.DossierDTO;
import com.siadoc.backend.model.Militaire;

import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/dossiers")
public class DossierAdministratifController {

    private final DossierAdministratifService service;

    public DossierAdministratifController(DossierAdministratifService service) {
        this.service = service;
    }

    @GetMapping
    public List<Militaire> getAll() {
        return service.getMilitairesActifs();
    }

    @GetMapping("/actifs")
    public List<Militaire> getMilitairesActifs() {
        return service.getMilitairesActifs();
    }

    @GetMapping("/militaire/{militaireId}")
    @Transactional
    public ResponseEntity<DossierAdministratif> getByMilitaire(@PathVariable UUID militaireId) {

        DossierAdministratif dossier = service.getByMilitaireId(militaireId);

        return ResponseEntity.ok(dossier);
    }

    @PutMapping("/archiver/militaire/{militaireId}")
    public ResponseEntity<Void> archiverParMilitaire(@PathVariable UUID militaireId) {

        service.archiverParMilitaire(militaireId);

        return ResponseEntity.ok().build();
    }

    @PostMapping("/{id}/soumettre")
    public ResponseEntity<Void> soumettre(@PathVariable UUID id, @RequestBody String modulesList) {
        service.soumettreValidation(id, modulesList);
        return ResponseEntity.ok().build();
    }

    @PostMapping("/{id}/approuver")
    public ResponseEntity<Void> approuver(@PathVariable UUID id) {
        service.approuverModifications(id);
        return ResponseEntity.ok().build();
    }

    @PostMapping("/{id}/rejeter")
    public ResponseEntity<Void> rejeter(@PathVariable UUID id, @RequestBody String motif) {
        service.rejeterModifications(id, motif);
        return ResponseEntity.ok().build();
    }
}