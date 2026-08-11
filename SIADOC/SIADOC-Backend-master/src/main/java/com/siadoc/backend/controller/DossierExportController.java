package com.siadoc.backend.controller;

import com.siadoc.backend.dto.export.FullDossierExportDTO;
import com.siadoc.backend.service.DossierExportService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/export")
public class DossierExportController {
    
    private final DossierExportService exportService;

    public DossierExportController(DossierExportService exportService) {
        this.exportService = exportService;
    }

    /**
     * Expose le dossier complet d'un militaire pour une application tierce.
     * Utilisation de @RequestParam pour éviter les erreurs de parsing d'URL avec des points.
     * 
     * @param matricule Le matricule (Solde ou Militaire).
     * @return Le DTO complet.
     */
    @GetMapping("/dossier")
    public ResponseEntity<?> getFullDossier(@RequestParam String matricule) {
        try {
            FullDossierExportDTO dto = exportService.exportByAnyMatricule(matricule);
            return ResponseEntity.ok(dto);
        } catch (Exception e) {
            return ResponseEntity.status(404).body(e.getMessage());
        }
    }

    /**
     * API 2: Retourne la liste de tous les militaires présents (actifs).
     */
    @GetMapping("/all")
    public ResponseEntity<?> getAllMilitaires() {
        try {
            return ResponseEntity.ok(exportService.exportAllMilitaires());
        } catch (Exception e) {
            return ResponseEntity.status(500).body(e.getMessage());
        }
    }

    /**
     * API 3: Retourne les militaires enregistrés dans une plage de temps.
     * @param start Date de début (ex: 2024-01-01T00:00:00)
     * @param end Date de fin (ex: 2024-12-31T23:59:59)
     */
    @GetMapping("/by-date-range")
    public ResponseEntity<?> getMilitairesByDateRange(
            @RequestParam @org.springframework.format.annotation.DateTimeFormat(iso = org.springframework.format.annotation.DateTimeFormat.ISO.DATE_TIME) java.time.LocalDateTime start,
            @RequestParam @org.springframework.format.annotation.DateTimeFormat(iso = org.springframework.format.annotation.DateTimeFormat.ISO.DATE_TIME) java.time.LocalDateTime end) {
        try {
            return ResponseEntity.ok(exportService.exportByRegistrationDateRange(start, end));
        } catch (Exception e) {
            return ResponseEntity.status(500).body(e.getMessage());
        }
    }

    /** Import d'un seul dossier (JSON objet unique) */
    @PostMapping("/import")
    public ResponseEntity<?> importDossier(@RequestBody FullDossierExportDTO dto) {
        try {
            var result = exportService.importFullDossier(dto);
            return ResponseEntity.ok(result);
        } catch (Exception e) {
            return ResponseEntity.status(400).body("Erreur : " + e.getMessage());
        }
    }

    /** Import en lot (JSON tableau de dossiers) */
    @PostMapping("/import/bulk")
    public ResponseEntity<?> importBulk(@RequestBody java.util.List<FullDossierExportDTO> dtos) {
        try {
            var result = exportService.importBulkDossiers(dtos);
            return ResponseEntity.ok(result);
        } catch (Exception e) {
            return ResponseEntity.status(400).body("Erreur lors de l'import en lot : " + e.getMessage());
        }
    }

    /** Liste des militaires sans compagnie affectée ("Pool non affectés") */
    @GetMapping("/non-affectes")
    public ResponseEntity<?> getNonAffectes() {
        try {
            var list = exportService.getMilitairesSansCompagnie();
            return ResponseEntity.ok(list);
        } catch (Exception e) {
            return ResponseEntity.status(500).body(e.getMessage());
        }
    }

    /** Affectation manuelle (unitaire ou bulk) */
    @PostMapping("/assign-company")
    public ResponseEntity<?> assignCompany(@RequestBody AssignmentRequest request) {
        try {
            exportService.assignMilitairesToCompagnie(request.militaireIds, request.compagnieId);
            return ResponseEntity.ok("Affectation réussie.");
        } catch (Exception e) {
            return ResponseEntity.status(400).body("Erreur lors de l'affectation : " + e.getMessage());
        }
    }

    public static record AssignmentRequest(java.util.List<java.util.UUID> militaireIds, java.util.UUID compagnieId) {}
}
