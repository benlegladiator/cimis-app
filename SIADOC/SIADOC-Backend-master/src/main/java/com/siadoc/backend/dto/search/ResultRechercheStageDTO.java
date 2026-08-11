package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheStageDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String designation;
    private String lieu;
    private LocalDate dateStage;

    public ResultRechercheStageDTO() {}

    public ResultRechercheStageDTO(String nom, String prenom, String grade, String arme, String designation, String lieu, LocalDate dateStage) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.designation = designation;
        this.lieu = lieu;
        this.dateStage = dateStage;
    }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getLieu() { return lieu; }
    public void setLieu(String lieu) { this.lieu = lieu; }
    public LocalDate getDateStage() { return dateStage; }
    public void setDateStage(LocalDate dateStage) { this.dateStage = dateStage; }
}
