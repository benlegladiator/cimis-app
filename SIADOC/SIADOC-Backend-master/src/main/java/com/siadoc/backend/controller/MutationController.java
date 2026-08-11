package com.siadoc.backend.controller;

import com.siadoc.backend.dto.MutationItemDTO;
import com.siadoc.backend.dto.MutationRequestDTO;
import com.siadoc.backend.dto.MutationsModuleDTO;
import com.siadoc.backend.model.MutationItem;
import com.siadoc.backend.service.MutationService;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.UUID;

@RestController
@RequestMapping("/api/mutations")
public class MutationController {

    private final MutationService mutationService;
    private final ObjectMapper objectMapper;

    public MutationController(MutationService mutationService, ObjectMapper objectMapper) {
        this.mutationService = mutationService;
        this.objectMapper = objectMapper;
    }

    @GetMapping("/{militaireId}")
    public ResponseEntity<MutationsModuleDTO> getGlobal(@PathVariable UUID militaireId) {
        return ResponseEntity.ok(
                mutationService.getMutationsByMilitaire(militaireId)
        );
    }

    @GetMapping("/item/{itemId}/document")
    public ResponseEntity<byte[]> getDocument(@PathVariable UUID itemId) {

        MutationItem item = mutationService.getItem(itemId);

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

    @PostMapping(value = "/{militaireId}",
            consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<MutationItemDTO> create(
            @PathVariable UUID militaireId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file
    ) throws IOException {

        MutationRequestDTO request =
                objectMapper.readValue(json, MutationRequestDTO.class);

        return ResponseEntity.ok(
                mutationService.addItem(militaireId, request, file)
        );
    }

    @PutMapping(value = "/item/{itemId}",
            consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<MutationItemDTO> update(
            @PathVariable UUID itemId,
            @RequestPart("data") String json,
            @RequestPart(value = "file", required = false) MultipartFile file
    ) throws IOException {

        MutationRequestDTO request =
                objectMapper.readValue(json, MutationRequestDTO.class);

        return ResponseEntity.ok(
                mutationService.updateItem(itemId, request, file)
        );
    }

    @DeleteMapping("/item/{itemId}")
    public ResponseEntity<Void> delete(@PathVariable UUID itemId) {
        mutationService.deleteItem(itemId);
        return ResponseEntity.noContent().build();
    }
}
