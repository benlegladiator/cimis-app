<?php
/**
 * Configuration pour CIMIS hébergé sur Render avec PostgreSQL
 * Adaptée pour l'environnement Render + PostgreSQL
 */

// Configuration PostgreSQL pour Render
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'cimis');
define('DB_USER', $_ENV['DB_USER'] ?? 'postgres');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? '');

// Configuration PDO pour PostgreSQL
try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . "";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // En cas d'erreur, essayer avec MySQL (fallback)
    try {
        $dsn_mysql = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn_mysql, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e_mysql) {
        die(json_encode([
            'success' => false,
            'error' => 'Erreur de connexion PostgreSQL et MySQL: ' . $e->getMessage(),
            'postgresql_error' => $e->getMessage(),
            'mysql_error' => $e_mysql->getMessage()
        ]));
    }
}

// Afficher les informations de connexion pour débogage
if (isset($_GET['debug'])) {
    echo json_encode([
        'database_type' => 'PostgreSQL',
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME,
        'user' => DB_USER,
        'dsn' => $dsn,
        'connection_status' => $pdo ? 'success' : 'failed'
    ]);
    exit();
}
?>
