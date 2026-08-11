package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.siadoc.backend.model.DossierAdministratif;
import jakarta.persistence.*;
import lombok.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.util.UUID;

import java.util.List;


@Entity
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Carriere {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @OneToOne
    @JoinColumn(name = "dossier_id", nullable = false)
    @JsonIgnore
    private DossierAdministratif dossier;

    @Enumerated(EnumType.STRING)
    private CorpsArmee corps;

    private String arme;

    @Enumerated(EnumType.STRING)
    private OrigineRecrutement origine;

    private String cnim;

    @Enumerated(EnumType.STRING)
    private TypeStructure typeStructure;

    private String nomCompagnie;

    @OneToMany(mappedBy = "carriere", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<Reengagement> reengagements;

    @OneToMany(mappedBy = "carriere", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<AdmissionSoc> admissionSocs;

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] documentScan;

    private String documentNom;
    private String documentType;

    // TEEC
    private String observationEmploi; // Ex: "COM 31°BA", "PS", "ACB", "OD"
    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public DossierAdministratif getDossier() { return dossier; }
    public void setDossier(DossierAdministratif dossier) { this.dossier = dossier; }
    public CorpsArmee getCorps() { return corps; }
    public void setCorps(CorpsArmee corps) { this.corps = corps; }
    public String getArme() { return arme; }
    public void setArme(String arme) { this.arme = arme; }
    public OrigineRecrutement getOrigine() { return origine; }
    public void setOrigine(OrigineRecrutement origine) { this.origine = origine; }
    public String getCnim() { return cnim; }
    public void setCnim(String cnim) { this.cnim = cnim; }
    public TypeStructure getTypeStructure() { return typeStructure; }
    public void setTypeStructure(TypeStructure typeStructure) { this.typeStructure = typeStructure; }
    public String getNomCompagnie() { return nomCompagnie; }
    public void setNomCompagnie(String nomCompagnie) { this.nomCompagnie = nomCompagnie; }
    public List<Reengagement> getReengagements() { return reengagements; }
    public void setReengagements(List<Reengagement> reengagements) { this.reengagements = reengagements; }
    public List<AdmissionSoc> getAdmissionSocs() { return admissionSocs; }
    public void setAdmissionSocs(List<AdmissionSoc> admissionSocs) { this.admissionSocs = admissionSocs; }
    public byte[] getDocumentScan() { return documentScan; }
    public void setDocumentScan(byte[] documentScan) { this.documentScan = documentScan; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public String getObservationEmploi() { return observationEmploi; }
    public void setObservationEmploi(String observationEmploi) { this.observationEmploi = observationEmploi; }
}
