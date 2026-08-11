package com.siadoc.backend.dto;

/**
 * DTO entrant : reçu depuis l'application CIMIS via POST.
 *
 * Toutes les données binaires (empreintes, photo, QR code image)
 * sont envoyées encodées en Base64 dans les champs correspondants.
 *
 * Exemple de payload JSON :
 * {
 *   "matricule"         : "MAT-2023-12345",
 *   "empreinteDoigt1"   : "<base64 de l'image/données>",
 *   "empreinteDoigt1Type": "image/png",
 *   "empreinteDoigt2"   : "<base64>",
 *   "empreinteDoigt2Type": "image/png",
 *   "photoVisage"       : "<base64>",
 *   "photoVisageType"   : "image/jpeg",
 *   "qrCodeImage"       : "<base64>",
 *   "qrCodeContenu"     : "https://cimis.cm/verify/MAT-2023-12345"
 * }
 */
public class DonneeBiometriqueDTO {

    /** Matricule du militaire (militaire ou solde) permettant de l'identifier */
    private String matricule;

    // ===================================================
    // Empreintes digitales (Base64)
    // ===================================================

    private String empreinteDoigt1;       // image ou données en base64
    private String empreinteDoigt1Type;   // ex: "image/png"

    private String empreinteDoigt2;
    private String empreinteDoigt2Type;

    // ===================================================
    // Photo biométrique (Base64)
    // ===================================================

    private String photoVisage;
    private String photoVisageType;       // ex: "image/jpeg"

    // ===================================================
    // QR Code
    // ===================================================

    private String qrCodeImage;           // image PNG du QR code en base64
    private String qrCodeContenu;         // contenu textuel du QR code (URL, identifiant...)

    /** Numéro de Carte d'Identité Militaire attribué par CIMIS */
    private String numeroCIM;

    // ===================================================
    // Getters & Setters
    // ===================================================

    public String getMatricule() { return matricule; }
    public void setMatricule(String matricule) { this.matricule = matricule; }

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
}
