package com.siadoc.backend.model;

import jakarta.persistence.*;
import java.util.UUID;
import lombok.*;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class ArchiveDecede {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String nom;
    private String prenom;
    private String matricule;
    private String armee;
    private String numeroCase;
    private UUID militaireId;
    private Integer anneeContingent;
    private String grade;
    @Enumerated(EnumType.STRING)
    private CategorieMilitaire categorie;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public String getPrenom() { return prenom; }
    public void setPrenom(String prenom) { this.prenom = prenom; }
    public String getMatricule() { return matricule; }
    public void setMatricule(String matricule) { this.matricule = matricule; }
    public String getArmee() { return armee; }
    public void setArmee(String armee) { this.armee = armee; }
    public String getNumeroCase() { return numeroCase; }
    public void setNumeroCase(String numeroCase) { this.numeroCase = numeroCase; }
    public UUID getMilitaireId() { return militaireId; }
    public void setMilitaireId(UUID militaireId) { this.militaireId = militaireId; }
    public Integer getAnneeContingent() { return anneeContingent; }
    public void setAnneeContingent(Integer anneeContingent) { this.anneeContingent = anneeContingent; }
    public String getGrade() { return grade; }
    public void setGrade(String grade) { this.grade = grade; }
    public CategorieMilitaire getCategorie() { return categorie; }
    public void setCategorie(CategorieMilitaire categorie) { this.categorie = categorie; }
}
