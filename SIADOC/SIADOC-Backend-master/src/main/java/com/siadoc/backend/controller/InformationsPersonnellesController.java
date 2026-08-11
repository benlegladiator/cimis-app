package com.siadoc.backend.controller;

import com.siadoc.backend.model.InformationsPersonnelles ;
import com.siadoc.backend.service.InformationsPersonnellesService;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import java.util.UUID;

@RestController
@RequestMapping("/api/etat-civil/informations")
public class InformationsPersonnellesController {

    private final InformationsPersonnellesService service;

    public InformationsPersonnellesController(InformationsPersonnellesService service) {
        this.service = service;
    }

    // ==========================
    // AJOUTER / MODIFIER
    // ==========================
    @PostMapping("/{etatCivilId}")
    public ResponseEntity<InformationsPersonnelles> enregistrer(
            @PathVariable UUID etatCivilId,
            @RequestBody InformationsPersonnelles dto
    ) {

        InformationsPersonnelles saved =
                service.enregistrer(etatCivilId, dto);

        return ResponseEntity.ok(saved);
    }

    // ==========================
    // RECUPERER
    // ==========================
    @GetMapping("/{etatCivilId}")
    public ResponseEntity<InformationsPersonnelles> getByEtatCivil(
            @PathVariable UUID etatCivilId
    ) {
        return ResponseEntity.ok(
                service.getByEtatCivil(etatCivilId)
        );
    }
}