package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import jakarta.persistence.*;
import lombok.*;
import java.util.List;
import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonBackReference;

@Entity
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class EtatCivil {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private java.util.UUID id;

    // Relation 1-1 avec DossierAdministratif
    @OneToOne
    @JoinColumn(name = "dossier_id", nullable = false, unique = true)
    @JsonIgnore
    private DossierAdministratif dossier;


    @OneToMany(mappedBy = "etatCivil", cascade = CascadeType.ALL)
    private java.util.List<CNI> cnis;

    // Actes de naissance

    @OneToMany(mappedBy = "etatCivil", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<ActeNaissance> actesNaissance;

    // Actes de mariage

    @OneToMany(mappedBy = "etatCivil", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<ActeMariage> actesMariage;

    // Actes de décès

    @OneToMany(mappedBy = "etatCivil", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<ActeDeces> actesDeces;

    // Actes de divorce

    @OneToMany(mappedBy = "etatCivil", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<ActeDivorce> actesDivorce;

    // Jugements supplétifs

    @OneToMany(mappedBy = "etatCivil", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<JugementSuppletif> jugementsSuppletifs;

    // Informations Personnelles (pour TEEC)
    @OneToOne(mappedBy = "etatCivil", cascade = CascadeType.ALL)
    private InformationsPersonnelles informationsPersonnelles;

    public java.util.UUID getId() { return id; }
    public void setId(java.util.UUID id) { this.id = id; }
    public DossierAdministratif getDossier() { return dossier; }
    public void setDossier(DossierAdministratif dossier) { this.dossier = dossier; }
    public java.util.List<CNI> getCnis() { return cnis; }
    public void setCnis(java.util.List<CNI> cnis) { this.cnis = cnis; }
    public List<ActeNaissance> getActesNaissance() { return actesNaissance; }
    public void setActesNaissance(List<ActeNaissance> actesNaissance) { this.actesNaissance = actesNaissance; }
    public List<ActeMariage> getActesMariage() { return actesMariage; }
    public void setActesMariage(List<ActeMariage> actesMariage) { this.actesMariage = actesMariage; }
    public List<ActeDeces> getActesDeces() { return actesDeces; }
    public void setActesDeces(List<ActeDeces> actesDeces) { this.actesDeces = actesDeces; }
    public List<ActeDivorce> getActesDivorce() { return actesDivorce; }
    public void setActesDivorce(List<ActeDivorce> actesDivorce) { this.actesDivorce = actesDivorce; }
    public List<JugementSuppletif> getJugementsSuppletifs() { return jugementsSuppletifs; }
    public void setJugementsSuppletifs(List<JugementSuppletif> jugementsSuppletifs) { this.jugementsSuppletifs = jugementsSuppletifs; }
    public InformationsPersonnelles getInformationsPersonnelles() { return informationsPersonnelles; }
    public void setInformationsPersonnelles(InformationsPersonnelles informationsPersonnelles) { this.informationsPersonnelles = informationsPersonnelles; }
}
