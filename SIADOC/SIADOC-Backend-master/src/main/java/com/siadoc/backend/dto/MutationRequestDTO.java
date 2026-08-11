package com.siadoc.backend.dto;

import com.siadoc.backend.model.*;
import lombok.Data;
import java.time.LocalDate;

@Data
public class MutationRequestDTO {
    private TypeMutation type; // OBLIGATOIRE : AFFECTATION ou FONCTION
    private String emploi;
    private UniteMilitaire unite;
    private VilleCameroun ville;
    private String numeroTexte;
    private LocalDate dateTexte;
    private java.util.UUID compagnieId; // Nouvelle unité de destination

    public TypeMutation getType() { return type; }
    public void setType(TypeMutation type) { this.type = type; }
    public String getEmploi() { return emploi; }
    public void setEmploi(String emploi) { this.emploi = emploi; }
    public UniteMilitaire getUnite() { return unite; }
    public void setUnite(UniteMilitaire unite) { this.unite = unite; }
    public VilleCameroun getVille() { return ville; }
    public void setVille(VilleCameroun ville) { this.ville = ville; }
    public String getNumeroTexte() { return numeroTexte; }
    public void setNumeroTexte(String numeroTexte) { this.numeroTexte = numeroTexte; }
    public LocalDate getDateTexte() { return dateTexte; }
    public void setDateTexte(LocalDate dateTexte) { this.dateTexte = dateTexte; }
    public java.util.UUID getCompagnieId() { return compagnieId; }
    public void setCompagnieId(java.util.UUID compagnieId) { this.compagnieId = compagnieId; }
}
