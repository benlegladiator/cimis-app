package com.siadoc.backend.service;

import com.siadoc.backend.dto.DonneeBiometriqueDTO;
import com.siadoc.backend.dto.DonneeBiometriqueReponseDTO;
import com.siadoc.backend.model.DonneeBiometrique;
import com.siadoc.backend.model.Militaire;
import com.siadoc.backend.repository.DonneeBiometriqueRepository;
import com.siadoc.backend.repository.MilitaireRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.Base64;
import java.util.Optional;

/**
 * Service gérant la réception et la consultation des données biométriques
 * envoyées par l'application CIMIS.
 *
 * Logique d'upsert : si un enregistrement biométrique existe déjà pour le
 * militaire, il est mis à jour (les nouvelles données écrasent les anciennes).
 * Cela garantit qu'il n'y a qu'une seule entrée par militaire.
 */
@Service
public class DonneeBiometriqueService {

    private final DonneeBiometriqueRepository biometriqueRepository;
    private final MilitaireRepository militaireRepository;

    public DonneeBiometriqueService(DonneeBiometriqueRepository biometriqueRepository,
                                    MilitaireRepository militaireRepository) {
        this.biometriqueRepository = biometriqueRepository;
        this.militaireRepository = militaireRepository;
    }

    // -----------------------------------------------------------------------
    // Helpers Base64 → byte[]
    // -----------------------------------------------------------------------

    /**
     * Décode une chaîne Base64 en tableau de bytes.
     * Retourne null si la chaîne est nulle ou vide.
     */
    private byte[] decodeBase64(String base64) {
        if (base64 == null || base64.isBlank()) return null;
        // Supprimer un éventuel préfixe "data:image/png;base64,..." envoyé par certains clients
        String data = base64.contains(",") ? base64.split(",", 2)[1] : base64;
        return Base64.getDecoder().decode(data.trim());
    }

    /**
     * Encode un tableau de bytes en Base64 pour l'exposer en JSON.
     * Retourne null si le tableau est null ou vide.
     */
    private String encodeBase64(byte[] bytes) {
        if (bytes == null || bytes.length == 0) return null;
        return Base64.getEncoder().encodeToString(bytes);
    }

    // -----------------------------------------------------------------------
    // Recherche du militaire par matricule (militaire ou solde)
    // -----------------------------------------------------------------------

    private Militaire findMilitaireByMatricule(String matricule) {
        Optional<Militaire> opt = militaireRepository.findByMatriculeMilitaire(matricule);
        if (opt.isEmpty()) {
            opt = militaireRepository.findByMatriculeSolde(matricule);
        }
        return opt.orElseThrow(() ->
                new RuntimeException("Aucun militaire trouvé pour le matricule : " + matricule));
    }

    // -----------------------------------------------------------------------
    // Réception (upsert)
    // -----------------------------------------------------------------------

    /**
     * Reçoit les données biométriques de CIMIS et les persiste.
     * Si une entrée existe déjà pour ce militaire, elle est mise à jour
     * (upsert). Sinon, une nouvelle entrée est créée.
     *
     * @param dto Le DTO reçu depuis CIMIS.
     * @return Un message de confirmation.
     */
    @Transactional
    public String recevoirDonneesBiometriques(DonneeBiometriqueDTO dto) {

        if (dto.getMatricule() == null || dto.getMatricule().isBlank()) {
            throw new RuntimeException("Le champ 'matricule' est obligatoire.");
        }

        Militaire militaire = findMilitaireByMatricule(dto.getMatricule());

        // Upsert : récupérer l'entrée existante ou en créer une nouvelle
        DonneeBiometrique entite = biometriqueRepository
                .findByMilitaireId(militaire.getId())
                .orElse(new DonneeBiometrique());

        entite.setMilitaire(militaire);
        entite.setDateReception(LocalDateTime.now());
        entite.setSourceApplication("CIMIS");

        // Empreintes
        if (dto.getEmpreinteDoigt1() != null) {
            entite.setEmpreinteDoigt1(decodeBase64(dto.getEmpreinteDoigt1()));
            entite.setEmpreinteDoigt1Type(dto.getEmpreinteDoigt1Type());
        }
        if (dto.getEmpreinteDoigt2() != null) {
            entite.setEmpreinteDoigt2(decodeBase64(dto.getEmpreinteDoigt2()));
            entite.setEmpreinteDoigt2Type(dto.getEmpreinteDoigt2Type());
        }

        // Photo biométrique
        if (dto.getPhotoVisage() != null) {
            entite.setPhotoVisage(decodeBase64(dto.getPhotoVisage()));
            entite.setPhotoVisageType(dto.getPhotoVisageType());
        }

        // QR Code
        if (dto.getQrCodeImage() != null) {
            entite.setQrCodeImage(decodeBase64(dto.getQrCodeImage()));
        }
        if (dto.getQrCodeContenu() != null) {
            entite.setQrCodeContenu(dto.getQrCodeContenu());
        }

        // Numéro CIM (Carte d'Identité Militaire)
        if (dto.getNumeroCIM() != null) {
            entite.setNumeroCIM(dto.getNumeroCIM());
        }

        biometriqueRepository.save(entite);

        boolean estMiseAJour = entite.getId() != null;
        return estMiseAJour
                ? "Données biométriques mises à jour pour le militaire : " + dto.getMatricule()
                : "Données biométriques enregistrées pour le militaire : " + dto.getMatricule();
    }

    // -----------------------------------------------------------------------
    // Consultation
    // -----------------------------------------------------------------------

    /**
     * Retourne les données biométriques stockées pour un militaire donné.
     *
     * @param matricule Le matricule du militaire.
     * @return Le DTO de réponse avec les binaires encodés en Base64.
     */
    public DonneeBiometriqueReponseDTO consulter(String matricule) {

        Militaire militaire = findMilitaireByMatricule(matricule);

        DonneeBiometrique entite = biometriqueRepository
                .findByMilitaireId(militaire.getId())
                .orElseThrow(() -> new RuntimeException(
                        "Aucune donnée biométrique trouvée pour le matricule : " + matricule));

        DonneeBiometriqueReponseDTO reponse = new DonneeBiometriqueReponseDTO();
        reponse.setMatricule(matricule);
        reponse.setNomMilitaire(militaire.getNom());
        reponse.setPrenomMilitaire(militaire.getPrenom());

        reponse.setEmpreinteDoigt1(encodeBase64(entite.getEmpreinteDoigt1()));
        reponse.setEmpreinteDoigt1Type(entite.getEmpreinteDoigt1Type());
        reponse.setEmpreinteDoigt2(encodeBase64(entite.getEmpreinteDoigt2()));
        reponse.setEmpreinteDoigt2Type(entite.getEmpreinteDoigt2Type());

        reponse.setPhotoVisage(encodeBase64(entite.getPhotoVisage()));
        reponse.setPhotoVisageType(entite.getPhotoVisageType());

        reponse.setQrCodeImage(encodeBase64(entite.getQrCodeImage()));
        reponse.setQrCodeContenu(entite.getQrCodeContenu());

        // Numéro CIM
        reponse.setNumeroCIM(entite.getNumeroCIM());

        reponse.setDateReception(entite.getDateReception());
        reponse.setSourceApplication(entite.getSourceApplication());

        return reponse;
    }
}
