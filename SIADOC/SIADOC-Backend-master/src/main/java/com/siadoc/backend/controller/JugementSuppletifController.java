package com.siadoc.backend.controller;

import com.siadoc.backend.model.JugementSuppletif;
import com.siadoc.backend.service.JugementSuppletifService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/etat-civil/jugement-suppletif")
@RequiredArgsConstructor
public class JugementSuppletifController {

    private final JugementSuppletifService service;

    // ========= AJOUT =========
    @PostMapping
    public ResponseEntity<?> add(
            @RequestParam UUID etatCivilId,
            @RequestParam(required = false) String numeroJugement,
            @RequestParam(required = false) String objet,
            @RequestParam(required = false) String dateJugement,
            @RequestParam(required = false) String tribunal,
            @RequestParam(required = false) MultipartFile fichier
    ) throws Exception {

        LocalDate date = null;
        if (dateJugement != null && !dateJugement.isBlank()) {
            date = LocalDate.parse(dateJugement);
        }

        return ResponseEntity.ok(
                service.add(
                        etatCivilId,
                        numeroJugement,
                        objet,
                        date,
                        tribunal,
                        fichier
                )
        );
    }

    // ========= LISTE =========
    @GetMapping("/module/{moduleId}")
    public ResponseEntity<List<JugementSuppletif>> getAll(@PathVariable UUID moduleId) {
        return ResponseEntity.ok(service.getByModule(moduleId));
    }

    // ========= FICHIER =========
    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFile(@PathVariable UUID id) {

        JugementSuppletif j = service.get(id);

        return ResponseEntity.ok()
                .header("Content-Type", j.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + j.getDocumentNom() + "\"")
                .body(j.getDocumentData());
    }

    // ========= DELETE =========
    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }
}