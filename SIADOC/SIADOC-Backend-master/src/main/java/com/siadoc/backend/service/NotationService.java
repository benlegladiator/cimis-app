package com.siadoc.backend.service;

import com.siadoc.backend.dto.CritereDTO;
import com.siadoc.backend.dto.NotationDTO;
import com.siadoc.backend.dto.QualitesDTO;
import com.siadoc.backend.dto.search.RechercheNotationDTO;
import com.siadoc.backend.dto.search.ResultRechercheNotationDTO;
import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.NotationItemRepository;
import com.siadoc.backend.repository.NotationModuleRepository;
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
public class NotationService {

    private final NotationModuleRepository moduleRepository;
    private final NotationItemRepository itemRepository;
    private final DossierAdministratifRepository dossierRepository;

    // 1. RECUPERER
    public List<NotationDTO> getList(UUID militaireId) {

        DossierAdministratif dossier = dossierRepository
                .findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        NotationModule module = dossier.getNotationModule();

        if (module == null) {
            throw new IllegalStateException("NotationModule non initialisé");
        }

        return itemRepository
                .findByModuleIdOrderByPeriodeAuDesc(module.getId())
                .stream()
                .map(this::mapToDTO)
                .collect(Collectors.toList());
    }

    // 2. AJOUTER
    public NotationDTO add(UUID militaireId, NotationDTO dto, MultipartFile file) throws IOException {

        DossierAdministratif dossier =
                dossierRepository.findByMilitaireId(militaireId)
                        .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        // récupérer le module depuis la DB
        NotationModule module = moduleRepository
                .findByDossierId(dossier.getId())
                .orElseThrow(() -> new IllegalStateException("NotationModule non initialisé"));

        NotationItem item = new NotationItem();

        // lier les deux côtés
        item.setModule(module);
        module.getItems().add(item);

        mapDtoToEntity(item, dto);

        if (file != null && !file.isEmpty()) {
            item.setDocumentData(file.getBytes());
            item.setDocumentNom(file.getOriginalFilename());
            item.setDocumentType(file.getContentType());
        }

        return mapToDTO(itemRepository.save(item));
    }


    // 3. MODIFIER
    public NotationDTO update(UUID id, NotationDTO dto) {
        NotationItem item = itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Notation introuvable"));

        mapDtoToEntity(item, dto);
        return mapToDTO(itemRepository.save(item));
    }

    // 4. SUPPRIMER
    public void delete(UUID id) { itemRepository.deleteById(id); }

    // --- MAPPERS ---

    private void mapDtoToEntity(NotationItem item, NotationDTO dto) {
        item.setPeriodeDu(dto.getPeriodeDu());
        item.setPeriodeAu(dto.getPeriodeAu());
        item.setAppreciationGenerale(dto.getAppreciationGenerale());

    }

    private NotationDTO mapToDTO(NotationItem item) {
        NotationDTO dto = new NotationDTO();
        dto.setId(item.getId());
        dto.setPeriodeDu(item.getPeriodeDu());
        dto.setPeriodeAu(item.getPeriodeAu());
        dto.setAppreciationGenerale(item.getAppreciationGenerale());
        dto.setDocument(item.getDocumentNom());

        return dto;
    }
    public NotationItem getItem(UUID id) {
        return itemRepository.findById(id)
                .orElseThrow(() -> new RuntimeException("Notation introuvable"));
    }

    private String like(String v) {
        if (v == null || v.trim().isEmpty()) return null;
        return "%" + v.trim() + "%";
    }

    public List<ResultRechercheNotationDTO> rechercher(RechercheNotationDTO dto) {
        return itemRepository.rechercherNotations(
                like(dto.getNom()),
                like(dto.getPrenom()),
                dto.getGrade(),
                dto.getArme(),
                like(dto.getAppreciationGenerale()),
                dto.getAnnee()
        );
    }
}


//        GrilleNotation grille = new GrilleNotation();
//        grille.setPresentation(dto.getQualites().getPresentation().getNote());
//        grille.setObsPresentation(dto.getQualites().getPresentation().getObs());
//
//        grille.setValeurPhysique(dto.getQualites().getValeurPhysique().getNote());
//        grille.setObsValeurPhysique(dto.getQualites().getValeurPhysique().getObs());
//
//        grille.setValeurMorale(dto.getQualites().getValeurMorale().getNote());
//        grille.setObsValeurMorale(dto.getQualites().getValeurMorale().getObs());
//
//        grille.setInstruction(dto.getQualites().getInstruction().getNote());
//        grille.setObsInstruction(dto.getQualites().getInstruction().getObs());
//
//        grille.setCommandement(dto.getQualites().getCommandement().getNote());
//        grille.setObsCommandement(dto.getQualites().getCommandement().getObs());
//
//        item.setGrille(grille);


//        dto.setMoyenneCalculee(item.getMoyenne()); // Le service renvoie la moyenne auto
//
//        QualitesDTO q = new QualitesDTO();
//        GrilleNotation g = item.getGrille();
//
//        q.setPresentation(new CritereDTO(g.getPresentation(), g.getObsPresentation()));
//        q.setValeurPhysique(new CritereDTO(g.getValeurPhysique(), g.getObsValeurPhysique()));
//        q.setValeurMorale(new CritereDTO(g.getValeurMorale(), g.getObsValeurMorale()));
//        q.setInstruction(new CritereDTO(g.getInstruction(), g.getObsInstruction()));
//        q.setCommandement(new CritereDTO(g.getCommandement(), g.getObsCommandement()));
//
//        dto.setQualites(q);