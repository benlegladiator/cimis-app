package com.siadoc.backend.model;

import jakarta.persistence.*;
import java.time.LocalDate;
import java.util.UUID;
import lombok.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;
import jakarta.persistence.Basic;
import jakarta.persistence.FetchType;
import com.fasterxml.jackson.annotation.JsonIgnore;

import jakarta.persistence.Lob;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class Militaire {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String nom;
    private String prenom;
    private LocalDate dateNaissance;

    @Column(unique = true)
    private String matriculeMilitaire;

    @Column(unique = true, nullable = false)
    private String matriculeSolde;

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] photo;

    private String photoNom;
    private String photoType;

    private String grade;
    
    @Enumerated(EnumType.STRING)
    private CategorieMilitaire categorie;
    
    private LocalDate dateGrade;

    private Integer echelon;
    private LocalDate dateEchelon;
    private LocalDate dateService;

    private String armeService;
    private String statut;

    private String lieuNaissance;
    private String sexe;
    private String aptitudeOps;

    @Enumerated(EnumType.STRING)
    private StatutValidation statutValidation;

    @Enumerated(EnumType.STRING)
    private EtatMilitaire etat;

    @Column(updatable = false)
    private java.time.LocalDateTime dateEnregistrement;

    private java.time.LocalDateTime dateMiseAJour;

    @PrePersist
    protected void onCreate() {
        this.dateEnregistrement = java.time.LocalDateTime.now();
        this.dateMiseAJour = java.time.LocalDateTime.now();
    }

    @PreUpdate
    protected void onUpdate() {
        this.dateMiseAJour = java.time.LocalDateTime.now();
    }

    // RELATION AVEC DOSSIER
    @OneToOne(mappedBy = "militaire", cascade = CascadeType.ALL)
    @JsonIgnore
    private DossierAdministratif dossier;

    // Getters & Setters
    public UUID getId() {
        return id;
    }

    public void setId(UUID id) {
        this.id = id;
    }

    public String getNom() {
        return nom;
    }

    public void setNom(String nom) {
        this.nom = nom;
    }

    public String getPrenom() {
        return prenom;
    }

    public void setPrenom(String prenom) {
        this.prenom = prenom;
    }

    public LocalDate getDateNaissance() {
        return dateNaissance;
    }

    public void setDateNaissance(LocalDate dateNaissance) {
        this.dateNaissance = dateNaissance;
    }

    public String getMatriculeMilitaire() {
        return matriculeMilitaire;
    }

    public void setMatriculeMilitaire(String matriculeMilitaire) {
        this.matriculeMilitaire = matriculeMilitaire;
    }

    public String getMatriculeSolde() {
        return matriculeSolde;
    }

    public void setMatriculeSolde(String matriculeSolde) {
        this.matriculeSolde = matriculeSolde;
    }

    public byte[] getPhoto() {
        return photo;
    }

    public void setPhoto(byte[] photo) {
        this.photo = photo;
    }

    public String getPhotoNom() {
        return photoNom;
    }

    public void setPhotoNom(String photoNom) {
        this.photoNom = photoNom;
    }

    public String getPhotoType() {
        return photoType;
    }

    public void setPhotoType(String photoType) {
        this.photoType = photoType;
    }

    public String getGrade() {
        return grade;
    }

    public void setGrade(String grade) {
        this.grade = grade;
        updateCategorie();
    }

    public CategorieMilitaire getCategorie() {
        return categorie;
    }

    public void setCategorie(CategorieMilitaire categorie) {
        this.categorie = categorie;
    }

    private void updateCategorie() {
        this.categorie = com.siadoc.backend.service.GradeService.determinerCategorie(this.grade, this.armeService);
    }

    public LocalDate getDateGrade() {
        return dateGrade;
    }

    public void setDateGrade(LocalDate dateGrade) {
        this.dateGrade = dateGrade;
    }

    public Integer getEchelon() {
        return echelon;
    }

    public void setEchelon(Integer echelon) {
        this.echelon = echelon;
    }

    public LocalDate getDateEchelon() {
        return dateEchelon;
    }

    public void setDateEchelon(LocalDate dateEchelon) {
        this.dateEchelon = dateEchelon;
    }

    public LocalDate getDateService() {
        return dateService;
    }

    public void setDateService(LocalDate dateService) {
        this.dateService = dateService;
    }

    public String getArmeService() {
        return armeService;
    }

    public void setArmeService(String armeService) {
        this.armeService = armeService;
        updateCategorie();
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    public String getLieuNaissance() {
        return lieuNaissance;
    }

    public void setLieuNaissance(String lieuNaissance) {
        this.lieuNaissance = lieuNaissance;
    }

    public String getSexe() {
        return sexe;
    }

    public void setSexe(String sexe) {
        this.sexe = sexe;
    }

    public String getAptitudeOps() {
        return aptitudeOps;
    }

    public void setAptitudeOps(String aptitudeOps) {
        this.aptitudeOps = aptitudeOps;
    }

    public StatutValidation getStatutValidation() {
        return statutValidation;
    }

    public void setStatutValidation(StatutValidation statutValidation) {
        this.statutValidation = statutValidation;
    }

    public EtatMilitaire getEtat() {
        return etat;
    }

    public void setEtat(EtatMilitaire etat) {
        this.etat = etat;
    }

    public DossierAdministratif getDossier() {
        return dossier;
    }

    public void setDossier(DossierAdministratif dossier) {
        this.dossier = dossier;
    }

    public java.time.LocalDateTime getDateEnregistrement() {
        return dateEnregistrement;
    }

    public void setDateEnregistrement(java.time.LocalDateTime dateEnregistrement) {
        this.dateEnregistrement = dateEnregistrement;
    }

}
