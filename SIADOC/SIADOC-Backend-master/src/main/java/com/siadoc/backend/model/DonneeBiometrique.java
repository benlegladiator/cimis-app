package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.LocalDateTime;
import java.util.UUID;

/**
 * Entité stockant les données biométriques reçues de l'application CIMIS
 * pour un militaire donné.
 *
 * Champs binaires (empreintes, photo visage, image QR code) stockés en bytea.
 * La table est en relation ManyToOne avec Militaire (un militaire peut avoir
 * plusieurs envois CIMIS, mais en pratique on fait un upsert sur le dernier).
 */
@Entity
@Table(name = "donnee_biometrique")
public class DonneeBiometrique {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    // ===================================================
    // Lien vers le militaire concerné
    // ===================================================
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "militaire_id", nullable = false)
    @JsonIgnore
    private Militaire militaire;

    // ===================================================
    // Empreintes digitales (stockées en binaire)
    // ===================================================

    /** Empreinte index gauche (image PNG ou données ISO 19794 en base64 côté API) */
    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] empreinteDoigt1;

    private String empreinteDoigt1Type; // ex: "image/png" ou "application/octet-stream"

    /** Empreinte index droit */
    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] empreinteDoigt2;

    private String empreinteDoigt2Type;

    // ===================================================
    // Photo biométrique du visage
    // ===================================================

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] photoVisage;

    private String photoVisageType; // ex: "image/jpeg"

    // ===================================================
    // QR Code
    // ===================================================

    /** Image du QR code (PNG en base64 côté API) */
    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] qrCodeImage;

    /** Contenu textuel encodé dans le QR code (URL, JSON, identifiant...) */
    @Column(length = 2048)
    private String qrCodeContenu;

    // ===================================================
    // Numéro de Carte d'Identité Militaire
    // ===================================================

    /** Numéro CIM envoyé par CIMIS — identifiant de la carte d'identité militaire */
    private String numeroCIM;

    // ===================================================
    // Métadonnées de réception
    // ===================================================

    /** Date à laquelle SIADOC a reçu les données */
    private LocalDateTime dateReception;

    /** Identifiant de l'application source (ex: "CIMIS") */
    private String sourceApplication;

    // ===================================================
    // Getters & Setters
    // ===================================================

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }

    public Militaire getMilitaire() { return militaire; }
    public void setMilitaire(Militaire militaire) { this.militaire = militaire; }

    public byte[] getEmpreinteDoigt1() { return empreinteDoigt1; }
    public void setEmpreinteDoigt1(byte[] empreinteDoigt1) { this.empreinteDoigt1 = empreinteDoigt1; }

    public String getEmpreinteDoigt1Type() { return empreinteDoigt1Type; }
    public void setEmpreinteDoigt1Type(String empreinteDoigt1Type) { this.empreinteDoigt1Type = empreinteDoigt1Type; }

    public byte[] getEmpreinteDoigt2() { return empreinteDoigt2; }
    public void setEmpreinteDoigt2(byte[] empreinteDoigt2) { this.empreinteDoigt2 = empreinteDoigt2; }

    public String getEmpreinteDoigt2Type() { return empreinteDoigt2Type; }
    public void setEmpreinteDoigt2Type(String empreinteDoigt2Type) { this.empreinteDoigt2Type = empreinteDoigt2Type; }

    public byte[] getPhotoVisage() { return photoVisage; }
    public void setPhotoVisage(byte[] photoVisage) { this.photoVisage = photoVisage; }

    public String getPhotoVisageType() { return photoVisageType; }
    public void setPhotoVisageType(String photoVisageType) { this.photoVisageType = photoVisageType; }

    public byte[] getQrCodeImage() { return qrCodeImage; }
    public void setQrCodeImage(byte[] qrCodeImage) { this.qrCodeImage = qrCodeImage; }

    public String getQrCodeContenu() { return qrCodeContenu; }
    public void setQrCodeContenu(String qrCodeContenu) { this.qrCodeContenu = qrCodeContenu; }

    public String getNumeroCIM() { return numeroCIM; }
    public void setNumeroCIM(String numeroCIM) { this.numeroCIM = numeroCIM; }

    public LocalDateTime getDateReception() { return dateReception; }
    public void setDateReception(LocalDateTime dateReception) { this.dateReception = dateReception; }

    public String getSourceApplication() { return sourceApplication; }
    public void setSourceApplication(String sourceApplication) { this.sourceApplication = sourceApplication; }
}
