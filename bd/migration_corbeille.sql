-- Migration pour le système de corbeille CIMIS
-- Ajout des attributs pour la gestion de la suppression soft

-- 1. Ajout de l'attribut 'supprimer' (0 = dans corbeille, 1 = visible)
ALTER TABLE candidat ADD COLUMN supprimer TINYINT(1) NOT NULL DEFAULT 1;

-- 2. Ajout de l'attribut 'supprimer_par' pour suivre qui a supprimé la carte
ALTER TABLE candidat ADD COLUMN supprimer_par VARCHAR(50) NULL;

-- 3. Ajout de l'attribut 'date_suppression' pour suivre quand la carte a été supprimée
ALTER TABLE candidat ADD COLUMN date_suppression DATETIME NULL;

-- 4. Création d'un index pour optimiser les requêtes sur la corbeille
CREATE INDEX idx_supprimer ON candidat(supprimer);
CREATE INDEX idx_supprimer_par ON candidat(supprimer_par);

-- 5. Mise à jour des cartes existantes (elles sont toutes visibles par défaut)
UPDATE candidat SET supprimer = 1 WHERE supprimer IS NULL;
