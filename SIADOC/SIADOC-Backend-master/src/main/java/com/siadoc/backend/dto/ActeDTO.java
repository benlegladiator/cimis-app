package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;

@Data
public class ActeDTO {
    private String numeroActe;
    private String numeroJugement;
    private LocalDate dateEtablissement;
    private LocalDate dateMariage;
    private LocalDate dateDeces;
    private LocalDate dateJugement;
    private String lieuEtablissement;
    private String lieuMariage;
    private String lieuDeces;
    private String tribunal;
    private String nomConjoint;
    private String fichierNom;
    
    public ActeDTO() {}
}
