<?php
// Configuration de la base de données (Supporte Render via Variables d'Environnement)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'cimis');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// === CONFIGURATION INFINITYFREE (commentée) ===
/*
define('DB_HOST', 'sql113.infinityfree.com');
define('DB_NAME', 'if0_39882531_cimis');
define('DB_USER', 'if0_39882531');
define('DB_PASS', 'cmTJtR2Z2yq8MO');
*/

// Connexion à la base de données PostgreSQL
try {
    $databaseUrl = getenv('DATABASE_URL');
    
    if ($databaseUrl) {
        // Extraction des informations depuis DATABASE_URL (Render)
        $dbUrlParams = parse_url($databaseUrl);
        $host = $dbUrlParams["host"];
        $port = isset($dbUrlParams["port"]) ? $dbUrlParams["port"] : 5432;
        $user = $dbUrlParams["user"];
        $pass = isset($dbUrlParams["pass"]) ? urldecode($dbUrlParams["pass"]) : '';
        $dbname = ltrim($dbUrlParams["path"], "/");
        
        // Toujours utiliser TCP avec port explicite (évite les erreurs de socket Unix)
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
        $pdo = new PDO($dsn, $user, $pass);
    } elseif (getenv('RENDER')) {
        // Sur Render, DATABASE_URL est obligatoire
        die("Erreur de configuration: La variable d'environnement DATABASE_URL n'est pas définie. "
          . "Veuillez la configurer dans le dashboard Render (Environment > Add Environment Variable).");
    } else {
        // Connexion locale (XAMPP) — MySQL via socket ou TCP
        $host = DB_HOST;
        $port = 3306;
        $dsn = "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $env = getenv('RENDER') ? 'Render' : 'local';
    die("Erreur de connexion à la base de données ({$env}): " . $e->getMessage());
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
