-- ========================================
-- SCRIPT D'INSERTION DES DONNÉES DE TEST
-- BASE DE DONNÉES SIADOC
-- ========================================

-- 1. INSERTION DES RÉGIONS MILITAIRES (RMIA)
INSERT INTO region_militaire (id, nom, code, created_at, updated_at) VALUES
(UUID(), 'Région Militaire Nord', 'RMIA-NORD', NOW(), NOW()),
(UUID(), 'Région Militaire Centre', 'RMIA-CENTRE', NOW(), NOW()),
(UUID(), 'Région Militaire Sud', 'RMIA-SUD', NOW(), NOW()),
(UUID(), 'Région Militaire Est', 'RMIA-EST', NOW(), NOW()),
(UUID(), 'Région Militaire Ouest', 'RMIA-OUEST', NOW(), NOW());

-- 2. INSERTION DES BRIGADES
INSERT INTO brigade (id, nom, code, region_id, created_at, updated_at) VALUES
(UUID(), '1ère Brigade d''Infanterie', 'BDE-001', (SELECT id FROM region_militaire WHERE code = 'RMIA-NORD' LIMIT 1), NOW(), NOW()),
(UUID(), '2ème Brigade d''Infanterie', 'BDE-002', (SELECT id FROM region_militaire WHERE code = 'RMIA-NORD' LIMIT 1), NOW(), NOW()),
(UUID(), '3ème Brigade d''Infanterie', 'BDE-003', (SELECT id FROM region_militaire WHERE code = 'RMIA-CENTRE' LIMIT 1), NOW(), NOW()),
(UUID(), 'Brigade de Support Logistique', 'BDE-SUP-001', (SELECT id FROM region_militaire WHERE code = 'RMIA-SUD' LIMIT 1), NOW(), NOW()),
(UUID(), 'Brigade du Génie Militaire', 'BDE-GEN-001', (SELECT id FROM region_militaire WHERE code = 'RMIA-EST' LIMIT 1), NOW(), NOW());

-- 3. INSERTION DES BATAILLONS
INSERT INTO bataillon (id, nom, code, brigade_id, created_at, updated_at) VALUES
(UUID(), '1er Bataillon de Commandement', 'BTA-001', (SELECT id FROM brigade WHERE code = 'BDE-001' LIMIT 1), NOW(), NOW()),
(UUID(), '2ème Bataillon d''Infanterie', 'BTA-002', (SELECT id FROM brigade WHERE code = 'BDE-001' LIMIT 1), NOW(), NOW()),
(UUID(), '3ème Bataillon d''Infanterie', 'BTA-003', (SELECT id FROM brigade WHERE code = 'BDE-002' LIMIT 1), NOW(), NOW()),
(UUID(), 'Bataillon de Support', 'BTA-SUP-001', (SELECT id FROM brigade WHERE code = 'BDE-SUP-001' LIMIT 1), NOW(), NOW()),
(UUID(), 'Bataillon du Génie', 'BTA-GEN-001', (SELECT id FROM brigade WHERE code = 'BDE-GEN-001' LIMIT 1), NOW(), NOW());

-- 4. INSERTION DES COMPAGNIES
INSERT INTO compagnie (id, nom, code, bataillon_id, created_at, updated_at) VALUES
-- Compagnie 1 - Compagnie de Commandement
(UUID(), 'Compagnie de Commandement', 'CIE-001', (SELECT id FROM bataillon WHERE code = 'BTA-001' LIMIT 1), NOW(), NOW()),
-- Compagnie 2 - 1ère Compagnie d'Infanterie  
(UUID(), '1ère Compagnie d''Infanterie', 'CIE-002', (SELECT id FROM bataillon WHERE code = 'BTA-002' LIMIT 1), NOW(), NOW()),
-- Compagnie 3 - 2ème Compagnie d'Infanterie
(UUID(), '2ème Compagnie d''Infanterie', 'CIE-003', (SELECT id FROM bataillon WHERE code = 'BTA-003' LIMIT 1), NOW(), NOW()),
-- Compagnie 4 - Compagnie de Support
(UUID(), 'Compagnie de Support', 'CIE-SUP-001', (SELECT id FROM bataillon WHERE code = 'BTA-SUP-001' LIMIT 1), NOW(), NOW()),
-- Compagnie 5 - Compagnie du Génie
(UUID(), 'Compagnie du Génie', 'CIE-GEN-001', (SELECT id FROM bataillon WHERE code = 'BTA-GEN-001' LIMIT 1), NOW(), NOW()),
-- Compagnie 6 - Compagnie de Transmission
(UUID(), 'Compagnie de Transmission', 'CIE-TRANS-001', (SELECT id FROM bataillon WHERE code = 'BTA-001' LIMIT 1), NOW(), NOW());

-- 5. INSERTION DES UTILISATEURS/ROLES
-- RMIA 1 - Administrateur
INSERT INTO utilisateur (id, username, password, email, role, created_at, updated_at) VALUES
(UUID(), 'rmia1_admin', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'rmia1.admin@siadoc.cm', 'RMIA_ADMIN', NOW(), NOW());

-- Brigade 1 - Chef de Brigade
INSERT INTO utilisateur (id, username, password, email, role, created_at, updated_at) VALUES
(UUID(), 'bde1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'bde1.chef@siadoc.cm', 'BRIGADE_CHEF', NOW(), NOW());

-- Bataillon 1 - Chef de Bataillon
INSERT INTO utilisateur (id, username, password, email, role, created_at, updated_at) VALUES
(UUID(), 'bta1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'bta1.chef@siadoc.cm', 'BATAILLON_CHEF', NOW(), NOW());

-- Compagnie 1 - Chef de Compagnie
INSERT INTO utilisateur (id, username, password, email, role, created_at, updated_at) VALUES
(UUID(), 'cie1_chef', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAg6fl7K2u', 'cie1.chef@siadoc.cm', 'COMPAGNIE_CHEF', NOW(), NOW());

-- 6. INSERTION DES MILITAIRES (36 militaires au total, 6 par compagnie)

-- Compagnie 1 - Compagnie de Commandement
INSERT INTO militaire (id, nom, prenom, matricule_militaire, matricule_solde, grade, arme_service, date_naissance, lieu_naissance, compagnie_id, statut_validation, etat, created_at, updated_at) VALUES
(UUID(), 'Etoundi', 'Essomba', 'MAT-2024-0001', 'MAT-SOL-2024-0001', 'Colonel', 'Commandement', '1980-03-15', 'Yaoundé', (SELECT id FROM compagnie WHERE code = 'CIE-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mbarga', 'Ousmanou', 'MAT-2024-0002', 'MAT-SOL-2024-0002', 'Capitaine', 'Commandement', '1985-07-22', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Tchatchoua', 'Pierre', 'MAT-2024-0003', 'MAT-SOL-2024-0003', 'Adjudant', 'Commandement', '1978-11-10', 'Bafoussam', (SELECT id FROM compagnie WHERE code = 'CIE-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Biya', 'Thomas', 'MAT-2024-0004', 'MAT-SOL-2024-0004', 'Caporal', 'Commandement', '1990-05-08', 'Garoua', (SELECT id FROM compagnie WHERE code = 'CIE-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Fouda', 'Mathieu', 'MAT-2024-0005', 'MAT-SOL-2024-0005', 'Soldat', 'Commandement', '1992-09-20', 'Maroua', (SELECT id FROM compagnie WHERE code = 'CIE-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Moukouri', 'Jean', 'MAT-2024-0006', 'MAT-SOL-2024-0006', 'Soldat', 'Commandement', '1988-12-03', 'Bamenda', (SELECT id FROM compagnie WHERE code = 'CIE-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW());

-- Compagnie 2 - 1ère Compagnie d'Infanterie
INSERT INTO militaire (id, nom, prenom, matricule_militaire, matricule_solde, grade, arme_service, date_naissance, lieu_naissance, compagnie_id, statut_validation, etat, created_at, updated_at) VALUES
(UUID(), 'Ngo', 'Nlend', 'MAT-2024-0007', 'MAT-SOL-2024-0007', 'Capitaine', 'Infanterie', '1975-06-18', 'Bertoua', (SELECT id FROM compagnie WHERE code = 'CIE-002' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Talla', 'André', 'MAT-2024-0008', 'MAT-SOL-2024-0008', 'Lieutenant', 'Infanterie', '1983-08-25', 'Ngaoundéré', (SELECT id FROM compagnie WHERE code = 'CIE-002' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mballa', 'Roger', 'MAT-2024-0009', 'MAT-SOL-2024-0009', 'Adjudant', 'Infanterie', '1979-04-12', 'Ebolowa', (SELECT id FROM compagnie WHERE code = 'CIE-002' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Etoa', 'Jean-Claude', 'MAT-2024-0010', 'MAT-SOL-2024-0010', 'Caporal', 'Infanterie', '1987-02-14', 'Kribi', (SELECT id FROM compagnie WHERE code = 'CIE-002' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mbappe', 'Samuel', 'MAT-2024-0011', 'MAT-SOL-2024-0011', 'Soldat', 'Infanterie', '1991-11-30', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-002' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Abega', 'Paul', 'MAT-2024-0012', 'MAT-SOL-2024-0012', 'Soldat', 'Infanterie', '1986-07-08', 'Bafoussam', (SELECT id FROM compagnie WHERE code = 'CIE-002' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW());

-- Compagnie 3 - 2ème Compagnie d'Infanterie
INSERT INTO militaire (id, nom, prenom, matricule_militaire, matricule_solde, grade, arme_service, date_naissance, lieu_naissance, compagnie_id, statut_validation, etat, created_at, updated_at) VALUES
(UUID(), 'Kamga', 'Yves', 'MAT-2024-0013', 'MAT-SOL-2024-0013', 'Capitaine', 'Infanterie', '1972-09-05', 'Yaoundé', (SELECT id FROM compagnie WHERE code = 'CIE-003' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Fouda', 'Joseph', 'MAT-2024-0014', 'MAT-SOL-2024-0014', 'Lieutenant', 'Infanterie', '1984-12-20', 'Garoua', (SELECT id FROM compagnie WHERE code = 'CIE-003' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Tchameni', 'Martin', 'MAT-2024-0015', 'MAT-SOL-2024-0015', 'Adjudant', 'Infanterie', '1977-03-28', 'Maroua', (SELECT id FROM compagnie WHERE code = 'CIE-003' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Ze', 'Emile', 'MAT-2024-0016', 'MAT-SOL-2024-0016', 'Caporal', 'Infanterie', '1989-06-15', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-003' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mba', 'Michel', 'MAT-2024-0017', 'MAT-SOL-2024-0017', 'Soldat', 'Infanterie', '1993-08-10', 'Bamenda', (SELECT id FROM compagnie WHERE code = 'CIE-003' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Nkodo', 'Pierre', 'MAT-2024-0018', 'MAT-SOL-2024-0018', 'Soldat', 'Infanterie', '1985-04-22', 'Bertoua', (SELECT id FROM compagnie WHERE code = 'CIE-003' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW());

-- Compagnie 4 - Compagnie de Support
INSERT INTO militaire (id, nom, prenom, matricule_militaire, matricule_solde, grade, arme_service, date_naissance, lieu_naissance, compagnie_id, statut_validation, etat, created_at, updated_at) VALUES
(UUID(), 'Olinga', 'Henri', 'MAT-2024-0019', 'MAT-SOL-2024-0019', 'Capitaine', 'Support Logistique', '1971-11-17', 'Yaoundé', (SELECT id FROM compagnie WHERE code = 'CIE-SUP-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Ngando', 'Paul', 'MAT-2024-0020', 'MAT-SOL-2024-0020', 'Lieutenant', 'Support Logistique', '1982-05-09', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-SUP-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mounkala', 'Jean', 'MAT-2024-0021', 'MAT-SOL-2024-0021', 'Adjudant', 'Support Logistique', '1976-08-14', 'Bafoussam', (SELECT id FROM compagnie WHERE code = 'CIE-SUP-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Etoundi', 'Jacques', 'MAT-2024-0022', 'MAT-SOL-2024-0022', 'Caporal', 'Support Logistique', '1988-10-25', 'Garoua', (SELECT id FROM compagnie WHERE code = 'CIE-SUP-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Fotsing', 'Laurent', 'MAT-2024-0023', 'MAT-SOL-2024-0023', 'Soldat', 'Support Logistique', '1990-12-18', 'Maroua', (SELECT id FROM compagnie WHERE code = 'CIE-SUP-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mbemba', 'Armand', 'MAT-2024-0024', 'MAT-SOL-2024-0024', 'Soldat', 'Support Logistique', '1987-03-07', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-SUP-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW());

-- Compagnie 5 - Compagnie du Génie
INSERT INTO militaire (id, nom, prenom, matricule_militaire, matricule_solde, grade, arme_service, date_naissance, lieu_naissance, compagnie_id, statut_validation, etat, created_at, updated_at) VALUES
(UUID(), 'Mvondo', 'Arsène', 'MAT-2024-0025', 'MAT-SOL-2024-0025', 'Capitaine', 'Génie Militaire', '1973-02-28', 'Yaoundé', (SELECT id FROM compagnie WHERE code = 'CIE-GEN-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Tchuisse', 'Blaise', 'MAT-2024-0026', 'MAT-SOL-2024-0026', 'Lieutenant', 'Génie Militaire', '1981-09-15', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-GEN-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Nkoulou', 'Thomas', 'MAT-2024-0027', 'MAT-SOL-2024-0027', 'Adjudant', 'Génie Militaire', '1978-06-22', 'Bafoussam', (SELECT id FROM compagnie WHERE code = 'CIE-GEN-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mballa', 'Etienne', 'MAT-2024-0028', 'MAT-SOL-2024-0028', 'Caporal', 'Génie Militaire', '1986-11-08', 'Maroua', (SELECT id FROM compagnie WHERE code = 'CIE-GEN-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Fotsing', 'Marcel', 'MAT-2024-0029', 'MAT-SOL-2024-0029', 'Soldat', 'Génie Militaire', '1992-04-17', 'Garoua', (SELECT id FROM compagnie WHERE code = 'CIE-GEN-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Ngando', 'René', 'MAT-2024-0030', 'MAT-SOL-2024-0030', 'Soldat', 'Génie Militaire', '1989-07-30', 'Bamenda', (SELECT id FROM compagnie WHERE code = 'CIE-GEN-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW());

-- Compagnie 6 - Compagnie de Transmission
INSERT INTO militaire (id, nom, prenom, matricule_militaire, matricule_solde, grade, arme_service, date_naissance, lieu_naissance, compagnie_id, statut_validation, etat, created_at, updated_at) VALUES
(UUID(), 'Nlend', 'Joseph', 'MAT-2024-0031', 'MAT-SOL-2024-0031', 'Capitaine', 'Transmission', '1974-05-12', 'Yaoundé', (SELECT id FROM compagnie WHERE code = 'CIE-TRANS-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Fouda', 'Emile', 'MAT-2024-0032', 'MAT-SOL-2024-0032', 'Lieutenant', 'Transmission', '1983-08-19', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-TRANS-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Talla', 'Jean-Pierre', 'MAT-2024-0033', 'MAT-SOL-2024-0033', 'Adjudant', 'Transmission', '1977-12-04', 'Bafoussam', (SELECT id FROM compagnie WHERE code = 'CIE-TRANS-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Ousmanou', 'Moussa', 'MAT-2024-0034', 'MAT-SOL-2024-0034', 'Caporal', 'Transmission', '1988-03-26', 'Garoua', (SELECT id FROM compagnie WHERE code = 'CIE-TRANS-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Etoundi', 'François', 'MAT-2024-0035', 'MAT-SOL-2024-0035', 'Soldat', 'Transmission', '1991-09-14', 'Maroua', (SELECT id FROM compagnie WHERE code = 'CIE-TRANS-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW()),
(UUID(), 'Mballa', 'Antoine', 'MAT-2024-0036', 'MAT-SOL-2024-0036', 'Soldat', 'Transmission', '1986-06-21', 'Douala', (SELECT id FROM compagnie WHERE code = 'CIE-TRANS-001' LIMIT 1), 'VALIDE', 'ACTIF', NOW(), NOW());

-- 7. CRÉATION DES DOSSIERS ADMINISTRATIFS POUR CHAQUE MILITAIRE
INSERT INTO dossier_administratif (id, militaire_id, statut_dossier, created_at, updated_at)
SELECT UUID(), m.id, 'ADMINISTRATIF', NOW(), NOW() FROM militaire m;

-- ========================================
-- FIN DU SCRIPT
-- ========================================

-- NOTES:
-- 1. Les mots de passe sont hashés avec BCrypt (mot de passe: "password123")
-- 2. Chaque compagnie a exactement 6 militaires
-- 3. La hiérarchie est complète: RMIA → Brigade → Bataillon → Compagnie → Militaire
-- 4. Les grades et services sont variés pour tester tous les cas
-- 5. Les dates de naissance sont réalistes (entre 1970 et 1995)
-- 6. Les lieux de naissance sont des villes camerounaises réelles
