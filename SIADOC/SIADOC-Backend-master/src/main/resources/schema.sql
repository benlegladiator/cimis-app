-- Supprimer les anciennes contraintes qui empêchent le démarrage
ALTER TABLE IF EXISTS bataillon DROP CONSTRAINT IF EXISTS ukho3x0c1wj55aolmi2scamagvk;
ALTER TABLE IF EXISTS brigade DROP CONSTRAINT IF EXISTS uk_brigade_nom;
