package com.siadoc.backend.dto;

import java.util.UUID;

public class BataillonDTO {
    private UUID id;
    private String nom;
    private UUID brigadeId;

    public BataillonDTO() {}

    public BataillonDTO(UUID id, String nom, UUID brigadeId) {
        this.id = id;
        this.nom = nom;
        this.brigadeId = brigadeId;
    }

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }
    public UUID getBrigadeId() { return brigadeId; }
    public void setBrigadeId(UUID brigadeId) { this.brigadeId = brigadeId; }
}
