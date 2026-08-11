package com.siadoc.backend.service;

import com.siadoc.backend.model.CategorieMilitaire;
import com.siadoc.backend.model.GradeConfig;
import com.siadoc.backend.repository.GradeConfigRepository;
import jakarta.annotation.PostConstruct;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import java.util.*;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class GradeService {

    private final GradeConfigRepository gradeConfigRepository;

    private static final Map<String, List<String>> GRADES_OFFICIERS = new HashMap<>();
    private static final Map<String, List<String>> GRADES_SOUS_OFFICIERS = new HashMap<>();
    private static final Map<String, List<String>> GRADES_MDR = new HashMap<>();

    @PostConstruct
    public void init() {
        try {
            System.out.println(">>> Initialisation de GradeService...");
            long count = gradeConfigRepository.count();
            if (count == 0) {
                populateFromStaticData();
            }
            refreshCache();
            System.out.println(">>> GradeService initialisé avec succès (" + count + " grades).");
        } catch (Exception e) {
            System.err.println(">>> ERREUR lors de l'initialisation de GradeService (non bloquant) : " + e.getMessage());
            // On tente tout de même de peupler les caches statiques par défaut si la DB échoue
            populateFromStaticData();
        }
    }

    public void refreshCache() {
        GRADES_OFFICIERS.clear();
        GRADES_SOUS_OFFICIERS.clear();
        GRADES_MDR.clear();

        List<GradeConfig> all = gradeConfigRepository.findAllByOrderByOrdreAsc();
        for (GradeConfig g : all) {
            if ("OFFICIERS".equals(g.getCategorie())) {
                GRADES_OFFICIERS.computeIfAbsent(g.getArmee(), k -> new ArrayList<>()).add(g.getLabel());
            } else if ("SOUS_OFFICIERS".equals(g.getCategorie())) {
                GRADES_SOUS_OFFICIERS.computeIfAbsent(g.getArmee(), k -> new ArrayList<>()).add(g.getLabel());
            } else if ("MILITAIRES_DU_RANG".equals(g.getCategorie())) {
                GRADES_MDR.computeIfAbsent(g.getArmee(), k -> new ArrayList<>()).add(g.getLabel());
            }
        }
    }

    private void populateFromStaticData() {
        Map<String, List<String>> off = new HashMap<>();
        off.put("AT", Arrays.asList("Général d'Armée", "Général de Corps d'Armée", "Général de Division", "Général de Brigade", "Colonel", "Lieutenant-Colonel", "Chef de Bataillon", "Capitaine", "Lieutenant", "Sous-Lieutenant", "Aspirant"));
        off.put("AM", Arrays.asList("Amiral d'Escadre", "Vice-Amiral d'Escadre", "Vice-Amiral", "Contre-Amiral", "Capitaine de Vaisseau", "Capitaine de Corvette", "Capitaine de Frégate", "Enseigne de Vaisseau de 1E Classe", "Enseigne de Vaisseau de 2E Classe", "Aspirant"));
        off.put("AA", Arrays.asList("Général d'Armée Aérienne", "Général de Corps Aérien", "Général de Division Aérienne", "Général de Brigade Aérienne", "Colonel", "Lieutenant-Colonel", "Commandant", "Capitaine", "Lieutenant", "Sous-Lieutenant", "Aspirant"));
        off.put("GN", Arrays.asList("Général d'Armée", "Général de Corps d'Armée", "Général de Division", "Général de Brigade", "Colonel", "Lieutenant-Colonel", "Chef d'Escadron", "Capitaine", "Lieutenant", "Sous-Lieutenant", "Aspirant"));
        saveStaticCategory(off, "OFFICIERS", 100);

        Map<String, List<String>> soff = new HashMap<>();
        soff.put("AT", Arrays.asList("Adjudant-Chef Major", "Adjudant-Chef", "Adjudant", "Sergent-Chef", "Sergent", "Caporal-Chef", "Caporal"));
        soff.put("AM", Arrays.asList("Maître Principal Major", "Maître Principal", "Premier Maître", "Maître", "Second Maître"));
        soff.put("AA", Arrays.asList("Adjudant-Chef Major", "Adjudant-Chef", "Adjudant", "Sergent-Chef", "Sergent", "Caporal-Chef", "Caporal"));
        soff.put("GN", Arrays.asList("Adjudant-Chef Major", "Adjudant-Chef", "Adjudant", "Maréchal des Logis-Chef", "Maréchal des Logis", "Gendarme Major", "Gendarme", "Élève-Gendarme"));
        saveStaticCategory(soff, "SOUS_OFFICIERS", 200);

        Map<String, List<String>> mdr = new HashMap<>();
        mdr.put("AT", Arrays.asList("Soldat de 1E Classe", "Soldat de 2E Classe"));
        mdr.put("AM", Arrays.asList("Quartier-Maître de 1E Classe", "Quartier-Maître de 2E Classe", "Matelot de 1E Classe", "Matelot de 2E Classe"));
        mdr.put("AA", Arrays.asList("Soldat de 1E Classe", "Soldat de 2E Classe"));
        mdr.put("GN", new ArrayList<>());
        saveStaticCategory(mdr, "MILITAIRES_DU_RANG", 300);
    }

    private void saveStaticCategory(Map<String, List<String>> map, String cat, int baseOrdre) {
        for (String armee : map.keySet()) {
            List<String> labels = map.get(armee);
            for (int i = 0; i < labels.size(); i++) {
                GradeConfig g = new GradeConfig();
                g.setLabel(labels.get(i));
                g.setArmee(armee);
                g.setCategorie(cat);
                g.setOrdre(baseOrdre + i);
                gradeConfigRepository.save(g);
            }
        }
    }

    public static CategorieMilitaire determinerCategorie(String grade, String corpsStr) {
        if (grade == null || grade.isEmpty()) return CategorieMilitaire.MILITAIRE_RANG;
        String key = normaliserCorps(corpsStr);
        if (isMatch(grade, GRADES_OFFICIERS.get(key))) return CategorieMilitaire.OFFICIER;
        if (isMatch(grade, GRADES_SOUS_OFFICIERS.get(key))) return CategorieMilitaire.SOUS_OFFICIER;
        if (isMatch(grade, GRADES_MDR.get(key))) return CategorieMilitaire.MILITAIRE_RANG;
        if (isMatchGlobal(grade, GRADES_OFFICIERS)) return CategorieMilitaire.OFFICIER;
        if (isMatchGlobal(grade, GRADES_SOUS_OFFICIERS)) return CategorieMilitaire.SOUS_OFFICIER;
        if (isMatchGlobal(grade, GRADES_MDR)) return CategorieMilitaire.MILITAIRE_RANG;
        return CategorieMilitaire.MILITAIRE_RANG;
    }

    private static boolean isMatch(String grade, List<String> list) {
        if (list == null) return false;
        String normalizedGrade = normalize(grade);
        return list.stream().anyMatch(g -> normalize(g).equals(normalizedGrade));
    }

    private static boolean isMatchGlobal(String grade, Map<String, List<String>> map) {
        String normalizedGrade = normalize(grade);
        return map.values().stream().flatMap(List::stream).anyMatch(g -> normalize(g).equals(normalizedGrade));
    }

    public static int getGradeRank(String grade, String corps) {
        String normalizedGrade = normalize(grade);
        String armee = normaliserCorps(corps);
        List<String> officiers = GRADES_OFFICIERS.get(armee);
        List<String> sousOfficiers = GRADES_SOUS_OFFICIERS.get(armee);
        List<String> mdr = GRADES_MDR.get(armee);
        if (officiers != null) { for (int i = 0; i < officiers.size(); i++) { if (normalize(officiers.get(i)).equals(normalizedGrade)) return 100 + i; } }
        if (sousOfficiers != null) { for (int i = 0; i < sousOfficiers.size(); i++) { if (normalize(sousOfficiers.get(i)).equals(normalizedGrade)) return 200 + i; } }
        if (mdr != null) { for (int i = 0; i < mdr.size(); i++) { if (normalize(mdr.get(i)).equals(normalizedGrade)) return 300 + i; } }
        return 999;
    }

    private static String normalize(String s) {
        if (s == null) return "";
        String normalized = s.trim().toUpperCase();
        normalized = java.text.Normalizer.normalize(normalized, java.text.Normalizer.Form.NFD);
        normalized = normalized.replaceAll("[\\p{InCombiningDiacriticalMarks}]", "");
        return normalized;
    }

    private static String normaliserCorps(String corpsStr) {
        if (corpsStr == null) return "AT";
        String s = corpsStr.toUpperCase();
        if (s.contains("TERRE")) return "AT";
        if (s.contains("AIR")) return "AA";
        if (s.contains("MARINE")) return "AM";
        if (s.contains("GENDARMERIE")) return "GN";
        return "AT";
    }

    public Map<String, Map<String, List<String>>> getGradesGroupesParArmee() {
        Map<String, Map<String, List<String>>> result = new HashMap<>();
        for (String key : Arrays.asList("AT", "AA", "AM", "GN")) {
            Map<String, List<String>> categories = new HashMap<>();
            categories.put("OFFICIERS", GRADES_OFFICIERS.get(key));
            categories.put("SOUS_OFFICIERS", GRADES_SOUS_OFFICIERS.get(key));
            categories.put("MILITAIRES_DU_RANG", GRADES_MDR.get(key));
            result.put(key, categories);
        }
        return result;
    }

    public Map<String, List<String>> getGradesParArmee() {
        Map<String, List<String>> all = new HashMap<>();
        for (String key : Arrays.asList("AT", "AA", "AM", "GN")) {
            List<String> combined = new ArrayList<>();
            if (GRADES_OFFICIERS.get(key) != null) combined.addAll(GRADES_OFFICIERS.get(key));
            if (GRADES_SOUS_OFFICIERS.get(key) != null) combined.addAll(GRADES_SOUS_OFFICIERS.get(key));
            if (GRADES_MDR.get(key) != null) combined.addAll(GRADES_MDR.get(key));
            all.put(key, combined);
        }
        return all;
    }
}
