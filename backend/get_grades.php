<?php
require_once 'config.php';

header('Content-Type: application/json');

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
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
