package com.siadoc.backend.dto;

import java.util.UUID;

public class ArchiveResultDTO {

    private UUID militaireId;
    private String nom;
    private String prenom;
    private String matricule;
    private String numeroCase;
    private boolean physique;
    private String armee;
    private Integer anneeContingent;
    private String grade;
    private String categorie;

    // Constructeur pour les archives physiques (avec armee + anneeContingent)
    public ArchiveResultDTO(
            UUID militaireId,
            String nom,
            String prenom,
            String matricule,
            String numeroCase,
            String armee,
            Integer anneeContingent,
            String grade,
            String categorie) {
        this.militaireId = militaireId;
        this.nom = nom;
        this.prenom = prenom;
        this.matricule = matricule;
        this.numeroCase = numeroCase;
        this.physique = (numeroCase != null);
        this.armee = armee;
        this.anneeContingent = anneeContingent;
        this.grade = grade;
        this.categorie = categorie;
    }

    // Constructeur legacy pour les archives numériques (dossiers administratifs)
    public ArchiveResultDTO(
            UUID militaireId,
            String nom,
            String prenom,
            String matricule,
            String numeroCase) {
        this.militaireId = militaireId;
        this.nom = nom;
        this.prenom = prenom;
        this.matricule = matricule;
        this.numeroCase = numeroCase;
        this.physique = (numeroCase != null);
    }

    public UUID getMilitaireId() { return militaireId; }
    public String getNom() { return nom; }
    public String getPrenom() { return prenom; }
    public String getMatricule() { return matricule; }
    public String getNumeroCase() { return numeroCase; }
    public boolean isPhysique() { return physique; }
    public String getArmee() { return armee; }
    public Integer getAnneeContingent() { return anneeContingent; }
    public String getGrade() { return grade; }
    public String getCategorie() { return categorie; }
}