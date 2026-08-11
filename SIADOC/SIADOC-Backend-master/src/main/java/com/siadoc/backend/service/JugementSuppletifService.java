package com.siadoc.backend.service;

import com.siadoc.backend.model.EtatCivil;
import com.siadoc.backend.model.JugementSuppletif;
import com.siadoc.backend.repository.EtatCivilRepository;
import com.siadoc.backend.repository.JugementSuppletifRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.time.LocalDate;
import java.util.List;
import java.util.UUID;

@Service
@RequiredArgsConstructor
public class JugementSuppletifService {

    private final JugementSuppletifRepository repo;
    private final EtatCivilRepository etatCivilRepository;

    // ================= AJOUT =================

    public JugementSuppletif add(
            UUID etatCivilId,
            String numero,
            String objet,
            LocalDate date,
            String tribunal,
            MultipartFile fichier
    ) throws Exception {

        EtatCivil module = etatCivilRepository.findById(etatCivilId)
                .orElseThrow(() -> new RuntimeException("Etat civil introuvable"));

        JugementSuppletif j = new JugementSuppletif();
        j.setEtatCivil(module);
        j.setNumeroJugement(numero);
        j.setObjet(objet);
        j.setDateJugement(date);
        j.setTribunal(tribunal);

        if (fichier != null && !fichier.isEmpty()) {
            j.setDocumentData(fichier.getBytes());
            j.setDocumentNom(fichier.getOriginalFilename());
            j.setDocumentType(fichier.getContentType());
        }

        return repo.save(j);
    }

    // ================= LISTE =================

    public List<JugementSuppletif> getByModule(UUID moduleId) {
        return repo.findByEtatCivilId(moduleId);
    }

    // ================= GET =================

    public JugementSuppletif get(UUID id) {
        return repo.findById(id)
                .orElseThrow(() -> new RuntimeException("Jugement supplétif introuvable"));
    }

    // ================= DELETE =================

    public void delete(UUID id) {
        repo.deleteById(id);
    }
}