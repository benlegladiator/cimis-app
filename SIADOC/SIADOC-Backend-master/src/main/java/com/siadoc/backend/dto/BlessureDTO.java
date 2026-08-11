package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class BlessureDTO {
    private UUID id;
    private String nature;
    private String lieu;
    private String autorite;
    private LocalDate dateEffet;
    private String document; // Nom du fichier pour affichage

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNature() { return nature; }
    public void setNature(String nature) { this.nature = nature; }
    public String getLieu() { return lieu; }
    public void setLieu(String lieu) { this.lieu = lieu; }
    public String getAutorite() { return autorite; }
    public void setAutorite(String autorite) { this.autorite = autorite; }
    public LocalDate getDateEffet() { return dateEffet; }
    public void setDateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
}