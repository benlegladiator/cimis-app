package com.siadoc.backend.controller;

import com.siadoc.backend.model.CivilSetting;
import com.siadoc.backend.repository.CivilSettingRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.UUID;

@RestController
@RequestMapping("/api/settings/civil")
@RequiredArgsConstructor
public class CivilSettingsController {

    private final CivilSettingRepository repository;

    @GetMapping
    public List<CivilSetting> getAll() {
        return repository.findAll();
    }

    @GetMapping("/type/{type}")
    public List<CivilSetting> getByType(@PathVariable String type) {
        return repository.findByType(type);
    }

    @PostMapping
    public CivilSetting save(@RequestBody CivilSetting setting) {
        return repository.save(setting);
    }

    @DeleteMapping("/{id}")
    public void delete(@PathVariable UUID id) {
        repository.deleteById(id);
    }
}
