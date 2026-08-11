package com.siadoc.backend.controller;

import com.siadoc.backend.service.StatsService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.Map;

@RestController
@RequestMapping("/api/stats")
@RequiredArgsConstructor
public class StatsController {

    private final StatsService statsService;

    @GetMapping("/dashboard")
    public Map<String, Object> dashboard() {
        return statsService.getDashboard();
    }

    @GetMapping("/compagnies-counts")
    public Map<String, Long> getCompagniesCounts() {
        return statsService.getCompagniesCounts();
    }

    @GetMapping("/unites-counts")
    public Map<String, Long> getUnitesCounts() {
        return statsService.getUnitesCounts();
    }
}