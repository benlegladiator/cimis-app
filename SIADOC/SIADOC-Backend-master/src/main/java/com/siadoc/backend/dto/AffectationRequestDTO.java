package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class AffectationRequestDTO {
    private UUID compagnieId;
    private String numeroTexte;
    private LocalDate dateTexte;
    private String emploi;
    private String motif; // Optionnel

    public AffectationRequestDTO() {}

    public UUID getCompagnieId() { return compagnieId; }
    public void setCompagnieId(UUID compagnieId) { this.compagnieId = compagnieId; }
    public String getNumeroTexte() { return numeroTexte; }
    public void setNumeroTexte(String numeroTexte) { this.numeroTexte = numeroTexte; }
    public LocalDate getDateTexte() { return dateTexte; }
    public void setDateTexte(LocalDate dateTexte) { this.dateTexte = dateTexte; }
    public String getEmploi() { return emploi; }
    public void setEmploi(String emploi) { this.emploi = emploi; }
    public String getMotif() { return motif; }
    public void setMotif(String motif) { this.motif = motif; }
}
