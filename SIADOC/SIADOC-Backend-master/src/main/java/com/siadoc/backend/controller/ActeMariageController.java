package com.siadoc.backend.controller;

import com.siadoc.backend.model.ActeMariage;
import com.siadoc.backend.service.ActeMariageService;

import org.springframework.http.ResponseEntity;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/etat-civil/acte-mariage")
public class ActeMariageController {

    private final ActeMariageService service;

    public ActeMariageController(ActeMariageService service) {
        this.service = service;
    }

    @PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<ActeMariage> ajouter(
            @RequestParam UUID etatCivilId,
            @RequestParam(required = false) String numeroActe,
            @RequestParam(required = false) String nomConjoint,
            @RequestParam(required = false) LocalDate dateMariage,
            @RequestParam(required = false) String lieuMariage,
            @RequestPart(value = "fichier", required = false) MultipartFile fichier
    ) throws Exception {

        return ResponseEntity.ok(
                service.ajouter(
                        etatCivilId,
                        numeroActe,
                        nomConjoint,
                        dateMariage,
                        lieuMariage,
                        fichier
                )
        );
    }

    @GetMapping("/module/{etatCivilId}")
    public ResponseEntity<List<ActeMariage>> getByEtatCivil(@PathVariable UUID etatCivilId) {
        return ResponseEntity.ok(service.getByEtatCivil(etatCivilId));
    }

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFichier(@PathVariable UUID id) {

        ActeMariage acte = service.getById(id);

        return ResponseEntity.ok()
                .contentType(MediaType.parseMediaType(acte.getFichierType()))
                .body(acte.getFichier());
    }
}
