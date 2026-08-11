package com.siadoc.backend.controller;

import com.siadoc.backend.model.Avancement;
import com.siadoc.backend.model.TypeAvancement;
import com.siadoc.backend.service.AvancementService;
import org.springframework.http.ResponseEntity;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.UUID;
import java.util.List;

@RestController
@RequestMapping("/api/avancement")
public class AvancementController {

    private final AvancementService service;

    public AvancementController(AvancementService service) {
        this.service = service;
    }

    @PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<Avancement> ajouter(
            @RequestParam UUID moduleId,
            @RequestParam TypeAvancement typeAvancement,  // NOUVEAU
            @RequestParam String avancement,
            @RequestParam String numeroTexte,
            @RequestParam String signataire,
            @RequestParam LocalDate dateEffet,
            @RequestParam(required = false) Integer dureeAnnees,  // NOUVEAU (pour prolongations)
            @RequestPart(value = "fichier", required = false) MultipartFile fichier
    ) throws Exception {

        return ResponseEntity.ok(
                service.ajouter(
                        moduleId,
                        typeAvancement,
                        avancement,
                        numeroTexte,
                        signataire,
                        dateEffet,
                        dureeAnnees,
                        fichier
                )
        );
    }


    @GetMapping("/module/{moduleId}")
    public ResponseEntity<List<Avancement>> getByModule(
            @PathVariable UUID moduleId) {
        return ResponseEntity.ok(service.getByModule(moduleId));
    }

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFichier(@PathVariable UUID id) {

        Avancement av = service.getById(id);

        if (av.getFichier() == null) {
            return ResponseEntity.notFound().build();
        }

        return ResponseEntity.ok()
                .contentType(MediaType.parseMediaType(av.getFichierType()))
                .header("Content-Disposition",
                        "inline; filename=\"" + av.getFichierNom() + "\"")
                .body(av.getFichier());
    }
}

