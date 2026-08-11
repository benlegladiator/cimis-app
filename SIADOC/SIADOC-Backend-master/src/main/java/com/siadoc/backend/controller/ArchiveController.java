package com.siadoc.backend.controller;

import com.siadoc.backend.dto.ArchiveResultDTO;
import com.siadoc.backend.service.ArchiveService;

import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/archives")
@RequiredArgsConstructor
public class ArchiveController {

    private final ArchiveService service;

    @GetMapping("/search")
    public List<ArchiveResultDTO> search(
            @RequestParam(required = false) String search) {

        return service.searchArchives(search);
    }
}