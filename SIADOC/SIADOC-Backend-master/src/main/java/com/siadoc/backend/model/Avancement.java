package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;
import com.fasterxml.jackson.annotation.JsonIgnore;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class Avancement {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String avancement;      // Grade obtenu
    private String numeroTexte;
    private String signataire;
    private LocalDate dateEffet;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    private TypeAvancement typeAvancement;

    private Integer dureeAnnees;

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] fichier;

    private String fichierNom;
    private String fichierType;

    @ManyToOne
    @JoinColumn(name = "module_id")
    @JsonIgnore
    private AvancementModule module;

    public boolean isProlongation() {
        return this.typeAvancement == TypeAvancement.PROLONGATION_SERVICE;
    }

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getAvancement() { return avancement; }
    public void setAvancement(String avancement) { this.avancement = avancement; }
    public String getNumeroTexte() { return numeroTexte; }
    public void setNumeroTexte(String numeroTexte) { this.numeroTexte = numeroTexte; }
    public String getSignataire() { return signataire; }
    public void setSignataire(String signataire) { this.signataire = signataire; }
    public LocalDate getDateEffet() { return dateEffet; }
    public void setDateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; }
    public TypeAvancement getTypeAvancement() { return typeAvancement; }
    public void setTypeAvancement(TypeAvancement typeAvancement) { this.typeAvancement = typeAvancement; }
    public Integer getDureeAnnees() { return dureeAnnees; }
    public void setDureeAnnees(Integer dureeAnnees) { this.dureeAnnees = dureeAnnees; }
    public byte[] getFichier() { return fichier; }
    public void setFichier(byte[] fichier) { this.fichier = fichier; }
    public String getFichierNom() { return fichierNom; }
    public void setFichierNom(String fichierNom) { this.fichierNom = fichierNom; }
    public String getFichierType() { return fichierType; }
    public void setFichierType(String fichierType) { this.fichierType = fichierType; }
    public AvancementModule getModule() { return module; }
    public void setModule(AvancementModule module) { this.module = module; }
}
