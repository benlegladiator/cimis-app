package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;
import java.time.LocalDate;
import java.util.UUID;

@Entity
public class ActeDeces {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String numeroActe;
    private LocalDate dateDeces;
    private String lieuDeces;

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] documentData;

    private String documentNom;
    private String documentType;

    @ManyToOne
    @JoinColumn(name = "etat_civil_id")
    @JsonIgnore
    private EtatCivil etatCivil;

    public ActeDeces() {}

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNumeroActe() { return numeroActe; }
    public void setNumeroActe(String numeroActe) { this.numeroActe = numeroActe; }
    public LocalDate getDateDeces() { return dateDeces; }
    public void setDateDeces(LocalDate dateDeces) { this.dateDeces = dateDeces; }
    public String getLieuDeces() { return lieuDeces; }
    public void setLieuDeces(String lieuDeces) { this.lieuDeces = lieuDeces; }
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public EtatCivil getEtatCivil() { return etatCivil; }
    public void setEtatCivil(EtatCivil etatCivil) { this.etatCivil = etatCivil; }
}