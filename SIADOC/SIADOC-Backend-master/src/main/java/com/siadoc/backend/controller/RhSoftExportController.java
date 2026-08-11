package com.siadoc.backend.controller;

import com.siadoc.backend.dto.export.FullDossierExportDTO;
import com.siadoc.backend.service.DossierExportService;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDateTime;
import java.util.List;

/**
 * Contrôleur spécifique pour l'interopérabilité avec GESMIL (Standard RhSoft).
 * Implémente la signature de requête demandée par le partenaire.
 */
@RestController
@RequestMapping("/services/rhsoftmsgap/api/personnel")
public class RhSoftExportController {

    private final DossierExportService exportService;

    public RhSoftExportController(DossierExportService exportService) {
        this.exportService = exportService;
    }

    /**
     * Récupère le dossier complet (Fichier Situation) d'un militaire spécifique.
     * 
     * @param matricule Le matricule (Solde ou Militaire).
     * @return Le JSON complet enrichi.
     */
    @GetMapping("/{matricule}/fichier-situation")
    public ResponseEntity<?> getFichierSituation(@PathVariable String matricule) {
        try {
            FullDossierExportDTO dto = exportService.exportByAnyMatricule(matricule);
            return ResponseEntity.ok(dto);
        } catch (Exception e) {
            return ResponseEntity.status(404).body("Militaire introuvable : " + e.getMessage());
        }
    }

    /**
     * Récupère la liste des fichiers situations mis à jour ou créés dans une plage de dates.
     * C'est le point d'entrée principal pour la synchronisation GESMIL.
     * 
     * @param startDate Date de début (ISO format).
     * @param endDate Date de fin (ISO format).
     * @return Liste de dossiers complets.
     */
    @GetMapping("/fichier-situation")
    public ResponseEntity<?> getSycnhronisationData(
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME) LocalDateTime startDate,
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME) LocalDateTime endDate) {
        try {
            List<FullDossierExportDTO> list = exportService.exportByUpdateDateRange(startDate, endDate);
            return ResponseEntity.ok(list);
        } catch (Exception e) {
            return ResponseEntity.status(500).body("Erreur lors de la récupération des données : " + e.getMessage());
        }
    }

    /**
     * Récupère la liste de TOUS les militaires actifs.
     */
    @GetMapping("/all/fichier-situation")
    public ResponseEntity<?> getAllData() {
        try {
            List<FullDossierExportDTO> list = exportService.exportAllMilitaires();
            return ResponseEntity.ok(list);
        } catch (Exception e) {
            return ResponseEntity.status(500).body("Erreur : " + e.getMessage());
        }
    }
}
