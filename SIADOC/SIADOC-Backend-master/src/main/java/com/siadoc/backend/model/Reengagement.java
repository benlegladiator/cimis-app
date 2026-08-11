package com.siadoc.backend.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Reengagement {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    private String designation; // Ex: "Contrat initial"
    private String lieu;
    private LocalDate date;

    @ManyToOne
    @JoinColumn(name = "carriere_module_id")
    @ToString.Exclude
    @JsonIgnore
    private Carriere carriere;

    public UUID getId() { return id; }
    public void setId(UUID id) { this.id = id; }
    public String getDesignation() { return designation; }
    public void setDesignation(String designation) { this.designation = designation; }
    public String getLieu() { return lieu; }
    public void setLieu(String lieu) { this.lieu = lieu; }
    public LocalDate getDate() { return date; }
    public void setDate(LocalDate date) { this.date = date; }
    public Carriere getCarriere() { return carriere; }
    public void setCarriere(Carriere carriere) { this.carriere = carriere; }
}