package com.siadoc.backend.controller;

import com.siadoc.backend.dto.CimisResponseDTO;
import com.siadoc.backend.service.CimisClientService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.HashMap;
import java.util.Map;

@RestController
@RequestMapping("/api/integration/cimis")
public class CimisIntegrationController {

    @Autowired
    private CimisClientService cimisService;

    @GetMapping("/liste")
    public ResponseEntity<?> getListe(
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int limit,
            @RequestParam(required = false) String grade,
            @RequestParam(required = false) String unite,
            @RequestParam(required = false) String search) {
        System.out.println("API SIADOC : Appel de /liste reçu !");
        try {
            Map<String, Object> data = cimisService.getListeCartes(page, limit, grade, unite, search);
            return ResponseEntity.ok(data);
        } catch (Exception e) {
            System.err.println("API SIADOC : Erreur dans /liste : " + e.getMessage());
            Map<String, String> error = new HashMap<>();
            error.put("error", e.getMessage());
            return ResponseEntity.status(500).body(error);
        }
    }

    @GetMapping("/statistiques")
    public ResponseEntity<?> getStatistiques() {
        try {
            return ResponseEntity.ok(cimisService.getStatistiques());
        } catch (Exception e) {
            Map<String, String> error = new HashMap<>();
            error.put("error", e.getMessage());
            return ResponseEntity.status(500).body(error);
        }
    }

    @GetMapping("/test-connection")
    public String testConnection() {
        return cimisService.getHelp();
    }

    @GetMapping("/carte/{matricule}")
    public ResponseEntity<?> getCimisCarte(@PathVariable String matricule) {
        try {
            return ResponseEntity.ok(cimisService.getCarte(matricule));
        } catch (Exception e) {
            Map<String, String> error = new HashMap<>();
            error.put("error", e.getMessage());
            return ResponseEntity.status(500).body(error);
        }
    }

    @Autowired
    private com.siadoc.backend.service.InfoMilitaireService infoMilitaireService;

    @GetMapping("/simulate-export")
    public ResponseEntity<?> simulateCimisRequest(@RequestParam String matricule) {
        try {
            com.siadoc.backend.dto.export.InfoMilitaireDTO dto = infoMilitaireService.getByMatricule(matricule);
            return ResponseEntity.ok(dto);
        } catch (Exception e) {
            Map<String, String> error = new HashMap<>();
            error.put("error", "Militaire non trouvé ou erreur : " + e.getMessage());
            return ResponseEntity.status(404).body(error);
        }
    }

    /**
     * TEST CIMIS -> SIADOC (Pour le dev CIMIS)

    /**
     * TEST CIMIS -> SIADOC (Pour le dev CIMIS)
     * Endpoint que le dev CIMIS peut appeler pour vérifier que SIADOC est en ligne.
     */
    @GetMapping("/ping")
    public Map<String, Object> ping() {
        Map<String, Object> response = new HashMap<>();
        response.put("status", "UP");
        response.put("message", "SIADOC Integration API is live");
        response.put("system", "SIADOC");
        response.put("timestamp", System.currentTimeMillis());
        return response;
    }
}
