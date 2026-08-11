package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDateTime;
import java.util.UUID;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonIgnore;


@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
@Entity
public class DossierAdministratif {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @OneToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "militaire_id", nullable = false , unique = true)
    private Militaire militaire;

    private LocalDateTime dateCreation;

    private LocalDateTime dateArchivage;

    @Enumerated(EnumType.STRING)
    private StatutDossier statut;

    @Enumerated(EnumType.STRING)
    private StatutValidation statutValidation = StatutValidation.EN_COURS;

    @Column(columnDefinition = "TEXT")
    private String motifRefus;

    @Column(columnDefinition = "TEXT")
    private String modulesModifies;

    @ManyToOne
    @JoinColumn(name = "secteur_id", nullable = true, updatable = false)
    private SecteurMilitaire secteur;

    @ManyToOne
    @JoinColumn(name = "compagnie_id")
    private Compagnie compagnie;

    @ManyToOne
    @JoinColumn(name = "unite_organisationnelle_id")
    private UniteOrganisationnelle uniteOrganisationnelle;

    // ===== MODULES =====
    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private EtatCivil etatCivil;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private AvancementModule avancementModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private Carriere carriere;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private MutationsModule mutationsModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private DiplomeModule diplomeModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private NotationModule notationModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private PunitionModule punitionModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private MedicalModule medicalModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private RecompenseModule recompenseModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private StageModule stageModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private CampagneMilitaireModule campagneMilitaireModule;

    @OneToOne(mappedBy = "dossier", cascade = CascadeType.ALL)
    private HabillementModule habillementModule;

    // ===== GETTERS & SETTERS =====
    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public Militaire getMilitaire() { return militaire; }
    public void setMilitaire(Militaire militaire) { this.militaire = militaire; }
    public LocalDateTime getDateCreation() { return dateCreation; }
    public void setDateCreation(LocalDateTime dateCreation) { this.dateCreation = dateCreation; }
    public LocalDateTime getDateArchivage() { return dateArchivage; }
    public void setDateArchivage(LocalDateTime dateArchivage) { this.dateArchivage = dateArchivage; }
    public StatutDossier getStatut() { return statut; }
    public void setStatut(StatutDossier statut) { this.statut = statut; }
    public SecteurMilitaire getSecteur() { return secteur; }
    public void setSecteur(SecteurMilitaire secteur) { this.secteur = secteur; }
    public Compagnie getCompagnie() { return compagnie; }
    public void setCompagnie(Compagnie compagnie) { this.compagnie = compagnie; }
    public UniteOrganisationnelle getUniteOrganisationnelle() { return uniteOrganisationnelle; }
    public void setUniteOrganisationnelle(UniteOrganisationnelle uniteOrganisationnelle) { this.uniteOrganisationnelle = uniteOrganisationnelle; }
    public EtatCivil getEtatCivil() { return etatCivil; }
    public void setEtatCivil(EtatCivil etatCivil) { this.etatCivil = etatCivil; }
    public AvancementModule getAvancementModule() { return avancementModule; }
    public void setAvancementModule(AvancementModule avancementModule) { this.avancementModule = avancementModule; }
    public Carriere getCarriere() { return carriere; }
    public void setCarriere(Carriere carriere) { this.carriere = carriere; }
    public MutationsModule getMutationsModule() { return mutationsModule; }
    public void setMutationsModule(MutationsModule mutationsModule) { this.mutationsModule = mutationsModule; }
    public DiplomeModule getDiplomeModule() { return diplomeModule; }
    public void setDiplomeModule(DiplomeModule diplomeModule) { this.diplomeModule = diplomeModule; }
    public NotationModule getNotationModule() { return notationModule; }
    public void setNotationModule(NotationModule notationModule) { this.notationModule = notationModule; }
    public PunitionModule getPunitionModule() { return punitionModule; }
    public void setPunitionModule(PunitionModule punitionModule) { this.punitionModule = punitionModule; }
    public MedicalModule getMedicalModule() { return medicalModule; }
    public void setMedicalModule(MedicalModule medicalModule) { this.medicalModule = medicalModule; }
    public RecompenseModule getRecompenseModule() { return recompenseModule; }
    public void setRecompenseModule(RecompenseModule recompenseModule) { this.recompenseModule = recompenseModule; }
    public StageModule getStageModule() { return stageModule; }
    public void setStageModule(StageModule stageModule) { this.stageModule = stageModule; }
    public CampagneMilitaireModule getCampagneMilitaireModule() { return campagneMilitaireModule; }
    public void setCampagneMilitaireModule(CampagneMilitaireModule campagneMilitaireModule) { this.campagneMilitaireModule = campagneMilitaireModule; }
    public HabillementModule getHabillementModule() { return habillementModule; }
    public void setHabillementModule(HabillementModule habillementModule) { this.habillementModule = habillementModule; }

}
