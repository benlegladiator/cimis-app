package com.siadoc.backend.controller;

import com.siadoc.backend.dto.NotationDTO;
import com.siadoc.backend.model.NotationItem;
import com.siadoc.backend.service.NotationService;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/notations")
public class NotationController {

    private final NotationService service;
    private final ObjectMapper objectMapper;

    public NotationController(NotationService service, ObjectMapper objectMapper) {
        this.service = service;
        this.objectMapper = objectMapper;
    }

    @GetMapping("/{militaireId}")
    public ResponseEntity<List<NotationDTO>> getAll(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(service.getList(militaireId));
    }

    @PostMapping(value = "/{militaireId}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<NotationDTO> create(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file) throws IOException {

        NotationDTO dto = objectMapper.readValue(json, NotationDTO.class);
        return ResponseEntity.ok(service.add(militaireId, dto, file));
    }

    @PutMapping("/{id}")
    public ResponseEntity<NotationDTO> update(
            @PathVariable UUID id,
            @RequestBody NotationDTO dto) {
        return ResponseEntity.ok(service.update(id, dto));
    }

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFile(@PathVariable UUID id) {

        NotationItem item = service.getItem(id);

        return ResponseEntity.ok()
                .header("Content-Type", item.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + item.getDocumentNom() + "\"")
                .body(item.getDocumentData());
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }
}
