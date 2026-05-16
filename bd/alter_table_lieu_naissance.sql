-- Ajout du champ lieu_naissance à la table candidat
-- Tâche 1: Ajouter lieu de naissance à la base de données

ALTER TABLE `candidat` 
ADD COLUMN `lieu_naissance` varchar(255) DEFAULT NULL COMMENT 'Lieu de naissance du personnel' 
AFTER `date_naissance`;
