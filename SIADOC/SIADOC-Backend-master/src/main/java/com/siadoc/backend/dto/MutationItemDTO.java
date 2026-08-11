package com.siadoc.backend.dto;

import com.siadoc.backend.model.UniteMilitaire;
import com.siadoc.backend.model.VilleCameroun;
import lombok.Data;
import java.util.UUID;

@Data
public class MutationItemDTO {
    private UUID id;
    private String emploi;
    private UniteMilitaire unite;
    private VilleCameroun ville;
    private String numeroTexte;
    private String dateTexte;
    private String type;
    private String compagnieNom;
    private String documentNom;
    private String documentType;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getEmploi() { return emploi; }
    public void setEmploi(String emploi) { this.emploi = emploi; }
    public UniteMilitaire getUnite() { return unite; }
    public void setUnite(UniteMilitaire unite) { this.unite = unite; }
    public VilleCameroun getVille() { return ville; }
    public void setVille(VilleCameroun ville) { this.ville = ville; }
    public String getNumeroTexte() { return numeroTexte; }
    public void setNumeroTexte(String numeroTexte) { this.numeroTexte = numeroTexte; }
    public String getDateTexte() { return dateTexte; }
    public void setDateTexte(String dateTexte) { this.dateTexte = dateTexte; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }

    public String getType() { return type; }
    public void setType(String type) { this.type = type; }
    public String getCompagnieNom() { return compagnieNom; }
    public void setCompagnieNom(String compagnieNom) { this.compagnieNom = compagnieNom; }
}
