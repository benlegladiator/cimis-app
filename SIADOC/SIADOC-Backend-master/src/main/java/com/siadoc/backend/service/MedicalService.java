package com.siadoc.backend.service;



import com.siadoc.backend.dto.BlessureDTO;

import com.siadoc.backend.dto.MedicalGlobalDTO;

import com.siadoc.backend.dto.PensionDTO;

import com.siadoc.backend.dto.DocumentMedicalDTO;

import com.siadoc.backend.dto.search.RechercheMedicalDTO;

import com.siadoc.backend.dto.search.ResultRechercheMedicalDTO;

import com.siadoc.backend.model.DossierAdministratif;

import com.siadoc.backend.model.Blessure;

import com.siadoc.backend.model.MedicalModule;

import com.siadoc.backend.model.Pension;

import com.siadoc.backend.repository.BlessureRepository;

import com.siadoc.backend.repository.DossierAdministratifRepository;

import com.siadoc.backend.repository.MedicalModuleRepository;

import com.siadoc.backend.repository.PensionRepository;

import jakarta.transaction.Transactional;

import lombok.RequiredArgsConstructor;

import org.springframework.stereotype.Service;

import org.springframework.web.multipart.MultipartFile;



import java.io.IOException;

import java.util.ArrayList;

import java.util.List;

import java.util.UUID;

import java.util.stream.Collectors;



@Service

@Transactional

@RequiredArgsConstructor

public class MedicalService {



    private final MedicalModuleRepository moduleRepository;

    private final BlessureRepository blessureRepository;

    private final PensionRepository pensionRepository;

    private final DossierAdministratifRepository dossierRepository;



    // 1. RECUPERER TOUT LE DOSSIER MEDICAL

    public MedicalGlobalDTO getDossierMedical(UUID militaireId) {

        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)

                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));



        MedicalModule module = dossier.getMedicalModule(); // Adapter getter



        // Initialisation Lazy si null

        if (module == null) {

            module = new MedicalModule();

            module.setDossier(dossier);

            module = moduleRepository.save(module);

        }



        MedicalGlobalDTO globalDTO = new MedicalGlobalDTO();



        // Mapping Blessures

        if (module.getBlessures() != null) {

            globalDTO.setBlessures(module.getBlessures().stream().map(b -> {

                BlessureDTO dto = new BlessureDTO();

                dto.setId(b.getId());

                dto.setNature(b.getNature());

                dto.setLieu(b.getLieu());

                dto.setAutorite(b.getAutorite());

                dto.setDateEffet(b.getDateEffet());

                dto.setDocument(b.getDocumentNom());

                return dto;

            }).collect(Collectors.toList()));

        }



        // Mapping Pensions

        if (module.getPensions() != null) {

            globalDTO.setPensions(module.getPensions().stream().map(p -> {

                PensionDTO dto = new PensionDTO();

                dto.setId(p.getId());

                dto.setTypeInvalidite(p.getTypeInvalidite());

                dto.setDatePriseEffet(p.getDatePriseEffet());

                dto.setReference(p.getReference());

                dto.setTaux(p.getTaux());

                dto.setDocument(p.getDocumentNom());

                return dto;

            }).collect(Collectors.toList()));

        }



        // Mapping Documents médicaux

        if (module.getDocumentsMedicaux() != null) {

            globalDTO.setDocuments(module.getDocumentsMedicaux().stream().map(d -> {

                DocumentMedicalDTO dto = new DocumentMedicalDTO();

                dto.setId(d.getId());

                dto.setTitre(d.getTitre());

                dto.setDescription(d.getDescription());

                dto.setDateDocument(d.getDateDocument());

                dto.setDocument(d.getDocumentNom());

                return dto;

            }).collect(Collectors.toList()));

        }



        return globalDTO;

    }



    private String like(String v) {

        if (v == null || v.trim().isEmpty()) return null;

        return "%" + v.trim() + "%";

    }



    public List<ResultRechercheMedicalDTO> rechercher(RechercheMedicalDTO dto) {

        List<ResultRechercheMedicalDTO> results = new ArrayList();



        // Si on a des critères spécifiques Blessures OR criteria généraux

        // (Pour simplifier on cherche dans les deux si les critères spécifiques sont compatibles)

        

        boolean canSearchBlessure = (dto.getTaux() == null);

        boolean canSearchPension = (dto.getNature() == null && dto.getLieu() == null);



        if (canSearchBlessure) {

            results.addAll(blessureRepository.rechercherBlessures(

                    like(dto.getNom()),

                    like(dto.getPrenom()),

                    dto.getGrade(),

                    dto.getArme(),

                    like(dto.getNature()),

                    like(dto.getLieu()),

                    dto.getAnnee()

            ));

        }



        if (canSearchPension) {

            results.addAll(pensionRepository.rechercherPensions(

                    like(dto.getNom()),

                    like(dto.getPrenom()),

                    dto.getGrade(),

                    dto.getArme(),

                    dto.getTaux(),

                    dto.getAnnee()

            ));

        }



        return results;

    }



    // 2. AJOUTER BLESSURE

    public BlessureDTO addBlessure(UUID militaireId, BlessureDTO dto, MultipartFile file) throws IOException {



        DossierAdministratif dossier =

                dossierRepository.findByMilitaireId(militaireId)

                        .orElseThrow(() -> new RuntimeException("Dossier introuvable"));



        // ✅ Sécurisation module (IMPORTANT)

        MedicalModule module = dossier.getMedicalModule();



        if (module == null) {

            module = new MedicalModule();

            module.setDossier(dossier);

            module = moduleRepository.save(module);

            dossier.setMedicalModule(module);

        }



        Blessure b = new Blessure();

        b.setModule(module);

        b.setNature(dto.getNature());

        b.setLieu(dto.getLieu());

        b.setAutorite(dto.getAutorite());

        b.setDateEffet(dto.getDateEffet());



        if (file != null && !file.isEmpty()) {

            b.setDocumentData(file.getBytes());

            b.setDocumentNom(file.getOriginalFilename());

            b.setDocumentType(file.getContentType());

        }



        Blessure saved = blessureRepository.save(b);



        dto.setId(saved.getId());

        dto.setDocument(saved.getDocumentNom());



        return dto;

    }





    // 3. AJOUTER PENSION

    public PensionDTO addPension(UUID militaireId, PensionDTO dto, MultipartFile file) throws IOException {



        DossierAdministratif dossier =

                dossierRepository.findByMilitaireId(militaireId)

                        .orElseThrow(() -> new RuntimeException("Dossier introuvable"));



        // ✅ Sécurisation module (IMPORTANT)

        MedicalModule module = dossier.getMedicalModule();



        if (module == null) {

            module = new MedicalModule();

            module.setDossier(dossier);

            module = moduleRepository.save(module);

            dossier.setMedicalModule(module);

        }



        Pension p = new Pension();

        p.setModule(module);

        p.setTypeInvalidite(dto.getTypeInvalidite());

        p.setDatePriseEffet(dto.getDatePriseEffet());

        p.setReference(dto.getReference());

        p.setTaux(dto.getTaux());



        if (file != null && !file.isEmpty()) {

            p.setDocumentData(file.getBytes());

            p.setDocumentNom(file.getOriginalFilename());

            p.setDocumentType(file.getContentType());

        }



        Pension saved = pensionRepository.save(p);



        dto.setId(saved.getId());

        dto.setDocument(saved.getDocumentNom());



        return dto;

    }





    public Blessure getBlessure(UUID id) {

        return blessureRepository.findById(id)

                .orElseThrow(() -> new RuntimeException("Blessure introuvable"));

    }



    public Pension getPension(UUID id) {

        return pensionRepository.findById(id)

                .orElseThrow(() -> new RuntimeException("Pension introuvable"));

    }





    private MedicalModule getOrCreateModule(DossierAdministratif dossier) {



        MedicalModule module = dossier.getMedicalModule();



        if (module == null) {

            module = new MedicalModule();

            module.setDossier(dossier);

            module = moduleRepository.save(module);

            dossier.setMedicalModule(module);

        }



        return module;

    }





    // 4. SUPPRESSION (Générique)

    public void deleteBlessure(UUID id) { blessureRepository.deleteById(id); }

    public void deletePension(UUID id) { pensionRepository.deleteById(id); }

}