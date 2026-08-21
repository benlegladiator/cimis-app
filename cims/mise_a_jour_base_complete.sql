-- Script de mise à jour complète de la base CIMIS
-- Pour intégrer toutes les améliorations du refactoring (30 tâches)
-- Généré le 28 avril 2026

-- ========================================
-- TÂCHE 1: Ajouter lieu de naissance
-- ========================================
ALTER TABLE `candidat` 
ADD COLUMN `lieu_naissance` varchar(255) DEFAULT NULL 
COMMENT 'Lieu de naissance du personnel' 
AFTER `date_naissance`;

-- ========================================
-- TÂCHE 29: Ajouter catégories pour personnel civil
-- ========================================
ALTER TABLE `candidat` 
ADD COLUMN `categorie_civil` varchar(50) DEFAULT NULL 
COMMENT 'Catégorie pour le personnel civil' 
AFTER `grade`;

-- ========================================
-- VÉRIFICATION: Mettre à jour le champ annee_dernier_galon
-- ========================================
-- Si le champ est encore en YEAR(4), le convertir en DATE pour stocker la date complète
ALTER TABLE `candidat` 
MODIFY COLUMN `annee_dernier_galon` date DEFAULT NULL 
COMMENT 'Date de la dernière promotion au grade (remplace année dernier galon)';

-- ========================================
-- VÉRIFICATION: S'assurer que nb_reimpressions existe
-- ========================================
-- Le champ existe déjà dans votre base (ligne 90), mais vérifions
ALTER TABLE `candidat` 
MODIFY COLUMN `nb_reimpressions` int(11) DEFAULT 0 
COMMENT 'Nombre de fois que la carte a été réimprimée (compteur d''impressions)';

-- ========================================
-- NETTOYAGE: Mettre à jour les données existantes si nécessaire
-- ========================================
-- Mettre à jour les valeurs CIVIL en PERSONNEL CIVIL si nécessaire
UPDATE `candidat` 
SET `unite` = 'CIVIL' 
WHERE `unite` = 'PERSONNEL CIVIL';

-- ========================================
-- VÉRIFICATION DE LA STRUCTURE FINALE
-- ========================================
-- La table candidat devrait maintenant contenir tous les champs nécessaires:
-- - id, matricule, matricule_militaire, nom, prenom
-- - date_naissance, lieu_naissance (NOUVEAU)
-- - sexe, numero_cni
-- - taille, poids, groupe_sanguin
-- - unite, grade, categorie_civil (NOUVEAU)
-- - photo, date_enrolement, date_dernier_grade
-- - annee_dernier_galon (converti en DATE)
-- - code_qr, empreinte_data
-- - nb_reimpressions, date_derniere_reimpression
-- - statut_militaire, date_changement_statut, motif_changement_statut
-- - source_system, date_modification, sync_status
-- - suspendus

-- ========================================
-- INDEXATION pour optimisation
-- ========================================
CREATE INDEX IF NOT EXISTS `idx_matricule` ON `candidat` (`matricule`);
CREATE INDEX IF NOT EXISTS `idx_matricule_militaire` ON `candidat` (`matricule_militaire`);
CREATE INDEX IF NOT EXISTS `idx_unite` ON `candidat` (`unite`);
CREATE INDEX IF NOT EXISTS `idx_grade` ON `candidat` (`grade`);
CREATE INDEX IF NOT EXISTS `idx_categorie_civil` ON `candidat` (`categorie_civil`);

-- ========================================
-- RAPPORT DE MISE À JOUR
-- ========================================
SELECT 'Mise à jour de la base CIMIS terminée avec succès!' as message;
SELECT 'Champs ajoutés: lieu_naissance, categorie_civil' as champs_ajoutes;
SELECT 'Champs modifiés: annee_dernier_galon (YEAR -> DATE)' as champs_modifies;
SELECT 'Index créés pour optimisation' as indexation;
