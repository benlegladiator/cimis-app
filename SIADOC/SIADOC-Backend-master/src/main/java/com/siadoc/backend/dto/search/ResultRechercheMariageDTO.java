package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheMariageDTO {

    private String nom;
    private String prenom;
    private String grade;
    private String arme;

    private String numeroActe;
    private LocalDate dateMariage;
    private String lieuMariage;
    private String nomConjoint;

    public ResultRechercheMariageDTO() {}

    public ResultRechercheMariageDTO(
            String nom,
            String prenom,
            String grade,
            String arme,
            String numeroActe,
            LocalDate dateMariage,
            String lieuMariage,
            String nomConjoint
    ) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.numeroActe = numeroActe;
        this.dateMariage = dateMariage;
        this.lieuMariage = lieuMariage;
        this.nomConjoint = nomConjoint;
    }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }

    public String getNumeroActe() { return numeroActe; }
    public void setNumeroActe(String numeroActe) { this.numeroActe = numeroActe; }
    public LocalDate getDateMariage() { return dateMariage; }
    public void setDateMariage(LocalDate dateMariage) { this.dateMariage = dateMariage; }
    public String getLieuMariage() { return lieuMariage; }
    public void setLieuMariage(String lieuMariage) { this.lieuMariage = lieuMariage; }
    public String getNomConjoint() { return nomConjoint; }
    public void setNomConjoint(String nomConjoint) { this.nomConjoint = nomConjoint; }
}