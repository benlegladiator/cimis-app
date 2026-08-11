-- Migration pour l'intégration SIADOC - CIMIS
-- Création des tables et champs nécessaires pour l'interopérabilité

-- 1. Table de suivi des synchronisations API
CREATE TABLE IF NOT EXISTS api_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system VARCHAR(50) NOT NULL COMMENT 'Nom du système (SIADOC, etc.)',
    action VARCHAR(100) NOT NULL COMMENT 'Action effectuée',
    status ENUM('SUCCESS', 'ERROR', 'PENDING') DEFAULT 'SUCCESS',
    details TEXT NULL COMMENT 'Détails de l\'opération',
    last_sync DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_system (system),
    INDEX idx_last_sync (last_sync)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ajout des champs SIADOC dans la table candidat
ALTER TABLE candidat 
ADD COLUMN IF NOT EXISTS matricule_militaire VARCHAR(50) NULL COMMENT 'Matricule militaire SIADOC',
ADD COLUMN IF NOT EXISTS source_system VARCHAR(20) DEFAULT 'MANUEL' COMMENT 'Source: MANUEL, SIADOC, IMPORT',
ADD COLUMN IF NOT EXISTS type_personnel ENUM('MILITAIRE', 'CIVIL', 'GENDARME') DEFAULT 'MILITAIRE',
ADD COLUMN IF NOT EXISTS statut_carte ENUM('ACTIVE', 'INACTIVE', 'SUSPENDUE', 'EXPIREE') DEFAULT 'ACTIVE',
ADD COLUMN IF NOT EXISTS date_enrolement DATE NULL COMMENT 'Date d\'enrôlement dans CIMIS',
ADD COLUMN IF NOT EXISTS empreinte_data TEXT NULL COMMENT 'Données biométriques empreinte digitale',
ADD COLUMN IF NOT EXISTS siadoc_sync_date DATETIME NULL COMMENT 'Dernière synchronisation avec SIADOC',
ADD COLUMN IF NOT EXISTS siadoc_sync_status ENUM('SYNCED', 'PENDING', 'ERROR') DEFAULT 'PENDING';

-- 3. Index pour optimiser les requêtes SIADOC
CREATE INDEX IF NOT EXISTS idx_matricule_militaire ON candidat(matricule_militaire);
CREATE INDEX IF NOT EXISTS idx_source_system ON candidat(source_system);
CREATE INDEX IF NOT EXISTS idx_type_personnel ON candidat(type_personnel);
CREATE INDEX IF NOT EXISTS idx_statut_carte ON candidat(statut_carte);
CREATE INDEX IF NOT EXISTS idx_siadoc_sync_status ON candidat(siadoc_sync_status);

-- 4. Table des logs de synchronisation détaillés
CREATE TABLE IF NOT EXISTS siadoc_sync_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidat_id INT NOT NULL,
    matricule_militaire VARCHAR(50) NOT NULL,
    operation_type ENUM('IMPORT', 'EXPORT', 'BIOMETRIE', 'QR_CODE') NOT NULL,
    operation_status ENUM('SUCCESS', 'ERROR', 'PENDING') NOT NULL,
    request_data JSON NULL COMMENT 'Données envoyées',
    response_data JSON NULL COMMENT 'Réponse reçue',
    error_message TEXT NULL,
    operation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidat_id) REFERENCES candidat(id) ON DELETE CASCADE,
    INDEX idx_matricule (matricule_militaire),
    INDEX idx_operation_type (operation_type),
    INDEX idx_operation_date (operation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Configuration des endpoints SIADOC
CREATE TABLE IF NOT EXISTS siadoc_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Insertion de la configuration par défaut
INSERT INTO siadoc_config (config_key, config_value, description) VALUES
('api_url', 'https://siadoc.gt.tc/api/', 'URL de base de l\'API SIADOC'),
('api_key', 'a1b2c3d4-e5f6-7890', 'Clé API pour l\'authentification SIADOC'),
('sync_enabled', 'true', 'Activer/Désactiver la synchronisation SIADOC'),
('auto_sync_interval', '3600', 'Intervalle de synchronisation automatique en secondes'),
('biometrie_endpoint', 'import/cimis/biometrie', 'Endpoint pour l\'envoi des biométries'),
('militaire_endpoint', 'export/militaire/info', 'Endpoint pour récupérer les infos militaires')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- 7. Vue pour le suivi des synchronisations
CREATE OR REPLACE VIEW v_siadoc_sync_status AS
SELECT 
    c.id,
    c.matricule,
    c.matricule_militaire,
    c.nom,
    c.prenom,
    c.source_system,
    c.statut_carte,
    c.siadoc_sync_date,
    c.siadoc_sync_status,
    CASE 
        WHEN c.siadoc_sync_status = 'SYNCED' THEN '✅ Synchronisé'
        WHEN c.siadoc_sync_status = 'PENDING' THEN '⏳ En attente'
        WHEN c.siadoc_sync_status = 'ERROR' THEN '❌ Erreur'
        ELSE '❓ Inconnu'
    END as sync_status_label,
    CASE 
        WHEN c.siadoc_sync_date IS NULL THEN 'Jamais synchronisé'
        ELSE CONCAT('Dernière sync: ', DATE_FORMAT(c.siadoc_sync_date, '%d/%m/%Y %H:%i'))
    END as last_sync_label
FROM candidat c
WHERE c.source_system = 'SIADOC' OR c.matricule_militaire IS NOT NULL;

-- 8. Vue pour les statistiques d'intégration
CREATE OR REPLACE VIEW v_siadoc_stats AS
SELECT 
    COUNT(*) as total_militaires,
    COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc,
    COUNT(CASE WHEN siadoc_sync_status = 'SYNCED' THEN 1 END) as synchronises,
    COUNT(CASE WHEN siadoc_sync_status = 'PENDING' THEN 1 END) as en_attente,
    COUNT(CASE WHEN siadoc_sync_status = 'ERROR' THEN 1 END) en_erreur,
    COUNT(CASE WHEN statut_carte = 'ACTIVE' THEN 1 END) as cartes_actives,
    COUNT(CASE WHEN code_qr IS NOT NULL THEN 1 END) as avec_qr_code,
    COUNT(CASE WHEN empreinte_data IS NOT NULL THEN 1 END) as avec_biometrie
FROM candidat 
WHERE type_personnel = 'MILITAIRE';

-- 9. Trigger pour mettre à jour les logs de synchronisation
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_candidat_insert_siadoc
AFTER INSERT ON candidat
FOR EACH ROW
BEGIN
    IF NEW.source_system = 'SIADOC' THEN
        INSERT INTO siadoc_sync_details (
            candidat_id, 
            matricule_militaire, 
            operation_type, 
            operation_status,
            operation_date
        ) VALUES (
            NEW.id,
            NEW.matricule_militaire,
            'IMPORT',
            'SUCCESS',
            NOW()
        );
    END IF;
END//
DELIMITER ;

-- 10. Mise à jour des données existantes
UPDATE candidat SET 
    source_system = 'MANUEL',
    type_personnel = 'MILITAIRE',
    statut_carte = CASE WHEN supprimer = 1 THEN 'ACTIVE' ELSE 'INACTIVE' END,
    siadoc_sync_status = 'PENDING'
WHERE source_system IS NULL OR source_system = '';

-- Commentaire explicatif
COMMENT ON TABLE api_sync_log IS 'Table de suivi des synchronisations avec les systèmes externes';
COMMENT ON TABLE siadoc_sync_details IS 'Détail des opérations de synchronisation SIADOC';
COMMENT ON TABLE siadoc_config IS 'Configuration de l\'intégration SIADOC';
