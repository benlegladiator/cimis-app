package com.siadoc.backend.dto;

import lombok.Data;
import java.util.List;

@Data
public class MutationsModuleDTO {
    private List<MutationItemDTO> affectations;
    private List<MutationItemDTO> fonctions;

    public List<MutationItemDTO> getAffectations() { return affectations; }
    public void setAffectations(List<MutationItemDTO> affectations) { this.affectations = affectations; }
    public List<MutationItemDTO> getFonctions() { return fonctions; }
    public void setFonctions(List<MutationItemDTO> fonctions) { this.fonctions = fonctions; }
}
