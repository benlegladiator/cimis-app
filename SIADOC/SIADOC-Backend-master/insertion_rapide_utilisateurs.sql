-- ========================================
-- INSERTION RAPIDE DES UTILISATEURS MANQUANTS
-- ========================================

-- INSERER LES UTILISATEURS POUR L'AUTHENTIFICATION
INSERT INTO utilisateur (id, username, password, email, role, created_at, updated_at) VALUES
('11111111-1111-1111-1111-111111111111', 'rmia1_admin', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'rmia1.admin@siadoc.cm', 'RMIA_ADMIN', NOW(), NOW()),
('22222222-2222-2222-2222-222222222222', 'bde1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'bde1.chef@siadoc.cm', 'BRIGADE_CHEF', NOW(), NOW()),
('33333333-3333-3333-3333-333333333333', 'bta1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'bta1.chef@siadoc.cm', 'BATAILLON_CHEF', NOW(), NOW()),
('44444444-4444-4444-4444-444444444444', 'cie1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'cie1.chef@siadoc.cm', 'COMPAGNIE_CHEF', NOW(), NOW());

-- ========================================
-- INSTRUCTIONS D'EXECUTION
-- ========================================

-- 1. Connectez-vous à PostgreSQL:
-- psql -U votre_user -d siadoc_db

-- 2. Exécutez le script:
-- \i insertion_rapide_utilisateurs.sql

-- 3. TEST DE CONNEXION:
-- Username: rmia1_admin
-- Password: password123

-- ========================================
-- VALIDATION APRES INSERTION
-- ========================================

-- Vérifiez que les utilisateurs existent:
-- SELECT * FROM utilisateur;

-- Testez l'authentification avec curl:
-- curl -X POST "http://localhost:8080/api/auth/login" -H "Content-Type: application/json" -d '{"username":"rmia1_admin","password":"password123"}'
