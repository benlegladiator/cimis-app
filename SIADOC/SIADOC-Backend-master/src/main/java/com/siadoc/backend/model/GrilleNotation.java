package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.*;

@Embeddable
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class GrilleNotation {

    @Enumerated(EnumType.STRING)
    private NoteAppreciation presentation;
    private String obsPresentation;

    @Enumerated(EnumType.STRING)
    private NoteAppreciation valeurPhysique;
    private String obsValeurPhysique;

    @Enumerated(EnumType.STRING)
    private NoteAppreciation valeurMorale;
    private String obsValeurMorale;

    @Enumerated(EnumType.STRING)
    private NoteAppreciation instruction;
    private String obsInstruction;

    @Enumerated(EnumType.STRING)
    private NoteAppreciation commandement;
    private String obsCommandement;
}