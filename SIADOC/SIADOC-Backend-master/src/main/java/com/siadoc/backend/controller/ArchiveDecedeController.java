package com.siadoc.backend.controller;

import com.siadoc.backend.dto.ArchiveResultDTO;
import com.siadoc.backend.model.ArchiveDecede;
import com.siadoc.backend.service.ArchiveService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/archives-physiques")
@RequiredArgsConstructor
public class ArchiveDecedeController {

    private final ArchiveService service;

    @GetMapping
    public List<ArchiveResultDTO> getAll() {
        return service.getAllPhysicalArchives();
    }

    @PostMapping
    public ArchiveDecede create(@RequestBody ArchiveDecede archive) {
        return service.savePhysicalArchive(archive);
    }

    @GetMapping("/check/{militaireId}")
    public boolean checkPhysicalArchive(@PathVariable UUID militaireId) {
        return service.isPhysicalArchive(militaireId);
    }
}
