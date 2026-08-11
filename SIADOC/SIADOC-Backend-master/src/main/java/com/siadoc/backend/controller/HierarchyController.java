package com.siadoc.backend.controller;

import com.siadoc.backend.model.Bataillon;
import com.siadoc.backend.model.Brigade;
import com.siadoc.backend.model.RegionMilitaire;
import com.siadoc.backend.repository.BataillonRepository;
import com.siadoc.backend.repository.BrigadeRepository;
import com.siadoc.backend.repository.RegionMilitaireRepository;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api")
public class HierarchyController {

    private final RegionMilitaireRepository regionRepository;
    private final BrigadeRepository brigadeRepository;
    private final BataillonRepository bataillonRepository;

    public HierarchyController(RegionMilitaireRepository regionRepository, 
                               BrigadeRepository brigadeRepository, 
                               BataillonRepository bataillonRepository) {
        this.regionRepository = regionRepository;
        this.brigadeRepository = brigadeRepository;
        this.bataillonRepository = bataillonRepository;
    }

    @GetMapping("/region-militaires")
    public List<RegionMilitaire> getRMIA() {
        return regionRepository.findAll();
    }

    @GetMapping("/brigades")
    public List<com.siadoc.backend.dto.BrigadeDTO> getBrigades() {
        return brigadeRepository.findAll().stream()
                .map(b -> new com.siadoc.backend.dto.BrigadeDTO(b.getId(), b.getNom(), b.getRegion() != null ? b.getRegion().getId() : null))
                .toList();
    }

    @GetMapping("/bataillons")
    public List<com.siadoc.backend.dto.BataillonDTO> getBataillons() {
        return bataillonRepository.findAll().stream()
                .map(b -> new com.siadoc.backend.dto.BataillonDTO(b.getId(), b.getNom(), b.getBrigade() != null ? b.getBrigade().getId() : null))
                .toList();
    }
}
