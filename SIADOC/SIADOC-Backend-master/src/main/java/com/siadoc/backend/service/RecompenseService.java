package com.siadoc.backend.service;

import com.siadoc.backend.dto.RecompenseDTO;
import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.model.RecompenseItem;
import com.siadoc.backend.model.RecompenseModule;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.RecompenseItemRepository;
import com.siadoc.backend.repository.RecompenseModuleRepository;
import com.siadoc.backend.dto.search.RechercheRecompenseDTO;
import com.siadoc.backend.dto.search.ResultRechercheRecompenseDTO;
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
public class RecompenseService {

    private final RecompenseModuleRepository moduleRepository;
    private final RecompenseItemRepository itemRepository;
    private final DossierAdministratifRepository dossierRepository;

    // =========================
    // 1. RECUPERER
    // =========================
    public List<RecompenseDTO> getList(UUID militaireId) {

        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        RecompenseModule module = dossier.getRecompenseModule();

        // ✅ création lazy sécurisée
        if (module == null) {
            module = new RecompenseModule();
            module.setDossier(dossier);

            module = moduleRepository.save(module);

            // ⭐ LIGNE CRITIQUE (manquait)
            dossier.setRecompenseModule(module);
        }

        return itemRepository.findByModuleIdOrderByDateEffetAsc(module.getId())
                .stream()
                .map(this::mapToDTO)
                .collect(Collectors.toList());
    }

    // =========================
    // 2. AJOUTER
    // =========================
    public RecompenseDTO add(UUID militaireId, RecompenseDTO dto, MultipartFile file) throws IOException {

        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        RecompenseModule module = dossier.getRecompenseModule();

        // ✅ sécurité si module absent
        if (module == null) {
            module = new RecompenseModule();
            module.setDossier(dossier);

            module = moduleRepository.save(module);

            // ⭐ liaison obligatoire
            dossier.setRecompenseModule(module);
        }

        RecompenseItem item = new RecompenseItem();
        item.setModule(module);
        item.setDesignation(dto.getDesignation());
        item.setTexte(dto.getTexte());
        item.setDateEffet(dto.getDateEffet());

        if (file != null && !file.isEmpty()) {
            item.setDocumentData(file.getBytes());
            item.setDocumentNom(file.getOriginalFilename());
            item.setDocumentType(file.getContentType());
        }

        return mapToDTO(itemRepository.save(item));
    }

    // =========================
    // 3. MODIFIER
    // =========================
    public RecompenseDTO update(UUID id, RecompenseDTO dto) {

        RecompenseItem item = itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Récompense introuvable"));

        item.setDesignation(dto.getDesignation());
        item.setTexte(dto.getTexte());
        item.setDateEffet(dto.getDateEffet());

        return mapToDTO(itemRepository.save(item));
    }

    // =========================
    // 4. SUPPRIMER
    // =========================
    public void delete(UUID id) {
        itemRepository.deleteById(id);
    }

    // =========================
    // MAPPER
    // =========================
    private RecompenseDTO mapToDTO(RecompenseItem item) {
        RecompenseDTO dto = new RecompenseDTO();
        dto.setId(item.getId());
        dto.setDesignation(item.getDesignation());
        dto.setTexte(item.getTexte());
        dto.setDateEffet(item.getDateEffet());
        dto.setDocumentNom(item.getDocumentNom());
        return dto;
    }

    public RecompenseItem getItem(UUID id) {

        return itemRepository.findById(id)
                .orElseThrow(() ->
                        new RuntimeException("Récompense introuvable"));
    }

    private String like(String v) {
        if (v == null || v.trim().isEmpty()) return null;
        return "%" + v.trim() + "%";
    }

    public List<ResultRechercheRecompenseDTO> rechercher(RechercheRecompenseDTO dto) {
        return itemRepository.rechercherRecompenses(
                like(dto.getNom()),
                like(dto.getPrenom()),
                dto.getGrade(),                 // égalité simple
                dto.getArme(),                  // égalité simple
                like(dto.getDesignation()),
                like(dto.getTexte()),
                dto.getAnnee()
        );
    }

}
