package com.siadoc.backend.controller;

import com.siadoc.backend.model.SecteurMilitaire;
import com.siadoc.backend.repository.SecteurRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/secteurs")
@RequiredArgsConstructor
public class SecteurController {

    private final SecteurRepository secteurRepository;

    @GetMapping
    public List<SecteurMilitaire> getAll() {
        return secteurRepository.findAll();
    }
}
