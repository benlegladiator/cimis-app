package com.siadoc.backend.service;



import com.siadoc.backend.model.Avancement;

import com.siadoc.backend.model.AvancementModule;

import com.siadoc.backend.model.TypeAvancement;

import com.siadoc.backend.repository.AvancementRepository;

import com.siadoc.backend.repository.AvancementModuleRepository;



import com.siadoc.backend.dto.AvancementDTO;

import com.siadoc.backend.dto.search.RechercheAvancementDTO;

import com.siadoc.backend.dto.search.ResultRechercheAvancementDTO;



import org.springframework.stereotype.Service;

import jakarta.transaction.Transactional;

import org.springframework.web.multipart.MultipartFile;



import java.time.LocalDate;

import java.util.List;

import java.util.UUID;



@Service

@Transactional

public class AvancementService {



    private final AvancementRepository repository;

    private final AvancementModuleRepository moduleRepository;



    public AvancementService(

            AvancementRepository repository,

            AvancementModuleRepository moduleRepository

    ) {

        this.repository = repository;

        this.moduleRepository = moduleRepository;

    }



    private AvancementDTO mapToDTO(Avancement a) {

        return AvancementDTO.builder()

                .id(a.getId())

                .avancement(a.getAvancement())

                .numeroTexte(a.getNumeroTexte())

                .signataire(a.getSignataire())

                .dateEffet(a.getDateEffet())

                .typeAvancement(a.getTypeAvancement())

                .dureeAnnees(a.getDureeAnnees())

                .fichierNom(a.getFichierNom())

                .build();

    }



    private String like(String v) {

        if (v == null || v.trim().isEmpty()) return null;

        return "%" + v.trim() + "%";

    }



    public List<ResultRechercheAvancementDTO> rechercher(RechercheAvancementDTO dto) {

        return repository.rechercherAvancements(

                like(dto.getNom()),

                like(dto.getPrenom()),

                dto.getGrade(),

                dto.getArme(),

                like(dto.getNouveauGrade()),

                dto.getAnnee()

        );

    }



    public Avancement ajouter(

            UUID moduleId,

            TypeAvancement typeAvancement,

            String avancement,

            String numeroTexte,

            String signataire,

            LocalDate dateEffet,

            Integer dureeAnnees,

            MultipartFile fichier

    ) throws Exception {



        // Validation métier

        if (typeAvancement == TypeAvancement.PROLONGATION_SERVICE) {

            if (dureeAnnees == null || dureeAnnees < 1 || dureeAnnees > 5) {

                throw new IllegalArgumentException(

                        "Une prolongation doit avoir une durée entre 1 et 5 ans"

                );

            }

            // Pour une prolongation, le champ "avancement" (grade) n'a pas de sens

            avancement = "Prolongation de service";

        } else {

            // Pour un vrai avancement, on s'assure qu'un grade est fourni

            if (avancement == null || avancement.isBlank()) {

                throw new IllegalArgumentException("Le grade est obligatoire pour un avancement");

            }

            dureeAnnees = null; // Pas de durée pour un avancement normal

        }



        AvancementModule module = moduleRepository.findById(moduleId)

                .orElseThrow(() -> new RuntimeException("Module introuvable"));



        Avancement av = new Avancement();

        av.setAvancement(avancement);

        av.setTypeAvancement(typeAvancement);

        av.setDureeAnnees(dureeAnnees);

        av.setNumeroTexte(numeroTexte);

        av.setSignataire(signataire);

        av.setDateEffet(dateEffet);

        av.setModule(module);



        if (fichier != null && !fichier.isEmpty()) {

            av.setFichier(fichier.getBytes());

            av.setFichierNom(fichier.getOriginalFilename());

            av.setFichierType(fichier.getContentType());

        }



        return repository.save(av);

    }



    public List<Avancement> getByModule(UUID moduleId) {

        return repository.findByModuleIdOrderByDateEffetDesc(moduleId);

    }



    public Avancement getById(UUID id) {

        return repository.findById(id)

                .orElseThrow(() -> new RuntimeException("Avancement introuvable"));

    }

    // Méthode pour récupérer uniquement les prolongations (pour affichage dans Carrière)

    public List<Avancement> getProlongationsByMilitaire(UUID militaireId) {

        // Il faut d'abord récupérer le module via le dossier...

        // Ou créer une requête directe dans le repository

        return repository.findByModuleIdAndTypeAvancement(militaireId, TypeAvancement.PROLONGATION_SERVICE);

    }



}

