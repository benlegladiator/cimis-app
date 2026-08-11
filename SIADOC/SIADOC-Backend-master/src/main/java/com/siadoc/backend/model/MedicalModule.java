package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonBackReference;
import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.siadoc.backend.model.DossierAdministratif;
import jakarta.persistence.*;
import lombok.*;
import java.util.List;
import java.util.UUID;

@Entity
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class MedicalModule {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @OneToOne
    @JoinColumn(name = "dossier_id", nullable = false)
    @JsonIgnore
    private DossierAdministratif dossier;

    // Liste 1 : Les Blessures
    @OneToMany(mappedBy = "module", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<Blessure> blessures;

    // Liste 2 : Les Pensions
    @OneToMany(mappedBy = "module", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<Pension> pensions;

    // Liste 3 : Les Arrêts de travail
    @OneToMany(mappedBy = "module", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<ArretTravail> arretsTravail;

    // Liste 4 : Les Documents médicaux
    @OneToMany(mappedBy = "module", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<DocumentMedical> documentsMedicaux;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public DossierAdministratif getDossier() { return dossier; }
    public void setDossier(DossierAdministratif dossier) { this.dossier = dossier; }
    public List<Blessure> getBlessures() { return blessures; }
    public void setBlessures(List<Blessure> blessures) { this.blessures = blessures; }
    public List<Pension> getPensions() { return pensions; }
    public void setPensions(List<Pension> pensions) { this.pensions = pensions; }
    public List<ArretTravail> getArretsTravail() { return arretsTravail; }
    public void setArretsTravail(List<ArretTravail> arretsTravail) { this.arretsTravail = arretsTravail; }
    public List<DocumentMedical> getDocumentsMedicaux() { return documentsMedicaux; }
    public void setDocumentsMedicaux(List<DocumentMedical> documentsMedicaux) { this.documentsMedicaux = documentsMedicaux; }
}