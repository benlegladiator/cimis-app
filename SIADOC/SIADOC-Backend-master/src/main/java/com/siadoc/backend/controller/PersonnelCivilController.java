package com.siadoc.backend.controller;

import com.siadoc.backend.model.DocumentCivil;
import com.siadoc.backend.model.PersonnelCivil;
import com.siadoc.backend.repository.DocumentCivilRepository;
import com.siadoc.backend.repository.PersonnelCivilRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.transaction.annotation.Transactional;

import java.io.IOException;
import java.time.LocalDateTime;
import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/personnel-civil")
@RequiredArgsConstructor
public class PersonnelCivilController {

    private final PersonnelCivilRepository repository;
    private final DocumentCivilRepository documentRepository;

    @GetMapping
    public List<PersonnelCivil> lister() {
        return repository.findAll();
    }

    @PostMapping
    public ResponseEntity<?> ajouter(@RequestBody PersonnelCivil personnel) {
        if (personnel.getMatricule() != null && repository.existsByMatricule(personnel.getMatricule())) {
            return ResponseEntity.badRequest().body("Le matricule " + personnel.getMatricule() + " est déjà utilisé.");
        }
        return ResponseEntity.ok(repository.save(personnel));
    }

    @GetMapping("/{id}")
    public ResponseEntity<PersonnelCivil> getById(@PathVariable UUID id) {
        return repository.findById(id)
                .map(ResponseEntity::ok)
                .orElse(ResponseEntity.notFound().build());
    }

    @GetMapping("/{id}/documents")
    public List<DocumentCivil> listerDocuments(@PathVariable UUID id) {
        return documentRepository.findByPersonnelCivilId(id);
    }

    @Transactional
    @PostMapping(value = "/{id}/documents", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public DocumentCivil ajouterDocument(
            @PathVariable UUID id,
            @RequestParam String label,
            @RequestParam("fichier") MultipartFile fichier
    ) throws IOException {
        PersonnelCivil personnel = repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Personnel non trouvé"));

        DocumentCivil doc = new DocumentCivil();
        doc.setLabel(label);
        doc.setNomFichier(fichier.getOriginalFilename());
        doc.setTypeFichier(fichier.getContentType());
        doc.setFichier(fichier.getBytes());
        doc.setDateTeleversement(LocalDateTime.now());
        doc.setPersonnelCivil(personnel);

        return documentRepository.save(doc);
    }

    @GetMapping("/documents/{docId}/download")
    public ResponseEntity<byte[]> downloadDocument(@PathVariable UUID docId) {
        DocumentCivil doc = documentRepository.findById(docId)
                .orElseThrow(() -> new RuntimeException("Document non trouvé"));

        return ResponseEntity.ok()
                .contentType(MediaType.parseMediaType(doc.getTypeFichier()))
                .header("Content-Disposition", "inline; filename=\"" + doc.getNomFichier() + "\"")
                .body(doc.getFichier());
    }
}
