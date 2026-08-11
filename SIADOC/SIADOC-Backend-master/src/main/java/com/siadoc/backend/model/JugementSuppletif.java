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
public class JugementSuppletif {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String numeroJugement;
    private String objet;
    private String tribunal;
    private LocalDate dateJugement;

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

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNumeroJugement() { return numeroJugement; }
    public void setNumeroJugement(String numeroJugement) { this.numeroJugement = numeroJugement; }
    public String getObjet() { return objet; }
    public void setObjet(String objet) { this.objet = objet; }
    public String getTribunal() { return tribunal; }
    public void setTribunal(String tribunal) { this.tribunal = tribunal; }
    public LocalDate getDateJugement() { return dateJugement; }
    public void setDateJugement(LocalDate dateJugement) { this.dateJugement = dateJugement; }
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public EtatCivil getEtatCivil() { return etatCivil; }
    public void setEtatCivil(EtatCivil etatCivil) { this.etatCivil = etatCivil; }
}