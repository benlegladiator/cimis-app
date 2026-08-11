package com.siadoc.backend.model;
import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.LocalDate;
import java.util.UUID;

@Entity
public class CNI {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    // Relation vers le module EtatCivil
    @ManyToOne
    @JsonIgnore
    @JoinColumn(name = "etat_civil_id", nullable = false)
    private EtatCivil etatCivil;

    // Champs métier
    private String numero;
    private LocalDate dateDelivrance;
    private LocalDate dateExpiration;
    private String lieuDelivrance;

    // Fichier PDF
    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] fichier;

    private String fichierNom;
    private String fichierType;

    public CNI() {}

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public EtatCivil getEtatCivil() { return etatCivil; }
    public void setEtatCivil(EtatCivil etatCivil) { this.etatCivil = etatCivil; }
    public String getNumero() { return numero; }
    public void setNumero(String numero) { this.numero = numero; }
    public LocalDate getDateDelivrance() { return dateDelivrance; }
    public void setDateDelivrance(LocalDate dateDelivrance) { this.dateDelivrance = dateDelivrance; }
    public LocalDate getDateExpiration() { return dateExpiration; }
    public void setDateExpiration(LocalDate dateExpiration) { this.dateExpiration = dateExpiration; }
    public String getLieuDelivrance() { return lieuDelivrance; }
    public void setLieuDelivrance(String lieuDelivrance) { this.lieuDelivrance = lieuDelivrance; }
    public byte[] getFichier() { return fichier; }
    public void setFichier(byte[] fichier) { this.fichier = fichier; }
    public String getFichierNom() { return fichierNom; }
    public void setFichierNom(String fichierNom) { this.fichierNom = fichierNom; }
    public String getFichierType() { return fichierType; }
    public void setFichierType(String fichierType) { this.fichierType = fichierType; }
}
