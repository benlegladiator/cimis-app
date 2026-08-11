package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;


import java.util.List;
import java.util.UUID;

@Entity
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class AvancementModule {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @OneToOne
    @JoinColumn(name = "dossier_id", nullable = false)
    @JsonIgnore
    private DossierAdministratif dossier;

    @OneToMany(mappedBy = "module", cascade = CascadeType.ALL)
    private List<Avancement> avancements;
    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public DossierAdministratif getDossier() { return dossier; }
    public void setDossier(DossierAdministratif dossier) { this.dossier = dossier; }
    public List<Avancement> getAvancements() { return avancements; }
    public void setAvancements(List<Avancement> avancements) { this.avancements = avancements; }
}
