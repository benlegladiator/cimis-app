package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.LocalDate;
import java.util.UUID;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class ActeNaissance {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String numeroActe;
    private LocalDate dateEtablissement;
    private String lieuEtablissement;

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] fichier;

    private String fichierNom;
    private String fichierType;

    @ManyToOne
    @JoinColumn(name = "etat_civil_id")
    @JsonIgnore
    private EtatCivil etatCivil;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNumeroActe() { return numeroActe; }
    public void setNumeroActe(String numeroActe) { this.numeroActe = numeroActe; }
    public LocalDate getDateEtablissement() { return dateEtablissement; }
    public void setDateEtablissement(LocalDate dateEtablissement) { this.dateEtablissement = dateEtablissement; }
    public String getLieuEtablissement() { return lieuEtablissement; }
    public void setLieuEtablissement(String lieuEtablissement) { this.lieuEtablissement = lieuEtablissement; }
    public byte[] getFichier() { return fichier; }
    public void setFichier(byte[] fichier) { this.fichier = fichier; }
    public String getFichierNom() { return fichierNom; }
    public void setFichierNom(String fichierNom) { this.fichierNom = fichierNom; }
    public String getFichierType() { return fichierType; }
    public void setFichierType(String fichierType) { this.fichierType = fichierType; }
    public EtatCivil getEtatCivil() { return etatCivil; }
    public void setEtatCivil(EtatCivil etatCivil) { this.etatCivil = etatCivil; }
}
