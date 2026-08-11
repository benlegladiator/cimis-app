package com.siadoc.backend.controller;

import com.siadoc.backend.model.Notification;
import com.siadoc.backend.model.Utilisateur;
import com.siadoc.backend.repository.NotificationRepository;
import com.siadoc.backend.security.UserSession;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/notifications")
@RequiredArgsConstructor
public class NotificationController {

    private final NotificationRepository repository;
    private final UserSession userSession;

    @GetMapping("/ma-compagnie")
    public List<Notification> getMaCompagnie() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getCompagnie() == null) return List.of();
        List<Notification> notifs = repository.findByCompagnieConcerneeAndLuFalseOrderByDateCreationDesc(user.getCompagnie());
        repairLegacyNotifications(notifs);
        return notifs;
    }

    @GetMapping("/mon-bataillon")
    public List<Notification> getMonBataillon() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getBataillon() == null) return List.of();
        List<Notification> notifs = repository.findByBataillonConcerneAndLuFalseOrderByDateCreationDesc(user.getBataillon());
        repairLegacyNotifications(notifs);
        return notifs;
    }

    @GetMapping("/ma-brigade")
    public List<Notification> getMaBrigade() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getBrigade() == null) return List.of();
        List<Notification> notifs = repository.findByBrigadeConcerneeAndLuFalseOrderByDateCreationDesc(user.getBrigade());
        repairLegacyNotifications(notifs);
        return notifs;
    }

    @GetMapping("/ma-region")
    public List<Notification> getMaRegion() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getRegion() == null) return List.of();
        List<Notification> notifs = repository.findByRegionConcerneeAndLuFalseOrderByDateCreationDesc(user.getRegion());
        repairLegacyNotifications(notifs);
        return notifs;
    }

    @GetMapping("/drh")
    public List<Notification> getDrh() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getRole() != com.siadoc.backend.model.Role.DRH) return List.of();
        // Pour la DRH, on retourne les notifications qui lui sont adressées
        List<Notification> notifs = repository.findByDestinataireOrderByDateCreationDesc(user);
        repairLegacyNotifications(notifs);
        return notifs;
    }

    @GetMapping("/em-terre")
    public List<Notification> getEmTerre() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getRole() != com.siadoc.backend.model.Role.ETAT_MAJOR_TERRE) return List.of();
        List<Notification> notifs = repository.findByDestinataireOrderByDateCreationDesc(user);
        repairLegacyNotifications(notifs);
        return notifs;
    }

    @GetMapping("/em-air")
    public List<Notification> getEmAir() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getRole() != com.siadoc.backend.model.Role.ETAT_MAJOR_AIR) return List.of();
        List<Notification> notifs = repository.findByDestinataireOrderByDateCreationDesc(user);
        repairLegacyNotifications(notifs);
        return notifs;
    }

    @GetMapping("/em-marine")
    public List<Notification> getEmMarine() {
        Utilisateur user = userSession.getCurrentUser();
        if (user == null || user.getRole() != com.siadoc.backend.model.Role.ETAT_MAJOR_MARINE) return List.of();
        List<Notification> notifs = repository.findByDestinataireOrderByDateCreationDesc(user);
        repairLegacyNotifications(notifs);
        return notifs;
    }

    private void repairLegacyNotifications(List<Notification> notifs) {
        for (Notification n : notifs) {
            // Si pas de militaire mais dossier présent, on répare
            if (n.getMilitaire() == null && n.getDossierConcerne() != null) {
                n.setMilitaire(n.getDossierConcerne().getMilitaire());
            }
            // Si pas de type mais message de validation, on force le type
            if (n.getType() == null && n.getMessage() != null && n.getMessage().contains("soumis")) {
                n.setType(com.siadoc.backend.model.TypeNotification.VALIDATION_REQUISE);
            }
            // Si pas de titre, on en met un par défaut
            if (n.getTitre() == null) {
                if (n.getType() == com.siadoc.backend.model.TypeNotification.VALIDATION_REQUISE) {
                    n.setTitre("Validation requise" + (n.getMilitaire() != null ? " : " + n.getMilitaire().getNom() : ""));
                } else {
                    n.setTitre("Notification");
                }
            }
        }
    }

    @PostMapping("/{id}/lu")
    public void marquerCommeLue(@PathVariable UUID id) {
        repository.findById(id).ifPresent(n -> {
            n.setLu(true);
            repository.save(n);
        });
    }
}
