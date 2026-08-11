package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheCampagneMilitaireDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String campagne;
    private LocalDate date;

    public ResultRechercheCampagneMilitaireDTO() {}

    public ResultRechercheCampagneMilitaireDTO(String nom, String prenom, String grade, String arme, String campagne, LocalDate date) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.campagne = campagne;
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
    public String getCampagne() { return campagne; }
    public void setCampagne(String campagne) { this.campagne = campagne; }
    public LocalDate getDate() { return date; }
    public void setDate(LocalDate date) { this.date = date; }
}
