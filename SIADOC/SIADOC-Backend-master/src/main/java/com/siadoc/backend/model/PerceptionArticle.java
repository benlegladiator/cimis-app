package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class PerceptionArticle {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    // Au lieu de stocker "Vareuse" en dur, on devrait idéalement avoir une table RefArticle
    // Pour simplifier ici, on stocke le nom, mais dans un vrai ERP, ce serait une @ManyToOne
    private String designationArticle;

    @Enumerated(EnumType.STRING)
    private CategorieArticle categorie;

    private Integer quantitePercue;
    private String etat; // Neuf, Bon, Usé
    private LocalDate datePerception;
    private String observation;

    @ManyToOne
    @JoinColumn(name = "module_id")
    @JsonIgnore
    private HabillementModule module;
}
