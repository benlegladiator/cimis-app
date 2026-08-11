package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.Data;
import java.util.UUID;

@Entity
@Data
public class CivilSetting {
    @Id
    @GeneratedValue(strategy = GenerationType.AUTO)
    private UUID id;

    private String type; // POSTE, TYPE_DOCUMENT
    private String label;
}
