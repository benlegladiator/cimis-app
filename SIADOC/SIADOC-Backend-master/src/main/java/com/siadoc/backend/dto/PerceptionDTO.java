package com.siadoc.backend.dto;

import com.siadoc.backend.model.CategorieArticle;
import lombok.Data;

import java.util.UUID;

@Data
public class PerceptionDTO {
    private UUID id;
    private String designation;
    private CategorieArticle categorie;
    private Integer quantite;
    private String etat;
    private String datePerception;
    private String observation;
}
