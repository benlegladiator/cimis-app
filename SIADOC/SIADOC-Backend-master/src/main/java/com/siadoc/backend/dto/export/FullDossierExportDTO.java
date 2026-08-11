package com.siadoc.backend.dto.export;

import com.siadoc.backend.dto.*;
import lombok.Data;
import java.util.List;
import java.util.UUID;

@Data
public class FullDossierExportDTO {
    private UUID dossierId;
    private String matriculeMilitaire;
    private String matriculeSolde;
    private String nom;
    private String prenom;
    private String nomJeuneFille;
    // Identité complète (Enrichi pour GESMIL)
    private String grade;
    private String dateGrade;
    private Integer echelon;
    private String dateEchelon;
    private String dateService;
    private String armeService;
    private String sexe;
    private String aptitudeOps;
    private String statut;
    private String dateEnregistrement;
    private String dateMiseAJour;
    private String photoBase64;
    
    // Modules
    private CarriereDTO carriere;
    private EtatCivilDTO etatCivil;
    private MedicalGlobalDTO medical;
    private List<DiplomeDTO> diplomes;
    private List<StageDTO> stages;
    private List<MutationItemDTO> mutations;
    private List<AvancementDTO> avancements;
    private List<PunitionDTO> punitions;
    private List<NotationDTO> notations;
    private List<RecompenseDTO> recompenses;
    private List<CampagneDTO> campagnes;
    
    // l'État Civil si nécessaire
    private String dateNaissance;
    private String lieuNaissance;
    private String categorie;
    private Integer anneeContingent;
    // Unités
    private String region;
    private String brigade;
    private String bataillon;
    private String compagnie;
}
