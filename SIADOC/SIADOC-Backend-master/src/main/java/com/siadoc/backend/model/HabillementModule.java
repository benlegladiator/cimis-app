package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import com.siadoc.backend.model.DossierAdministratif;
import jakarta.persistence.*;
import lombok.*;
import java.util.List;
import java.util.UUID;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class HabillementModule {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @OneToOne
    @JoinColumn(name = "dossier_id", nullable = false)
    @JsonIgnore
    private DossierAdministratif dossier;

    // MENSURATIONS
    private Double tailleCm;
    private Double poidsKg;
    private String tourDeTete; // Pour képi/béret
    private String pointure;
    private String tailleVeste;
    private String taillePantalon;

    // LISTE DES PERCEPTIONS (Ce qu'il a reçu)
    @OneToMany(mappedBy = "module", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<PerceptionArticle> perceptions;
}
