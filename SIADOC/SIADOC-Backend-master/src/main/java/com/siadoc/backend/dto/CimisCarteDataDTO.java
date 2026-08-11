package com.siadoc.backend.dto;

import com.fasterxml.jackson.annotation.JsonProperty;
import lombok.Data;

@Data
public class CimisCarteDataDTO {
    @JsonProperty("matricule_militaire")
    private String matriculeMilitaire;

    @JsonProperty("matricule_cimis")
    private String matriculeCimis;

    private String nom;
    private String prenom;

    @JsonProperty("qr_code")
    private String qrCode;

    @JsonProperty("empreinte")
    private String empreinte;

    @JsonProperty("date_generation_carte")
    private String dateGenerationCarte;

    @JsonProperty("date_expiration_carte")
    private String dateExpirationCarte;

    @JsonProperty("statut_carte")
    private String statutCarte;

    @JsonProperty("timestamp_envoi")
    private String timestampEnvoi;
}
