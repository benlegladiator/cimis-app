package com.siadoc.backend.controller;

import com.siadoc.backend.model.ActeDeces;
import com.siadoc.backend.service.ActeDecesService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/etat-civil/acte-deces")
@RequiredArgsConstructor
public class ActeDecesController {

    private final ActeDecesService service;

    // ================= AJOUT =================

    @PostMapping
    public ResponseEntity<?> add(
            @RequestParam UUID etatCivilId,
            @RequestParam(required = false) String numeroActe,
            @RequestParam(required = false) String dateDeces,
            @RequestParam(required = false) String lieu,
            @RequestParam(required = false) MultipartFile fichier
    ) throws Exception {

        LocalDate date = null;
        if (dateDeces != null && !dateDeces.isBlank()) {
            date = LocalDate.parse(dateDeces);
        }

        return ResponseEntity.ok(
                service.add(
                        etatCivilId,
                        numeroActe,
                        date,
                        lieu,
                        fichier
                )
        );
    }

    // ================= LISTE =================

    @GetMapping("/module/{moduleId}")
    public ResponseEntity<List<ActeDeces>> getAll(@PathVariable UUID moduleId) {
        return ResponseEntity.ok(service.getByModule(moduleId));
    }

    // ================= FICHIER =================

    @GetMapping(value = "/{id}/fichier",
            produces = org.springframework.http.MediaType.APPLICATION_OCTET_STREAM_VALUE)
    public ResponseEntity<byte[]> getFile(@PathVariable UUID id) {

        ActeDeces a = service.get(id);

        return ResponseEntity.ok()
                .header(
                        "Content-Type",
                        a.getDocumentType() != null
                                ? a.getDocumentType()
                                : "application/octet-stream"
                )
                .header("Content-Disposition",
                        "inline; filename=\"" + a.getDocumentNom() + "\"")
                .body(a.getDocumentData());
    }

    // ================= GET ONE =================

    @GetMapping("/{id}")
    public ResponseEntity<ActeDeces> getOne(@PathVariable UUID id) {
        return ResponseEntity.ok(service.get(id));
    }

    // ================= DELETE =================

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }


}
