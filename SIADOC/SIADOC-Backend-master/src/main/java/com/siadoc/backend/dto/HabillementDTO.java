package com.siadoc.backend.dto;

import lombok.Data;

import java.util.List;

@Data
public class HabillementDTO {
    // Onglet 1
    private MensurationsDTO mensurations;

    // Onglet 2, 3, 4 (Tout est une perception)
    private List<PerceptionDTO> articles;
}
