package com.siadoc.backend.service;

import com.siadoc.backend.repository.DossierAdministratifRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.UUID;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class DashboardService {

    private final DossierAdministratifRepository dossierRepository;
    private final com.siadoc.backend.repository.MutationItemRepository mutationRepository;
    private final com.siadoc.backend.repository.CompagnieRepository compagnieRepository;
    private final com.siadoc.backend.repository.BataillonRepository bataillonRepository;
    private final com.siadoc.backend.repository.BrigadeRepository brigadeRepository;

    public Map<String, Object> getStatsForRMIA(UUID regionId, String arme) {
        String code = getArmeCode(arme);
        Map<String, Object> stats = new HashMap<>();
        
        if (arme != null && !arme.isEmpty()) {
            stats.put("totalDossiers", dossierRepository.countByRegionAndArme(regionId, arme, code));
            stats.put("totalArchives", 0L);
            stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForRegionAndArme(regionId, arme, code));
            stats.put("repartitionSexes", dossierRepository.getStatsBySexeForRMIA(regionId));
            stats.put("repartitionBrigades", dossierRepository.getStatsByBrigadeAndCorpsForRMIAAndArme(regionId, arme, code));
            stats.put("repartitionBataillons", dossierRepository.getStatsByBataillonAndCorpsForRMIAAndArme(regionId, arme, code));
            stats.put("repartitionCompagnies", dossierRepository.getStatsByCompagnieAndCorpsForRMIAAndArme(regionId, arme, code));
        } else {
            stats.put("totalDossiers", dossierRepository.countStatsByRegionId(regionId));
            stats.put("totalArchives", dossierRepository.countByStatutAndRegionId(com.siadoc.backend.model.StatutDossier.ARCHIVE, regionId));
            stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForRMIA(regionId));
            stats.put("repartitionSexes", dossierRepository.getStatsBySexeForRMIA(regionId));
            stats.put("repartitionBrigades", dossierRepository.getStatsByBrigadeAndCorpsForRMIA(regionId));
            stats.put("repartitionBataillons", dossierRepository.getStatsByBataillonAndCorpsForRMIA(regionId));
            stats.put("repartitionCompagnies", dossierRepository.getStatsByCompagnieAndCorpsForRMIA(regionId));
            stats.put("totalCompagnies", 0L);
            stats.put("totalRetraites", dossierRepository.countByStatutAndRegionId(com.siadoc.backend.model.StatutDossier.CAMPAGNE, regionId));
        }
        stats.put("repartitionArmes", formatAggregation(dossierRepository.countByRegionAndGroupByCorps(regionId)));
        return stats;
    }

    public Map<String, Object> getStatsForDRH() {
        Map<String, Object> stats = new HashMap<>();
        stats.put("totalDossiers", dossierRepository.count());
        stats.put("totalArchives", dossierRepository.countByStatut(com.siadoc.backend.model.StatutDossier.ARCHIVE));
        stats.put("repartitionArmes", formatAggregation(dossierRepository.countAllAndGroupByCorps()));
        stats.put("repartitionBrigades", dossierRepository.getStatsByBrigadeAndCorpsGlobal());
        stats.put("repartitionRMIA", dossierRepository.getStatsByRegionAndCorpsGlobal());
        stats.put("repartitionCategories", dossierRepository.getStatsByCategorieGlobal());
        stats.put("repartitionSexes", dossierRepository.getStatsBySexeGlobal());
        stats.put("totalRetraites", dossierRepository.countByStatut(com.siadoc.backend.model.StatutDossier.CAMPAGNE));
        stats.put("detailed", dossierRepository.getFullStatsGlobal());
        return stats;
    }

    public Map<String, Object> getStatsForBrigade(UUID brigadeId, String arme) {
        String code = getArmeCode(arme);
        Map<String, Object> stats = new HashMap<>();
        
        if (arme != null && !arme.isEmpty()) {
            stats.put("totalDossiers", dossierRepository.countByBrigadeAndArme(brigadeId, arme, code));
            stats.put("totalArchives", 0L);
            stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForBrigadeAndArme(brigadeId, arme, code));
            stats.put("repartitionSexes", dossierRepository.getStatsBySexeForBrigade(brigadeId));
            stats.put("repartitionBataillons", dossierRepository.getStatsByBataillonAndCorpsForBrigadeAndArme(brigadeId, arme, code));
            stats.put("repartitionCompagnies", dossierRepository.getStatsByCompagnieAndCorpsForBrigadeAndArme(brigadeId, arme, code));
        } else {
            stats.put("totalDossiers", dossierRepository.countStatsByBrigadeId(brigadeId));
            stats.put("totalArchives", dossierRepository.countByStatutAndBrigadeId(com.siadoc.backend.model.StatutDossier.ARCHIVE, brigadeId));
            stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForBrigade(brigadeId));
            stats.put("repartitionSexes", dossierRepository.getStatsBySexeForBrigade(brigadeId));
            stats.put("repartitionBataillons", dossierRepository.getStatsByBataillonAndCorpsForBrigade(brigadeId));
            stats.put("repartitionCompagnies", dossierRepository.getStatsByCompagnieAndCorpsForBrigade(brigadeId));
            stats.put("totalRetraites", dossierRepository.countByStatutAndBrigadeId(com.siadoc.backend.model.StatutDossier.CAMPAGNE, brigadeId));
        }
        stats.put("repartitionArmes", formatAggregation(dossierRepository.countByBrigadeAndGroupByCorps(brigadeId)));
        return stats;
    }

    public Map<String, Object> getStatsForBataillon(UUID bataillonId, String arme) {
        String code = getArmeCode(arme);
        Map<String, Object> stats = new HashMap<>();
        
        if (arme != null && !arme.isEmpty()) {
            stats.put("totalDossiers", dossierRepository.countByBataillonAndArme(bataillonId, arme, code));
            stats.put("totalArchives", 0L);
            stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForBataillonAndArme(bataillonId, arme, code));
            stats.put("repartitionSexes", dossierRepository.getStatsBySexeForBataillon(bataillonId));
            stats.put("repartitionCompagnies", dossierRepository.getStatsByCompagnieAndCorpsForBataillonAndArme(bataillonId, arme, code));
        } else {
            stats.put("totalDossiers", dossierRepository.countStatsByBataillonId(bataillonId));
            stats.put("totalArchives", dossierRepository.countByStatutAndBataillonId(com.siadoc.backend.model.StatutDossier.ARCHIVE, bataillonId));
            stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForBataillon(bataillonId));
            stats.put("repartitionSexes", dossierRepository.getStatsBySexeForBataillon(bataillonId));
            stats.put("repartitionCompagnies", dossierRepository.getStatsByCompagnieAndCorpsForBataillon(bataillonId));
            stats.put("totalRetraites", dossierRepository.countByStatutAndBataillonId(com.siadoc.backend.model.StatutDossier.CAMPAGNE, bataillonId));
        }
        stats.put("repartitionArmes", formatAggregation(dossierRepository.countByBataillonAndGroupByCorps(bataillonId)));
        return stats;
    }

    public Map<String, Object> getStatsForCompagnie(UUID compagnieId) {
        Map<String, Object> stats = new HashMap<>();
        stats.put("totalDossiers", dossierRepository.countByCompagnieId(compagnieId));
        stats.put("totalArchives", dossierRepository.countByStatutAndCompagnieId(com.siadoc.backend.model.StatutDossier.ARCHIVE, compagnieId));
        stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForCompagnie(compagnieId));
        stats.put("repartitionSexes", dossierRepository.getStatsBySexeForCompagnie(compagnieId));
        stats.put("totalRetraites", dossierRepository.countByStatutAndCompagnieId(com.siadoc.backend.model.StatutDossier.CAMPAGNE, compagnieId));
        stats.put("repartitionArmes", formatAggregation(dossierRepository.countByCompagnieAndGroupByCorps(compagnieId)));
        return stats;
    }

    private String getArmeCode(String arme) {
        if (arme == null) return "";
        if (arme.toUpperCase().contains("TERRE")) return "AT";
        if (arme.toUpperCase().contains("AIR")) return "AA";
        if (arme.toUpperCase().contains("MARINE")) return "AM";
        if (arme.toUpperCase().contains("GENDARMERIE") || arme.toUpperCase().contains("GN")) return "GN";
        return "";
    }

    public Map<String, Object> getStatsForEtatMajor(String arme) {
        String code = "";
        if ("TERRE".equalsIgnoreCase(arme)) code = "AT";
        else if ("AIR".equalsIgnoreCase(arme)) code = "AA";
        else if ("MARINE".equalsIgnoreCase(arme)) code = "AM";

        Map<String, Object> stats = new HashMap<>();
        stats.put("totalDossiers", dossierRepository.countByArme(arme, code));
        stats.put("totalArchives", dossierRepository.countByStatutAndArme(com.siadoc.backend.model.StatutDossier.ARCHIVE, arme, code));
        stats.put("repartitionCategories", dossierRepository.getStatsByCategorieForArme(arme, code));
        stats.put("repartitionBrigades", dossierRepository.getStatsByBrigadeAndCategoryForArme(arme, code));
        stats.put("repartitionRMIA", dossierRepository.getStatsByRegionAndCategoryForArme(arme, code));
        stats.put("detailed", dossierRepository.getFullStatsForArme(arme, code));
        return stats;
    }

    private Map<String, Long> formatAggregation(List<Object[]> results) {
        Map<String, Long> map = new java.util.LinkedHashMap<>();
        for (Object[] row : results) {
            String key = (row[0] != null) ? row[0].toString() : "Non renseigné";
            map.merge(key, (Long) row[1], Long::sum);
        }
        return map;
    }
}