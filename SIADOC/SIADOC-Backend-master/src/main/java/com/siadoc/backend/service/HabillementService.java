package com.siadoc.backend.service;


import com.siadoc.backend.dto.HabillementDTO;
import com.siadoc.backend.dto.MensurationsDTO;
import com.siadoc.backend.dto.PerceptionDTO;
import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.model.HabillementModule;
import com.siadoc.backend.model.PerceptionArticle;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.HabillementModuleRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;
import java.util.stream.Collectors;

@Service
@Transactional
@RequiredArgsConstructor
public class HabillementService {

    private final HabillementModuleRepository moduleRepository;
    private final DossierAdministratifRepository dossierRepository;

    // --- 1. RÉCUPÉRER LE DOSSIER D'HABILLEMENT ---
    public HabillementDTO getDossierHabillement(UUID militaireId) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier militaire introuvable"));

        HabillementModule module = dossier.getHabillementModule();

        // Si le module n'existe pas encore pour ce soldat, on le crée à la volée
        if (module == null) {
            module = new HabillementModule();
            module.setDossier(dossier);
            module.setPerceptions(new ArrayList<>());
            module = moduleRepository.save(module);
        }

        return mapToDTO(module);
    }

    // --- 2. SAUVEGARDER (Mensurations + Articles) ---
    public HabillementDTO saveGlobal(UUID militaireId, HabillementDTO dto) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier militaire introuvable"));

        HabillementModule module = dossier.getHabillementModule();
        if (module == null) {
            module = new HabillementModule();
            module.setDossier(dossier);
            module.setPerceptions(new ArrayList<>());
        }

        // A. Mise à jour des Mensurations (Onglet 1)
        if (dto.getMensurations() != null) {
            module.setTailleCm(dto.getMensurations().getTailleCm());
            module.setPoidsKg(dto.getMensurations().getPoidsKg());
            module.setTourDeTete(dto.getMensurations().getTourDeTete());
            module.setPointure(dto.getMensurations().getPointure());
            module.setTailleVeste(dto.getMensurations().getTailleVeste());
            module.setTaillePantalon(dto.getMensurations().getTaillePantalon());
        }

        // B. Mise à jour des Articles perçus (Onglets 2, 3 et 4)
        if (dto.getArticles() != null) {
            // ANTI-DOUBLON : On vide la liste existante.
            // JPA va automatiquement supprimer les anciennes lignes (grâce à orphanRemoval=true)
            module.getPerceptions().clear();

            // On ajoute les nouveaux articles envoyés par le Frontend
            for (PerceptionDTO pDTO : dto.getArticles()) {
                PerceptionArticle article = new PerceptionArticle();
                article.setModule(module);
                article.setDesignationArticle(pDTO.getDesignation());
                article.setCategorie(pDTO.getCategorie());
                article.setQuantitePercue(pDTO.getQuantite());
                article.setEtat(pDTO.getEtat());
                article.setObservation(pDTO.getObservation());

                // Gestion sécurisée de la date
                if (pDTO.getDatePerception() != null && !pDTO.getDatePerception().isEmpty()) {
                    article.setDatePerception(LocalDate.parse(pDTO.getDatePerception()));
                } else {
                    article.setDatePerception(LocalDate.now()); // Date du jour par défaut
                }

                module.getPerceptions().add(article);
            }
        }

        HabillementModule savedModule = moduleRepository.save(module);
        return mapToDTO(savedModule);
    }

    // --- 3. MAPPING ENTITY -> DTO ---
    private HabillementDTO mapToDTO(HabillementModule module) {
        HabillementDTO dto = new HabillementDTO();

        // Map Mensurations
        MensurationsDTO mDTO = new MensurationsDTO();
        mDTO.setTailleCm(module.getTailleCm());
        mDTO.setPoidsKg(module.getPoidsKg());
        mDTO.setTourDeTete(module.getTourDeTete());
        mDTO.setPointure(module.getPointure());
        mDTO.setTailleVeste(module.getTailleVeste());
        mDTO.setTaillePantalon(module.getTaillePantalon());
        dto.setMensurations(mDTO);

        // Map Articles
        if (module.getPerceptions() != null) {
            List<PerceptionDTO> articlesDTO = module.getPerceptions().stream().map(p -> {
                PerceptionDTO pDTO = new PerceptionDTO();
                pDTO.setId(p.getId());
                pDTO.setDesignation(p.getDesignationArticle());
                pDTO.setCategorie(p.getCategorie());
                pDTO.setQuantite(p.getQuantitePercue());
                pDTO.setEtat(p.getEtat());
                pDTO.setObservation(p.getObservation());
                if (p.getDatePerception() != null) {
                    pDTO.setDatePerception(p.getDatePerception().toString());
                }
                return pDTO;
            }).collect(Collectors.toList());

            dto.setArticles(articlesDTO);
        }

        return dto;
    }
}
