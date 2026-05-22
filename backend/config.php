<?php
// Connexion à la base de données PostgreSQL via Render
try {
    $databaseUrl = getenv('DATABASE_URL');
    if (!$databaseUrl) {
        die("Erreur de configuration: La variable d'environnement DATABASE_URL est manquante.");
    }

    // Extraction des informations depuis DATABASE_URL
    $dbUrlParams = parse_url($databaseUrl);
    $host   = $dbUrlParams["host"];
    $port   = isset($dbUrlParams["port"]) ? $dbUrlParams["port"] : 5432;
    $user   = $dbUrlParams["user"];
    $pass   = isset($dbUrlParams["pass"]) ? urldecode($dbUrlParams["pass"]) : '';
    $dbname = ltrim($dbUrlParams["path"], "/");

    // DSN PostgreSQL avec SSL obligatoire
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require;options='--client_encoding=UTF8'";
    $pdo = new PDO($dsn, $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données Render: " . $e->getMessage());
}

// Configuration de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonctions utiles
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
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
    return $_SESSION['role'] ?? null;
}

function isSupervisor() {
    return getUserRole() === 'SUPERVISOR';
}

function isOfficier() {
    return getUserRole() === 'OFFICIER';
}
?>
