package com.siadoc.backend.service;

import com.siadoc.backend.model.SystemSetting;
import com.siadoc.backend.repository.SystemSettingRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
@RequiredArgsConstructor
public class SystemSettingService {
    private final SystemSettingRepository repository;

    public SystemSetting getSettings() {
        return repository.findFirstBy().orElseGet(() -> {
            // Create default settings if none exist
            SystemSetting defaults = SystemSetting.builder()
                .institutionName("Ministère de la Défense")
                .directionName("Direction des Ressources Humaines")
                .motto("Paix - Travail - Patrie")
                .appVersion("2.4.0")
                .fiscalYear(2026)
                .maxFileSize(5)
                .allowedExtensions(".pdf, .jpg, .png")
                .defaultLanguage("fr")
                .maintenanceMode(false)
                .build();
            return repository.save(defaults);
        });
    }

    @Transactional
    public SystemSetting updateSettings(SystemSetting newSettings) {
        SystemSetting current = getSettings();
        
        // Update fields
        current.setInstitutionName(newSettings.getInstitutionName());
        current.setDirectionName(newSettings.getDirectionName());
        current.setMotto(newSettings.getMotto());
        current.setFiscalYear(newSettings.getFiscalYear());
        current.setSupportEmail(newSettings.getSupportEmail());
        current.setDefaultLanguage(newSettings.getDefaultLanguage());
        current.setMaxFileSize(newSettings.getMaxFileSize());
        current.setAllowedExtensions(newSettings.getAllowedExtensions());
        current.setLogoHeader(newSettings.getLogoHeader());
        current.setLogoSeal(newSettings.getLogoSeal());
        current.setMaintenanceMode(newSettings.isMaintenanceMode());
        
        return repository.save(current);
    }
}
