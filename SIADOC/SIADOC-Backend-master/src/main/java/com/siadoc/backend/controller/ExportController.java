package com.siadoc.backend.controller;

import com.siadoc.backend.dto.ExportRequestDTO;
import com.siadoc.backend.service.ExportService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/export")
@RequiredArgsConstructor
public class ExportController {

    private final ExportService exportService;

    @PostMapping("/pdf")
    public ResponseEntity<byte[]> exportPdf(@RequestBody ExportRequestDTO request) {
        
        byte[] pdfContent = exportService.generateArchivePdf(request);
        
        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=archive.pdf")
                .contentType(MediaType.APPLICATION_PDF)
                .body(pdfContent);
    }
}
