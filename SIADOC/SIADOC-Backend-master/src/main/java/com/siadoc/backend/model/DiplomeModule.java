package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonBackReference;
import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.siadoc.backend.model.DossierAdministratif;
import jakarta.persistence.*;
import lombok.*;
import java.util.List;
import java.util.UUID;

@Entity
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class DiplomeModule {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @OneToOne
    @JoinColumn(name = "dossier_id", nullable = false)
    @JsonIgnore
    private DossierAdministratif dossier;

    @OneToMany(mappedBy = "module", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<DiplomeItem> items;





    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public DossierAdministratif getDossier() { return dossier; }
    public void setDossier(DossierAdministratif dossier) { this.dossier = dossier; }
    public List<DiplomeItem> getItems() { return items; }
    public void setItems(List<DiplomeItem> items) { this.items = items; }
}

