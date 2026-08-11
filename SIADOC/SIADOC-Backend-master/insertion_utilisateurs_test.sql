-- ========================================
-- INSERTION UTILISATEURS DE TEST POUR L'AUTHENTIFICATION
-- ========================================

-- 1. INSÉRER LES UTILISATEURS DE TEST
INSERT INTO utilisateur (id, username, password, email, role, created_at, updated_at) VALUES
(UUID(), 'rmia1_admin', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'rmia1.admin@siadoc.cm', 'RMIA_ADMIN', NOW(), NOW()),
(UUID(), 'bde1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'bde1.chef@siadoc.cm', 'BRIGADE_CHEF', NOW(), NOW()),
(UUID(), 'bta1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'bta1.chef@siadoc.cm', 'BATAILLON_CHEF', NOW(), NOW()),
(UUID(), 'cie1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'cie1.chef@siadoc.cm', 'COMPAGNIE_CHEF', NOW(), NOW());

-- ========================================
-- INSTRUCTIONS
-- ========================================

-- MOT DE PASSE UNIVERSEL: password123
-- Exécuter ce script dans votre base de données PostgreSQL:
-- psql -U votre_user -d siadoc_db
-- \i insertion_utilisateurs_test.sql

-- TEST DE CONNEXION:
-- 1. rmia1_admin / password123
-- 2. bde1_chef / password123  
-- 3. bta1_chef / password123
-- 4. cie1_chef / password123
