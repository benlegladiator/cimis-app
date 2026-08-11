package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDateTime;
import java.util.UUID;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Notification {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String titre;
    private String message;
    
    @Enumerated(EnumType.STRING)
    private TypeNotification type;

    private LocalDateTime dateCreation;
    private boolean lu; // Changé de lue à lu pour cohérence avec les méthodes

    // Destinataire (peut être un utilisateur précis ou un rôle dans une unité)
    @ManyToOne
    private Utilisateur destinataire;

    @ManyToOne
    private Compagnie compagnieConcernee;

    @ManyToOne
    private Bataillon bataillonConcerne;

    @ManyToOne
    private Brigade brigadeConcernee;

    @ManyToOne
    private RegionMilitaire regionConcernee;

    @ManyToOne
    private DossierAdministratif dossierConcerne;

    @ManyToOne
    private Militaire militaire; // Ajout du champ manquant

    @PrePersist
    protected void onCreate() {
        this.dateCreation = LocalDateTime.now();
        this.lu = false;
    }

    // Getters/Setters manuels au cas où Lombok ne les génère pas comme attendu pour le booléen
    public boolean isLu() { return lu; }
    public void setLu(boolean lu) { this.lu = lu; }
}
