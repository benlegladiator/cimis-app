package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class PunitionDTO {
    private UUID id;
    private String designation;
    private String texte;
    private LocalDate dateEffet;
    private String document; // Nom du fichier pour affichage

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getTexte() { return texte; }
    public void setTexte(String texte) { this.texte = texte; }
    public LocalDate getDateEffet() { return dateEffet; }
    public void setDateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
}