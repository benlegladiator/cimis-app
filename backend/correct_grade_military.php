<?php
require_once 'config.php';

echo "<h2>CORRECTION DES GRADES MILITAIRES INCOHERENTS</h2>";

try {
    $pdo->beginTransaction();
    
    $total_corrections = 0;
    
    // 1. Corriger "MAITRE PRINCIPAL MAJOR" dans l'Armée de Terre (n'existe pas)
    echo "<h3>Correction des grades incohérents dans l'Armée de Terre</h3>";
    
    $stmt_at = $pdo->prepare("SELECT id, matricule, nom, prenom, grade FROM candidat WHERE unite = 'ARMÉE DE TERRE' AND grade = 'MAITRE PRINCIPAL MAJOR'");
    $stmt_at->execute();
    $militaires_at = $stmt_at->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($militaires_at as $militaire) {
        // Remplacer par "Adjudant-Chef Major" qui est l'équivalent dans l'Armée de Terre
        $update_stmt = $pdo->prepare("UPDATE candidat SET grade = 'Adjudant-Chef Major' WHERE id = ?");
        $update_stmt->execute([$militaire['id']]);
        
        echo "✅ Corrigé: {$militaire['matricule']} - {$militaire['nom']} {$militaire['prenom']}<br>";
        echo "   Ancien grade: MAITRE PRINCIPAL MAJOR<br>";
        echo "   Nouveau grade: Adjudant-Chef Major<br><br>";
        
        $total_corrections++;
    }
    
    // 2. Corriger les autres grades incohérents
    echo "<h3>Vérification des autres grades incohérents</h3>";
    
    // Vérifier les grades qui n'existent pas dans l'Armée de Terre
    $grades_marine_seulement = ['MAITRE PRINCIPAL', 'PREMIER MAITRE', 'MAITRE', 'SECOND MAITRE'];
    
    // Vérifier les grades qui n'existent pas dans l'Armée de l'Air
    $grades_marine_dans_air = ['MAITRE PRINCIPAL MAJOR'];
    
    foreach ($grades_marine_seulement as $grade_marine) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE unite = 'ARMÉE DE TERRE' AND grade = ?");
        $stmt_check->execute([$grade_marine]);
        $count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            echo "⚠️ $count militaire(s) dans l'Armée de Terre avec le grade '$grade_marine' (grade Marine uniquement)<br>";
            
            // Correction automatique pour les grades Marine dans l'Armée de Terre
            $correspondances = [
                'MAITRE PRINCIPAL' => 'Adjudant-Chef',
                'PREMIER MAITRE' => 'Adjudant',
                'MAITRE' => 'Sergent-Chef',
                'SECOND MAITRE' => 'Sergent'
            ];
            
            if (isset($correspondances[$grade_marine])) {
                $stmt_correct = $pdo->prepare("UPDATE candidat SET grade = ? WHERE unite = 'ARMÉE DE TERRE' AND grade = ?");
                $stmt_correct->execute([$correspondances[$grade_marine], $grade_marine]);
                
                echo "   → Corrigé automatiquement en: {$correspondances[$grade_marine]}<br>";
                $total_corrections += $count;
            }
        }
    }
    
    // 3. Vérifier les grades qui n'existent pas dans l'Armée de l'Air
    echo "<h3>Vérification des grades incohérents dans l'Armée de l'Air</h3>";
    
    foreach ($grades_marine_dans_air as $grade_marine) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE unite = 'ARMÉE DE L\'AIR' AND grade = ?");
        $stmt_check->execute([$grade_marine]);
        $count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            echo "⚠️ $count militaire(s) dans l'Armée de l'Air avec le grade '$grade_marine' (grade Marine uniquement)<br>";
            
            // Correction automatique pour les grades Marine dans l'Armée de l'Air
            $correspondances = [
                'MAITRE PRINCIPAL MAJOR' => 'Adjudant-Chef Major'
            ];
            
            if (isset($correspondances[$grade_marine])) {
                $stmt_correct = $pdo->prepare("UPDATE candidat SET grade = ? WHERE unite = 'ARMÉE DE L\'AIR' AND grade = ?");
                $stmt_correct->execute([$correspondances[$grade_marine], $grade_marine]);
                
                echo "   → Corrigé automatiquement en: {$correspondances[$grade_marine]}<br>";
                $total_corrections += $count;
            }
        }
    }
    
    // 4. Vérifier les grades qui n'existent pas dans la Marine
    echo "<h3>Vérification des grades incohérents dans la Marine</h3>";
    
    $grades_terre_seulement = ['Adjudant-Chef Major', 'Adjudant-Chef', 'Adjudant', 'Sergent-Chef', 'Sergent', 'Caporal-Chef', 'Caporal'];
    
    foreach ($grades_terre_seulement as $grade_terre) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE unite = 'MARINE NATIONALE' AND grade = ?");
        $stmt_check->execute([$grade_terre]);
        $count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            echo "⚠️ $count militaire(s) dans la Marine avec le grade '$grade_terre' (grade Terre uniquement)<br>";
            
            // Correction automatique pour les grades Terre dans la Marine
            $correspondances = [
                'Adjudant-Chef Major' => 'Maître Principal Major',
                'Adjudant-Chef' => 'Maître Principal',
                'Adjudant' => 'Premier Maître',
                'Sergent-Chef' => 'Maître',
                'Sergent' => 'Second Maître',
                'Caporal-Chef' => 'Quartier-Maître de 1ère Classe',
                'Caporal' => 'Quartier-Maître de 2ème Classe'
            ];
            
            if (isset($correspondances[$grade_terre])) {
                $stmt_correct = $pdo->prepare("UPDATE candidat SET grade = ? WHERE unite = 'MARINE NATIONALE' AND grade = ?");
                $stmt_correct->execute([$correspondances[$grade_terre], $grade_terre]);
                
                echo "   → Corrigé automatiquement en: {$correspondances[$grade_terre]}<br>";
                $total_corrections += $count;
            }
        }
    }
    
    $pdo->commit();
    
    echo "<h2>RÉSUMÉ</h2>";
    echo "<strong>Total corrections effectuées:</strong> $total_corrections<br>";
    echo "<strong>Opération terminée avec succès!</strong><br>";
    
    // Afficher les grades par unité après correction
    echo "<h3>Grades par unité après correction:</h3>";
    
    $unites = ['GENDARMERIE NATIONALE', 'ARMÉE DE TERRE', 'MARINE NATIONALE', 'ARMÉE DE L\'AIR'];
    
    foreach ($unites as $unite) {
        $stmt_unite = $pdo->prepare("SELECT DISTINCT grade, COUNT(*) as count FROM candidat WHERE unite = ? GROUP BY grade ORDER BY count DESC");
        $stmt_unite->execute([$unite]);
        $grades_unite = $stmt_unite->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>$unite:</h4>";
        foreach ($grades_unite as $grade_info) {
            echo "- {$grade_info['grade']}: {$grade_info['count']} militaire(s)<br>";
        }
        echo "<br>";
    }
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "Erreur: " . $e->getMessage();
}
?>
