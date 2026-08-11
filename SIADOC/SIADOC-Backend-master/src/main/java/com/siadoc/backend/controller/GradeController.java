package com.siadoc.backend.controller;

import com.siadoc.backend.model.GradeConfig;
import com.siadoc.backend.repository.GradeConfigRepository;
import com.siadoc.backend.service.GradeService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;
import java.util.UUID;

@RestController
@RequestMapping("/api/grades")
@RequiredArgsConstructor
public class GradeController {

    private final GradeService gradeService;
    private final GradeConfigRepository gradeConfigRepository;

    @GetMapping
    public Map<String, List<String>> getGradesParArmee() {
        return gradeService.getGradesParArmee();
    }

    @GetMapping("/groupes")
    public Map<String, Map<String, List<String>>> getGradesGroupesParArmee() {
        return gradeService.getGradesGroupesParArmee();
    }

    // --- ENDPOINTS DE PARAMÉTRAGE ---

    @GetMapping("/config")
    public List<GradeConfig> getAllConfigs() {
        return gradeConfigRepository.findAllByOrderByOrdreAsc();
    }

    @PostMapping("/config")
    public GradeConfig saveConfig(@RequestBody GradeConfig config) {
        GradeConfig saved = gradeConfigRepository.save(config);
        gradeService.refreshCache();
        return saved;
    }

    @DeleteMapping("/config/{id}")
    public ResponseEntity<Void> deleteConfig(@PathVariable UUID id) {
        gradeConfigRepository.deleteById(id);
        gradeService.refreshCache();
        return ResponseEntity.ok().build();
    }
}
