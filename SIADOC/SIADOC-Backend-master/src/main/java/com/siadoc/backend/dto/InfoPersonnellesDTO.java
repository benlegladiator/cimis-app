package com.siadoc.backend.dto;

import lombok.Data;

@Data
public class InfoPersonnellesDTO {
    private String sexe;
    private String numeroCNI;
    private String situationMatrimoniale;
    private String regime;
    private Integer nombreConjoints;
    private Integer nombreEnfants;
    private String telephone;
    private String ppcaNom;
    private String ppcaTelephone;
    private String ppcaLien;
    private String adresseComplete;
    private String regionOrigine;
    private String languesParlees;
}
