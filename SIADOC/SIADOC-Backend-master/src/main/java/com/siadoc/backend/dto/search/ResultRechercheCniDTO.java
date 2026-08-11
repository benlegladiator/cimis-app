package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheCniDTO {

    private String nom;
    private String prenom;
    private String grade;
    private String arme;

    private String numero;
    private LocalDate dateExpiration;

    public ResultRechercheCniDTO() {}

    public ResultRechercheCniDTO(
            String nom,
            String prenom,
            String grade,
            String arme,
            String numero,
            LocalDate dateExpiration
    ){
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.numero = numero;
        this.dateExpiration = dateExpiration;
    }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getNumero() { return numero; }
    public void setNumero(String numero) { this.numero = numero; }
    public LocalDate getDateExpiration() { return dateExpiration; }
    public void setDateExpiration(LocalDate dateExpiration) { this.dateExpiration = dateExpiration; }
}
