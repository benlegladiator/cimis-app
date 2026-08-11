package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;
import com.fasterxml.jackson.annotation.JsonIgnore;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;


import java.time.LocalDate;
import java.util.UUID;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class CampagneMilitaireItem {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String designation; // Ex: "LIBELLE"
    private String signataire;     // Ex: "MINDEF"

    private LocalDate date;

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
    private CampagneMilitaireModule module;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getSignataire() { return signataire; }
    public void setSignataire(String signataire) { this.signataire = signataire; }
    public LocalDate getDate() { return date; }
    public void setDate(LocalDate date) { this.date = date; }
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public CampagneMilitaireModule getModule() { return module; }
    public void setModule(CampagneMilitaireModule module) { this.module = module; }
}
