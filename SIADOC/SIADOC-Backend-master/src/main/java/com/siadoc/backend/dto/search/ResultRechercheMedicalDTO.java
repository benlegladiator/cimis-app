package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheMedicalDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String nature;
    private String lieu;
    private LocalDate date;

    public ResultRechercheMedicalDTO() {}

    public ResultRechercheMedicalDTO(String nom, String prenom, String grade, String arme, String nature, String lieu, LocalDate date) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.nature = nature;
        this.lieu = lieu;
        this.date = date;
    }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getNature() { return nature; }
    public void setNature(String nature) { this.nature = nature; }
    public String getLieu() { return lieu; }
    public void setLieu(String lieu) { this.lieu = lieu; }
    public LocalDate getDate() { return date; }
    public void setDate(LocalDate date) { this.date = date; }
}
