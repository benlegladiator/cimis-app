package com.siadoc.backend.dto;

import lombok.Data;
import java.util.List;

@Data
public class EtatCivilDTO {
    private InfoPersonnellesDTO informationsPersonnelles;
    private List<CniDTO> cnis;
    private List<ActeDTO> actesNaissance;
    private List<ActeDTO> actesMariage;
    private List<ActeDTO> actesDeces;
    private List<ActeDTO> actesDivorce;
    private List<ActeDTO> jugementsSuppletifs;
}
