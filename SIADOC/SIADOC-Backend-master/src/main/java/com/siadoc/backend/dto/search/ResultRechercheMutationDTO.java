package com.siadoc.backend.dto.search;

import java.time.LocalDate;

public class ResultRechercheMutationDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private String emploi;
    private String unite;
    private String ville;
    private LocalDate dateMutation;

    public ResultRechercheMutationDTO() {}

    public ResultRechercheMutationDTO(String nom, String prenom, String grade, String arme, String emploi, String unite, String ville, LocalDate dateMutation) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.emploi = emploi;
        this.unite = unite;
        this.ville = ville;
        this.dateMutation = dateMutation;
    }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getEmploi() { return emploi; }
    public void setEmploi(String emploi) { this.emploi = emploi; }
    public String getUnite() { return unite; }
    public void setUnite(String unite) { this.unite = unite; }
    public String getVille() { return ville; }
    public void setVille(String ville) { this.ville = ville; }
    public LocalDate getDateMutation() { return dateMutation; }
    public void setDateMutation(LocalDate dateMutation) { this.dateMutation = dateMutation; }
}
