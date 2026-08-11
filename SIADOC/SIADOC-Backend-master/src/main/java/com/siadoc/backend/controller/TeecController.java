package com.siadoc.backend.controller;

import com.siadoc.backend.dto.TeecLigneDTO;
import com.siadoc.backend.service.TeecService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/teec-report")
public class TeecController {

    private final TeecService teecService;

    public TeecController(TeecService teecService) {
        this.teecService = teecService;
    }

    @GetMapping("/test")
    public String test() {
        return "OK";
    }

    @GetMapping
    public List<TeecLigneDTO> getTeec(@RequestParam(required = false) UUID compagnieId) {
        return teecService.genererTeec(compagnieId);
    }
}
