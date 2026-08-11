package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDate;
import java.util.UUID;
import com.fasterxml.jackson.annotation.JsonIgnore;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class RecompenseItem {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String designation; // Ex: "Médaille de la Valeur"
    private String texte;       // Ex: "Décret N°..."
    private LocalDate dateEffet;

    // Fichier joint (Scan de la décoration)
    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] documentData;
    private String documentNom;
    private String documentType;

    @ManyToOne
    @JoinColumn(name = "module_id")
    @JsonIgnore
    private RecompenseModule module;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getTexte() { return texte; }
    public void setTexte(String texte) { this.texte = texte; }
    public LocalDate getDateEffet() { return dateEffet; }
    public void setDateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; }
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public RecompenseModule getModule() { return module; }
    public void setModule(RecompenseModule module) { this.module = module; }
}
