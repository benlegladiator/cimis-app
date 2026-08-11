<?php
// Fichier de test pour vérifier le fonctionnement de l'API SIADOC
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test API SIADOC</h1>";

// Test 1: Accès direct à l'interface
echo "<h2>Test 1: Accès direct à l'interface</h2>";
echo "<p>URL: http://localhost/cimcim/Frontend/api_siadoc.php</p>";
echo "<a href='api_siadoc.php' target='_blank'>Ouvrir l'interface</a><br><br>";

// Test 2: Appel avec action help
echo "<h2>Test 2: Appel avec action help</h2>";
echo "<p>URL: http://localhost/cimcim/Frontend/api_siadoc.php?action=help</p>";
$help_url = 'http://localhost/cimcim/Frontend/api_siadoc.php?action=help';
$help_response = file_get_contents($help_url);
echo "<pre>" . htmlspecialchars($help_response) . "</pre><br>";

// Test 3: Appel avec action get_militaire
echo "<h2>Test 3: Appel avec action get_militaire</h2>";
echo "<p>URL: http://localhost/cimcim/Frontend/api_siadoc.php?action=get_militaire&matricule=MAT-2023-001</p>";
$militaire_url = 'http://localhost/cimcim/Frontend/api_siadoc.php?action=get_militaire&matricule=MAT-2023-001';
$militaire_response = file_get_contents($militaire_url);
echo "<pre>" . htmlspecialchars($militaire_response) . "</pre><br>";

// Test 4: Vérification des variables serveur
echo "<h2>Test 4: Variables serveur</h2>";
echo "<p><strong>REQUEST_METHOD:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>SCRIPT_NAME:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>PHP_SELF:</strong> " . $_SERVER['PHP_SELF'] . "</p>";
echo "<p><strong>GET params:</strong> " . json_encode($_GET) . "</p>";

// Test 5: Simulation du routeur
echo "<h2>Test 5: Simulation du routeur</h2>";
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path_info = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path_info, '/'));

echo "<p><strong>Path parts:</strong> " . json_encode($path_parts) . "</p>";
echo "<p><strong>Empty GET:</strong> " . (empty($_GET) ? 'true' : 'false') . "</p>";
echo "<p><strong>Condition interface:</strong> " . ($request_method === 'GET' && empty($_GET) ? 'true' : 'false') . "</p>";
echo "<p><strong>Condition API:</strong> " . (isset($_GET['action']) ? 'true' : 'false') . "</p>";

?>
