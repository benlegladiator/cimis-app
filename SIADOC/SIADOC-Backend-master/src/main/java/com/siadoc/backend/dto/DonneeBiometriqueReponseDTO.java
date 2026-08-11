package com.siadoc.backend.dto;

import java.time.LocalDateTime;

/**
 * DTO de réponse renvoyé lors de la consultation des données biométriques
 * d'un militaire — les données binaires sont encodées en Base64 pour
 * pouvoir être transmises en JSON.
 */
public class DonneeBiometriqueReponseDTO {

    private String matricule;
    private String nomMilitaire;
    private String prenomMilitaire;

    // Binaires encodés en Base64
    private String empreinteDoigt1;
    private String empreinteDoigt1Type;
    private String empreinteDoigt2;
    private String empreinteDoigt2Type;
    private String photoVisage;
    private String photoVisageType;
    private String qrCodeImage;
    private String qrCodeContenu;

    /** Numéro de Carte d'Identité Militaire reu de CIMIS */
    private String numeroCIM;

    private LocalDateTime dateReception;
    private String sourceApplication;

    public DonneeBiometriqueReponseDTO() {}

    // ===================================================
    // Getters & Setters
    // ===================================================

    public String getMatricule() { return matricule; }
    public void setMatricule(String matricule) { this.matricule = matricule; }

    public String getNomMilitaire() { return nomMilitaire; }
    public void setNomMilitaire(String nomMilitaire) { this.nomMilitaire = nomMilitaire; }

    public String getPrenomMilitaire() { return prenomMilitaire; }
    public void setPrenomMilitaire(String prenomMilitaire) { this.prenomMilitaire = prenomMilitaire; }

    public String getEmpreinteDoigt1() { return empreinteDoigt1; }
    public void setEmpreinteDoigt1(String empreinteDoigt1) { this.empreinteDoigt1 = empreinteDoigt1; }

    public String getEmpreinteDoigt1Type() { return empreinteDoigt1Type; }
    public void setEmpreinteDoigt1Type(String empreinteDoigt1Type) { this.empreinteDoigt1Type = empreinteDoigt1Type; }

    public String getEmpreinteDoigt2() { return empreinteDoigt2; }
    public void setEmpreinteDoigt2(String empreinteDoigt2) { this.empreinteDoigt2 = empreinteDoigt2; }

    public String getEmpreinteDoigt2Type() { return empreinteDoigt2Type; }
    public void setEmpreinteDoigt2Type(String empreinteDoigt2Type) { this.empreinteDoigt2Type = empreinteDoigt2Type; }

    public String getPhotoVisage() { return photoVisage; }
    public void setPhotoVisage(String photoVisage) { this.photoVisage = photoVisage; }

    public String getPhotoVisageType() { return photoVisageType; }
    public void setPhotoVisageType(String photoVisageType) { this.photoVisageType = photoVisageType; }

    public String getQrCodeImage() { return qrCodeImage; }
    public void setQrCodeImage(String qrCodeImage) { this.qrCodeImage = qrCodeImage; }

    public String getQrCodeContenu() { return qrCodeContenu; }
    public void setQrCodeContenu(String qrCodeContenu) { this.qrCodeContenu = qrCodeContenu; }

    public String getNumeroCIM() { return numeroCIM; }
    public void setNumeroCIM(String numeroCIM) { this.numeroCIM = numeroCIM; }

    public LocalDateTime getDateReception() { return dateReception; }
    public void setDateReception(LocalDateTime dateReception) { this.dateReception = dateReception; }

    public String getSourceApplication() { return sourceApplication; }
    public void setSourceApplication(String sourceApplication) { this.sourceApplication = sourceApplication; }
}
