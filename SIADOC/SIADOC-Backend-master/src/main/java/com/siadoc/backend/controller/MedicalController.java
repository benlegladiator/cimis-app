package com.siadoc.backend.controller;

import com.siadoc.backend.dto.BlessureDTO;
import com.siadoc.backend.dto.MedicalGlobalDTO;
import com.siadoc.backend.dto.PensionDTO;
import com.siadoc.backend.service.MedicalService;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import com.siadoc.backend.model.Blessure;
import com.siadoc.backend.model.Pension;

import java.io.IOException;
import java.util.UUID;

@RestController
@RequestMapping("/api/medical")
public class MedicalController {

    private final MedicalService medicalService;
    private final ObjectMapper objectMapper;

    public MedicalController(MedicalService medicalService, ObjectMapper objectMapper) {
        this.medicalService = medicalService;
        this.objectMapper = objectMapper;
    }

    @GetMapping("/{militaireId}")
    public ResponseEntity<MedicalGlobalDTO> getDossier(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(medicalService.getDossierMedical(militaireId));
    }

    @GetMapping("/blessures/{id}/fichier")
    public ResponseEntity<byte[]> getBlessureFile(@PathVariable UUID id) {

        Blessure b = medicalService.getBlessure(id);

        return ResponseEntity.ok()
                .header("Content-Type", b.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + b.getDocumentNom() + "\"")
                .body(b.getDocumentData());
    }


    @GetMapping("/pensions/{id}/fichier")
    public ResponseEntity<byte[]> getPensionFile(@PathVariable UUID id) {

        Pension p = medicalService.getPension(id);

        return ResponseEntity.ok()
                .header("Content-Type", p.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + p.getDocumentNom() + "\"")
                .body(p.getDocumentData());
    }


    @PostMapping(value = "/{militaireId}/blessure", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<BlessureDTO> addBlessure(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file) throws IOException {

        BlessureDTO dto = objectMapper.readValue(json, BlessureDTO.class);
        return ResponseEntity.ok(medicalService.addBlessure(militaireId, dto, file));
    }

    @PostMapping(value = "/{militaireId}/pension", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<PensionDTO> addPension(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file) throws IOException {

        PensionDTO dto = objectMapper.readValue(json, PensionDTO.class);
        return ResponseEntity.ok(medicalService.addPension(militaireId, dto, file));
    }

    @DeleteMapping("/blessure/{id}")
    public ResponseEntity<Void> deleteBlessure(@PathVariable UUID id) {
        medicalService.deleteBlessure(id);
        return ResponseEntity.noContent().build();
    }

    @DeleteMapping("/pension/{id}")
    public ResponseEntity<Void> deletePension(@PathVariable UUID id) {
        medicalService.deletePension(id);
        return ResponseEntity.noContent().build();
    }
}
