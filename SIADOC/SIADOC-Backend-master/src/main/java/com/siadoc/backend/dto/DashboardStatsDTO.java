package com.siadoc.backend.dto;

import lombok.Data;
import java.util.List;
import java.util.Map;

@Data
public class DashboardStatsDTO {

    // ================= GLOBAL =================
    private long totalMilitaires;
    private long totalDossiers;
    private long totalArchives;

    // ================= ETAT CIVIL =================
    private Map<String, Long> etatCivil;

    // ================= FORMATIONS =================
    private Map<String, Long> formations;

    // ================= CARRIERE =================
    private Map<String, Long> carriere;

    // ================= DISCIPLINE =================
    private Map<String, Long> discipline;

    // ================= MEDICAL =================
    private Map<String, Long> medical;

    // ================= REPARTITION =================
    private List<Map<String, Object>> repartitionArmes;
}