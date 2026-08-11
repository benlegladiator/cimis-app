package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDate;
import java.util.UUID;
import com.fasterxml.jackson.annotation.JsonIgnore;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class MutationItem {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    private TypeMutation type; // C'est ce qui sépare SECTION 1 de SECTION 2

    private String emploi; // "Emploi tenu"

    @Enumerated(EnumType.STRING)
    private UniteMilitaire unite;

    @Enumerated(EnumType.STRING)
    private VilleCameroun ville;

    private String numeroTexte;
    private LocalDate dateTexte;

    // GESTION FICHIER (PDF/IMG)
    @JdbcTypeCode(SqlTypes.BINARY)
    @Column(columnDefinition = "bytea")
    @JsonIgnore
    private byte[] documentData;

    private String documentNom;
    private String documentType;

    @ManyToOne
    @JoinColumn(name = "module_id")
    @JsonIgnore
    private MutationsModule module;

    @ManyToOne
    @JoinColumn(name = "compagnie_id")
    @JsonIgnore
    private Compagnie compagnie;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public TypeMutation getType() { return type; }
    public void setType(TypeMutation type) { this.type = type; }
    public String getEmploi() { return emploi; }
    public void setEmploi(String emploi) { this.emploi = emploi; }
    public UniteMilitaire getUnite() { return unite; }
    public void setUnite(UniteMilitaire unite) { this.unite = unite; }
    public VilleCameroun getVille() { return ville; }
    public void setVille(VilleCameroun ville) { this.ville = ville; }
    public String getNumeroTexte() { return numeroTexte; }
    public void setNumeroTexte(String numeroTexte) { this.numeroTexte = numeroTexte; }
    public LocalDate getDateTexte() { return dateTexte; }
    public void setDateTexte(LocalDate dateTexte) { this.dateTexte = dateTexte; }
    public byte[] getDocumentData() { return documentData; }
    public void setDocumentData(byte[] documentData) { this.documentData = documentData; }
    public String getDocumentNom() { return documentNom; }
    public void setDocumentNom(String documentNom) { this.documentNom = documentNom; }
    public String getDocumentType() { return documentType; }
    public void setDocumentType(String documentType) { this.documentType = documentType; }
    public MutationsModule getModule() { return module; }
    public void setModule(MutationsModule module) { this.module = module; }
    public Compagnie getCompagnie() { return compagnie; }
    public void setCompagnie(Compagnie compagnie) { this.compagnie = compagnie; }
}
