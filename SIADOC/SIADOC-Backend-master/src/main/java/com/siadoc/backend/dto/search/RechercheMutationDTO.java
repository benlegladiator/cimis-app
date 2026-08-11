package com.siadoc.backend.dto.search;

import lombok.Data;

@Data
public class RechercheMutationDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String provenance;
    private String destination;
    private Integer annee;

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getProvenance() { return provenance; }
    public void setProvenance(String provenance) { this.provenance = provenance; }
    public String getDestination() { return destination; }
    public void setDestination(String destination) { this.destination = destination; }
    public Integer getAnnee() { return annee; }
    public void setAnnee(Integer annee) { this.annee = annee; }
}
