package com.siadoc.backend.dto.search;

public class ResultRechercheNotationDTO {
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    
    private Integer annee;
    private String appreciationGenerale;
    private Double notation;

    public ResultRechercheNotationDTO() {}

    public ResultRechercheNotationDTO(String nom, String prenom, String grade, String arme, Integer annee, String appreciationGenerale, Double notation) {
        this.nom = nom;
        this.prenom = prenom;
        this.grade = grade;
        this.arme = arme;
        this.annee = annee;
        this.appreciationGenerale = appreciationGenerale;
        this.notation = notation;
    }

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
    public String getAppreciationGenerale() { return appreciationGenerale; }
    public void setAppreciationGenerale(String appreciationGenerale) { this.appreciationGenerale = appreciationGenerale; }
    public Double getNotation() { return notation; }
    public void setNotation(Double notation) { this.notation = notation; }
}
