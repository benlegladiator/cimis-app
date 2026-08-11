package com.siadoc.backend.dto.search;

import lombok.Data;





@Data

public class RechercheEtatCivilDTO {

    private String nom;

    private String prenom;

    private String grade;

    private String arme;



    private Integer annee;

    private String lieu;



    public String getNom() { return nom; }

    public void setNom(String nom) { this.nom = nom; }

    public String getPrenom() { return prenom; }

    public void setPrenom(String prenom) { this.prenom = prenom; }

    public String getGrade() { return grade; }

    public void setGrade(String grade) { this.grade = grade; }

    public String getArme() { return arme; }

    public void setArme(String arme) { this.arme = arme; }

    public Integer getAnnee() { return annee; }

    public void setAnnee(Integer annee) { this.annee = annee; }

    public String getLieu() { return lieu; }

    public void setLieu(String lieu) { this.lieu = lieu; }

}

