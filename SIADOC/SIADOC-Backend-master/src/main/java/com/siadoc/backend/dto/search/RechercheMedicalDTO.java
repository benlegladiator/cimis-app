package com.siadoc.backend.dto.search;

import lombok.Data;

@Data
public class RechercheMedicalDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String typeExamen;
    private String lieu;
    private Integer annee;
    private String aptitude;

    private String nature;
    private Integer taux;

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getTypeExamen() { return typeExamen; }
    public void setTypeExamen(String typeExamen) { this.typeExamen = typeExamen; }
    public String getLieu() { return lieu; }
    public void setLieu(String lieu) { this.lieu = lieu; }
    public Integer getAnnee() { return annee; }
    public void setAnnee(Integer annee) { this.annee = annee; }
    public String getAptitude() { return aptitude; }
    public void setAptitude(String aptitude) { this.aptitude = aptitude; }
    public String getNature() { return nature; }
    public void setNature(String nature) { this.nature = nature; }
    public Integer getTaux() { return taux; }
    public void setTaux(Integer taux) { this.taux = taux; }
}
