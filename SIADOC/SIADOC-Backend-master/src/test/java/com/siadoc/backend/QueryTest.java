package com.siadoc.backend;

import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;

import java.util.List;

@SpringBootTest
public class QueryTest {

    @Autowired
    private MilitaireRepository militaireRepository;

    @Autowired
    private RegionMilitaireRepository regionRepository;

    @Autowired
    private DossierAdministratifRepository dossierRepository;

    @Test
    public void testQueries() {
        System.out.println("========== DEBUG START ==========");

        long totalActifs = militaireRepository.findActifs().size();
        System.out.println("Total findActifs: " + totalActifs);

        List<RegionMilitaire> regions = regionRepository.findAll();
        for (RegionMilitaire r : regions) {
            System.out.println("Region: " + r.getNom());
            List<Militaire> mils = militaireRepository.findByDossierCompagnieBataillonBrigadeRegionId(r.getId());
            System.out.println("  -> Militaires found: " + mils.size());
            
            // Try with LEFT JOINs
            long countDashboard = dossierRepository.countStatsByRegionId(r.getId());
            System.out.println("  -> Dossiers Dashboard Count (LEFT JOIN): " + countDashboard);
        }

        System.out.println("========== DEBUG END ==========");
    }
}
