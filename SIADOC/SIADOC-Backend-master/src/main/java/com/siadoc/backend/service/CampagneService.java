package com.siadoc.backend.service;

import com.siadoc.backend.dto.CampagneDTO;
import com.siadoc.backend.dto.search.RechercheCampagneMilitaireDTO;
import com.siadoc.backend.dto.search.ResultRechercheCampagneMilitaireDTO;
import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.CampagneItemRepository;
import com.siadoc.backend.repository.CampagneModuleRepository;
import com.siadoc.backend.repository.DossierAdministratifRepository;
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
public class CampagneService {

    private final CampagneModuleRepository moduleRepository;
    private final CampagneItemRepository itemRepository;
    private final DossierAdministratifRepository dossierRepository;

    // 1. RECUPERER (Liste triée)
    public List<CampagneDTO> getList(UUID militaireId) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        CampagneMilitaireModule module = dossier.getCampagneMilitaireModule(); // Adapter getter Dossier

        // Lazy loading
        if (module == null) {
            module = new CampagneMilitaireModule();
            module.setDossier(dossier);
            module = moduleRepository.save(module);
            return List.of();
        }

        return itemRepository.findByModuleIdOrderByDateAsc(module.getId())
                .stream()
                .map(this::mapToDTO)
                .collect(Collectors.toList());
    }

    // 2. AJOUTER
    public CampagneDTO add(UUID militaireId, CampagneDTO dto, MultipartFile file) throws IOException {

        DossierAdministratif dossier =
                dossierRepository.findByMilitaireId(militaireId)
                        .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        CampagneMilitaireModule module = dossier.getCampagneMilitaireModule();

        // ✅ création automatique si inexistant
        if (module == null) {
            module = new CampagneMilitaireModule();
            module.setDossier(dossier);
            module = moduleRepository.save(module);
            dossier.setCampagneMilitaireModule(module);
        }

        CampagneMilitaireItem item = new CampagneMilitaireItem();
        item.setModule(module);
        item.setDesignation(dto.getDesignation());
        item.setSignataire(dto.getSignataire());
        item.setDate(dto.getDate());

        if (file != null && !file.isEmpty()) {
            item.setDocumentData(file.getBytes());
            item.setDocumentNom(file.getOriginalFilename());
            item.setDocumentType(file.getContentType());
        }

        return mapToDTO(itemRepository.save(item));
    }

    // 3. MODIFIER (Infos textuelles uniquement)
    public CampagneDTO update(UUID id, CampagneDTO dto) {
        CampagneMilitaireItem item = itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Stage introuvable"));

        item.setDesignation(dto.getDesignation());
        item.setSignataire(dto.getSignataire());
        item.setDate(dto.getDate());

        return mapToDTO(itemRepository.save(item));
    }

    // 4. SUPPRIMER
    public void delete(UUID id) {
        itemRepository.deleteById(id);
    }

    private CampagneDTO mapToDTO(CampagneMilitaireItem item) {
        CampagneDTO dto = new CampagneDTO();
        dto.setId(item.getId());
        dto.setDesignation(item.getDesignation());
        dto.setSignataire(item.getSignataire());
        dto.setDate(item.getDate());
        dto.setDocument(item.getDocumentNom());
        return dto;
    }

    public CampagneMilitaireItem getItem(UUID id) {
        return itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Campagne introuvable"));
    }

    private String like(String v) {
        if (v == null || v.trim().isEmpty()) return null;
        return "%" + v.trim() + "%";
    }

    public List<ResultRechercheCampagneMilitaireDTO> rechercher(RechercheCampagneMilitaireDTO dto) {
        return itemRepository.rechercherCampagnes(
                like(dto.getNom()),
                like(dto.getPrenom()),
                dto.getGrade(),
                dto.getArme(),
                like(dto.getCampagne()),
                dto.getAnnee()
        );
    }
}
