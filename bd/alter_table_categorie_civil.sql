-- Ajout du champ categorie_civil à la table candidat
-- Tâche 29: Ajouter catégories pour personnel civil avec liste déroulante

ALTER TABLE `candidat` 
ADD COLUMN `categorie_civil` varchar(50) DEFAULT NULL COMMENT 'Catégorie pour le personnel civil' 
AFTER `fonction`;
