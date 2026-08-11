package com.siadoc.backend.controller;

import com.siadoc.backend.service.DashboardService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.Map;
import java.util.UUID;

@RestController
@RequestMapping("/api/dashboards")
@RequiredArgsConstructor
public class DashboardController {

    private final DashboardService dashboardService;
    private final com.siadoc.backend.security.UserSession userSession;

    @GetMapping("/etat-major")
    public Map<String, Object> getEtatMajorStats() {
        com.siadoc.backend.model.Utilisateur user = userSession.getCurrentUser();
        String arme = "";
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_TERRE) arme = "TERRE";
        else if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_AIR) arme = "AIR";
        else if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_MARINE) arme = "MARINE";
        
        return dashboardService.getStatsForEtatMajor(arme);
    }

    @GetMapping("/drh")
    public Map<String, Object> getDrhStats() {
        return dashboardService.getStatsForDRH();
    }

    @GetMapping("/rmia/{id}")
    public Map<String, Object> getRmiaStats(@PathVariable UUID id) {
        String arme = getArmeForCurrentUser();
        return dashboardService.getStatsForRMIA(id, arme);
    }

    @GetMapping("/brigade/{id}")
    public Map<String, Object> getBrigadeStats(@PathVariable UUID id) {
        String arme = getArmeForCurrentUser();
        return dashboardService.getStatsForBrigade(id, arme);
    }

    @GetMapping("/bataillon/{id}")
    public Map<String, Object> getBataillonStats(@PathVariable UUID id) {
        String arme = getArmeForCurrentUser();
        return dashboardService.getStatsForBataillon(id, arme);
    }

    @GetMapping("/compagnie/{id}")
    public Map<String, Object> getCompagnieStats(@PathVariable UUID id) {
        return dashboardService.getStatsForCompagnie(id);
    }

    private String getArmeForCurrentUser() {
        com.siadoc.backend.model.Utilisateur user = userSession.getCurrentUser();
        if (user == null) return null;
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_TERRE) return "TERRE";
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_AIR) return "AIR";
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_MARINE) return "MARINE";
        return null;
    }
}