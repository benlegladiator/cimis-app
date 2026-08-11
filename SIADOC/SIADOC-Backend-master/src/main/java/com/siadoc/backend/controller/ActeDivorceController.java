package com.siadoc.backend.controller;

import com.siadoc.backend.model.ActeDivorce;
import com.siadoc.backend.service.ActeDivorceService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/etat-civil/acte-divorce")
@RequiredArgsConstructor
public class ActeDivorceController {

    private final ActeDivorceService service;

    // ================= AJOUT =================

    @PostMapping
    public ResponseEntity<?> add(
            @RequestParam UUID etatCivilId,
            @RequestParam(required = false) String numeroJugement,
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
                        date,
                        tribunal,
                        fichier
                )
        );
    }

    // ================= LISTE =================

    @GetMapping("/module/{moduleId}")
    public ResponseEntity<List<ActeDivorce>> getAll(@PathVariable UUID moduleId) {
        return ResponseEntity.ok(service.getByModule(moduleId));
    }

    // ================= GET ONE =================

    @GetMapping("/{id}")
    public ResponseEntity<ActeDivorce> getOne(@PathVariable UUID id) {
        return ResponseEntity.ok(service.get(id));
    }

    // ================= FICHIER =================

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFile(@PathVariable UUID id) {

        ActeDivorce a = service.get(id);

        return ResponseEntity.ok()
                .header("Content-Type", a.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + a.getDocumentNom() + "\"")
                .body(a.getDocumentData());
    }

    // ================= DELETE =================

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }
}