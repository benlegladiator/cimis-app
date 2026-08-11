package com.siadoc.backend.service;



import com.siadoc.backend.dto.DiplomeDTO;

import com.siadoc.backend.model.DiplomeItem;

import com.siadoc.backend.model.DiplomeModule;

import com.siadoc.backend.dto.search.RechercheDiplomeDTO;

import com.siadoc.backend.dto.search.ResultRechercheDiplomeDTO;

import com.siadoc.backend.model.DossierAdministratif;

import com.siadoc.backend.repository.DiplomeItemRepository;

import com.siadoc.backend.repository.DiplomeModuleRepository;

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

public class DiplomeService {



    private final DiplomeModuleRepository moduleRepository;

    private final DiplomeItemRepository itemRepository;

    private final DossierAdministratifRepository dossierRepository;

    private final DiplomeItemRepository diplomeItemRepository;



    // 1. RECUPERER (Liste triée)

    public List<DiplomeDTO> getList(UUID militaireId) {

        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)

                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));



        DiplomeModule module = dossier.getDiplomeModule(); // Adapter getter Dossier



        // Lazy loading : Création si inexistant

        if (module == null) {

            module = new DiplomeModule();

            module.setDossier(dossier);

            module = moduleRepository.save(module);

            return List.of();

        }



        return itemRepository.findByModuleIdOrderByDateObtentionAsc(module.getId())

                .stream()

                .map(this::mapToDTO)

                .collect(Collectors.toList());

    }



    // 2. AJOUTER

    public DiplomeDTO add(UUID militaireId, DiplomeDTO dto, MultipartFile file) throws IOException {

        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId).orElseThrow();

        DiplomeModule module = dossier.getDiplomeModule();



        DiplomeItem item = new DiplomeItem();

        item.setModule(module);

        item.setDesignation(dto.getDesignation());

        item.setEcole(dto.getEcole());

        item.setDateObtention(dto.getDateObtention());



        if (file != null && !file.isEmpty()) {

            item.setDocumentData(file.getBytes());

            item.setDocumentNom(file.getOriginalFilename());

            item.setDocumentType(file.getContentType());

        }



        return mapToDTO(itemRepository.save(item));

    }



    // 3. MODIFIER (Texte uniquement)

    public DiplomeDTO update(UUID id, DiplomeDTO dto) {

        DiplomeItem item = itemRepository.findById(id)

                .orElseThrow(() -> new RuntimeException("Diplôme introuvable"));



        item.setDesignation(dto.getDesignation());

        item.setEcole(dto.getEcole());

        item.setDateObtention(dto.getDateObtention());



        return mapToDTO(itemRepository.save(item));

    }



    // 4. SUPPRIMER

    public void delete(UUID id) {

        itemRepository.deleteById(id);

    }



    public DiplomeItem getItem(UUID id) {

        return itemRepository.findById(id)

                .orElseThrow(() -> new RuntimeException("Diplôme introuvable"));

    }



    private DiplomeDTO mapToDTO(DiplomeItem item) {

        DiplomeDTO dto = new DiplomeDTO();

        dto.setId(item.getId());

        dto.setDesignation(item.getDesignation());

        dto.setEcole(item.getEcole());

        dto.setDateObtention(item.getDateObtention());

        dto.setDocument(item.getDocumentNom());

        return dto;

    }



    public List<ResultRechercheDiplomeDTO> rechercher(RechercheDiplomeDTO dto){



        return diplomeItemRepository.rechercherDiplome(

                like(dto.getNom()),

                like(dto.getPrenom()),

                dto.getGrade(),

                dto.getArme(),

                like(dto.getDesignation()),

                like(dto.getEcole()),

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