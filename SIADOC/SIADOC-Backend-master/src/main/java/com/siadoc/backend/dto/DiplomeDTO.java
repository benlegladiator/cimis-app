package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class DiplomeDTO {
    private UUID id;
    private String designation;
    private String ecole;
    private LocalDate dateObtention;
    private String document; // Nom du fichier pour l'affichage "📄 nom.pdf"

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getEcole() { return ecole; }
    public void setEcole(String ecole) { this.ecole = ecole; }
    public LocalDate getDateObtention() { return dateObtention; }
    public void setDateObtention(LocalDate dateObtention) { this.dateObtention = dateObtention; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
}