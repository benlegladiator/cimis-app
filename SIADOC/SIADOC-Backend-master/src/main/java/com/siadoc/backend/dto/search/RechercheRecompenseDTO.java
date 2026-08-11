package com.siadoc.backend.dto.search;

import lombok.Data;

@Data
public class RechercheRecompenseDTO {

    // Filtres militaires
    private String nom;
    private String prenom;
    private String grade;
    private String arme;

    // Filtres récompense
    private String designation;
    private String texte;
    private Integer annee;

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
    public Integer getAnnee() { return annee; }
    public void setAnnee(Integer annee) { this.annee = annee; }
}