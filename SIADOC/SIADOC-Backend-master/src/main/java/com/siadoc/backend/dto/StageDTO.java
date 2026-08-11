package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class StageDTO {
    private UUID id;
    private String designation;
    private String diplome;
    private String ville;
    private String pays;
    private LocalDate dateObtention;
    private String document; // Nom du fichier pour affichage

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getDiplome() { return diplome; }
    public void setDiplome(String diplome) { this.diplome = diplome; }
    public String getVille() { return ville; }
    public void setVille(String ville) { this.ville = ville; }
    public String getPays() { return pays; }
    public void setPays(String pays) { this.pays = pays; }
    public LocalDate getDateObtention() { return dateObtention; }
    public void setDateObtention(LocalDate dateObtention) { this.dateObtention = dateObtention; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
}
