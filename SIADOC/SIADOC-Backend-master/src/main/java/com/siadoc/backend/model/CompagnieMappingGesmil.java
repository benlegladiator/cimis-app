package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import java.util.UUID;

@Entity
@Table(name = "compagnie_mapping_gesmil")
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class CompagnieMappingGesmil {

    @Id
    @GeneratedValue(strategy = GenerationType.AUTO)
    private UUID id;

    /** Le code ou nom d'unité tel qu'il arrive dans les données GESMIL */
    @Column(nullable = false, unique = true)
    private String codeGesmil;

    /** La compagnie correspondante dans SIADOC */
    @ManyToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "compagnie_id", nullable = false)
    private Compagnie compagnie;
}
