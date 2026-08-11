package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheCarriereDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String poste;
    private String unite;
    private LocalDate datePrisePoste;

    public ResultRechercheCarriereDTO() {}

    public ResultRechercheCarriereDTO(String nom, String prenom, String grade, String arme, String poste, String unite, LocalDate datePrisePoste) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.poste = poste;
        this.unite = unite;
        this.datePrisePoste = datePrisePoste;
    }

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
    public LocalDate getDatePrisePoste() { return datePrisePoste; }
    public void setDatePrisePoste(LocalDate datePrisePoste) { this.datePrisePoste = datePrisePoste; }
}
