package com.siadoc.backend.controller;

import com.siadoc.backend.model.SystemSetting;
import com.siadoc.backend.service.SystemSettingService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/settings/system")
@RequiredArgsConstructor
@CrossOrigin(origins = "*")
public class SystemSettingController {
    private final SystemSettingService service;

    @GetMapping
    public SystemSetting getSettings() {
        return service.getSettings();
    }

    @PutMapping
    public SystemSetting updateSettings(@RequestBody SystemSetting settings) {
        return service.updateSettings(settings);
    }
}
