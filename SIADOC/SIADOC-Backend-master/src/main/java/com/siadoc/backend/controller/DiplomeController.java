package com.siadoc.backend.controller;

import com.siadoc.backend.dto.DiplomeDTO;
import com.siadoc.backend.model.DiplomeItem;
import com.siadoc.backend.service.DiplomeService;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/diplomes")
public class DiplomeController {

    private final DiplomeService service;
    private final ObjectMapper objectMapper;

    public DiplomeController(DiplomeService service, ObjectMapper objectMapper) {
        this.service = service;
        this.objectMapper = objectMapper;
    }

    @GetMapping("/{militaireId}")
    public ResponseEntity<List<DiplomeDTO>> getAll(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(service.getList(militaireId));
    }

    @PostMapping(value = "/{militaireId}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<DiplomeDTO> create(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file) throws IOException {

        DiplomeDTO dto = objectMapper.readValue(json, DiplomeDTO.class);
        return ResponseEntity.ok(service.add(militaireId, dto, file));
    }

    @GetMapping("/{id}/fichier")
    public ResponseEntity<byte[]> getFile(@PathVariable UUID id) {
        DiplomeItem item = service.getItem(id);

        return ResponseEntity.ok()
                .header("Content-Type", item.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + item.getDocumentNom() + "\"")
                .body(item.getDocumentData());
    }

    @GetMapping("/item/{id}/document")
    public ResponseEntity<byte[]> getDocument(@PathVariable UUID id) {
        DiplomeItem item = service.getItem(id);

        return ResponseEntity.ok()
                .header("Content-Type", item.getDocumentType())
                .header("Content-Disposition",
                        "inline; filename=\"" + item.getDocumentNom() + "\"")
                .body(item.getDocumentData());
    }

    @PutMapping("/{id}")
    public ResponseEntity<DiplomeDTO> update(
            @PathVariable UUID id,
            @RequestBody DiplomeDTO dto) {
        return ResponseEntity.ok(service.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        service.delete(id);
        return ResponseEntity.noContent().build();
    }
}