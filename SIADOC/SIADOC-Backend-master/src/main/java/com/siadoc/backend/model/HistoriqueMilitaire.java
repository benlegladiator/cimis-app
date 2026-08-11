package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.time.LocalDateTime;
import java.util.UUID;

@Entity
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class HistoriqueMilitaire {

    @Id
    @GeneratedValue(strategy = GenerationType.AUTO)
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "militaire_id", nullable = false)
    private Militaire militaire;

    private String action; // e.g., "Création du dossier", "Modification de l'affectation"

    private LocalDateTime dateAction;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "utilisateur_id", nullable = true)
    private Utilisateur acteur; // Who performed the action

    private String details; // Extra JSON or text for more info optionally

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public Militaire getMilitaire() { return militaire; }
    public void setMilitaire(Militaire militaire) { this.militaire = militaire; }
    public String getAction() { return action; }
    public void setAction(String action) { this.action = action; }
    public LocalDateTime getDateAction() { return dateAction; }
    public void setDateAction(LocalDateTime dateAction) { this.dateAction = dateAction; }
    public Utilisateur getActeur() { return acteur; }
    public void setActeur(Utilisateur acteur) { this.acteur = acteur; }
    public String getDetails() { return details; }
    public void setDetails(String details) { this.details = details; }

    public static HistoriqueMilitaireBuilder builder() {
        return new HistoriqueMilitaireBuilder();
    }

    public static class HistoriqueMilitaireBuilder {
        private Militaire militaire;
        private String action;
        private LocalDateTime dateAction;
        private Utilisateur acteur;
        private String details;

        public HistoriqueMilitaireBuilder militaire(Militaire militaire) { this.militaire = militaire; return this; }
        public HistoriqueMilitaireBuilder action(String action) { this.action = action; return this; }
        public HistoriqueMilitaireBuilder dateAction(LocalDateTime dateAction) { this.dateAction = dateAction; return this; }
        public HistoriqueMilitaireBuilder acteur(Utilisateur acteur) { this.acteur = acteur; return this; }
        public HistoriqueMilitaireBuilder details(String details) { this.details = details; return this; }

        public HistoriqueMilitaire build() {
            HistoriqueMilitaire h = new HistoriqueMilitaire();
            h.setMilitaire(militaire);
            h.setAction(action);
            h.setDateAction(dateAction);
            h.setActeur(acteur);
            h.setDetails(details);
            return h;
        }
    }
}
