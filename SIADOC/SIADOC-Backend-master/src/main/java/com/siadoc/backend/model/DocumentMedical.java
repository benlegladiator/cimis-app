package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonBackReference;
import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.siadoc.backend.model.DossierAdministratif;
import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class DocumentMedical {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @ManyToOne
    @JoinColumn(name = "module_id", nullable = false)
    @JsonIgnore
    private MedicalModule module;

    @Column(name = "titre")
    private String titre;

    @Column(name = "description")
    private String description;

    @Column(name = "date_document")
    private LocalDate dateDocument;

    @Column(name = "document_nom")
    private String documentNom;

    @Column(name = "document_data")
    @Lob
    private byte[] documentData;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public MedicalModule getModule() { return module; }
    public void setModule(MedicalModule module) { this.module = module; }
    public String getTitre() { return titre; }
    public void setTitre(String titre) { this.titre = titre; }
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    public LocalDate getDateDocument() { return dateDocument; }
    public void setDateDocument(LocalDate dateDocument) { this.dateDocument = dateDocument; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
}
