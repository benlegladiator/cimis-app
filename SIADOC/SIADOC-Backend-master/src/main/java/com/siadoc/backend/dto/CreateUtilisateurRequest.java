package com.siadoc.backend.dto;

import com.siadoc.backend.model.Role;
import java.util.UUID;

public class CreateUtilisateurRequest {

    private String username;
    private String password;
    private Role role;
    private UUID secteurId;
    private UUID regionId;
    private UUID brigadeId;
    private UUID bataillonId;
    private UUID compagnieId;

    public CreateUtilisateurRequest() {}

    public String getUsername() { return username; }
    public void setUsername(String username) { this.username = username; }
    public String getPassword() { return password; }
    public void setPassword(String password) { this.password = password; }
    public Role getRole() { return role; }
    public void setRole(Role role) { this.role = role; }
    public UUID getSecteurId() { return secteurId; }
    public void setSecteurId(UUID secteurId) { this.secteurId = secteurId; }
    public UUID getRegionId() { return regionId; }
    public void setRegionId(UUID regionId) { this.regionId = regionId; }
    public UUID getBrigadeId() { return brigadeId; }
    public void setBrigadeId(UUID brigadeId) { this.brigadeId = brigadeId; }
    public UUID getBataillonId() { return bataillonId; }
    public void setBataillonId(UUID bataillonId) { this.bataillonId = bataillonId; }
    public UUID getCompagnieId() { return compagnieId; }
    public void setCompagnieId(UUID compagnieId) { this.compagnieId = compagnieId; }
}