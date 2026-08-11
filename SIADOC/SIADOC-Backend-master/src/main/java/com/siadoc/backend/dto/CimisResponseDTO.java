package com.siadoc.backend.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;
import lombok.Data;

@Data
@JsonIgnoreProperties(ignoreUnknown = true)
public class CimisResponseDTO {
    // Champs principaux
    private String matricule;
    private String nom;
    private String prenom;
    private String grade;
    private String unite;
    
    // Dates
    @JsonProperty("date_naissance")
    private String dateNaissance;
    
    @JsonProperty("date_enrolement")
    private String dateEnrolement;
    
    @JsonProperty("date_dernier_grade")
    private String dateDernierGrade;
    
    // Identification
    private String sexe;
    @JsonProperty("matricule_militaire")
    private String matriculeMilitaire;
    
    @JsonProperty("matricule_cimis")
    private String matriculeCimis;
    
    @JsonProperty("numero_cni")
    private String numeroCni;
    
    // Caractéristiques physiques
    private String taille;
    private String poids;
    @JsonProperty("groupe_sanguin")
    private String groupeSanguin;
    
    // Médias et biométrie
    @JsonProperty("photo_base64")
    private String photoBase64;
    
    @JsonProperty("empreinte_data")
    private String empreinteData;
    
    @JsonProperty("code_qr")
    private String codeQr;
    
    // Administration
    @JsonProperty("type_personnel")
    private String typePersonnel;
    
    private String statut;
    
    // Métadonnées
    @JsonProperty("source_system")
    private String sourceSystem;
    
    @JsonProperty("date_modification")
    private String dateModification;
    
    @JsonProperty("sync_status")
    private String syncStatus;
}
