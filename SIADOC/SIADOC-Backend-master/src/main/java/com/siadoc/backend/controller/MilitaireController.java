package com.siadoc.backend.controller;

import com.siadoc.backend.model.Militaire;
import com.siadoc.backend.model.Utilisateur;
import com.siadoc.backend.repository.CompagnieRepository;
import com.siadoc.backend.service.MilitaireService;
import com.siadoc.backend.security.UserSession;
import com.siadoc.backend.dto.AffectationRequestDTO;

import org.springframework.http.ResponseEntity;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.util.UUID;
import java.time.LocalDate;
import java.util.List;
import java.io.IOException;

@RestController
@RequestMapping("/api/militaires")
public class MilitaireController {

    private final MilitaireService militaireService;
    private final UserSession userSession;
    private final CompagnieRepository compagnieRepository;

    public MilitaireController(
            MilitaireService militaireService,
            UserSession userSession,
            CompagnieRepository compagnieRepository
    ) {
        this.militaireService = militaireService;
        this.userSession = userSession;
        this.compagnieRepository = compagnieRepository;
    }

    @PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseEntity<Militaire> creerMilitaire(
            @RequestParam String nom,
            @RequestParam String prenom,
            @RequestParam(required = false) String dateNaissance,
            @RequestParam(required = false) String matriculeMilitaire,
            @RequestParam(required = false) String matriculeSolde,
            @RequestParam String grade,
            @RequestParam(required = false) String dateGrade,
            @RequestParam(required = false) Integer echelon,
            @RequestParam(required = false) String dateEchelon,
            @RequestParam(required = false) String dateService,
            @RequestParam(required = false) String armeService,
            @RequestParam(required = false) String statut,
            @RequestParam(required = false) String etat,
            @RequestParam(required = false) String lieuNaissance,
            @RequestParam(required = false) String sexe,
            @RequestParam(required = false) UUID compagnieId,
            @RequestParam(value = "photo", required = false) MultipartFile photo
    ) throws IOException {

        Utilisateur utilisateur = userSession.getCurrentUser();
        if (utilisateur == null) throw new RuntimeException("Utilisateur non connecté");

        Militaire militaire = new Militaire();
        militaire.setNom(nom);
        militaire.setPrenom(prenom);

        if (dateNaissance != null && !dateNaissance.isBlank()) militaire.setDateNaissance(LocalDate.parse(dateNaissance.trim()));
        militaire.setMatriculeMilitaire(matriculeMilitaire);
        militaire.setMatriculeSolde(matriculeSolde);
        militaire.setGrade(grade);

        if (dateGrade != null && !dateGrade.isBlank()) militaire.setDateGrade(LocalDate.parse(dateGrade.trim()));
        militaire.setEchelon(echelon);
        if (dateEchelon != null && !dateEchelon.isBlank()) militaire.setDateEchelon(LocalDate.parse(dateEchelon.trim()));
        if (dateService != null && !dateService.isBlank()) militaire.setDateService(LocalDate.parse(dateService.trim()));

        militaire.setArmeService(armeService);
        militaire.setStatut(statut);
        if (etat != null && !etat.isBlank()) militaire.setEtat(com.siadoc.backend.model.EtatMilitaire.valueOf(etat));
        militaire.setLieuNaissance(lieuNaissance);
        militaire.setSexe(sexe);

        if (photo != null && !photo.isEmpty()) {
            militaire.setPhoto(photo.getBytes());
            militaire.setPhotoNom(photo.getOriginalFilename());
            militaire.setPhotoType(photo.getContentType());
        }

        return ResponseEntity.ok(militaireService.creerMilitaire(militaire, utilisateur, compagnieId));
    }

    @GetMapping
    public ResponseEntity<List<Militaire>> lister() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null) {
            return ResponseEntity.status(401).build();
        }
        return ResponseEntity.ok(militaireService.listerParRole(user));
    }

    @GetMapping("/debug")
    public ResponseEntity<String> debugLister() {
        try {
            Utilisateur user = userSession.getCurrentUser();
            if (user == null) return ResponseEntity.status(401).body("NO SESSION USER - 401 UNAUTHORIZED");
            List<Militaire> result = militaireService.listerParRole(user);
            return ResponseEntity.ok("Success: Count = " + result.size() + ", User Role: " + user.getRole());
        } catch (Exception e) {
            java.io.StringWriter sw = new java.io.StringWriter();
            e.printStackTrace(new java.io.PrintWriter(sw));
            return ResponseEntity.status(500).body("Error: " + e.getMessage() + "\n" + sw.toString());
        }
    }

    @GetMapping("/by-compagnie-nom")
    public ResponseEntity<List<Militaire>> getByCompagnieNom(@RequestParam String nom) {
        return ResponseEntity.ok(militaireService.getByCompagnieNom(nom));
    }

    @GetMapping("/by-unite-nom")
    public ResponseEntity<List<Militaire>> getByUniteNom(@RequestParam String nom) {
        return ResponseEntity.ok(militaireService.getByUniteNom(nom));
    }

    @GetMapping("/{id}")
    public ResponseEntity<Militaire> getById(@PathVariable UUID id) {
        return ResponseEntity.ok(militaireService.getById(id));
    }

    @GetMapping("/{id}/photo")
    public ResponseEntity<byte[]> getPhoto(@PathVariable UUID id) {
        Militaire militaire = militaireService.getById(id);
        if (militaire == null || militaire.getPhoto() == null) {
            return ResponseEntity.notFound().build();
        }

        MediaType contentType = MediaType.IMAGE_JPEG;
        if (militaire.getPhotoType() != null) {
            try {
                contentType = MediaType.parseMediaType(militaire.getPhotoType());
            } catch (Exception e) {
                // Fallback to JPEG
            }
        }

        return ResponseEntity.ok()
                .contentType(contentType)
                .body(militaire.getPhoto());
    }

    @GetMapping("/{id}/historique")
    public ResponseEntity<List<com.siadoc.backend.model.HistoriqueMilitaire>> getHistorique(@PathVariable UUID id) {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getRole() != com.siadoc.backend.model.Role.DRH) {
            return ResponseEntity.status(org.springframework.http.HttpStatus.FORBIDDEN).build();
        }
        return ResponseEntity.ok(militaireService.getHistorique(id));
    }

    @GetMapping("/ma-compagnie")
    public List<Militaire> getMaCompagnie() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getCompagnie() == null) throw new RuntimeException("Aucune compagnie associée");
        return militaireService.getMilitairesMaCompagnie(user.getCompagnie().getId());
    }

    @PostMapping("/{id}/recevoir")
    public ResponseEntity<String> recevoir(@PathVariable UUID id) {
        militaireService.confirmerReceptionDossier(id);
        return ResponseEntity.ok("Réception confirmée.");
    }

    @PostMapping("/search")
    public List<Militaire> search(@RequestBody java.util.Map<String, String> filters) {
        return militaireService.searchComplex(filters.get("nom"), filters.get("matricule"), filters.get("grade"));
    }

    @GetMapping("/dashboard/nouvelles-integrations")
    public ResponseEntity<List<Militaire>> getNouvellesIntegrations() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null) return ResponseEntity.status(401).build();

        if (user.getRole() == com.siadoc.backend.model.Role.BATAILLON && user.getBataillon() != null) {
            return ResponseEntity.ok(militaireService.getNouvellesIntegrationsByBataillon(user.getBataillon().getId()));
        }
        
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_TERRE) {
            return ResponseEntity.ok(militaireService.getNouvellesIntegrationsByArme("TERRE"));
        }
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_AIR) {
            return ResponseEntity.ok(militaireService.getNouvellesIntegrationsByArme("AIR"));
        }
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_MARINE) {
            return ResponseEntity.ok(militaireService.getNouvellesIntegrationsByArme("MARINE"));
        }

        return ResponseEntity.ok(militaireService.getNouvellesIntegrations());
    }

    @PostMapping("/{id}/affecter")
    public ResponseEntity<Militaire> affecterMilitaire(
            @PathVariable UUID id,
            @RequestBody AffectationRequestDTO request
    ) {
        return ResponseEntity.ok(militaireService.effectuerMutationAffectation(id, request));
    }

    @GetMapping("/dashboard/retraites-proches")
    public ResponseEntity<List<Militaire>> getRetraitesProches() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null) return ResponseEntity.status(401).build();

        if (user.getRole() == com.siadoc.backend.model.Role.BATAILLON && user.getBataillon() != null) {
            return ResponseEntity.ok(militaireService.getRetraitesProchesByBataillon(user.getBataillon().getId()));
        }

        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_TERRE) {
            return ResponseEntity.ok(militaireService.getRetraitesProchesByArme("TERRE"));
        }
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_AIR) {
            return ResponseEntity.ok(militaireService.getRetraitesProchesByArme("AIR"));
        }
        if (user.getRole() == com.siadoc.backend.model.Role.ETAT_MAJOR_MARINE) {
            return ResponseEntity.ok(militaireService.getRetraitesProchesByArme("MARINE"));
        }
        return ResponseEntity.ok(militaireService.getRetraitesProches());
    }

    @PostMapping("/{id}/retraite")
    public ResponseEntity<String> mettreEnRetraite(@PathVariable UUID id) {
        militaireService.mettreEnRetraite(id);
        return ResponseEntity.ok("Mise en retraite effectuée avec succès.");
    }

    @GetMapping("/retraites")
    public ResponseEntity<List<Militaire>> listerRetraites() {
        return ResponseEntity.ok(militaireService.listerRetraites());
    }
}
