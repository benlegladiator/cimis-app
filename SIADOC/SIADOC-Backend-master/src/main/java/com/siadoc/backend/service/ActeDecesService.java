package com.siadoc.backend.service;

import com.siadoc.backend.model.ActeDeces;
import com.siadoc.backend.model.EtatCivil;
import com.siadoc.backend.repository.ActeDecesRepository;
import com.siadoc.backend.repository.EtatCivilRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@Service
@RequiredArgsConstructor
public class ActeDecesService {

    private final ActeDecesRepository repository;
    private final EtatCivilRepository etatCivilRepository;

    // ================= AJOUT =================

    public ActeDeces add(
            UUID etatCivilId,
            String numeroActe,
            LocalDate dateDeces,
            String lieu,
            MultipartFile fichier
    ) throws Exception {

        EtatCivil module = etatCivilRepository.findById(etatCivilId)
                .orElseThrow(() -> new RuntimeException("Etat civil introuvable"));

        ActeDeces acte = new ActeDeces();
        acte.setEtatCivil(module);
        acte.setNumeroActe(numeroActe);
        acte.setDateDeces(dateDeces);
        acte.setLieuDeces(lieu);

        if (fichier != null && !fichier.isEmpty()) {
            acte.setDocumentData(fichier.getBytes());
            acte.setDocumentNom(fichier.getOriginalFilename());
            acte.setDocumentType(fichier.getContentType());
        }

        return repository.save(acte);
    }
    // ================= LISTE =================

    public List<ActeDeces> getByModule(UUID moduleId) {
        return repository.findByEtatCivilId(moduleId);
    }

    // ================= GET FICHIER =================

    public ActeDeces get(UUID id) {
        return repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Acte décès introuvable"));
    }

    // ================= DELETE =================

    public void delete(UUID id) {
        repository.deleteById(id);
    }
}