package com.siadoc.backend.controller;

import com.siadoc.backend.service.GesmilClientService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.HashMap;
import java.util.Map;

@RestController
@RequestMapping("/api/integration/gesmil")
public class GesmilIntegrationController {

    @Autowired
    private GesmilClientService gesmilService;

    @GetMapping("/situation/{matricule}")
    public ResponseEntity<?> getSituation(@PathVariable String matricule) {
        try {
            Map<String, Object> data = gesmilService.getFichierSituation(matricule);
            return ResponseEntity.ok(data);
        } catch (Exception e) {
            Map<String, String> error = new HashMap<>();
            error.put("error", e.getMessage());
            return ResponseEntity.status(500).body(error);
        }
    }
}
