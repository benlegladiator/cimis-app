package com.siadoc.backend.service;

import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.jdbc.core.JdbcTemplate;

import java.time.LocalDate;
import java.util.*;
import java.io.File;
import java.util.stream.Collectors;

@Service
public class SampleDataService {

    private final RegionMilitaireRepository regionRepo;
    private final BrigadeRepository brigadeRepo;
    private final BataillonRepository bataillonRepo;
    private final CompagnieRepository compagnieRepo;
    private final MilitaireRepository militaireRepo;
    private final DossierAdministratifRepository dossierRepo;
    private final MutationItemRepository mutationRepo;
    private final MutationsModuleRepository mutationsModuleRepo;
    private final DiplomeItemRepository diplomeRepo;
    private final DiplomeModuleRepository diplomeModuleRepo;
    private final StageItemRepository stageRepo;
    private final StageModuleRepository stageModuleRepo;
    private final InformationsPersonnellesRepository infoPersoRepo;
    private final CarriereRepository carriereRepo;
    private final EtatCivilRepository etatCivilRepo;
    private final UtilisateurRepository userRepo;
    private final JdbcTemplate jdbcTemplate;

    public SampleDataService(
            RegionMilitaireRepository regionRepo,
            BrigadeRepository brigadeRepo,
            BataillonRepository bataillonRepo,
            CompagnieRepository compagnieRepo,
            MilitaireRepository militaireRepo,
            DossierAdministratifRepository dossierRepo,
            MutationItemRepository mutationRepo,
            MutationsModuleRepository mutationsModuleRepo,
            DiplomeItemRepository diplomeRepo,
            DiplomeModuleRepository diplomeModuleRepo,
            StageItemRepository stageRepo,
            StageModuleRepository stageModuleRepo,
            InformationsPersonnellesRepository infoPersoRepo,
            CarriereRepository carriereRepo,
            EtatCivilRepository etatCivilRepo,
            UtilisateurRepository userRepo,
            JdbcTemplate jdbcTemplate
    ) {
        this.regionRepo = regionRepo;
        this.brigadeRepo = brigadeRepo;
        this.bataillonRepo = bataillonRepo;
        this.compagnieRepo = compagnieRepo;
        this.militaireRepo = militaireRepo;
        this.dossierRepo = dossierRepo;
        this.mutationRepo = mutationRepo;
        this.mutationsModuleRepo = mutationsModuleRepo;
        this.diplomeRepo = diplomeRepo;
        this.diplomeModuleRepo = diplomeModuleRepo;
        this.stageRepo = stageRepo;
        this.stageModuleRepo = stageModuleRepo;
        this.infoPersoRepo = infoPersoRepo;
        this.carriereRepo = carriereRepo;
        this.etatCivilRepo = etatCivilRepo;
        this.userRepo = userRepo;
        this.jdbcTemplate = jdbcTemplate;
    }

    @Transactional
    public void cleanupTestData() {
        System.out.println("Nettoyage des données de test existantes...");
        
        // 1. Supprimer les militaires de test
        // NOTE: DROP TABLE document_civil retiré car la migration vers bytea est terminée.
        
        List<Militaire> testMilis = militaireRepo.findAll().stream()
                .filter(m -> m.getMatriculeSolde() != null && m.getMatriculeSolde().startsWith("TEST_"))
                .collect(Collectors.toList());
        for (Militaire m : testMilis) {
            militaireRepo.delete(m);
        }

        // 2. Supprimer les anciens comptes de test
        List<Utilisateur> testUsers = userRepo.findAll().stream()
                .filter(u -> (u.getUsername().startsWith("com_") || u.getUsername().startsWith("em_")) && !u.getUsername().equals("com_drh"))
                .collect(Collectors.toList());
        for (Utilisateur u : testUsers) {
            userRepo.delete(u);
        }
        
        System.out.println(testMilis.size() + " militaires et " + testUsers.size() + " comptes supprimés.");
    }

    @Transactional
    public void populateSampleData() {
        cleanupTestData();
        
        // --- HIERARCHIE RMIA 1 (ARMEE) ---
        RegionMilitaire rmia1 = regionRepo.findByNomIgnoreCase("RMIA 1").orElseGet(() -> {
            RegionMilitaire r = new RegionMilitaire();
            r.setNom("RMIA 1");
            return regionRepo.save(r);
        });
        creerCompte(rmia1, null, null, null, "com_rmia1", Role.RMIA);

        // A. BRIGADE QG & BCS
        Brigade bqs = brigadeRepo.findByNomIgnoreCase("BRIGADE QG RMIA 1").orElseGet(() -> {
            Brigade b = new Brigade();
            b.setNom("BRIGADE QG RMIA 1");
            b.setRegion(rmia1);
            return brigadeRepo.save(b);
        });
        creerCompte(null, bqs, null, null, "com_brigade_qg", Role.BRIGADE);

        Bataillon bcs = bataillonRepo.findByNomIgnoreCase("BATAILLON DE COMMANDEMENT ET DE SOUTIEN").orElseGet(() -> {
            Bataillon bat = new Bataillon();
            bat.setNom("BATAILLON DE COMMANDEMENT ET DE SOUTIEN");
            bat.setBrigade(bqs);
            return bataillonRepo.save(bat);
        });
        creerCompte(null, null, bcs, null, "com_bcs", Role.BATAILLON);

        // Création de l'État-Major du Bataillon (BCS)
        peuplerEtatMajorBataillon(bcs);

        String[] compsBCS = {"CCT", "CHT", "ESCADRON DE MAINTENANCE"};
        int companyIndex = 5; // Les compagnies commencent à 5 après l'EM
        for (String cName : compsBCS) {
            final int currentIndex = companyIndex++;
            Compagnie comp = compagnieRepo.findByNomIgnoreCaseAndBataillon(cName, bcs).orElseGet(() -> {
                Compagnie c = new Compagnie();
                c.setNom(cName);
                c.setBataillon(bcs);
                c.setLocalisation("YAOUNDÉ");
                return compagnieRepo.save(c);
            });
            creerCompte(null, null, null, comp, "com_" + cName.toLowerCase().replace(" ", "_"), Role.COMMANDANT_COMPAGNIE);
            peuplerMilitaires(comp, cName, currentIndex);
        }

        // B. BRIGADE MARINE & FUSILIERS
        Brigade bMar = brigadeRepo.findByNomIgnoreCase("BRIGADE MARINE RMIA 1").orElseGet(() -> {
            Brigade b = new Brigade();
            b.setNom("BRIGADE MARINE RMIA 1");
            b.setRegion(rmia1);
            return brigadeRepo.save(b);
        });
        creerCompte(null, bMar, null, null, "com_brigade_marine", Role.BRIGADE);

        Bataillon bNav = bataillonRepo.findByNomIgnoreCase("BATAILLON DE FUSILIERS MARINS").orElseGet(() -> {
            Bataillon bat = new Bataillon();
            bat.setNom("BATAILLON DE FUSILIERS MARINS");
            bat.setBrigade(bMar);
            return bataillonRepo.save(bat);
        });
        creerCompte(null, null, bNav, null, "com_bat_fusiliers", Role.BATAILLON);

        Compagnie cfm111 = compagnieRepo.findByNomIgnoreCaseAndBataillon("111ème COMPAGNIE DE FUSILIERS MARINS", bNav).orElseGet(() -> {
            Compagnie c = new Compagnie();
            c.setNom("111ème COMPAGNIE DE FUSILIERS MARINS");
            c.setBataillon(bNav);
            c.setLocalisation("DOUALA");
            return compagnieRepo.save(c);
        });
        creerCompte(null, null, null, cfm111, "com_cfm111", Role.COMMANDANT_COMPAGNIE);
        peuplerMilitaires(cfm111, "CFM111", 5);

        // C. COMPTES ETAT-MAJOR
        creerCompte(null, null, null, null, "em_terre", Role.ETAT_MAJOR_TERRE);
        creerCompte(null, null, null, null, "em_marine", Role.ETAT_MAJOR_MARINE);
        
        // IMPORT GENDARMERIE (Hierarchy only)
        importGNHierarchy();
    }

    @Transactional
    public void importGNHierarchy() {
        System.out.println("Importation de la hiérarchie Gendarmerie...");
        try {
            File jsonFile = new File("src/main/resources/data/gn_hierarchy.json");
            if (!jsonFile.exists()) {
                jsonFile = new File("C:/Users/HP/Documents/01 Mars/SIADOC/SIADOC/src/main/resources/data/gn_hierarchy.json");
            }
            
            if (!jsonFile.exists()) return;
            
            ObjectMapper mapper = new ObjectMapper();
            Map<String, Object> data = mapper.readValue(jsonFile, Map.class);
            
            for (String rgName : data.keySet()) {
                RegionMilitaire rg = regionRepo.findByNomIgnoreCase(rgName).orElseGet(() -> {
                    RegionMilitaire r = new RegionMilitaire();
                    r.setNom(rgName);
                    return regionRepo.save(r);
                });
                
                Object legionsObj = data.get(rgName);
                if (!(legionsObj instanceof Map)) continue;
                Map<String, Object> legions = (Map<String, Object>) legionsObj;
                
                for (String legName : legions.keySet()) {
                    Brigade legion = brigadeRepo.findByNomIgnoreCase(legName).orElseGet(() -> {
                        Brigade b = new Brigade();
                        b.setNom(legName);
                        b.setRegion(rg);
                        return brigadeRepo.save(b);
                    });
                    
                    Object grpObj = legions.get(legName);
                    if (!(grpObj instanceof Map)) continue;
                    Map<String, Object> groupements = (Map<String, Object>) grpObj;
                    
                    for (String grpName : groupements.keySet()) {
                        Bataillon groupement = bataillonRepo.findByNomIgnoreCase(grpName).orElseGet(() -> {
                            Bataillon bat = new Bataillon();
                            bat.setNom(grpName);
                            bat.setBrigade(legion);
                            return bataillonRepo.save(bat);
                        });
                        
                        Object escObj = groupements.get(grpName);
                        if (!(escObj instanceof List)) continue;
                        List<String> escadrons = (List<String>) escObj;
                        
                        for (String escName : escadrons) {
                            if (escName == null || escName.isEmpty()) continue;
                            compagnieRepo.findByNomIgnoreCaseAndBataillon(escName, groupement).orElseGet(() -> {
                                Compagnie comp = new Compagnie();
                                comp.setNom(escName);
                                comp.setBataillon(groupement);
                                comp.setLocalisation("Secteur GN");
                                return compagnieRepo.save(comp);
                            });
                        }
                    }
                }
            }
        } catch (Exception e) {
            System.out.println("Erreur lors de l'import GN : " + e.getMessage());
        }
    }

    private void creerCompte(RegionMilitaire r, Brigade b, Bataillon bat, Compagnie c, String username, Role role) {
        if (userRepo.findByUsername(username).isPresent()) return;
        Utilisateur u = new Utilisateur();
        u.setUsername(username);
        u.setPassword("123");
        u.setRole(role);
        u.setRegion(r);
        u.setBrigade(b);
        u.setBataillon(bat);
        u.setCompagnie(c);
        userRepo.save(u);
    }

    private void peuplerEtatMajorBataillon(Bataillon bat) {
        // La CCS contient généralement l'État-Major
        Compagnie ccs = compagnieRepo.findByNomIgnoreCaseAndBataillon("CCS", bat).orElseGet(() -> {
            Compagnie c = new Compagnie();
            c.setNom("CCS");
            c.setBataillon(bat);
            c.setLocalisation(bat.getBrigade() != null ? "Secteur " + bat.getBrigade().getNom() : "Secteur Bataillon");
            return compagnieRepo.save(c);
        });

        String prefix = bat.getNom().replace(" ", "_");

        // 1. COMMANDEMENT
        creerMilitaireTest(ccs, "COLONEL", prefix + "_COM_BAT", "OFFICIER", "1 - COM BAT");
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_CSEC", "OFFICIER", "1.1 - CSEC/BAT");
        creerMilitaireTest(ccs, "LIEUTENANT", prefix + "_RAM", "OFFICIER", "1.2 - Chef RAM");
        creerMilitaireTest(ccs, "ADJUDANT-CHEF", prefix + "_MAJOR", "SOUS_OFFICIER", "1.3 - Major d'Homme");
        creerMilitaireTest(ccs, "ADJUDANT", prefix + "_ADJ_BAT", "SOUS_OFFICIER", "1.4 - ADJ BAT");

        // 2. CHEF EN SECOND
        creerMilitaireTest(ccs, "LIEUTENANT-COLONEL", prefix + "_CES", "OFFICIER", "2 - CES");
        creerMilitaireTest(ccs, "SERGENT", prefix + "_SEC_CES", "SOUS_OFFICIER", "2.1 - Secrétaire");
        creerMilitaireTest(ccs, "CAPORAL", prefix + "_CHAUFF_CES", "MILITAIRE_RANG", "2.2 - Chauffeur");

        // 3. ETAT-MAJOR (Bureaux)
        creerMilitaireTest(ccs, "CHEF DE BATAILLON", prefix + "_CEM", "OFFICIER", "3 - CEM");
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_B1", "OFFICIER", "3.1 - B1");
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_B2", "OFFICIER", "3.2 - B2");
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_B3", "OFFICIER", "3.3 - B3");
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_B4", "OFFICIER", "3.4 - B4");
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_B5", "OFFICIER", "3.5 - B5");
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_B6", "OFFICIER", "3.6 - B6");

        // 4. CCS
        creerMilitaireTest(ccs, "CAPITAINE", prefix + "_COM_CCS", "OFFICIER", "4 - COM CCS");
        creerMilitaireTest(ccs, "SERGENT", prefix + "_SECT_CCS", "SOUS_OFFICIER", "4.1 - SECT");
    }

    private void peuplerMilitaires(Compagnie comp, String prefix, int companyIndex) {
        // La structure de la compagnie s'adapte au numéro d'ordre (5, 6, 7...)
        String mainNum = String.valueOf(companyIndex);
        
        // Commandant de compagnie
        creerMilitaireTest(comp, "LIEUTENANT", prefix + "_CDT", "OFFICIER", mainNum + " - Commandant de compagnie");
        
        // Secrétaire de la compagnie
        creerMilitaireTest(comp, "ADJUDANT", prefix + "_SEC", "SOUS_OFFICIER", mainNum + ".1 - Secrétaire de la compagnie");
        
        // Reste des militaires
        creerMilitaireTest(comp, "SERGENT", prefix + "_SGT", "SOUS_OFFICIER", "Chef de groupe");
        creerMilitaireTest(comp, "CAPORAL", prefix + "_MDR", "MILITAIRE_RANG", "Chef d'élément");
    }

    private static final String[] NOMS = {"ABENA", "BEKONO", "CHETCHUENG", "DIBONGUE", "EBOA", "FOTSO", "GUEVARA", "HADJI", "ISSA", "JOPI", "KAMGA", "LONTSI", "MOUKOKO", "NGUESSAN", "ONANA", "PAGNA", "QUENUM", "REMO", "SONA", "TCHANA", "UM NYOBE", "VOUNDI", "WANDJI", "YAMEN", "ZAMBO"};
    private static final String[] PRENOMS = {"Jean-Pierre", "Hervé", "Marie-Noëlle", "Christian", "Abdoulaye", "Samuel", "Martine", "Guy", "Oumarou", "Cédric", "Alice", "Rodrigue", "Paul", "Marc", "Eric", "Fabrice", "Alain", "Stéphane", "Patrick", "David"};

    private void creerMilitaireTest(Compagnie comp, String grade, String suffixe, String cat, String emploi) {
        String matriculeSolde = "TEST_" + suffixe;
        if (militaireRepo.findByMatriculeSolde(matriculeSolde).isPresent()) return;

        Random rand = new Random(suffixe.hashCode()); // Seed stable par suffixe
        String nom = NOMS[Math.abs(rand.nextInt()) % NOMS.length];
        String prenom = PRENOMS[Math.abs(rand.nextInt()) % PRENOMS.length];

        Militaire m = new Militaire();
        m.setNom(nom);
        m.setPrenom(prenom);
        m.setMatriculeSolde(matriculeSolde);
        m.setMatriculeMilitaire("M" + matriculeSolde);
        m.setGrade(grade);
        m.setCategorie(CategorieMilitaire.valueOf(cat));
        
        // DETERMINATION DE L'ARME ET DU CORPS
        String arme = "ARMEE DE TERRE";
        CorpsArmee corps = CorpsArmee.AT;
        
        if (comp.getNom().contains("FUSILIERS MARINS") || comp.getNom().contains("MARINE")) {
            arme = "MARINE NATIONALE";
            corps = CorpsArmee.AM;
        } else if (comp.getLocalisation().equals("Secteur GN")) {
            arme = "GENDARMERIE NATIONALE";
            corps = CorpsArmee.GN;
        }
        
        m.setArmeService(arme);
        m.setDateNaissance(LocalDate.of(1990, 1, 1));
        m.setDateService(LocalDate.of(2012, 1, 1));
        m.setEchelon(1);
        m.setSexe("M");
        m.setLieuNaissance("YAOUNDÉ");
        m.setStatutValidation(StatutValidation.VALIDE);
        m.setEtat(EtatMilitaire.ACTIF);
        m = militaireRepo.save(m);

        DossierAdministratif dossier = new DossierAdministratif();
        dossier.setMilitaire(m);
        dossier.setCompagnie(comp);
        dossier.setStatut(StatutDossier.ADMINISTRATIF);
        dossier = dossierRepo.save(dossier);

        Carriere car = new Carriere();
        car.setDossier(dossier);
        car.setCorps(corps);
        car.setObservationEmploi("R.A.S - PERSONNEL DISPONIBLE");
        car = carriereRepo.save(car);
        dossier.setCarriere(car);

        MutationsModule mutMod = new MutationsModule();
        mutMod.setDossier(dossier);
        mutMod = mutationsModuleRepo.save(mutMod);
        MutationItem mut = new MutationItem();
        mut.setModule(mutMod);
        mut.setEmploi(emploi);
        mut.setUnite(UniteMilitaire.COMPAGNIE);
        mut.setDateTexte(LocalDate.now().minusYears(1));
        mut.setType(TypeMutation.AFFECTATION);
        mutationRepo.save(mut);

        DiplomeModule dipMod = new DiplomeModule();
        dipMod.setDossier(dossier);
        dipMod = diplomeModuleRepo.save(dipMod);
        DiplomeItem dip = new DiplomeItem();
        dip.setModule(dipMod);
        dip.setDesignation("BACCALAURÉAT");
        dip.setDateObtention(LocalDate.of(2008, 6, 15));
        diplomeRepo.save(dip);

        StageModule staMod = new StageModule();
        staMod.setDossier(dossier);
        staMod = stageModuleRepo.save(staMod);
        StageItem sta = new StageItem();
        sta.setModule(staMod);
        sta.setDesignation("CAT 1");
        sta.setDateObtention(LocalDate.of(2015, 12, 20));
        stageRepo.save(sta);

        EtatCivil ec = new EtatCivil();
        ec.setDossier(dossier);
        ec = etatCivilRepo.save(ec);
        InformationsPersonnelles ip = new InformationsPersonnelles();
        ip.setEtatCivil(ec);
        ip.setRegionOrigine("CENTRE");
        ip.setLanguesParlees("FRANÇAIS, ANGLAIS");
        infoPersoRepo.save(ip);

        dossierRepo.save(dossier);
    }
}
