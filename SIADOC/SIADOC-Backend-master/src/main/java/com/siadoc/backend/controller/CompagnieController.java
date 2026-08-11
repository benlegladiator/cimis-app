package com.siadoc.backend.controller;

import com.siadoc.backend.model.Compagnie;
import com.siadoc.backend.model.Utilisateur;
import com.siadoc.backend.model.Role;
import com.siadoc.backend.repository.CompagnieRepository;
import com.siadoc.backend.security.UserSession;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.ArrayList;

@RestController
@RequestMapping("/api/compagnies")
public class CompagnieController {

    private final CompagnieRepository compagnieRepository;
    private final UserSession userSession;

    public CompagnieController(CompagnieRepository compagnieRepository, UserSession userSession) {
        this.compagnieRepository = compagnieRepository;
        this.userSession = userSession;
    }

    @GetMapping
    public List<com.siadoc.backend.dto.CompagnieDTO> getAll() {
        Utilisateur user = userSession.getCurrentUser();
        List<Compagnie> compagnies;

        if (user == null || user.getRole() == Role.DRH || user.getRole() == Role.SUPER_ADMIN || 
            user.getRole() == Role.ETAT_MAJOR_TERRE || user.getRole() == Role.ETAT_MAJOR_AIR || user.getRole() == Role.ETAT_MAJOR_MARINE) {
            compagnies = compagnieRepository.findAllHierarchical();
        } else if (user.getRole() == Role.RMIA && user.getRegion() != null) {
            compagnies = compagnieRepository.findByRegionId(user.getRegion().getId());
        } else if (user.getRole() == Role.BRIGADE && user.getBrigade() != null) {
            compagnies = compagnieRepository.findByBrigadeId(user.getBrigade().getId());
        } else if (user.getRole() == Role.BATAILLON && user.getBataillon() != null) {
            compagnies = compagnieRepository.findByBataillonId(user.getBataillon().getId());
        } else if (user.getCompagnie() != null) {
            compagnies = List.of(user.getCompagnie());
        } else {
            compagnies = new ArrayList<>();
        }

        return compagnies.stream()
                .map(c -> {
                    com.siadoc.backend.dto.CompagnieDTO dto = new com.siadoc.backend.dto.CompagnieDTO();
                    dto.setId(c.getId());
                    dto.setNom(c.getNom());
                    
                    if (c.getBataillon() != null) {
                        dto.setBataillonId(c.getBataillon().getId());
                        
                        String rmia = (c.getBataillon().getBrigade() != null && c.getBataillon().getBrigade().getRegion() != null) 
                                      ? c.getBataillon().getBrigade().getRegion().getNom() : "RMIA ?";
                        String brigade = (c.getBataillon().getBrigade() != null) 
                                         ? c.getBataillon().getBrigade().getNom() : "Brigade ?";
                        String bataillon = c.getBataillon().getNom();
                        
                        dto.setLabelAffichage(String.format("%s > %s > %s > %s", rmia, brigade, bataillon, c.getNom()));
                        dto.setHierarchy(new com.siadoc.backend.dto.CompagnieDTO.HierarchyInfo(rmia, brigade, bataillon));
                    } else {
                        dto.setLabelAffichage("SANS BATAILLON > " + c.getNom());
                    }
                    return dto;
                })
                .toList();
    }

}