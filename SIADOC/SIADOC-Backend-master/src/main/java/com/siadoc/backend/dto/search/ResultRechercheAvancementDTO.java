package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheAvancementDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String nouveauGrade;
    private LocalDate dateAvancement;
    private String typeAvancement;

    public ResultRechercheAvancementDTO() {}

    public ResultRechercheAvancementDTO(String nom, String prenom, String grade, String arme, String nouveauGrade, LocalDate dateAvancement, String typeAvancement) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.nouveauGrade = nouveauGrade;
        this.dateAvancement = dateAvancement;
        this.typeAvancement = typeAvancement;
    }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getNouveauGrade() { return nouveauGrade; }
    public void setNouveauGrade(String nouveauGrade) { this.nouveauGrade = nouveauGrade; }
    public LocalDate getDateAvancement() { return dateAvancement; }
    public void setDateAvancement(LocalDate dateAvancement) { this.dateAvancement = dateAvancement; }
    public String getTypeAvancement() { return typeAvancement; }
    public void setTypeAvancement(String typeAvancement) { this.typeAvancement = typeAvancement; }
}
