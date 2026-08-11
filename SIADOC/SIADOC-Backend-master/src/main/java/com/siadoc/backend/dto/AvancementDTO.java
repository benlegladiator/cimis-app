package com.siadoc.backend.dto;

import com.siadoc.backend.model.TypeAvancement;
import java.time.LocalDate;
import java.util.UUID;

public class AvancementDTO {
    private UUID id;
    private String avancement;
    private String numeroTexte;
    private String signataire;
    private LocalDate dateEffet;
    private TypeAvancement typeAvancement;
    private Integer dureeAnnees;
    private String fichierNom;

    public AvancementDTO() {}

    public AvancementDTO(UUID id, String avancement, String numeroTexte, String signataire, LocalDate dateEffet, TypeAvancement typeAvancement, Integer dureeAnnees, String fichierNom) {
        this.id = id;
        this.avancement = avancement;
        this.numeroTexte = numeroTexte;
        this.signataire = signataire;
        this.dateEffet = dateEffet;
        this.typeAvancement = typeAvancement;
        this.dureeAnnees = dureeAnnees;
        this.fichierNom = fichierNom;
    }

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getAvancement() { return avancement; }
    public void setAvancement(String avancement) { this.avancement = avancement; }
    public String getNumeroTexte() { return numeroTexte; }
    public void setNumeroTexte(String numeroTexte) { this.numeroTexte = numeroTexte; }
    public String getSignataire() { return signataire; }
    public void setSignataire(String signataire) { this.signataire = signataire; }
    public LocalDate getDateEffet() { return dateEffet; }
    public void setDateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; }
    public TypeAvancement getTypeAvancement() { return typeAvancement; }
    public void setTypeAvancement(TypeAvancement typeAvancement) { this.typeAvancement = typeAvancement; }
    public Integer getDureeAnnees() { return dureeAnnees; }
    public void setDureeAnnees(Integer dureeAnnees) { this.dureeAnnees = dureeAnnees; }
    public String getFichierNom() { return fichierNom; }
    public void setFichierNom(String fichierNom) { this.fichierNom = fichierNom; }

    public static AvancementDTOBuilder builder() {
        return new AvancementDTOBuilder();
    }

    public static class AvancementDTOBuilder {
        private UUID id;
        private String avancement;
        private String numeroTexte;
        private String signataire;
        private LocalDate dateEffet;
        private TypeAvancement typeAvancement;
        private Integer dureeAnnees;
        private String fichierNom;

        public AvancementDTOBuilder id(UUID id) { this.id = id; return this; }
        public AvancementDTOBuilder avancement(String avancement) { this.avancement = avancement; return this; }
        public AvancementDTOBuilder numeroTexte(String numeroTexte) { this.numeroTexte = numeroTexte; return this; }
        public AvancementDTOBuilder signataire(String signataire) { this.signataire = signataire; return this; }
        public AvancementDTOBuilder dateEffet(LocalDate dateEffet) { this.dateEffet = dateEffet; return this; }
        public AvancementDTOBuilder typeAvancement(TypeAvancement typeAvancement) { this.typeAvancement = typeAvancement; return this; }
        public AvancementDTOBuilder dureeAnnees(Integer dureeAnnees) { this.dureeAnnees = dureeAnnees; return this; }
        public AvancementDTOBuilder fichierNom(String fichierNom) { this.fichierNom = fichierNom; return this; }

        public AvancementDTO build() {
            return new AvancementDTO(id, avancement, numeroTexte, signataire, dateEffet, typeAvancement, dureeAnnees, fichierNom);
        }
    }
}
