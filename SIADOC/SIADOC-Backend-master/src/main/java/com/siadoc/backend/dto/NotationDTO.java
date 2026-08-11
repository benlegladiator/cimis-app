package com.siadoc.backend.dto;

import lombok.Data;
import java.time.LocalDate;
import java.util.UUID;

@Data
public class NotationDTO {
    private UUID id;
    private LocalDate periodeDu;
    private LocalDate periodeAu;
    private String appreciationGenerale; // appreciationGlobale
    private QualitesDTO qualites; // L'objet imbriqué
    private String document;
    private Double moyenneCalculee; // Champ bonus pour l'affichage

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public LocalDate getPeriodeDu() { return periodeDu; }
    public void setPeriodeDu(LocalDate periodeDu) { this.periodeDu = periodeDu; }
    public LocalDate getPeriodeAu() { return periodeAu; }
    public void setPeriodeAu(LocalDate periodeAu) { this.periodeAu = periodeAu; }
    public String getAppreciationGenerale() { return appreciationGenerale; }
    public void setAppreciationGenerale(String appreciationGenerale) { this.appreciationGenerale = appreciationGenerale; }
    public QualitesDTO getQualites() { return qualites; }
    public void setQualites(QualitesDTO qualites) { this.qualites = qualites; }
    public String getDocument() { return document; }
    public void setDocument(String document) { this.document = document; }
    public Double getMoyenneCalculee() { return moyenneCalculee; }
    public void setMoyenneCalculee(Double moyenneCalculee) { this.moyenneCalculee = moyenneCalculee; }
}