package com.siadoc.backend.dto;

import lombok.Data;

@Data
public class MensurationsDTO {
    private Double tailleCm;
    private Double poidsKg;
    private String tourDeTete;
    private String pointure;
    private String tailleVeste;
    private String taillePantalon;
}
