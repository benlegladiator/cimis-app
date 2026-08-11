package com.siadoc.backend.service;

import com.siadoc.backend.repository.*;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import java.util.HashMap;
import java.util.Map;
import java.util.List;

@Service
@RequiredArgsConstructor
public class StatsService {

    private final MilitaireRepository militaireRepo;
    private final StageItemRepository stageRepo;
    private final RecompenseItemRepository recompenseRepo;
    private final BlessureRepository blessureRepo;
    private final PensionRepository pensionRepo;
    private final NotationItemRepository notationRepo;

    // ================= DASHBOARD GLOBAL =================

    public Map<String, Object> getDashboard() {

        Map<String, Object> stats = new HashMap<>();

        stats.put("totalStages", stageRepo.count());
        stats.put("totalRecompenses", recompenseRepo.count());
        stats.put("totalBlessures", blessureRepo.count());
        stats.put("totalPensions", pensionRepo.count());
        stats.put("totalNotations", notationRepo.count());

        return stats;
    }

    public Map<String, Long> getCompagniesCounts() {
        Map<String, Long> counts = new HashMap<>();
        List<Object[]> results = militaireRepo.countMilitairesByCompagnie();
        for (Object[] result : results) {
            String nom = (String) result[0];
            Long count = ((Number) result[1]).longValue();
            counts.put(nom, count);
        }
        return counts;
    }

    public Map<String, Long> getUnitesCounts() {
        Map<String, Long> counts = new HashMap<>();
        List<Object[]> results = militaireRepo.countMilitairesByUnite();
        for (Object[] result : results) {
            String nom = (String) result[0];
            Long count = ((Number) result[1]).longValue();
            counts.put(nom, count);
        }
        return counts;
    }
}