package com.siadoc.backend.dto.search;

import lombok.Data;
import java.util.UUID;

@Data
public class SearchResultDTO {

    private UUID militaireId;
    private String nom;
    private String prenom;
    private String grade;
    private String arme;
    private String sexe;

    public UUID getMilitaireId() { return militaireId; }
    public void setMilitaireId(UUID militaireId) { this.militaireId = militaireId; }
    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public String getSexe() { return sexe; }
    public void setSexe(String sexe) { this.sexe = sexe; }
}