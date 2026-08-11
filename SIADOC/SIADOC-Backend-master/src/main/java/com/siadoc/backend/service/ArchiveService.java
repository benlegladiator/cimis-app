package com.siadoc.backend.service;

import com.siadoc.backend.dto.ArchiveResultDTO;
import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.ArchiveDecedeRepository;
import com.siadoc.backend.repository.MilitaireRepository;

import org.springframework.stereotype.Service;
import lombok.RequiredArgsConstructor;
import org.springframework.transaction.annotation.Transactional;

import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

@Service
@RequiredArgsConstructor
public class ArchiveService {
    private static final Logger log = LoggerFactory.getLogger(ArchiveService.class);

    private final DossierAdministratifRepository repository;
    private final ArchiveDecedeRepository decedeRepository;
    private final MilitaireRepository militaireRepository;
    private final DossierAdministratifService dossierService;

    public List<ArchiveResultDTO> searchArchives(String search) {
        log.info(">>> Recherche globale archives avec terme : {}", search);
        
        List<ArchiveResultDTO> allResults = new ArrayList<>();
        
        // 1. Recherche dans les dossiers administratifs archivés (Digital)
        List<ArchiveResultDTO> digitalResults = repository.searchArchives(
                StatutDossier.ARCHIVE,
                emptyToNull(search)
        );
        allResults.addAll(digitalResults);

        // 2. Recherche dans les archives physiques (Militaires décédés)
        List<ArchiveResultDTO> physicalResults = decedeRepository.search(emptyToNull(search));
        allResults.addAll(physicalResults);

        log.info(">>> Total : {} résultats (Digital: {}, Physique: {})", 
                 allResults.size(), digitalResults.size(), physicalResults.size());
        
        return allResults;
    }

    @Transactional
    public ArchiveDecede savePhysicalArchive(ArchiveDecede archive) {
        log.info(">>> Création d'une archive physique pour {} {}", archive.getNom(), archive.getPrenom());

        // 1. Créer un Militaire "fantôme" pour porter le dossier
        Militaire m = new Militaire();
        m.setNom(archive.getNom());
        m.setPrenom(archive.getPrenom());
        m.setMatriculeMilitaire(archive.getMatricule());
        m.setMatriculeSolde("ARCH-" + archive.getMatricule()); // Matricule solde technique
        m.setArmeService(archive.getArmee());
        m.setGrade(archive.getGrade());
        m.setStatutValidation(StatutValidation.VALIDE);
        m.setEtat(EtatMilitaire.DECEDE);
        
        Militaire savedMilitaire = militaireRepository.save(m);
        log.info(">>> Militaire créé avec ID: {} (Catégorie: {})", savedMilitaire.getId(), savedMilitaire.getCategorie());

        // 2. Initialiser son DossierAdministratif en statut ARCHIVE
        DossierAdministratif dossier = dossierService.initialiserDossier(savedMilitaire, null, StatutDossier.ARCHIVE);
        log.info(">>> Dossier administratif initialisé pour l'archive.");

        // 3. Lier l'archive physique au militaire créé et synchroniser la catégorie
        archive.setMilitaireId(savedMilitaire.getId());
        archive.setCategorie(savedMilitaire.getCategorie());
        
        return decedeRepository.save(archive);
    }

    public List<ArchiveResultDTO> getAllPhysicalArchives() {
        return decedeRepository.findAllDTO();
    }

    public boolean isPhysicalArchive(UUID militaireId) {
        return decedeRepository.existsByMilitaireId(militaireId);
    }

    private String emptyToNull(String value) {
        return (value == null || value.isBlank()) ? null : value;
    }
}