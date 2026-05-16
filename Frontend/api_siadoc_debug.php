<?php
// Fichier de test pour diagnostiquer le problème d'affichage HTML
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DIAGNOSTIC API SIADOC</h1>";

// Test 1: Vérifier si PHP fonctionne
echo "<h2>Test 1: PHP fonctionne</h2>";
echo "<p>Date/Heure: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test 2: Vérifier les variables serveur
echo "<h2>Test 2: Variables serveur</h2>";
echo "<p>REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>GET params: " . json_encode($_GET) . "</p>";

// Test 3: Vérifier la condition de routage
echo "<h2>Test 3: Condition de routage</h2>";
$request_method = $_SERVER['REQUEST_METHOD'];
$has_action = isset($_GET['action']);
echo "<p>Method: $request_method</p>";
echo "<p>Has action: " . ($has_action ? 'true' : 'false') . "</p>";
echo "<p>Should show HTML: " . ($request_method === 'GET' && !$has_action ? 'true' : 'false') . "</p>";

// Test 4: Test simple d'affichage HTML
echo "<h2>Test 4: Test simple HTML</h2>";
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Simple</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test { background: #f0f0f0; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="test">
        <h3>✅ HTML s'affiche correctement !</h3>
        <p>Si vous voyez ce texte formaté, PHP fonctionne bien.</p>
    </div>
</body>
</html>
<?php

// Test 5: Lien vers l'API principale
echo "<h2>Test 5: Lien vers l'API principale</h2>";
echo "<p><a href='api_siadoc.php' target='_blank'>Ouvrir l'API SIADOC principale</a></p>";

?>
