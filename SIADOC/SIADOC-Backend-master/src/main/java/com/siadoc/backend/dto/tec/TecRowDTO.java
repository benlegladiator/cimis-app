package com.siadoc.backend.dto.tec;

import lombok.Data;

@Data
public class TecRowDTO {

    private String nom;
    private String prenom;

    private String grade;

    private String emploi;

    private String diplomeMilitaire;
    private String diplomeCivil;

    private String region;
    private String langue;

    private String dateEntreeService;
    private String datePriseFonction;

    private String observation;
}