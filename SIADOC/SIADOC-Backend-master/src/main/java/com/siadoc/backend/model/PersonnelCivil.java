package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;
import java.util.List;

@Entity
@Data
public class PersonnelCivil {
    @Id
    @GeneratedValue(strategy = GenerationType.AUTO)
    private UUID id;

    private String nom;
    private String prenom;
    private LocalDate dateNaissance;
    private String lieuNaissance;
    private String sexe;
    
    @Column(unique = true)
    private String matricule;
    
    private LocalDate dateEntreeService;
    private String poste;

    @OneToMany(mappedBy = "personnelCivil", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<DocumentCivil> documents;
}
