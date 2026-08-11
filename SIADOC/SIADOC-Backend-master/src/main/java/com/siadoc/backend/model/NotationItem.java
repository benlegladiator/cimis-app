package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.LocalDate;
import java.util.UUID;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class NotationItem {
    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private LocalDate periodeDu;
    private LocalDate periodeAu;

    @Column(columnDefinition = "TEXT")
    private String appreciationGenerale;

    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] documentData;
    private String documentNom;
    private String documentType;

    @ManyToOne
    @JoinColumn(name = "module_id")
    @JsonIgnore
    private NotationModule module;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public LocalDate getPeriodeDu() { return periodeDu; }
    public void setPeriodeDu(LocalDate periodeDu) { this.periodeDu = periodeDu; }
    public LocalDate getPeriodeAu() { return periodeAu; }
    public void setPeriodeAu(LocalDate periodeAu) { this.periodeAu = periodeAu; }
    public String getAppreciationGenerale() { return appreciationGenerale; }
    public void setAppreciationGenerale(String appreciationGenerale) { this.appreciationGenerale = appreciationGenerale; }
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public NotationModule getModule() { return module; }
    public void setModule(NotationModule module) { this.module = module; }
}
