package com.siadoc.backend.model;
import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class InformationsPersonnelles {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    // ===== RELATION =====
    @OneToOne
    @JoinColumn(name = "etat_civil_id", nullable = false, unique = true)
    @JsonIgnore
    private EtatCivil etatCivil;

    // ===== CHAMPS =====
    private String nom;
    private String prenom;
    private String sexe;
    private String numeroCNI;
    private String situationMatrimoniale;
    private String regime;

    private Integer nombreConjoints;
    private Integer nombreEnfants;

    private String telephone;

    private String ppcaNom;
    private String ppcaTelephone;
    private String ppcaLien;

    @Column(length = 1000)
    private String adresseComplete;

    // TEEC
    private String regionOrigine;    // Ex: "EXT-NORD", "SUD"
    private String languesParlees;   // Ex: "FRANÇAIS/ANGLAIS"

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public EtatCivil getEtatCivil() { return etatCivil; }
    public void setEtatCivil(EtatCivil etatCivil) { this.etatCivil = etatCivil; }
    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getSexe() { return sexe; }
    public void setSexe(String sexe) { this.sexe = sexe; }
    public String getNumeroCNI() { return numeroCNI; }
    public void setNumeroCNI(String numeroCNI) { this.numeroCNI = numeroCNI; }
    public String getSituationMatrimoniale() { return situationMatrimoniale; }
    public void setSituationMatrimoniale(String situationMatrimoniale) { this.situationMatrimoniale = situationMatrimoniale; }
    public String getRegime() { return regime; }
    public void setRegime(String regime) { this.regime = regime; }
    public Integer getNombreConjoints() { return nombreConjoints; }
    public void setNombreConjoints(Integer nombreConjoints) { this.nombreConjoints = nombreConjoints; }
    public Integer getNombreEnfants() { return nombreEnfants; }
    public void setNombreEnfants(Integer nombreEnfants) { this.nombreEnfants = nombreEnfants; }
    public String getTelephone() { return telephone; }
    public void setTelephone(String telephone) { this.telephone = telephone; }
    public String getPpcaNom() { return ppcaNom; }
    public void setPpcaNom(String ppcaNom) { this.ppcaNom = ppcaNom; }
    public String getPpcaTelephone() { return ppcaTelephone; }
    public void setPpcaTelephone(String ppcaTelephone) { this.ppcaTelephone = ppcaTelephone; }
    public String getPpcaLien() { return ppcaLien; }
    public void setPpcaLien(String ppcaLien) { this.ppcaLien = ppcaLien; }
    public String getAdresseComplete() { return adresseComplete; }
    public void setAdresseComplete(String adresseComplete) { this.adresseComplete = adresseComplete; }
    public String getRegionOrigine() { return regionOrigine; }
    public void setRegionOrigine(String regionOrigine) { this.regionOrigine = regionOrigine; }
    public String getLanguesParlees() { return languesParlees; }
    public void setLanguesParlees(String languesParlees) { this.languesParlees = languesParlees; }
}
