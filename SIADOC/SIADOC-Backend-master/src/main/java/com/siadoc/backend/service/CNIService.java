package com.siadoc.backend.service;



import org.springframework.stereotype.Service;

import org.springframework.transaction.annotation.Transactional;

import org.springframework.web.multipart.MultipartFile;

import com.siadoc.backend.dto.search.ResultRechercheCniDTO;

import com.siadoc.backend.dto.search.RechercheCniDTO;

import com.siadoc.backend.model.*;

import com.siadoc.backend.repository.*;



import java.io.IOException;

import java.time.LocalDate;

import java.util.UUID;

import java.util.List;



@Service

public class CNIService {



    private final CNIRepository cniRepository;

    private final EtatCivilRepository etatCivilRepository;



    public CNIService(CNIRepository cniRepository,

                      EtatCivilRepository etatCivilRepository) {

        this.cniRepository = cniRepository;

        this.etatCivilRepository = etatCivilRepository;

    }



    @Transactional

    public CNI ajouterCNI(UUID etatCivilId,

                          String numero,

                          LocalDate dateDelivrance,

                          LocalDate dateExpiration,

                          String lieu,

                          MultipartFile fichier) throws IOException {



        EtatCivil etatCivil = etatCivilRepository.findById(etatCivilId)

                .orElseThrow(() -> new RuntimeException("Module EtatCivil introuvable"));



        CNI cni = new CNI();

        cni.setEtatCivil(etatCivil);

        cni.setNumero(numero);

        cni.setDateDelivrance(dateDelivrance);

        cni.setDateExpiration(dateExpiration);

        cni.setLieuDelivrance(lieu);



        if (fichier != null && !fichier.isEmpty()) {

            cni.setFichier(fichier.getBytes());

            cni.setFichierNom(fichier.getOriginalFilename());

            cni.setFichierType(fichier.getContentType());

        }



        return cniRepository.save(cni);

    }



    public List<CNI> getByEtatCivil(UUID etatCivilId) {

        return cniRepository.findByEtatCivilId(etatCivilId);

    }



    public CNI getById(UUID id) {

        return cniRepository.findById(id)

                .orElseThrow(() -> new RuntimeException("CNI introuvable"));

    }



    public List<ResultRechercheCniDTO> rechercherCni(RechercheCniDTO dto){



        return cniRepository.rechercherCni(

                like(dto.getNom()),

                like(dto.getPrenom()),

                dto.getGrade(),

                dto.getArme(),

                like(dto.getNumero())

        );

    }



    private String like(String value){

        if(value == null || value.isBlank()){

            return null;

        }

        return "%" + value + "%";

    }

}

