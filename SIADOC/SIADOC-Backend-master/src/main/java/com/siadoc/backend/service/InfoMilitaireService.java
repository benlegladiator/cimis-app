package com.siadoc.backend.service;

import com.siadoc.backend.dto.export.InfoMilitaireDTO;
import com.siadoc.backend.model.Carriere;
import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.model.Militaire;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.MilitaireRepository;
import org.springframework.stereotype.Service;

import java.util.List;
import java.util.Optional;
import java.util.stream.Collectors;

/**
 * Service exposant les informations essentielles d'un militaire
 * à destination des applications partenaires via l'API d'export.
 *
 * Informations retournées : nom, prénom, matricule, date de naissance,
 * corps (depuis le dossier/carrière), grade, date du grade, sexe, numeroCNI.
 */
@Service
public class InfoMilitaireService {

    private final MilitaireRepository militaireRepository;
    private final DossierAdministratifRepository dossierRepository;

    public InfoMilitaireService(MilitaireRepository militaireRepository,
                                DossierAdministratifRepository dossierRepository) {
        this.militaireRepository = militaireRepository;
        this.dossierRepository = dossierRepository;
    }

    // -----------------------------------------------------------------------
    // Conversion Militaire → InfoMilitaireDTO
    // -----------------------------------------------------------------------

    private InfoMilitaireDTO toDTO(Militaire m) {
        // Matricule : on expose le matricule militaire ; sinon le matricule solde
        String matricule = (m.getMatriculeMilitaire() != null && !m.getMatriculeMilitaire().isBlank())
                ? m.getMatriculeMilitaire()
                : m.getMatriculeSolde();

        // Corps : lu dans la carrière du dossier administratif
        String corps = null;
        if (m.getDossier() != null && m.getDossier().getCarriere() != null) {
            Carriere carriere = m.getDossier().getCarriere();
            if (carriere.getCorps() != null) {
                corps = carriere.getCorps().name();
            }
        }

        // Numéro CNI : lu dans l'état civil du dossier (on prend la 1ère CNI si plusieurs)
        String numeroCNI = null;
        if (m.getDossier() != null
                && m.getDossier().getEtatCivil() != null
                && m.getDossier().getEtatCivil().getCnis() != null
                && !m.getDossier().getEtatCivil().getCnis().isEmpty()) {
            numeroCNI = m.getDossier().getEtatCivil().getCnis().get(0).getNumero();
        }

        return new InfoMilitaireDTO(
                m.getNom(),
                m.getPrenom(),
                matricule,
                m.getDateNaissance(),
                corps,
                m.getGrade(),
                m.getDateGrade(),
                m.getSexe(),
                numeroCNI
        );
    }

    // -----------------------------------------------------------------------
    // Méthodes publiques
    // -----------------------------------------------------------------------

    /**
     * Retourne les informations d'un militaire identifié par son matricule
     * (matricule militaire OU matricule solde acceptés).
     *
     * @param matricule Le matricule à rechercher.
     * @return Le DTO correspondant.
     * @throws RuntimeException si aucun militaire n'est trouvé.
     */
    public InfoMilitaireDTO getByMatricule(String matricule) {
        // Recherche par matricule militaire
        Optional<Militaire> opt = militaireRepository.findByMatriculeMilitaire(matricule);

        // Sinon par matricule solde
        if (opt.isEmpty()) {
            opt = militaireRepository.findByMatriculeSolde(matricule);
        }

        Militaire militaire = opt.orElseThrow(() ->
                new RuntimeException("Aucun militaire trouvé pour le matricule : " + matricule));

        return toDTO(militaire);
    }

    /**
     * Retourne les informations essentielles de tous les militaires actifs.
     *
     * @return Liste de DTOs.
     */
    public List<InfoMilitaireDTO> getAllActifs() {
        return militaireRepository.findActifs()
                .stream()
                .map(this::toDTO)
                .collect(Collectors.toList());
    }
}
