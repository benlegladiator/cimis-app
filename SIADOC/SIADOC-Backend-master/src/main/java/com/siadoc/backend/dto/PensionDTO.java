package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class PensionDTO {
    private UUID id;
    private String typeInvalidite;
    private LocalDate datePriseEffet;
    private String reference;
    private Integer taux;
    private String document; // Nom du fichier pour affichage

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getTypeInvalidite() { return typeInvalidite; }
    public void setTypeInvalidite(String typeInvalidite) { this.typeInvalidite = typeInvalidite; }
    public LocalDate getDatePriseEffet() { return datePriseEffet; }
    public void setDatePriseEffet(LocalDate datePriseEffet) { this.datePriseEffet = datePriseEffet; }
    public String getReference() { return reference; }
    public void setReference(String reference) { this.reference = reference; }
    public Integer getTaux() { return taux; }
    public void setTaux(Integer taux) { this.taux = taux; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
}
