package com.siadoc.backend.dto;

import lombok.Data;

@Data
public class QualitesDTO {
    private CritereDTO presentation;
    private CritereDTO valeurPhysique;
    private CritereDTO valeurMorale;
    private CritereDTO instruction;
    private CritereDTO commandement;

    public CritereDTO getPresentation() { return presentation; }
    public void setPresentation(CritereDTO presentation) { this.presentation = presentation; }
    public CritereDTO getValeurPhysique() { return valeurPhysique; }
    public void setValeurPhysique(CritereDTO valeurPhysique) { this.valeurPhysique = valeurPhysique; }
    public CritereDTO getValeurMorale() { return valeurMorale; }
    public void setValeurMorale(CritereDTO valeurMorale) { this.valeurMorale = valeurMorale; }
    public CritereDTO getInstruction() { return instruction; }
    public void setInstruction(CritereDTO instruction) { this.instruction = instruction; }
    public CritereDTO getCommandement() { return commandement; }
    public void setCommandement(CritereDTO commandement) { this.commandement = commandement; }
}
