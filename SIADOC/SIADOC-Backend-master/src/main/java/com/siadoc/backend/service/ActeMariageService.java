package com.siadoc.backend.service;



import com.siadoc.backend.model.*;

import com.siadoc.backend.repository.*;

import org.springframework.stereotype.Service;

import org.springframework.web.multipart.MultipartFile;

import org.springframework.transaction.annotation.Transactional;

import com.siadoc.backend.dto.search.ResultRechercheMariageDTO;

import com.siadoc.backend.dto.search.RechercheMariageDTO;

import java.util.List;

import java.util.UUID;



@Service

public class ActeMariageService {



    private final ActeMariageRepository repository;

    private final EtatCivilRepository etatCivilRepository;



    public ActeMariageService(ActeMariageRepository repository,

                              EtatCivilRepository etatCivilRepository) {

        this.repository = repository;

        this.etatCivilRepository = etatCivilRepository;

    }



    public ActeMariage ajouter(

            UUID etatCivilId,

            String numeroActe,

            String nomConjoint,

            java.time.LocalDate dateMariage,

            String lieuMariage,

            MultipartFile fichier

    ) throws Exception {



        EtatCivil etatCivil = etatCivilRepository.findById(etatCivilId)

                .orElseThrow(() -> new RuntimeException("Module EtatCivil introuvable"));



        ActeMariage acte = new ActeMariage();

        acte.setNumeroActe(numeroActe);

        acte.setNomConjoint(nomConjoint);

        acte.setDateMariage(dateMariage);

        acte.setLieuMariage(lieuMariage);

        acte.setEtatCivil(etatCivil);



        if (fichier != null && !fichier.isEmpty()) {

            acte.setFichier(fichier.getBytes());

            acte.setFichierNom(fichier.getOriginalFilename());

            acte.setFichierType(fichier.getContentType());

        }



        return repository.save(acte);

    }



    @Transactional(readOnly = true)

    public List<ActeMariage> getByEtatCivil(UUID id) {

        return repository.findByEtatCivilId(id);

    }





    public ActeMariage getById(UUID id) {

        return repository.findById(id)

                .orElseThrow(() -> new RuntimeException("Acte mariage introuvable"));

    }



    public List<ResultRechercheMariageDTO> rechercherMariage(RechercheMariageDTO dto){



        return repository.rechercherActeMariage(

                like(dto.getNom()),

                like(dto.getPrenom()),

                dto.getGrade(),

                dto.getArme(),

                dto.getAnnee(),

                like(dto.getLieu()),

                like(dto.getNomConjoint())

        );



    }



    private String like(String value){

        if(value == null || value.isBlank()){

            return null;

        }

        return "%" + value + "%";

    }

}



