package com.siadoc.backend.dto.export;

import java.time.LocalDate;

/**
 * DTO simplifié exposant les informations essentielles d'un militaire
 * pour les applications partenaires via l'API d'export.
 *
 * Champs exposés :
 *   - nom, prenom
 *   - matricule (matricule militaire, ou matricule solde si absent)
 *   - dateNaissance
 *   - corps (ex: AA, AM, AT — lu dans le dossier/carrière)
 *   - grade, dateGrade
 *   - sexe
 *   - numeroCNI (numéro de la CNI, lu dans la table cni via l'état civil du dossier)
 */
public class InfoMilitaireDTO {

    private String nom;
    private String prenom;
    private String matricule;
    private LocalDate dateNaissance;
    private String corps;
    private String grade;
    private LocalDate dateGrade;
    private String sexe;
    private String numeroCNI;

    public InfoMilitaireDTO() {}

    public InfoMilitaireDTO(String nom, String prenom, String matricule,
                            LocalDate dateNaissance, String corps,
                            String grade, LocalDate dateGrade, String sexe,
                            String numeroCNI) {
        this.nom = nom;
        this.prenom = prenom;
        this.matricule = matricule;
        this.dateNaissance = dateNaissance;
        this.corps = corps;
        this.grade = grade;
        this.dateGrade = dateGrade;
        this.sexe = sexe;
        this.numeroCNI = numeroCNI;
    }

    // ===== Getters & Setters =====

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }

    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }

    public String getMatricule() { return matricule; }
    public void setMatricule(String matricule) { this.matricule = matricule; }

    public LocalDate getDateNaissance() { return dateNaissance; }
    public void setDateNaissance(LocalDate dateNaissance) { this.dateNaissance = dateNaissance; }

    public String getCorps() { return corps; }
    public void setCorps(String corps) { this.corps = corps; }

    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }

    public LocalDate getDateGrade() { return dateGrade; }
    public void setDateGrade(LocalDate dateGrade) { this.dateGrade = dateGrade; }

    public String getSexe() { return sexe; }
    public void setSexe(String sexe) { this.sexe = sexe; }

    public String getNumeroCNI() { return numeroCNI; }
    public void setNumeroCNI(String numeroCNI) { this.numeroCNI = numeroCNI; }
}
