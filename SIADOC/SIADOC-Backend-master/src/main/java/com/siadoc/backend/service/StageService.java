package com.siadoc.backend.service;

import com.siadoc.backend.dto.StageDTO;
import com.siadoc.backend.dto.search.RechercheStageDTO;
import com.siadoc.backend.dto.search.ResultRechercheStageDTO;
import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.model.StageItem;
import com.siadoc.backend.model.StageModule;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.StageItemRepository;
import com.siadoc.backend.repository.StageModuleRepository;
import jakarta.transaction.Transactional;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.List;
import java.util.UUID;
import java.util.stream.Collectors;

@Service
@Transactional
@RequiredArgsConstructor
public class StageService {

    private final StageModuleRepository moduleRepository;
    private final StageItemRepository itemRepository;
    private final DossierAdministratifRepository dossierRepository;

    // ===================================================
    // 1. RECUPERER LISTE
    // ===================================================
    public List<StageDTO> getList(UUID militaireId) {

        DossierAdministratif dossier =
                dossierRepository.findByMilitaireId(militaireId)
                        .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        StageModule module = dossier.getStageModule();

        // Lazy creation
        if (module == null) {
            module = new StageModule();
            module.setDossier(dossier);
            module = moduleRepository.save(module);
            dossier.setStageModule(module);
            return List.of();
        }

        return itemRepository
                .findByModuleIdOrderByDateObtentionAsc(module.getId())
                .stream()
                .map(this::mapToDTO)
                .collect(Collectors.toList());
    }

    private String like(String v) {
        if (v == null || v.trim().isEmpty()) return null;
        return "%" + v.trim() + "%";
    }

    public List<ResultRechercheStageDTO> rechercher(RechercheStageDTO dto) {
        return itemRepository.rechercherStages(
                like(dto.getNom()),
                like(dto.getPrenom()),
                dto.getGrade(),
                dto.getArme(),
                like(dto.getDesignation()),
                like(dto.getLieu()),
                dto.getAnnee()
        );
    }

    // ===================================================
    // 2. AJOUTER
    // ===================================================
    public StageDTO add(UUID militaireId,
                        StageDTO dto,
                        MultipartFile file) throws IOException {

        DossierAdministratif dossier =
                dossierRepository.findByMilitaireId(militaireId)
                        .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        StageModule module = dossier.getStageModule();

        if (module == null) {
            module = new StageModule();
            module.setDossier(dossier);
            module = moduleRepository.save(module);
            dossier.setStageModule(module);
        }

        StageItem item = new StageItem();
        item.setModule(module);

        // ✅ Champs métier
        item.setDesignation(dto.getDesignation());
        item.setDiplome(dto.getDiplome());
        item.setVille(dto.getVille());
        item.setPays(dto.getPays());
        item.setDateObtention(dto.getDateObtention());

        // ✅ Document
        if (file != null && !file.isEmpty()) {
            item.setDocumentData(file.getBytes());
            item.setDocumentNom(file.getOriginalFilename());
            item.setDocumentType(file.getContentType());
        }

        return mapToDTO(itemRepository.save(item));
    }

    // ===================================================
    // 3. MODIFIER
    // ===================================================
    public StageDTO update(UUID id, StageDTO dto) {

        StageItem item = itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Stage introuvable"));

        item.setDesignation(dto.getDesignation());
        item.setDiplome(dto.getDiplome());
        item.setVille(dto.getVille());
        item.setPays(dto.getPays());
        item.setDateObtention(dto.getDateObtention());

        return mapToDTO(itemRepository.save(item));
    }

    // ===================================================
    // 4. SUPPRIMER
    // ===================================================
    public void delete(UUID id) {
        itemRepository.deleteById(id);
    }

    // ===================================================
    // ENTITY -> DTO
    // ===================================================
    private StageDTO mapToDTO(StageItem item) {

        StageDTO dto = new StageDTO();

        dto.setId(item.getId());
        dto.setDesignation(item.getDesignation());
        dto.setDiplome(item.getDiplome());
        dto.setVille(item.getVille());
        dto.setPays(item.getPays());
        dto.setDateObtention(item.getDateObtention());
        dto.setDocument(item.getDocumentNom());

        return dto;
    }

    // ===================================================
    // DOCUMENT
    // ===================================================
    public StageItem getItem(UUID id) {
        return itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Stage introuvable"));
    }
}