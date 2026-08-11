package com.siadoc.backend.dto;

import java.util.UUID;

public class BrigadeDTO {
    private UUID id;
    private String nom;
    private UUID regionId;

    public BrigadeDTO() {}

    public BrigadeDTO(UUID id, String nom, UUID regionId) {
        this.id = id;
        this.nom = nom;
        this.regionId = regionId;
    }

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public UUID getRegionId() { return regionId; }
    public void setRegionId(UUID regionId) { this.regionId = regionId; }
}
