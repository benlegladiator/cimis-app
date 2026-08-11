package com.siadoc.backend.model;

public enum StatutValidation {
    EN_COURS,               // Modifications en cours par la compagnie
    EN_ATTENTE_VALIDATION,  // Soumis au bataillon pour validation
    EN_ATTENTE_DRH,         // En attente de validation finale par la DRH (si nécessaire)
    VALIDE,                 // Accepté
    REJETE                  // Refusé (avec motif)
}
