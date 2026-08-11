package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class RecompenseDTO {
    private UUID id;
    private String designation;
    private String texte;
    private LocalDate dateEffet;
    private String documentNom;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getTexte() { return texte; }
    public void setTexte(String texte) { this.texte = texte; }
    public LocalDate getDateEffet() { return dateEffet; }
    public void setDateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
}
