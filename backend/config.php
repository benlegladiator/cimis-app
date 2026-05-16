<?php
// Configuration de la base de données (Supporte Render via Variables d'Environnement)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'cimis');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// === CONFIGURATION INFINITYFREE (commentée) ===
/*
define('DB_HOST', 'sql113.infinityfree.com');
define('DB_NAME', 'if0_39882531_cimis');
define('DB_USER', 'if0_39882531');
define('DB_PASS', 'cmTJtR2Z2yq8MO');
*/

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Configuration de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonctions utiles
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function isSupervisor() {
    return getUserRole() === 'SUPERVISOR';
}

function isOfficier() {
    return getUserRole() === 'OFFICIER';
}
?>
