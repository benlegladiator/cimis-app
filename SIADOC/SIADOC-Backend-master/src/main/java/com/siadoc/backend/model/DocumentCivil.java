package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.Data;
import java.time.LocalDateTime;
import java.util.UUID;
import com.fasterxml.jackson.annotation.JsonIgnore;

@Entity
@Data
public class DocumentCivil {
    @Id
    @GeneratedValue(strategy = GenerationType.AUTO)
    private UUID id;

    private String label;
    private String nomFichier;
    private String typeFichier;
    
    @Column(columnDefinition = "bytea")
    private byte[] fichier;
    
    private LocalDateTime dateTeleversement;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "personnel_civil_id")
    @JsonIgnore
    private PersonnelCivil personnelCivil;
}
