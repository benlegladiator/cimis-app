package com.siadoc.backend.controller;

import com.siadoc.backend.dto.StageDTO;
import com.siadoc.backend.service.StageService;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import com.siadoc.backend.model.StageItem;

import java.io.IOException;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/stages")
public class StageController {

    private final StageService service;
    private final ObjectMapper objectMapper;

    public StageController(StageService service, ObjectMapper objectMapper) {
        this.service = service;
        this.objectMapper = objectMapper;
    }

    @GetMapping("/{militaireId}")
    public ResponseEntity<List<StageDTO>> getAll(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(service.getList(militaireId));
    }

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFile(@PathVariable UUID id) {

        StageItem item = service.getItem(id);

        return ResponseEntity.ok()
                .header("Content-Type", item.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + item.getDocumentNom() + "\"")
                .body(item.getDocumentData());
    }

    @GetMapping("/item/{id}/document")
    public ResponseEntity<byte[]> getDocument(@PathVariable UUID id) {

        StageItem item = service.getItem(id);

        return ResponseEntity.ok()
                .header("Content-Type", item.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + item.getDocumentNom() + "\"")
                .body(item.getDocumentData());
    }

    @PostMapping(value = "/{militaireId}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<StageDTO> create(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file) throws IOException {

        StageDTO dto = objectMapper.readValue(json, StageDTO.class);
        return ResponseEntity.ok(service.add(militaireId, dto, file));
    }

    @PutMapping("/{id}")
    public ResponseEntity<StageDTO> update(
            @PathVariable UUID id,
            @RequestBody StageDTO dto) {
        return ResponseEntity.ok(service.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }
}
