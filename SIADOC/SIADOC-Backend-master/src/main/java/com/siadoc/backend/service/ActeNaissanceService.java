package com.siadoc.backend.service;



import com.siadoc.backend.model.ActeNaissance;

import com.siadoc.backend.model.EtatCivil;

import com.siadoc.backend.repository.ActeNaissanceRepository;

import com.siadoc.backend.repository.EtatCivilRepository;

import com.siadoc.backend.dto.search.ResultRechercheEtatCivilDTO;

import com.siadoc.backend.dto.search.RechercheEtatCivilDTO;

import org.springframework.stereotype.Service;

import org.springframework.web.multipart.MultipartFile;



import java.util.List;

import java.util.UUID;



@Service

public class ActeNaissanceService {



    private final ActeNaissanceRepository repository;

    private final EtatCivilRepository etatCivilRepository;



    public ActeNaissanceService(ActeNaissanceRepository repository,

                                EtatCivilRepository etatCivilRepository) {

        this.repository = repository;

        this.etatCivilRepository = etatCivilRepository;

    }



    public ActeNaissance ajouter(UUID etatCivilId,

                                 String numeroActe,

                                 java.time.LocalDate dateEtablissement,

                                 String lieu,

                                 MultipartFile fichier) throws Exception {



        EtatCivil etatCivil = etatCivilRepository.findById(etatCivilId)

                .orElseThrow(() -> new RuntimeException("Module EtatCivil introuvable"));



        ActeNaissance acte = new ActeNaissance();

        acte.setNumeroActe(numeroActe);

        acte.setDateEtablissement(dateEtablissement);

        acte.setLieuEtablissement(lieu);

        acte.setEtatCivil(etatCivil);



        if (fichier != null && !fichier.isEmpty()) {

            acte.setFichier(fichier.getBytes());

            acte.setFichierNom(fichier.getOriginalFilename());

            acte.setFichierType(fichier.getContentType());

        }



        return repository.save(acte);

    }



    public List<ActeNaissance> getByEtatCivil(UUID etatCivilId) {

        return repository.findByEtatCivilId(etatCivilId);

    }



    public ActeNaissance getById(UUID id) {

        return repository.findById(id)

                .orElseThrow(() -> new RuntimeException("Acte introuvable"));

    }



    public List<ResultRechercheEtatCivilDTO> rechercherActeNaissance(RechercheEtatCivilDTO dto){



        return repository.rechercherActeNaissance(

                like(dto.getNom()),

                like(dto.getPrenom()),

                dto.getGrade(),

                dto.getArme(),

                dto.getAnnee(),

                like(dto.getLieu())

        );

    }



    private String like(String value){

        if(value == null || value.isBlank()){

            return null;

        }

        return "%" + value + "%";

    }



}

