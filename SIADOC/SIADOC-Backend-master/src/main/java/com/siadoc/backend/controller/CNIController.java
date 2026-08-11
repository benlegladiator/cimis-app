package com.siadoc.backend.controller;

import com.siadoc.backend.model.CNI;
import com.siadoc.backend.service.CNIService;

import org.springframework.http.ResponseEntity;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.UUID;
import java.util.List;

@RestController
@RequestMapping("/api/etat-civil/cni")
public class CNIController {

    private final CNIService cniService;

    public CNIController(CNIService cniService) {
        this.cniService = cniService;
    }

    // ===============================
    // AJOUTER UNE CNI
    // ===============================
    @PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<CNI> ajouter(
            @RequestParam UUID etatCivilId,
            @RequestParam(required = false) String numero,
            @RequestParam(required = false) LocalDate dateDelivrance,
            @RequestParam(required = false) LocalDate dateExpiration,
            @RequestParam(required = false) String lieu,
            @RequestPart(value = "fichier", required = false) MultipartFile fichier
    ) throws Exception {

        CNI saved = cniService.ajouterCNI(
                etatCivilId,
                numero,
                dateDelivrance,
                dateExpiration,
                lieu,
                fichier
        );

        return ResponseEntity.ok(saved);
    }

    // ===============================
    // LISTER LES CNI D'UN MODULE ETAT CIVIL
    // ===============================
    @GetMapping("/module/{etatCivilId}")
    public ResponseEntity<List<CNI>> getByEtatCivil(@PathVariable UUID etatCivilId) {
        return ResponseEntity.ok(cniService.getByEtatCivil(etatCivilId));
    }

    // ===============================
    // TELECHARGER LE FICHIER PDF
    // ===============================
    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFichier(@PathVariable UUID id) {

        CNI cni = cniService.getById(id);

        return ResponseEntity.ok()
                .contentType(MediaType.parseMediaType(cni.getFichierType()))
                .body(cni.getFichier());
    }
}
