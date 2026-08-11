package com.siadoc.backend.service;

import org.springframework.stereotype.Service;
import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;

import java.util.UUID;
import java.util.List;

@Service
public class InformationsPersonnellesService {

    private final InformationsPersonnellesRepository repository;
    private final EtatCivilRepository etatCivilRepository;
    private final DossierAdministratifService dossierService;

    public InformationsPersonnellesService(
            InformationsPersonnellesRepository repository,
            EtatCivilRepository etatCivilRepository,
            DossierAdministratifService dossierService) {

        this.repository = repository;
        this.etatCivilRepository = etatCivilRepository;
        this.dossierService = dossierService;
    }


    public InformationsPersonnelles enregistrer(
            UUID etatCivilId,
            InformationsPersonnelles dto) {

        EtatCivil etatCivil = etatCivilRepository.findById(etatCivilId)
                .orElseThrow(() ->
                        new RuntimeException("EtatCivil introuvable"));

        // Vérifie si existe déjà (OneToOne)
        InformationsPersonnelles info =
                repository.findByEtatCivilId(etatCivilId)
                        .orElse(new InformationsPersonnelles());

        info.setEtatCivil(etatCivil);

        info.setNom(dto.getNom());
        info.setPrenom(dto.getPrenom());
        info.setSexe(dto.getSexe());
        info.setNumeroCNI(dto.getNumeroCNI());
        info.setSituationMatrimoniale(dto.getSituationMatrimoniale());
        info.setRegime(dto.getRegime());
        info.setNombreConjoints(dto.getNombreConjoints());
        info.setNombreEnfants(dto.getNombreEnfants());
        info.setTelephone(dto.getTelephone());
        info.setPpcaNom(dto.getPpcaNom());
        info.setPpcaTelephone(dto.getPpcaTelephone());
        info.setPpcaLien(dto.getPpcaLien());
        info.setAdresseComplete(dto.getAdresseComplete());
        info.setRegionOrigine(dto.getRegionOrigine());
        info.setLanguesParlees(dto.getLanguesParlees());

        InformationsPersonnelles saved = repository.save(info);
        dossierService.notifierModification(etatCivil.getDossier(), "État-Civil");
        return saved;
    }

    public InformationsPersonnelles getByEtatCivil(UUID etatCivilId) {
        return repository.findByEtatCivilId(etatCivilId)
                .orElseThrow(() ->
                        new RuntimeException("Informations non trouvées"));
    }
}
