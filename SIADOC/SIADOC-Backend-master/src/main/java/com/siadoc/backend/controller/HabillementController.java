package com.siadoc.backend.controller;

import com.siadoc.backend.dto.HabillementDTO;
import com.siadoc.backend.service.HabillementService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.UUID;

@RestController
@RequestMapping("/api/habillement")
@CrossOrigin(origins = "http://localhost:4200", allowCredentials = "true")
@RequiredArgsConstructor
public class HabillementController {

    private final HabillementService service;

    @GetMapping("/{militaireId}")
    public ResponseEntity<HabillementDTO> getHabillement(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(service.getDossierHabillement(militaireId));
    }

    // Un seul point d'entrée POST pour tout le formulaire (Mensurations + Tous les articles)
    @PostMapping("/{militaireId}")
    public ResponseEntity<HabillementDTO> saveHabillement(
            @PathVariable UUID militaireId,
            @RequestBody HabillementDTO dto) {

        HabillementDTO savedDto = service.saveGlobal(militaireId, dto);
        return ResponseEntity.ok(savedDto);
    }
}
