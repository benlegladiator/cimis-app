package com.siadoc.backend.model;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;
import java.util.UUID;

@Entity
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class SystemSetting {
    @Id
    @GeneratedValue(strategy = GenerationType.AUTO)
    private UUID id;

    // Institution
    private String institutionName;
    private String directionName;
    private String motto;

    // App Config
    private String appVersion;
    private Integer fiscalYear;
    private String supportEmail;
    private String defaultLanguage;

    // File Config
    private Integer maxFileSize; // In MB
    private String allowedExtensions;

    // Visuals (Stored as Base64 or paths - here we use text for simplicity)
    @Column(columnDefinition = "TEXT")
    private String logoHeader;

    @Column(columnDefinition = "TEXT")
    private String logoSeal;

    private boolean maintenanceMode;
}
