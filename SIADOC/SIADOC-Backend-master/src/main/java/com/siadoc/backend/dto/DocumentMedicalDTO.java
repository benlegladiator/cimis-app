package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class DocumentMedicalDTO {
    private UUID id;
    private String titre;
    private String description;
    private LocalDate dateDocument;
    private String document;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getTitre() { return titre; }
    public void setTitre(String titre) { this.titre = titre; }
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    public LocalDate getDateDocument() { return dateDocument; }
    public void setDateDocument(LocalDate dateDocument) { this.dateDocument = dateDocument; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
}
