package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheDiplomeDTO {

    private String nom;
    private String prenom;
    private String grade;
    private String arme;

    private String designation;
    private String ecole;
    private LocalDate dateObtention;

    public ResultRechercheDiplomeDTO() {}

    public ResultRechercheDiplomeDTO(
            String nom,
            String prenom,
            String grade,
            String arme,
            String designation,
            String ecole,
            LocalDate dateObtention
    ) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.designation = designation;
        this.ecole = ecole;
        this.dateObtention = dateObtention;
    }

    public String getNom() {
        return nom;
    }

    public void setNom(String nom) { this.nom = nom; }

    public String getPrenom() {
        return prenom;
    }

    public void setPrenom(String prenom) { this.prenom = prenom; }

    public String getGrade() {
        return grade;
    }

    public void setGrade(String grade) { this.grade = grade; }

    public String getArme() {
        return arme;
    }

    public void setArme(String arme) { this.arme = arme; }

    public String getDesignation() {
        return designation;
    }

    public void setDesignation(String designation) { this.designation = designation; }

    public String getEcole() {
        return ecole;
    }

    public void setEcole(String ecole) { this.ecole = ecole; }

    public LocalDate getDateObtention() {
        return dateObtention;
    }

    public void setDateObtention(LocalDate dateObtention) { this.dateObtention = dateObtention; }
}