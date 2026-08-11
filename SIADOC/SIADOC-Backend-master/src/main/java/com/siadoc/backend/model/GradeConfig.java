package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.Data;
import java.util.UUID;

@Entity
@Data
public class GradeConfig {
    @Id
    @GeneratedValue(strategy = GenerationType.AUTO)
    private UUID id;

    private String label;
    
    private String armee; // AT, AA, AM, GN
    
    private String categorie; // OFFICIERS, SOUS_OFFICIERS, MILITAIRES_DU_RANG
    
    private int ordre; // Pour le tri hiérarchique
}
