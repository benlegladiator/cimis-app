package com.siadoc.backend.service;

import com.siadoc.backend.model.ActeDivorce;
import com.siadoc.backend.model.EtatCivil;
import com.siadoc.backend.repository.ActeDivorceRepository;
import com.siadoc.backend.repository.EtatCivilRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@Service
@RequiredArgsConstructor
public class ActeDivorceService {

    private final ActeDivorceRepository repository;
    private final EtatCivilRepository etatCivilRepository;

    // ================= AJOUT =================

    public ActeDivorce add(
            UUID etatCivilId,
            String numeroJugement,
            LocalDate dateJugement,
            String tribunal,
            MultipartFile fichier
    ) throws Exception {

        EtatCivil module = etatCivilRepository.findById(etatCivilId)
                .orElseThrow(() -> new RuntimeException("Etat civil introuvable"));

        ActeDivorce a = new ActeDivorce();
        a.setEtatCivil(module);
        a.setNumeroJugement(numeroJugement);
        a.setDateJugement(dateJugement);
        a.setTribunal(tribunal);

        if (fichier != null && !fichier.isEmpty()) {
            a.setDocumentData(fichier.getBytes());
            a.setDocumentNom(fichier.getOriginalFilename());
            a.setDocumentType(fichier.getContentType());
        }

        return repository.save(a);
    }


    // ================= LISTE =================

    public List<ActeDivorce> getByModule(UUID moduleId) {
        return repository.findByEtatCivilId(moduleId);
    }

    // ================= GET ONE =================

    public ActeDivorce get(UUID id) {
        return repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Acte divorce introuvable"));
    }

    // ================= DELETE =================

    public void delete(UUID id) {
        repository.deleteById(id);
    }
}