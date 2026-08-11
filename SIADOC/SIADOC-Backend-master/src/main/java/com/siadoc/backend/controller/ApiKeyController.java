package com.siadoc.backend.controller;

import com.siadoc.backend.model.ApiKey;
import com.siadoc.backend.service.ApiKeyService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/admin/api-keys")
@RequiredArgsConstructor
public class ApiKeyController {

    private final ApiKeyService apiKeyService;

    @GetMapping
    public List<ApiKey> getAll() {
        return apiKeyService.findAll();
    }

    @PostMapping
    public ResponseEntity<ApiKey> create(@RequestParam String appName) {
        return ResponseEntity.ok(apiKeyService.generateKey(appName));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> delete(@PathVariable UUID id) {
        apiKeyService.deleteKey(id);
        return ResponseEntity.noContent().build();
    }
}
