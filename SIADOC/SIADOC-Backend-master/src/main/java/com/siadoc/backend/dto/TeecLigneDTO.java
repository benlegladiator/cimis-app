package com.siadoc.backend.dto;

import java.time.LocalDate;

public class TeecLigneDTO {

    private String nomPrenom;
    private String grade;
    private Integer echelon;
    private String categorieX;
    private String categorieY;
    private String categorieZ;
    private String categorieC;
    private String qualifMilitaire;
    private String qualifCivile;
    private String regionOrigine;
    private String observationEmploi;
    private LocalDate dateEntreeService;
    private String languesParlees;
    private String aptitudeOps;
    private String emploiPoste;
    private String nomCompagnie;
    private LocalDate datePriseFonction;
    private String numero;

    public TeecLigneDTO() {}

    // Getters & Setters

    public String getNumero() { return numero; }
    public void setNumero(String numero) { this.numero = numero; }

    public LocalDate getDatePriseFonction() { return datePriseFonction; }
    public void setDatePriseFonction(LocalDate datePriseFonction) { this.datePriseFonction = datePriseFonction; }

    public String getNomCompagnie() { return nomCompagnie; }
    public void setNomCompagnie(String nomCompagnie) { this.nomCompagnie = nomCompagnie; }

    public String getNomPrenom() { return nomPrenom; }
    public void setNomPrenom(String nomPrenom) { this.nomPrenom = nomPrenom; }

    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }

    public Integer getEchelon() { return echelon; }
    public void setEchelon(Integer echelon) { this.echelon = echelon; }

    public String getCategorieX() { return categorieX; }
    public void setCategorieX(String categorieX) { this.categorieX = categorieX; }

    public String getCategorieY() { return categorieY; }
    public void setCategorieY(String categorieY) { this.categorieY = categorieY; }

    public String getCategorieZ() { return categorieZ; }
    public void setCategorieZ(String categorieZ) { this.categorieZ = categorieZ; }

    public String getCategorieC() { return categorieC; }
    public void setCategorieC(String categorieC) { this.categorieC = categorieC; }

    public String getQualifMilitaire() { return qualifMilitaire; }
    public void setQualifMilitaire(String qualifMilitaire) { this.qualifMilitaire = qualifMilitaire; }

    public String getQualifCivile() { return qualifCivile; }
    public void setQualifCivile(String qualifCivile) { this.qualifCivile = qualifCivile; }

    public String getRegionOrigine() { return regionOrigine; }
    public void setRegionOrigine(String regionOrigine) { this.regionOrigine = regionOrigine; }

    public String getObservationEmploi() { return observationEmploi; }
    public void setObservationEmploi(String observationEmploi) { this.observationEmploi = observationEmploi; }

    public LocalDate getDateEntreeService() { return dateEntreeService; }
    public void setDateEntreeService(LocalDate dateEntreeService) { this.dateEntreeService = dateEntreeService; }

    public String getLanguesParlees() { return languesParlees; }
    public void setLanguesParlees(String languesParlees) { this.languesParlees = languesParlees; }

    public String getAptitudeOps() { return aptitudeOps; }
    public void setAptitudeOps(String aptitudeOps) { this.aptitudeOps = aptitudeOps; }

    public String getEmploiPoste() { return emploiPoste; }
    public void setEmploiPoste(String emploiPoste) { this.emploiPoste = emploiPoste; }
}
