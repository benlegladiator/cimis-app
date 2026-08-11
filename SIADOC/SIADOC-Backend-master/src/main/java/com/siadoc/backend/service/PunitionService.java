package com.siadoc.backend.service;

import com.siadoc.backend.dto.PunitionDTO;
import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.model.PunitionItem;
import com.siadoc.backend.model.PunitionModule;
import com.siadoc.backend.repository.PunitionItemRepository;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.PunitionModuleRepository;
import com.siadoc.backend.dto.search.ResultRecherchePunitionDTO;
import com.siadoc.backend.dto.search.RecherchePunitionDTO;
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
public class PunitionService {

    private final PunitionModuleRepository moduleRepository;
    private final PunitionItemRepository itemRepository;
    private final DossierAdministratifRepository dossierRepository;
    private final PunitionItemRepository punitionItemRepository;

    // 1. RECUPERER (Trié ancien -> récent)
    public List<PunitionDTO> getList(UUID militaireId) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        PunitionModule module = dossier.getPunitionModule(); // Adapter getter Dossier
        if (module == null) {
            module = new PunitionModule();
            module.setDossier(dossier);
            module = moduleRepository.save(module);
            return List.of();
        }

        return itemRepository.findByModuleIdOrderByDateEffetAsc(module.getId())
                .stream()
                .map(this::mapToDTO)
                .collect(Collectors.toList());
    }

    // 2. AJOUTER
    public PunitionDTO add(UUID militaireId, PunitionDTO dto, MultipartFile file) throws IOException {

        // 1️⃣ Récupérer le dossier du militaire
        DossierAdministratif dossier = dossierRepository
                .findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        // 2️⃣ Récupérer ou créer le module (LAZY CREATION)
        PunitionModule module = dossier.getPunitionModule();

        if (module == null) {
            module = new PunitionModule();
            module.setDossier(dossier);
            module = moduleRepository.save(module);
        }

        // 3️⃣ Créer l'item
        PunitionItem item = new PunitionItem();
        item.setModule(module);
        item.setDesignation(dto.getDesignation());
        item.setTexte(dto.getTexte());
        item.setDateEffet(dto.getDateEffet());

        // 4️⃣ Gestion fichier
        if (file != null && !file.isEmpty()) {
            item.setDocumentData(file.getBytes());
            item.setDocumentNom(file.getOriginalFilename());
            item.setDocumentType(file.getContentType());
        }

        // 5️⃣ Sauvegarde
        PunitionItem saved = itemRepository.save(item);

        return mapToDTO(saved);
    }


    // 3. MODIFIER (Texte uniquement, car ton tableau Angular fait de l'édition en ligne sans upload)
    public PunitionDTO update(UUID id, PunitionDTO dto) {
        PunitionItem item = itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Sanction introuvable"));

        item.setDesignation(dto.getDesignation());
        item.setTexte(dto.getTexte());
        item.setDateEffet(dto.getDateEffet());

        return mapToDTO(itemRepository.save(item));
    }

    // 4. SUPPRIMER
    public void delete(UUID id) {
        itemRepository.deleteById(id);
    }

    private PunitionDTO mapToDTO(PunitionItem item) {
        PunitionDTO dto = new PunitionDTO();
        dto.setId(item.getId());
        dto.setDesignation(item.getDesignation());
        dto.setTexte(item.getTexte());
        dto.setDateEffet(item.getDateEffet());
        dto.setDocument(item.getDocumentNom());
        return dto;
    }

    public PunitionItem getItem(UUID id) {
        return itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Punition introuvable"));
    }

    public List<ResultRecherchePunitionDTO> rechercher(RecherchePunitionDTO dto){

        return punitionItemRepository.rechercherPunition(
                like(dto.getNom()),
                like(dto.getPrenom()),
                dto.getGrade(),
                dto.getArme(),
                like(dto.getDesignation()),
                like(dto.getTexte()),
                dto.getAnnee()
        );
    }

    private String like(String value){
        if(value == null || value.isBlank()){
            return null;
        }
        return "%" + value + "%";
    }

}