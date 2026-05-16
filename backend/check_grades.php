<?php
require_once 'config.php';

echo "<h2>ANALYSE DES GRADES PAR UNITÉ</h2>";

try {
    $stmt = $pdo->prepare("SELECT DISTINCT grade, unite FROM candidat WHERE unite != 'CIVIL' ORDER BY unite, grade");
    $stmt->execute();
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organiser par unité
    $result = [];
    foreach ($grades as $grade) {
        $unite = $grade['unite'];
        if (!isset($result[$unite])) {
            $result[$unite] = [];
        }
        $result[$unite][] = $grade['grade'];
    }
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Compter les grades par unité
    echo "<h2>STATISTIQUES</h2>";
    foreach ($result as $unite => $grades_list) {
        echo "<strong>$unite:</strong> " . count($grades_list) . " grades<br>";
        foreach ($grades_list as $grade) {
            echo "  - $grade<br>";
        }
        echo "<br>";
    }
    
} catch(PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
