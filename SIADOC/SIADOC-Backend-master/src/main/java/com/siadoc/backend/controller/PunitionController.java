package com.siadoc.backend.controller;

import com.siadoc.backend.dto.PunitionDTO;
import com.siadoc.backend.service.PunitionService;
import com.siadoc.backend.model.PunitionItem;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/punitions")
public class PunitionController {

    private final PunitionService service;
    private final ObjectMapper objectMapper;

    public PunitionController(PunitionService service, ObjectMapper objectMapper) {
        this.service = service;
        this.objectMapper = objectMapper;
    }

    @GetMapping("/{militaireId}")
    public ResponseEntity<List<PunitionDTO>> getAll(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(service.getList(militaireId));
    }

    @PostMapping(value = "/{militaireId}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<PunitionDTO> create(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file) throws IOException {

        PunitionDTO dto = objectMapper.readValue(json, PunitionDTO.class);
        return ResponseEntity.ok(service.add(militaireId, dto, file));
    }

    @PutMapping("/{id}")
    public ResponseEntity<PunitionDTO> update(
            @PathVariable UUID id,
            @RequestBody PunitionDTO dto) {
        return ResponseEntity.ok(service.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }

    @GetMapping("/item/{itemId}/document")
    public ResponseEntity<byte[]> getDocument(@PathVariable UUID itemId) {

        PunitionItem item = service.getItem(itemId);

        if (item.getDocumentData() == null) {
            return ResponseEntity.notFound().build();
        }

        return ResponseEntity.ok()
                .header("Content-Type", item.getDocumentType())
                .header(
                        "Content-Disposition",
                        "inline; filename=\"" + item.getDocumentNom() + "\""
                )
                .body(item.getDocumentData());
    }

}
