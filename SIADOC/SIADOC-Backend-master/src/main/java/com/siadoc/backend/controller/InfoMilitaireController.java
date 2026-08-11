package com.siadoc.backend.controller;

import com.siadoc.backend.dto.export.InfoMilitaireDTO;
import com.siadoc.backend.service.InfoMilitaireService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

/**
 * Contrôleur exposant les informations essentielles d'un militaire
 * à destination des applications partenaires.
 *
 * Authentification : header X-API-KEY requis (géré par ApiKeyInterceptor
 * sur tout chemin /api/export/**).
 *
 * Endpoints disponibles :
 *
 *   GET /api/export/militaire/info?matricule={matricule}
 *       → Retourne les informations d'un militaire par son matricule.
 *
 *   GET /api/export/militaire/info/all
 *       → Retourne la liste complète des militaires actifs (informations essentielles).
 */
@RestController
@RequestMapping("/api/export/militaire")
public class InfoMilitaireController {

    private final InfoMilitaireService infoMilitaireService;

    public InfoMilitaireController(InfoMilitaireService infoMilitaireService) {
        this.infoMilitaireService = infoMilitaireService;
    }

    /**
     * Récupère les informations essentielles d'un militaire via son matricule.
     *
     * Exemple : GET /api/export/militaire/info?matricule=MAT-2023-12345
     *
     * Champs retournés :
     *   nom, prenom, matricule, dateNaissance, corps, grade, dateGrade, sexe
     *
     * @param matricule Le matricule militaire ou solde.
     * @return 200 OK avec le DTO, ou 404 si introuvable.
     */
    @GetMapping("/info")
    public ResponseEntity<?> getInfoByMatricule(@RequestParam String matricule) {
        try {
            InfoMilitaireDTO dto = infoMilitaireService.getByMatricule(matricule);
            return ResponseEntity.ok(dto);
        } catch (RuntimeException e) {
            return ResponseEntity.status(404).body(e.getMessage());
        } catch (Exception e) {
            return ResponseEntity.status(500).body("Erreur interne : " + e.getMessage());
        }
    }

    /**
     * Récupère les informations essentielles de tous les militaires actifs.
     *
     * Exemple : GET /api/export/militaire/info/all
     *
     * @return 200 OK avec la liste des DTOs.
     */
    @GetMapping("/info/all")
    public ResponseEntity<?> getAllInfoMilitaires() {
        try {
            List<InfoMilitaireDTO> liste = infoMilitaireService.getAllActifs();
            return ResponseEntity.ok(liste);
        } catch (Exception e) {
            return ResponseEntity.status(500).body("Erreur interne : " + e.getMessage());
        }
    }
}
