-- ============================================================
-- SIADOC TABLES — Scripts SQL pour l'interopérabilité CIMIS-SIADOC
-- Version : 2.0 — 2026
-- Exécuter dans la base de données : cimis
-- ============================================================

-- ─── TABLE : api_sync_log ────────────────────────────────────────────────────
-- Journal de toutes les opérations de synchronisation
CREATE TABLE IF NOT EXISTS api_sync_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    system      VARCHAR(50)  NOT NULL COMMENT 'Ex: SIADOC_IMPORT, CIMIS_EXPORT, SIADOC_WEBHOOK',
    action      VARCHAR(100) NOT NULL COMMENT 'Ex: GET_CARTES, IMPORT, EXPORT',
    status      ENUM('SUCCESS','PARTIAL','ERROR','RECEIVED','PENDING') NOT NULL DEFAULT 'PENDING',
    details     TEXT         NULL     COMMENT 'JSON des détails de l\'opération',
    last_sync   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_system  (system),
    INDEX idx_status  (status),
    INDEX idx_sync    (last_sync)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Journal des synchronisations CIMIS-SIADOC';

-- ─── TABLE : siadoc_sync_details ─────────────────────────────────────────────
-- Détail par militaire de chaque opération d'import/export
CREATE TABLE IF NOT EXISTS siadoc_sync_details (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    candidat_id         INT          NULL     COMMENT 'ID dans la table candidat (NULL si import échoué)',
    matricule_militaire VARCHAR(50)  NOT NULL COMMENT 'Matricule SIADOC (ex: T14/6584)',
    operation_type      VARCHAR(50)  NOT NULL COMMENT 'IMPORT | UPDATE | EXPORT | WEBHOOK',
    operation_status    VARCHAR(20)  NOT NULL COMMENT 'SUCCESS | ERROR | SKIP',
    details             TEXT         NULL     COMMENT 'Informations complémentaires',
    operation_date      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_matricule    (matricule_militaire),
    INDEX idx_candidat     (candidat_id),
    INDEX idx_date         (operation_date),
    INDEX idx_type_status  (operation_type, operation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Détail par militaire des opérations SIADOC';

-- ─── TABLE : siadoc_webhook_queue ────────────────────────────────────────────
-- File d'attente pour traiter les webhooks SIADOC de manière asynchrone
CREATE TABLE IF NOT EXISTS siadoc_webhook_queue (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    event_type   VARCHAR(100) NOT NULL COMMENT 'Ex: MILITAIRE_CREE, MILITAIRE_MIS_A_JOUR, PING',
    payload      LONGTEXT     NOT NULL COMMENT 'Corps JSON du webhook',
    status       ENUM('PENDING','PROCESSING','DONE','ERROR') NOT NULL DEFAULT 'PENDING',
    attempts     INT          NOT NULL DEFAULT 0,
    error_msg    TEXT         NULL,
    received_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP    NULL,
    INDEX idx_status      (status),
    INDEX idx_event       (event_type),
    INDEX idx_received    (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='File d\'attente des webhooks reçus de SIADOC';

-- ─── COLONNES MANQUANTES dans candidat ───────────────────────────────────────
-- Ajouter les colonnes SIADOC si elles n'existent pas

ALTER TABLE candidat
    ADD COLUMN IF NOT EXISTS source_system       VARCHAR(20)  DEFAULT 'CIMIS'    COMMENT 'CIMIS ou SIADOC',
    ADD COLUMN IF NOT EXISTS siadoc_sync_date    TIMESTAMP    NULL               COMMENT 'Date de la dernière sync SIADOC',
    ADD COLUMN IF NOT EXISTS siadoc_sync_status  VARCHAR(20)  DEFAULT NULL       COMMENT 'SYNCED | ERROR | PENDING',
    ADD COLUMN IF NOT EXISTS date_modification   TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP;

-- Index utiles pour les performances SIADOC
ALTER TABLE candidat
    ADD INDEX IF NOT EXISTS idx_source_system    (source_system),
    ADD INDEX IF NOT EXISTS idx_siadoc_sync      (siadoc_sync_date),
    ADD INDEX IF NOT EXISTS idx_date_modif       (date_modification),
    ADD INDEX IF NOT EXISTS idx_supprimer_susp   (supprimer, suspendus);

-- ─── DONNÉES D'EXEMPLE pour les tests ────────────────────────────────────────
-- (Commenter si vous ne voulez pas insérer de données de test)
-- INSERT INTO api_sync_log (system, action, status, details)
-- VALUES ('SIADOC_IMPORT', 'TEST_INIT', 'SUCCESS', '{"message":"Tables créées avec succès"}');

SELECT 'Tables SIADOC créées/vérifiées avec succès' AS message;
