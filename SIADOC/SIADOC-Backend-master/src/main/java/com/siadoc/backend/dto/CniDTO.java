package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;

@Data
public class CniDTO {
    private String numero;
    private LocalDate dateDelivrance;
    private LocalDate dateExpiration;
    private String lieuDelivrance;
    private String fichierNom;
}
