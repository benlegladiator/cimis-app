<?php
// Script pour exécuter la migration de la base de données
// pour le système de corbeille CIMIS

require_once 'backend/config.php';

try {
    echo "Début de la migration pour le système de corbeille...\n\n";
    
    // 1. Ajout de l'attribut 'supprimer'
    echo "1. Ajout de l'attribut 'supprimer'...\n";
    $sql1 = "ALTER TABLE candidat ADD COLUMN supprimer TINYINT(1) NOT NULL DEFAULT 1";
    try {
        $pdo->exec($sql1);
        echo "   - Attribut 'supprimer' ajouté avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "   - Attribut 'supprimer' existe déjà\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Ajout de l'attribut 'supprimer_par'
    echo "2. Ajout de l'attribut 'supprimer_par'...\n";
    $sql2 = "ALTER TABLE candidat ADD COLUMN supprimer_par VARCHAR(50) NULL";
    try {
        $pdo->exec($sql2);
        echo "   - Attribut 'supprimer_par' ajouté avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "   - Attribut 'supprimer_par' existe déjà\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Ajout de l'attribut 'date_suppression'
    echo "3. Ajout de l'attribut 'date_suppression'...\n";
    $sql3 = "ALTER TABLE candidat ADD COLUMN date_suppression DATETIME NULL";
    try {
        $pdo->exec($sql3);
        echo "   - Attribut 'date_suppression' ajouté avec succès\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "   - Attribut 'date_suppression' existe déjà\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Création des index
    echo "4. Création des index pour optimisation...\n";
    $sql4a = "CREATE INDEX idx_supprimer ON candidat(supprimer)";
    try {
        $pdo->exec($sql4a);
        echo "   - Index 'idx_supprimer' créé\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate key name") !== false) {
            echo "   - Index 'idx_supprimer' existe déjà\n";
        } else {
            echo "   - Index 'idx_supprimer' non créé (peut-être déjà existant)\n";
        }
    }
    
    $sql4b = "CREATE INDEX idx_supprimer_par ON candidat(supprimer_par)";
    try {
        $pdo->exec($sql4b);
        echo "   - Index 'idx_supprimer_par' créé\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate key name") !== false) {
            echo "   - Index 'idx_supprimer_par' existe déjà\n";
        } else {
            echo "   - Index 'idx_supprimer_par' non créé (peut-être déjà existant)\n";
        }
    }
    
    // 5. Mise à jour des cartes existantes
    echo "5. Mise à jour des cartes existantes...\n";
    $sql5 = "UPDATE candidat SET supprimer = 1 WHERE supprimer IS NULL OR supprimer != 1";
    $rows = $pdo->exec($sql5);
    echo "   - $rows carte(s) mise(s) à jour comme visibles\n";
    
    echo "\n=== MIGRATION TERMINÉE AVEC SUCCÈS ===\n";
    echo "Les attributs suivants ont été ajoutés à la table 'candidat':\n";
    echo "- supprimer (TINYINT, DEFAULT 1)\n";
    echo "- supprimer_par (VARCHAR(50), NULL)\n";
    echo "- date_suppression (DATETIME, NULL)\n";
    echo "\nIndex créés pour optimisation des requêtes.\n";
    
} catch (PDOException $e) {
    echo "\n=== ERREUR DE MIGRATION ===\n";
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Vérifiez que la base de données est accessible et que vous avez les permissions nécessaires.\n";
}
?>
