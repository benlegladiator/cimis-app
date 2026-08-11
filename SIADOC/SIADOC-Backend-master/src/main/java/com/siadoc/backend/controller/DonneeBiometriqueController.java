package com.siadoc.backend.controller;

import com.siadoc.backend.dto.DonneeBiometriqueDTO;
import com.siadoc.backend.dto.DonneeBiometriqueReponseDTO;
import com.siadoc.backend.service.DonneeBiometriqueService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

/**
 * Contrôleur REST pour la réception et la consultation des données biométriques
 * envoyées par l'application CIMIS.
 *
 * Authentification : header X-API-KEY requis (géré par ApiKeyInterceptor
 * sur tout chemin /api/import/cimis/**).
 *
 * -----------------------------------------------------------------------
 * Endpoints :
 *
 *   POST /api/import/cimis/biometrie
 *       → CIMIS envoie les données biométriques + QR code d'un militaire.
 *         Le militaire est identifié via le champ "matricule" dans le body JSON.
 *
 *   GET  /api/import/cimis/biometrie?matricule={matricule}
 *       → Consulte les données biométriques stockées pour un militaire.
 * -----------------------------------------------------------------------
 */
@RestController
@RequestMapping("/api/import/cimis")
public class DonneeBiometriqueController {

    private final DonneeBiometriqueService biometriqueService;

    public DonneeBiometriqueController(DonneeBiometriqueService biometriqueService) {
        this.biometriqueService = biometriqueService;
    }

    /**
     * Endpoint principal : CIMIS pousse les données biométriques vers SIADOC.
     *
     * Corps de la requête (JSON) :
     * {
     *   "matricule"          : "MAT-2023-12345",
     *   "empreinteDoigt1"    : "<base64>",
     *   "empreinteDoigt1Type": "image/png",
     *   "empreinteDoigt2"    : "<base64>",
     *   "empreinteDoigt2Type": "image/png",
     *   "photoVisage"        : "<base64>",
     *   "photoVisageType"    : "image/jpeg",
     *   "qrCodeImage"        : "<base64>",
     *   "qrCodeContenu"      : "https://cimis.cm/verify/MAT-2023-12345"
     * }
     *
     * @return 200 OK avec message de confirmation, ou 404 si militaire introuvable,
     *         ou 400 si données invalides.
     */
    @PostMapping("/biometrie")
    public ResponseEntity<?> recevoirBiometrie(@RequestBody DonneeBiometriqueDTO dto) {
        try {
            String message = biometriqueService.recevoirDonneesBiometriques(dto);
            return ResponseEntity.ok(message);
        } catch (RuntimeException e) {
            return ResponseEntity.status(404).body(e.getMessage());
        } catch (Exception e) {
            return ResponseEntity.status(400).body("Erreur lors de la réception : " + e.getMessage());
        }
    }

    /**
     * Consultation des données biométriques stockées pour un militaire.
     *
     * Exemple : GET /api/import/cimis/biometrie?matricule=MAT-2023-12345
     *
     * @param matricule Le matricule du militaire.
     * @return 200 OK avec le DTO de réponse, ou 404 si introuvable.
     */
    @GetMapping("/biometrie")
    public ResponseEntity<?> consulterBiometrie(@RequestParam String matricule) {
        try {
            DonneeBiometriqueReponseDTO reponse = biometriqueService.consulter(matricule);
            return ResponseEntity.ok(reponse);
        } catch (RuntimeException e) {
            return ResponseEntity.status(404).body(e.getMessage());
        } catch (Exception e) {
            return ResponseEntity.status(500).body("Erreur interne : " + e.getMessage());
        }
    }
}
