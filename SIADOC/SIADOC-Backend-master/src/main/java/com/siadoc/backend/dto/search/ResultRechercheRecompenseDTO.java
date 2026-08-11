package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheRecompenseDTO {

    private String nom;
    private String prenom;
    private String grade;
    private String arme;

    private String designation;
    private String texte;
    private LocalDate dateEffet;

    public ResultRechercheRecompenseDTO() {}

    public ResultRechercheRecompenseDTO(String nom, String prenom, String grade, String arme, String designation, String texte, LocalDate dateEffet) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.designation = designation;
        this.texte = texte;
        this.dateEffet = dateEffet;
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
    public String getTexte() { return texte; }
    public void setTexte(String texte) { this.texte = texte; }
    public LocalDate getDateEffet() { return dateEffet; }
    public void setDateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; }
}