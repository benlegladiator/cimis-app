-- ====================================================================
-- SCRIPT DE MIGRATION & SYNCHRONISATION CIMIS ↔ RAILWAY (Production)
-- Exécuter ce script sur la base de données MySQL hébergée sur Railway
-- ====================================================================

-- 1. Mise à jour de la table 'candidat' pour les suspensions et l'interopérabilité
ALTER TABLE candidat 
ADD COLUMN IF NOT EXISTS suspendus TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS statut_militaire VARCHAR(50) DEFAULT 'ACTIF',
ADD COLUMN IF NOT EXISTS suspendu_par VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS date_suspension DATETIME NULL,
ADD COLUMN IF NOT EXISTS suspension_motif TEXT NULL,
ADD COLUMN IF NOT EXISTS source_system VARCHAR(50) DEFAULT 'CIMIS',
ADD COLUMN IF NOT EXISTS siadoc_sync_status VARCHAR(20) DEFAULT 'NON_SYNCHRONISE',
ADD COLUMN IF NOT EXISTS siadoc_sync_date DATETIME NULL,
ADD COLUMN IF NOT EXISTS supprimer TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS date_suppression DATETIME NULL,
ADD COLUMN IF NOT EXISTS supprime_par VARCHAR(100) NULL;

-- 2. Création de la table 'activity_log' pour la traçabilité et les Webhooks SIADOC
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Synchronisation des statuts militaires d'inaptitude existants vers suspendus = 1
UPDATE candidat 
SET suspendus = 1 
WHERE statut_militaire IN ('SUSPENDU_MEDICAL', 'SUSPENDU_ADMINISTRATIVEMENT', 'DESERTEUR', 'REVOQUE')
  AND (suspendus = 0 OR suspendus IS NULL);

-- 4. Initialisation des valeurs par défaut pour les anciens enregistrements
UPDATE candidat 
SET suspendus = 0 
WHERE suspendus IS NULL;

UPDATE candidat 
SET supprimer = 0 
WHERE supprimer IS NULL;

UPDATE candidat 
SET statut_militaire = 'ACTIF' 
WHERE statut_militaire IS NULL OR statut_militaire = '';
