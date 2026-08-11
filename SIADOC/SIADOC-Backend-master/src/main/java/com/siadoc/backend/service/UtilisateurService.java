package com.siadoc.backend.service;

import com.siadoc.backend.model.*;
import com.siadoc.backend.repository.*;
import org.springframework.stereotype.Service;

import java.util.UUID;

@Service
public class UtilisateurService {

    private final UtilisateurRepository utilisateurRepository;
    private final SecteurMilitaireRepository secteurRepository;
    private final RegionMilitaireRepository regionRepository;
    private final BrigadeRepository brigadeRepository;
    private final BataillonRepository bataillonRepository;
    private final CompagnieRepository compagnieRepository;

    public UtilisateurService(
            UtilisateurRepository utilisateurRepository,
            SecteurMilitaireRepository secteurRepository,
            RegionMilitaireRepository regionRepository,
            BrigadeRepository brigadeRepository,
            BataillonRepository bataillonRepository,
            CompagnieRepository compagnieRepository
    ) {
        this.utilisateurRepository = utilisateurRepository;
        this.secteurRepository = secteurRepository;
        this.regionRepository = regionRepository;
        this.brigadeRepository = brigadeRepository;
        this.bataillonRepository = bataillonRepository;
        this.compagnieRepository = compagnieRepository;
    }

    public Utilisateur creerUtilisateur(
            String username,
            String password,
            Role role,
            UUID secteurId,
            UUID regionId,
            UUID brigadeId,
            UUID bataillonId,
            UUID compagnieId
    ) {

        if (utilisateurRepository.findByUsername(username).isPresent()) {
            throw new RuntimeException("Username déjà utilisé");
        }

        Utilisateur user = new Utilisateur();
        user.setUsername(username);
        user.setPassword(password); // hash plus tard
        user.setRole(role);

        if (role == Role.COMMANDANT) {
            if (secteurId != null) {
                user.setSecteur(secteurRepository.findById(secteurId).orElse(null));
            }
        } else if (role == Role.RMIA) {
            if (regionId != null) {
                user.setRegion(regionRepository.findById(regionId).orElse(null));
            }
        } else if (role == Role.BRIGADE) {
            if (brigadeId != null) {
                user.setBrigade(brigadeRepository.findById(brigadeId).orElse(null));
            }
        } else if (role == Role.BATAILLON) {
            if (bataillonId != null) {
                user.setBataillon(bataillonRepository.findById(bataillonId).orElse(null));
            }
        } else if (role == Role.COMMANDANT_COMPAGNIE) {
            if (compagnieId != null) {
                user.setCompagnie(compagnieRepository.findById(compagnieId).orElse(null));
            }
        }

        return utilisateurRepository.save(user);
    }

    public void deleteUtilisateur(UUID id) {
        utilisateurRepository.deleteById(id);
    }
}