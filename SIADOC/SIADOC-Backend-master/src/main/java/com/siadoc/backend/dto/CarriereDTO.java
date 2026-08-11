package com.siadoc.backend.dto;

import com.siadoc.backend.model.*;
import lombok.Data;
import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@Data
public class CarriereDTO {
    // Infos éditables
    private CorpsArmee corps;
    private String arme;
    private OrigineRecrutement origine;
    private String cnim;
    private TypeStructure formationStructure; // Mappé vers typeStructure
    private String compagnie; // Mappé vers nomCompagnie
    private String observationEmploi;
    private String aptitudeOps;

    // Infos calculées (Read-only pour le front)
    private String statut; // Vient de Militaire
    private String matriculeSolde; // Vient de Militaire
    private String matriculeMilitaire; // Vient de Militaire

    // NOUVEAUX CHAMPS Section 1 - Anciennetés
    private String ancienneteService; // "15 ans, 3 mois"
    private String ancienneteGrade; // "5 ans, 2 mois"
    private Integer anneesProlongation; // Total cumulé (ex: 3)
    private boolean estArchive; // Pour afficher "Calcul figé" dans le front
    private LocalDate dateCalculReference; // Date jusqu'à laquelle on a calculé

    // Pour afficher la liste des prolongations sous forme de tableau
    private List<Avancement> prolongationsDetails;

    // Liste des réengagements
    private List<ReengagementDTO> reengagements;
    private List<AdmissionSocDTO> admissionSocs;

    // Info fichier
    private String nomFichier;

    public CorpsArmee getCorps() {
        return corps;
    }

    public void setCorps(CorpsArmee corps) {
        this.corps = corps;
    }

    public String getArme() {
        return arme;
    }

    public void setArme(String arme) {
        this.arme = arme;
    }

    public OrigineRecrutement getOrigine() {
        return origine;
    }

    public void setOrigine(OrigineRecrutement origine) {
        this.origine = origine;
    }

    public String getCnim() {
        return cnim;
    }

    public void setCnim(String cnim) {
        this.cnim = cnim;
    }

    public TypeStructure getFormationStructure() {
        return formationStructure;
    }

    public void setFormationStructure(TypeStructure formationStructure) {
        this.formationStructure = formationStructure;
    }

    public String getCompagnie() {
        return compagnie;
    }

    public void setCompagnie(String compagnie) {
        this.compagnie = compagnie;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    public String getMatriculeSolde() {
        return matriculeSolde;
    }
 
    public void setMatriculeSolde(String matriculeSolde) {
        this.matriculeSolde = matriculeSolde;
    }
 
    public String getMatriculeMilitaire() {
        return matriculeMilitaire;
    }
 
    public void setMatriculeMilitaire(String matriculeMilitaire) {
        this.matriculeMilitaire = matriculeMilitaire;
    }

    public String getAncienneteService() {
        return ancienneteService;
    }

    public void setAncienneteService(String ancienneteService) {
        this.ancienneteService = ancienneteService;
    }

    public String getAncienneteGrade() {
        return ancienneteGrade;
    }

    public void setAncienneteGrade(String ancienneteGrade) {
        this.ancienneteGrade = ancienneteGrade;
    }

    public Integer getAnneesProlongation() {
        return anneesProlongation;
    }

    public void setAnneesProlongation(Integer anneesProlongation) {
        this.anneesProlongation = anneesProlongation;
    }

    public boolean isEstArchive() {
        return estArchive;
    }

    public void setEstArchive(boolean estArchive) {
        this.estArchive = estArchive;
    }

    public LocalDate getDateCalculReference() {
        return dateCalculReference;
    }

    public void setDateCalculReference(LocalDate dateCalculReference) {
        this.dateCalculReference = dateCalculReference;
    }

    public List<Avancement> getProlongationsDetails() {
        return prolongationsDetails;
    }

    public void setProlongationsDetails(List<Avancement> prolongationsDetails) {
        this.prolongationsDetails = prolongationsDetails;
    }

    public List<ReengagementDTO> getReengagements() {
        return reengagements;
    }

    public void setReengagements(List<ReengagementDTO> reengagements) {
        this.reengagements = reengagements;
    }

    public List<AdmissionSocDTO> getAdmissionSocs() {
        return admissionSocs;
    }

    public void setAdmissionSocs(List<AdmissionSocDTO> admissionSocs) {
        this.admissionSocs = admissionSocs;
    }

    public String getNomFichier() {
        return nomFichier;
    }

    public void setNomFichier(String nomFichier) {
        this.nomFichier = nomFichier;
    }
}
