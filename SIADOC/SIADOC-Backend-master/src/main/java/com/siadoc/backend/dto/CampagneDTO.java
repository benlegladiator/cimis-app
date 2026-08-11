package com.siadoc.backend.dto;

import com.siadoc.backend.model.LieuStage;
import lombok.Data;

import java.time.LocalDate;
import java.util.UUID;

@Data
public class CampagneDTO {
    private UUID id;
    private String designation;
    private String signataire;
    private LocalDate date;
    private String document; // Nom du fichier pour affichage

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getSignataire() { return signataire; }
    public void setSignataire(String signataire) { this.signataire = signataire; }
    public LocalDate getDate() { return date; }
    public void setDate(LocalDate date) { this.date = date; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
}
