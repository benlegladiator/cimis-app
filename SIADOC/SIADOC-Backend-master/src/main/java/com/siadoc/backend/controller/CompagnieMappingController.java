package com.siadoc.backend.controller;

import com.siadoc.backend.dto.MappingGesmilRequestDTO;
import com.siadoc.backend.model.Compagnie;
import com.siadoc.backend.model.CompagnieMappingGesmil;
import com.siadoc.backend.repository.CompagnieMappingGesmilRepository;
import com.siadoc.backend.repository.CompagnieRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/admin/gesmil-mappings")
@RequiredArgsConstructor
public class CompagnieMappingController {

    private final CompagnieMappingGesmilRepository mappingRepository;
    private final CompagnieRepository compagnieRepository;

    @GetMapping
    public List<CompagnieMappingGesmil> getAll() {
        return mappingRepository.findAll();
    }

    @PostMapping
    public ResponseEntity<?> create(@RequestBody MappingGesmilRequestDTO request) {
        if (mappingRepository.existsByCodeGesmil(request.getCodeGesmil())) {
            return ResponseEntity.badRequest().body("Un mapping pour ce code GESMIL existe déjà.");
        }
        Compagnie compagnie = compagnieRepository.findById(request.getCompagnieId())
                .orElseThrow(() -> new RuntimeException("Compagnie introuvable"));

        CompagnieMappingGesmil mapping = CompagnieMappingGesmil.builder()
                .codeGesmil(request.getCodeGesmil())
                .compagnie(compagnie)
                .build();
        return ResponseEntity.ok(mappingRepository.save(mapping));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        mappingRepository.deleteById(id);
        return ResponseEntity.noContent().build();
    }
}
