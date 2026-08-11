package com.siadoc.backend.controller;

import com.siadoc.backend.service.HierarchyImportService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/admin/hierarchy")
@RequiredArgsConstructor
public class HierarchyAdminController {

    private final HierarchyImportService hierarchyImportService;

    @PostMapping("/import")
    public ResponseEntity<String> importHierarchy() {
        // hierarchyImportService.clearAll(); // Commented out to prevent FK errors with existing dossiers
        hierarchyImportService.importHierarchy();
        return ResponseEntity.ok("Hierarchy imported successfully");
    }
}
