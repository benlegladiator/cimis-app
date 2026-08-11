package com.siadoc.backend.dto;

import java.util.UUID;

public class CompagnieDTO {
    private UUID id;
    private String nom;
    private UUID bataillonId;
    private String labelAffichage; // Exemple: "RMIA1 > BQG > BCS > CCS"
    private HierarchyInfo hierarchy;

    public CompagnieDTO() {}

    public CompagnieDTO(UUID id, String nom, UUID bataillonId, String labelAffichage) {
        this.id = id;
        this.nom = nom;
        this.bataillonId = bataillonId;
        this.labelAffichage = labelAffichage;
    }

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public UUID getBataillonId() { return bataillonId; }
    public void setBataillonId(UUID bataillonId) { this.bataillonId = bataillonId; }
    public String getLabelAffichage() { return labelAffichage; }
    public void setLabelAffichage(String labelAffichage) { this.labelAffichage = labelAffichage; }
    public HierarchyInfo getHierarchy() { return hierarchy; }
    public void setHierarchy(HierarchyInfo hierarchy) { this.hierarchy = hierarchy; }

    public static class HierarchyInfo {
        private String rmia;
        private String brigade;
        private String bataillon;

        public HierarchyInfo() {}
        public HierarchyInfo(String rmia, String brigade, String bataillon) {
            this.rmia = rmia;
            this.brigade = brigade;
            this.bataillon = bataillon;
        }

        public String getRmia() { return rmia; }
        public void setRmia(String rmia) { this.rmia = rmia; }
        public String getBrigade() { return brigade; }
        public void setBrigade(String brigade) { this.brigade = brigade; }
        public String getBataillon() { return bataillon; }
        public void setBataillon(String bataillon) { this.bataillon = bataillon; }
    }
}
