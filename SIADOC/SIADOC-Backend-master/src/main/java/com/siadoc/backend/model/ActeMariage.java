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
public class ActeMariage {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String numeroActe;
    private LocalDate dateMariage;
    private String lieuMariage;
    private String nomConjoint;

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] fichier;

    private String fichierNom;
    @JsonIgnore
    private String fichierType;

    // RELATION AVEC MODULE ETAT CIVIL
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "etat_civil_id")
    @JsonIgnore
    private EtatCivil etatCivil;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNumeroActe() { return numeroActe; }
    public void setNumeroActe(String numeroActe) { this.numeroActe = numeroActe; }
    public LocalDate getDateMariage() { return dateMariage; }
    public void setDateMariage(LocalDate dateMariage) { this.dateMariage = dateMariage; }
    public String getLieuMariage() { return lieuMariage; }
    public void setLieuMariage(String lieuMariage) { this.lieuMariage = lieuMariage; }
    public String getNomConjoint() { return nomConjoint; }
    public void setNomConjoint(String nomConjoint) { this.nomConjoint = nomConjoint; }
    public byte[] getFichier() { return fichier; }
    public void setFichier(byte[] fichier) { this.fichier = fichier; }
    public String getFichierNom() { return fichierNom; }
    public void setFichierNom(String fichierNom) { this.fichierNom = fichierNom; }
    public String getFichierType() { return fichierType; }
    public void setFichierType(String fichierType) { this.fichierType = fichierType; }
    public EtatCivil getEtatCivil() { return etatCivil; }
    public void setEtatCivil(EtatCivil etatCivil) { this.etatCivil = etatCivil; }
}
