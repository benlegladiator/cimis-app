package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.LocalDate;
import java.util.UUID;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Pension {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String typeInvalidite;
    private LocalDate datePriseEffet;
    private String reference; // N° de décision
    private Integer taux;     // Pourcentage (0-100)

    // Gestion Fichier
    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] documentData;
    private String documentNom;
    private String documentType;

    @ManyToOne
    @JoinColumn(name = "module_id")
    @JsonIgnore
    private MedicalModule module;

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
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public MedicalModule getModule() { return module; }
    public void setModule(MedicalModule module) { this.module = module; }
}
