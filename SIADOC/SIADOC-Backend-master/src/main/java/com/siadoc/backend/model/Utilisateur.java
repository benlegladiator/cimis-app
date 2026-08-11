package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import jakarta.persistence.*;
import lombok.*;

import java.util.UUID;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
public class Utilisateur {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @Column(nullable = false, unique = true)
    private String username;

    @Column(nullable = false)
    private String password;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    private Role role;

    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "secteur_id")
    private SecteurMilitaire secteur;

    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "compagnie_id")
    private Compagnie compagnie;

    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "bataillon_id")
    private Bataillon bataillon;

    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "brigade_id")
    private Brigade brigade;

    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "region_id")
    private RegionMilitaire region;

    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "unite_organisationnelle_id")
    private UniteOrganisationnelle uniteOrganisationnelle;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getUsername() { return username; }
    public void setUsername(String username) { this.username = username; }
    public String getPassword() { return password; }
    public void setPassword(String password) { this.password = password; }
    public Role getRole() { return role; }
    public void setRole(Role role) { this.role = role; }
    public SecteurMilitaire getSecteur() { return secteur; }
    public void setSecteur(SecteurMilitaire secteur) { this.secteur = secteur; }
    public Compagnie getCompagnie() { return compagnie; }
    public void setCompagnie(Compagnie compagnie) { this.compagnie = compagnie; }
    public Bataillon getBataillon() { return bataillon; }
    public void setBataillon(Bataillon bataillon) { this.bataillon = bataillon; }
    public Brigade getBrigade() { return brigade; }
    public void setBrigade(Brigade brigade) { this.brigade = brigade; }
    public RegionMilitaire getRegion() { return region; }
    public void setRegion(RegionMilitaire region) { this.region = region; }
    public UniteOrganisationnelle getUniteOrganisationnelle() { return uniteOrganisationnelle; }
    public void setUniteOrganisationnelle(UniteOrganisationnelle uniteOrganisationnelle) { this.uniteOrganisationnelle = uniteOrganisationnelle; }
}