package com.siadoc.backend.controller;

import com.siadoc.backend.dto.RecompenseDTO;
import com.siadoc.backend.service.RecompenseService;
import com.siadoc.backend.model.RecompenseItem;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/recompenses")
public class RecompenseController {

    private final RecompenseService service;
    private final ObjectMapper objectMapper;

    public RecompenseController(RecompenseService service, ObjectMapper objectMapper) {
        this.service = service;
        this.objectMapper = objectMapper;
    }

    @GetMapping("/{militaireId}")
    public ResponseEntity<List<RecompenseDTO>> getAll(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(service.getList(militaireId));
    }

    @PostMapping(value = "/{militaireId}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<RecompenseDTO> create(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file) throws IOException {

        RecompenseDTO dto = objectMapper.readValue(json, RecompenseDTO.class);
        return ResponseEntity.ok(service.add(militaireId, dto, file));
    }

    @PutMapping("/{id}")
    public ResponseEntity<RecompenseDTO> update(
            @PathVariable UUID id,
            @RequestBody RecompenseDTO dto) {
        return ResponseEntity.ok(service.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getDocument(@PathVariable UUID id) {

        RecompenseItem item = service.getItem(id);

        return ResponseEntity.ok()
                .header("Content-Type", item.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + item.getDocumentNom() + "\"")
                .body(item.getDocumentData());
    }

}
