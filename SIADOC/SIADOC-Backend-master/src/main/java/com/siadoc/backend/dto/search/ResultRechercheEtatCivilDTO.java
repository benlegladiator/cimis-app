package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheEtatCivilDTO {

    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    private String lieuEtablissement;
    private LocalDate dateEtablissement;

    public ResultRechercheEtatCivilDTO() {}

    public ResultRechercheEtatCivilDTO(
            String nom,
            String prenom,
            String grade,
            String arme,
            String lieuEtablissement,
            LocalDate dateEtablissement) {

        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.lieuEtablissement = lieuEtablissement;
        this.dateEtablissement = dateEtablissement;
    }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getLieuEtablissement() { return lieuEtablissement; }
    public void setLieuEtablissement(String lieuEtablissement) { this.lieuEtablissement = lieuEtablissement; }
    public LocalDate getDateEtablissement() { return dateEtablissement; }
    public void setDateEtablissement(LocalDate dateEtablissement) { this.dateEtablissement = dateEtablissement; }
}