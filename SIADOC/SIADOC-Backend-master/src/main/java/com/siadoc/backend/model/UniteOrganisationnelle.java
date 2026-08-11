package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Entity
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class UniteOrganisationnelle {

    @Id
    @GeneratedValue(strategy = GenerationType.UUID)
    private UUID id;

    @Column(nullable = false)
    private String nom;

    @Column(nullable = false)
    private String type; // AC, FS, CT, RMIA, BRIGADE, BATAILLON, COMPAGNIE, BUREAU, DIVISION

    @Column
    private String description;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "parent_id")
    @com.fasterxml.jackson.annotation.JsonIgnore
    private UniteOrganisationnelle parent;

    @OneToMany(mappedBy = "parent", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<UniteOrganisationnelle> children = new ArrayList<>();

    @Column
    private String icon;

    // Helper method to add child
    public void addChild(UniteOrganisationnelle child) {
        children.add(child);
        child.setParent(this);
    }
}
