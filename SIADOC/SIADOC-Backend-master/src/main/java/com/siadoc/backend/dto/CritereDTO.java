package com.siadoc.backend.dto;

import com.siadoc.backend.model.NoteAppreciation;

public class CritereDTO {
    private NoteAppreciation note; // TB, B...
    private String obs;

    public CritereDTO() {}

    public CritereDTO(NoteAppreciation note, String obs) {
        this.note = note;
        this.obs = obs;
    }

    public NoteAppreciation getNote() { return note; }
    public void setNote(NoteAppreciation note) { this.note = note; }
    public String getObs() { return obs; }
    public void setObs(String obs) { this.obs = obs; }
}
