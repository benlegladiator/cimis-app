package com.siadoc.backend.controller;

import com.siadoc.backend.dto.AdmissionSocDTO;
import com.siadoc.backend.dto.CarriereDTO;
import com.siadoc.backend.dto.ReengagementDTO;
import com.siadoc.backend.model.Carriere;
import com.siadoc.backend.model.DiplomeItem;
import com.siadoc.backend.service.CarriereService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.UUID;


@RestController
@RequestMapping("/api/carriere")
@RequiredArgsConstructor
public class CarriereController {

    private final CarriereService carriereService;

    // Récupérer tout le dossier carrière
    @GetMapping("/{militaireId}")
    public ResponseEntity<CarriereDTO> getCarriere(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(carriereService.getCarriereByMilitaire(militaireId));
    }

    // Mettre à jour les infos principales (Section 1 & 2)
    @PutMapping("/{militaireId}")
    public ResponseEntity<CarriereDTO> updateCarriere(
            @PathVariable UUID militaireId,
            @RequestBody CarriereDTO dto) {
        return ResponseEntity.ok(carriereService.updateCarriere(militaireId, dto));
    }

    // Ajouter un réengagement (Section 3)
    @PostMapping("/{militaireId}/reengagement")
    public ResponseEntity<ReengagementDTO> addReengagement(
            @PathVariable UUID militaireId,
            @RequestBody ReengagementDTO dto) {
        return ResponseEntity.ok(carriereService.addReengagement(militaireId, dto));
    }

    // Supprimer un réengagement
    @DeleteMapping("/reengagement/{id}")
    public ResponseEntity<Void> deleteReengagement(@PathVariable UUID id) {
        carriereService.deleteReengagement(id);
        return ResponseEntity.noContent().build();
    }

    @GetMapping("/{militaireId}/anciennetes")
    public ResponseEntity<CarriereService.AncienneteCalculee> getAnciennetes(
            @PathVariable UUID militaireId) {
        return ResponseEntity.ok(carriereService.calculerAnciennetes(militaireId));
    }

    // Ajouter une AdmissionSOc (Section 4)
    @PostMapping("/{militaireId}/admission")
    public ResponseEntity<AdmissionSocDTO> addAdmission(
            @PathVariable UUID militaireId,
            @RequestBody AdmissionSocDTO dto) {
        return ResponseEntity.ok(carriereService.addAdmission(militaireId, dto));
    }

    // Supprimer une Admission
    @DeleteMapping("/admission/{id}")
    public ResponseEntity<Void> deleteAdmission(@PathVariable UUID id) {
        carriereService.deleteAdmission(id);
        return ResponseEntity.noContent().build();
    }

    // Upload de document global
    @PostMapping(value = "/{militaireId}/document", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<Void> uploadDocument(
            @PathVariable UUID militaireId,
            @RequestPart("file") MultipartFile file) throws IOException {
        carriereService.uploadFichier(militaireId, file);
        return ResponseEntity.ok().build();
    }

    // ✅ AJOUTÉ : Récupération du fichier pour visualisation
    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFile(@PathVariable UUID id) {
        Carriere module = carriereService.getDoc(id);

        return ResponseEntity.ok()
                .header("Content-Type", module.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + module.getDocumentNom() + "\"")
                .body(module.getDocumentScan());
    }
}