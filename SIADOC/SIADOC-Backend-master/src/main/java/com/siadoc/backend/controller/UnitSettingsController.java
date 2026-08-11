package com.siadoc.backend.controller;

import com.siadoc.backend.model.Bataillon;
import com.siadoc.backend.model.Brigade;
import com.siadoc.backend.model.Compagnie;
import com.siadoc.backend.model.RegionMilitaire;
import com.siadoc.backend.repository.BataillonRepository;
import com.siadoc.backend.repository.BrigadeRepository;
import com.siadoc.backend.repository.CompagnieRepository;
import com.siadoc.backend.repository.RegionMilitaireRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.UUID;

@RestController
@RequestMapping("/api/settings/hierarchy")
@RequiredArgsConstructor
public class UnitSettingsController {

    private final RegionMilitaireRepository regionRepo;
    private final BrigadeRepository brigadeRepo;
    private final BataillonRepository bataillonRepo;
    private final CompagnieRepository compagnieRepo;
    private final com.siadoc.backend.repository.UniteOrganisationnelleRepository uniteRepo;
    private final com.siadoc.backend.config.HierarchyMigrationRunner migrationRunner;

    @GetMapping("/migrate")
    public String triggerMigration() {
        try {
            migrationRunner.run();
            return "Migration triggered successfully!";
        } catch (Exception e) {
            return "Migration failed: " + e.getMessage();
        }
    }

    @GetMapping("/ac")
    public java.util.List<com.siadoc.backend.model.UniteOrganisationnelle> getACUnits() {
        return uniteRepo.findByType("AC").stream()
                .filter(u -> u.getParent() == null)
                .toList();
    }

    @GetMapping("/fs")
    public java.util.List<com.siadoc.backend.model.UniteOrganisationnelle> getFSUnits() {
        return uniteRepo.findByType("FS").stream()
                .filter(u -> u.getParent() == null)
                .toList();
    }

    @PostMapping("/generic")
    public com.siadoc.backend.model.UniteOrganisationnelle createGenericUnit(@RequestBody com.siadoc.backend.model.UniteOrganisationnelle unite) {
        return uniteRepo.save(unite);
    }

    @DeleteMapping("/generic/{id}")
    public void deleteGenericUnit(@PathVariable UUID id) {
        uniteRepo.deleteById(id);
    }

    @PostMapping("/rmia")
    public RegionMilitaire createRMIA(@RequestBody RegionMilitaire rmia) {
        return regionRepo.save(rmia);
    }

    @PostMapping("/brigade")
    public ResponseEntity<Brigade> createBrigade(@RequestParam String nom, @RequestParam UUID regionId) {
        RegionMilitaire region = regionRepo.findById(regionId)
                .orElseThrow(() -> new RuntimeException("RMIA non trouvée"));
        Brigade b = new Brigade();
        b.setNom(nom);
        b.setRegion(region);
        return ResponseEntity.ok(brigadeRepo.save(b));
    }

    @PostMapping("/bataillon")
    public ResponseEntity<Bataillon> createBataillon(@RequestParam String nom, @RequestParam UUID brigadeId) {
        Brigade brigade = brigadeRepo.findById(brigadeId)
                .orElseThrow(() -> new RuntimeException("Brigade non trouvée"));
        Bataillon bat = new Bataillon();
        bat.setNom(nom);
        bat.setBrigade(brigade);
        return ResponseEntity.ok(bataillonRepo.save(bat));
    }

    @PostMapping("/compagnie")
    public ResponseEntity<Compagnie> createCompagnie(@RequestParam String nom, @RequestParam UUID bataillonId, @RequestParam(required = false) String localisation) {
        Bataillon bat = bataillonRepo.findById(bataillonId)
                .orElseThrow(() -> new RuntimeException("Bataillon non trouvé"));
        Compagnie comp = new Compagnie();
        comp.setNom(nom);
        comp.setBataillon(bat);
        comp.setLocalisation(localisation);
        return ResponseEntity.ok(compagnieRepo.save(comp));
    }

    @DeleteMapping("/compagnie/{id}")
    public ResponseEntity<Void> deleteCompagnie(@PathVariable UUID id) {
        compagnieRepo.deleteById(id);
        return ResponseEntity.ok().build();
    }
}
