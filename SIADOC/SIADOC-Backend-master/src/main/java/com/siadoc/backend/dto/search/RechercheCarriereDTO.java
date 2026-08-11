package com.siadoc.backend.dto.search;

import lombok.Data;

@Data
public class RechercheCarriereDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String poste;
    private String unite;
    private Integer annee;

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getPoste() { return poste; }
    public void setPoste(String poste) { this.poste = poste; }
    public String getUnite() { return unite; }
    public void setUnite(String unite) { this.unite = unite; }
    public Integer getAnnee() { return annee; }
    public void setAnnee(Integer annee) { this.annee = annee; }
}
