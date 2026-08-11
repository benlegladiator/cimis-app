package com.siadoc.backend.model;

public enum NoteAppreciation {
    TRES_BIEN(5, "TB"),
    BIEN(4, "B"),
    ASSEZ_BIEN(3, "AB"),
    PASSABLE(2, "P"),
    MAUVAIS(1, "M");

    private final int valeur;
    private final String code;

    NoteAppreciation(int valeur, String code) {
        this.valeur = valeur;
        this.code = code;
    }

    public int getValeur() { return valeur; }
    public String getCode() { return code; }
}
