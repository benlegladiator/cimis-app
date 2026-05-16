<?php
// Version de test pour diagnostic InfinityFree
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Impression</title></head><body>";
echo "<h1>Test de la page impression.php</h1>";

// Test 1: Session
echo "<h2>Test 1: Session</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session démarrée: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ OK" : "❌ Erreur") . "<br>";

// Test 2: Fichiers requis
echo "<h2>Test 2: Fichiers requis</h2>";
$files_to_check = [
    '../backend/config.php',
    '../pdf/CarteMilitaire.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file - Existe<br>";
    } else {
        echo "❌ $file - Manquant<br>";
    }
}

// Test 3: Base de données
echo "<h2>Test 3: Base de données</h2>";
try {
    require_once '../backend/config.php';
    if (isset($pdo)) {
        echo "✅ PDO disponible<br>";
        
        $test = $pdo->query("SELECT 1");
        if ($test) {
            echo "✅ Connexion BDD OK<br>";
        } else {
            echo "❌ Connexion BDD échouée<br>";
        }
    } else {
        echo "❌ PDO non défini<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur BDD: " . $e->getMessage() . "<br>";
}

// Test 4: Permissions
echo "<h2>Test 4: Permissions</h2>";
if (is_writable('.')) {
    echo "✅ Dossier courant accessible en écriture<br>";
} else {
    echo "❌ Dossier courant non accessible en écriture<br>";
}

echo "<h2>Conclusion</h2>";
echo "<p>Si tous les tests sont OK, le problème vient probablement du code lui-même.</p>";
echo "<p>Sinon, corrigez les erreurs identifiées ci-dessus.</p>";

echo "</body></html>";
?>
