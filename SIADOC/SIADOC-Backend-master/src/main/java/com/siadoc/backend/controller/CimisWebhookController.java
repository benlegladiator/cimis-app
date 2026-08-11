package com.siadoc.backend.controller;

import com.siadoc.backend.dto.CimisCarteDataDTO;
import com.siadoc.backend.dto.CimisWebhookDTO;
import com.siadoc.backend.dto.DonneeBiometriqueDTO;
import com.siadoc.backend.service.DonneeBiometriqueService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

/**
 * Contrôleur passerelle (Webhook) créé spécifiquement pour absorber le format 
 * d'exportation natif de l'application CIMIS en toute transparence.
 */
@RestController
@RequestMapping("/api/cimis")
public class CimisWebhookController {

    private final DonneeBiometriqueService biometriqueService;

    public CimisWebhookController(DonneeBiometriqueService biometriqueService) {
        this.biometriqueService = biometriqueService;
    }

    @PostMapping("/recevoir_carte")
    public ResponseEntity<?> recevoirCarte(@RequestBody CimisWebhookDTO webhook) {
        if (webhook == null || webhook.getData() == null) {
            return ResponseEntity.badRequest().body("Données invalides : 'data' manquant");
        }

        CimisCarteDataDTO data = webhook.getData();

        // 1. Traduction du format CIMIS (PHP) vers le format standard SIADOC
        DonneeBiometriqueDTO dto = new DonneeBiometriqueDTO();
        
        dto.setMatricule(data.getMatriculeMilitaire()); // C'est leur clé primaire croisée
        dto.setNumeroCIM(data.getMatriculeCimis());     // Ce qu'ils appellent "matricule", c'est le N° de carte
        
        dto.setQrCodeImage(data.getQrCode());
        dto.setEmpreinteDoigt1(data.getEmpreinte());
        
        // 2. Traitement standard dans le service (qui ignorera lui-même les préfixes Base64 PHP)
        try {
            String message = biometriqueService.recevoirDonneesBiometriques(dto);
            return ResponseEntity.ok(message);
        } catch (RuntimeException e) {
            return ResponseEntity.status(404).body(e.getMessage());
        } catch (Exception e) {
            return ResponseEntity.status(500).body("Erreur : " + e.getMessage());
        }
    }
}
