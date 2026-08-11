package com.siadoc.backend.controller;

import com.siadoc.backend.model.ActeNaissance;
import com.siadoc.backend.service.ActeNaissanceService;

import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/etat-civil/acte-naissance")
public class ActeNaissanceController {

    private final ActeNaissanceService service;

    public ActeNaissanceController(ActeNaissanceService service) {
        this.service = service;
    }

    @PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<ActeNaissance> ajouter(
            @RequestParam UUID etatCivilId,
            @RequestParam(required = false) String numeroActe,
            @RequestParam(required = false) LocalDate dateEtablissement,
            @RequestParam(required = false) String lieu,
            @RequestPart(value = "fichier", required = false) MultipartFile fichier
    ) throws Exception {

        return ResponseEntity.ok(
                service.ajouter(etatCivilId, numeroActe, dateEtablissement, lieu, fichier)
        );
    }

    @GetMapping("/module/{etatCivilId}")
    public ResponseEntity<List<ActeNaissance>> getByEtatCivil(@PathVariable UUID etatCivilId) {
        return ResponseEntity.ok(service.getByEtatCivil(etatCivilId));
    }

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFichier(@PathVariable UUID id) {

        ActeNaissance acte = service.getById(id);

        return ResponseEntity.ok()
                .contentType(MediaType.parseMediaType(acte.getFichierType()))
                .body(acte.getFichier());
    }
}
