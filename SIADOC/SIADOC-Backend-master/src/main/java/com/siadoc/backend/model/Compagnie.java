package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;

import java.util.UUID;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
public class Compagnie {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @Column(nullable = false)
    private String nom;

    @Column
    private String abreviation;

    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "bataillon_id")
    private Bataillon bataillon;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "secteur_id")
    @JsonIgnore
    private SecteurMilitaire secteur;

    @Column
    private String localisation;

    public String getLocalisation() { return localisation; }
    public void setLocalisation(String localisation) { this.localisation = localisation; }

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getAbreviation() { return abreviation; }
    public void setAbreviation(String abreviation) { this.abreviation = abreviation; }
    public Bataillon getBataillon() { return bataillon; }
    public void setBataillon(Bataillon bataillon) { this.bataillon = bataillon; }
    public SecteurMilitaire getSecteur() { return secteur; }
    public void setSecteur(SecteurMilitaire secteur) { this.secteur = secteur; }
}