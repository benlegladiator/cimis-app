package com.siadoc.backend.controller;

import com.siadoc.backend.dto.search.*;
import com.siadoc.backend.service.*;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/recherche")
@RequiredArgsConstructor
public class RechercheController {

    private final RecompenseService recompenseService;
    private final ActeNaissanceService acteNaissanceService;
    private final ActeMariageService acteMariageService;
    private final CNIService cniService;
    private final PunitionService punitionService;
    private final DiplomeService diplomeService;
    private final AvancementService avancementService;
    private final StageService stageService;
    private final MedicalService medicalService;
    private final MutationService mutationService;
    private final NotationService notationService;
    private final CampagneService campagneService;
    private final CarriereService carriereService;

    @PostMapping("/recompenses")
    public List<ResultRechercheRecompenseDTO> rechercherRecompenses(@RequestBody RechercheRecompenseDTO dto) {
        return recompenseService.rechercher(dto);
    }

    @PostMapping("/etat-civil/naissance")
    public List<ResultRechercheEtatCivilDTO> rechercherActeNaissance(@RequestBody RechercheEtatCivilDTO dto) {
        return acteNaissanceService.rechercherActeNaissance(dto);
    }

    @PostMapping("/etat-civil/mariage")
    public List<ResultRechercheMariageDTO> rechercherMariage(@RequestBody RechercheMariageDTO dto) {
        return acteMariageService.rechercherMariage(dto);
    }

    @PostMapping("/etat-civil/cni")
    public List<ResultRechercheCniDTO> rechercherCni(@RequestBody RechercheCniDTO dto) {
        return cniService.rechercherCni(dto);
    }

    @PostMapping("/punitions")
    public List<ResultRecherchePunitionDTO> rechercherPunitions(@RequestBody RecherchePunitionDTO dto) {
        return punitionService.rechercher(dto);
    }

    @PostMapping("/diplomes")
    public List<ResultRechercheDiplomeDTO> rechercherDiplomes(@RequestBody RechercheDiplomeDTO dto) {
        return diplomeService.rechercher(dto);
    }

    @PostMapping("/avancements")
    public List<ResultRechercheAvancementDTO> rechercherAvancements(@RequestBody RechercheAvancementDTO dto) {
        return avancementService.rechercher(dto);
    }

    @PostMapping("/stages")
    public List<ResultRechercheStageDTO> rechercherStages(@RequestBody RechercheStageDTO dto) {
        return stageService.rechercher(dto);
    }

    @PostMapping("/medical")
    public List<ResultRechercheMedicalDTO> rechercherMedical(@RequestBody RechercheMedicalDTO dto) {
        return medicalService.rechercher(dto);
    }

    @PostMapping("/mutations")
    public List<ResultRechercheMutationDTO> rechercherMutations(@RequestBody RechercheMutationDTO dto) {
        return mutationService.rechercher(dto);
    }

    @PostMapping("/notations")
    public List<ResultRechercheNotationDTO> rechercherNotations(@RequestBody RechercheNotationDTO dto) {
        return notationService.rechercher(dto);
    }

    @PostMapping("/campagnes")
    public List<ResultRechercheCampagneMilitaireDTO> rechercherCampagnes(@RequestBody RechercheCampagneMilitaireDTO dto) {
        return campagneService.rechercher(dto);
    }

    @PostMapping("/carriere")
    public List<ResultRechercheCarriereDTO> rechercherCarriere(@RequestBody RechercheCarriereDTO dto) {
        return carriereService.rechercher(dto);
    }
}