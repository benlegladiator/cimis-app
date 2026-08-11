package com.siadoc.backend.dto.search;



public class RechercheDiplomeDTO {



    private String nom;

    private String prenom;

    private String grade;

    private String arme;



    private String designation;

    private String ecole;



    private Integer annee;



    public RechercheDiplomeDTO() {

    }



    public String getNom() {

        return nom;

    }



    public void setNom(String nom) {

        this.nom = nom;

    }



    public String getPrenom() {

        return prenom;

    }



    public void setPrenom(String prenom) {

        this.prenom = prenom;

    }



    public String getGrade() {

        return grade;

    }



    public void setGrade(String grade) {

        this.grade = grade;

    }



    public String getArme() {

        return arme;

    }



    public void setArme(String arme) {

        this.arme = arme;

    }



    public String getDesignation() {

        return designation;

    }



    public void setDesignation(String designation) {

        this.designation = designation;

    }



    public String getEcole() {

        return ecole;

    }



    public void setEcole(String ecole) {

        this.ecole = ecole;

    }



    public Integer getAnnee() {

        return annee;

    }



    public void setAnnee(Integer annee) {

        this.annee = annee;

    }

}