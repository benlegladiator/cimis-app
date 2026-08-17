<?php
// Démarrer l'Output Buffering pour éviter les erreurs "headers already sent"
if (ob_get_level() === 0) {
    ob_start();
}

// En-têtes HTTP de sécurité (Web Hardening)
if (!headers_sent()) {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self' https://cdnjs.cloudflare.com/ https://fonts.googleapis.com/ https://fonts.gstatic.com/; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com/ https://fonts.googleapis.com/; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com/; frame-ancestors 'none';");
}

// Configuration de la base de données (détection dynamique Render vs Local XAMPP)
$host_env = getenv('DB_HOST') ?: 'localhost';
$port_env = getenv('DB_PORT') ?: '3306';
$name_env = getenv('DB_NAME') ?: 'cimis';
$user_env = getenv('DB_USER') ?: 'root';
$pass_env = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

// Forcer le driver MySQL pour Railway ou si le port/hôte indique MySQL
$driver_env = 'mysql';
if (getenv('DB_DRIVER') && !str_contains($host_env, 'rlwy.net')) {
    $driver_env = getenv('DB_DRIVER');
}

define('DB_DRIVER', $driver_env);
define('DB_HOST',   $host_env);
define('DB_PORT',   $port_env);
define('DB_NAME',   $name_env);
define('DB_USER',   $user_env);
define('DB_PASS',   $pass_env);

// Codes secrets de l'application
define('ACCESS_CODE',    getenv('ACCESS_CODE')    ?: 'CIMIS2.02026');
define('RESET_CODE',     getenv('RESET_CODE')     ?: 'RESETRESET');
define('SIADOC_API_KEY', getenv('SIADOC_API_KEY') ?: 'siadoc-2026-cimis-integration');
define('SIADOC_API_URL', getenv('SIADOC_API_URL') ?: 'https://siadoc.onrender.com');

// Connexion à la base de données via PDO (avec fallback automatique vers MySQL)
try {
    if (DB_DRIVER === 'mysql') {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
    } else {
        $dsn = DB_DRIVER . ":host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
    }
} catch(PDOException $e) {
    // Si la connexion avec le driver spécifié échoue, tenter le driver mysql en fallback
    try {
        $dsn_fallback = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn_fallback, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
    } catch (PDOException $e_mysql) {
        die("Erreur de connexion à la base de données (MySQL & fallback): " . $e->getMessage() . " | " . $e_mysql->getMessage());
    }
}

// Configuration sécurisée des cookies de session et démarrage
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        @ini_set('session.cookie_secure', 1);
    }
    @session_start();
}

// Détection et tentative d'activation automatique de l'extension GD
if (!extension_loaded('gd') && !isset($_SESSION['gd_auto_enabled'])) {
    $php_ini_paths = [
        'C:\\xampp\\php\\php.ini',
        ini_get('cfg_file_path')
    ];
    
    foreach ($php_ini_paths as $php_ini_path) {
        if ($php_ini_path && file_exists($php_ini_path) && is_writable($php_ini_path)) {
            $content = file_get_contents($php_ini_path);
            $pattern = '/;\s*extension\s*=\s*gd/';
            if (preg_match($pattern, $content)) {
                $new_content = preg_replace($pattern, 'extension=gd', $content);
                if (file_put_contents($php_ini_path, $new_content) !== false) {
                    $_SESSION['gd_auto_enabled'] = true;
                    error_log("GD extension automatically enabled in php.ini: " . $php_ini_path);
                    break;
                }
            }
        }
    }
}

// Configuration sécurisée et durable des cookies de session (24h)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @ini_set('session.gc_maxlifetime', 86400);  // 24 heures
    @ini_set('session.cookie_lifetime', 86400); // 24 heures
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);

    if (function_exists('session_set_cookie_params')) {
        $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        @session_set_cookie_params([
            'lifetime' => 86400,
            'path'     => '/',
            'secure'   => $is_https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    @session_start();
}

// Protection contre le vol de session (Basée sur l'User-Agent pour supporter les IP dynamiques/mobiles et proxies Render)
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (!isset($_SESSION['secure_ua'])) {
        $_SESSION['secure_ua'] = $current_ua;
    } elseif ($_SESSION['secure_ua'] !== $current_ua) {
        // Discordance majeure d'User-Agent -> destruction de session
        session_unset();
        session_destroy();
        header('Location: ../index.php');
        exit();
    }
}

// Gestion de la déconnexion automatique (Délai étendu à 24h d'inactivité)
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $timeout_duration = 86400; // 24 heures en secondes
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Destruction de la session expirée
        session_unset();
        session_destroy();
        
        // Nouvelle session temporaire pour stocker le message d'erreur
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error'] = "Votre session a expiré après 24h d'inactivité. Veuillez vous reconnecter.";
        
        // Détermination dynamique du chemin de redirection vers le login
        $redirect_url = 'login.php';
        if (file_exists('Frontend/login.php')) {
            $redirect_url = 'Frontend/login.php';
        } elseif (file_exists('../Frontend/login.php')) {
            $redirect_url = '../Frontend/login.php';
        }
        header('Location: ' . $redirect_url);
        exit();
    }
    // Mettre à jour l'heure de dernière activité
    $_SESSION['last_activity'] = time();
}

// Filtrage récursif global anti-XSS sur toutes les entrées utilisateurs
function sanitizeGlobals(&$array) {
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            sanitizeGlobals($value);
        } else {
            // Supprimer les balises script et nettoyer le HTML
            $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $value);
            $value = strip_tags($value);
        }
    }
}
sanitizeGlobals($_GET);
sanitizeGlobals($_POST);
sanitizeGlobals($_COOKIE);

// Génération du token anti-CSRF s'il est absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
        if (!headers_sent()) {
            header('Location: ../index.php');
        } else {
            echo '<script>window.location.href="../index.php";</script>';
        }
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

/**
 * Génère un matricule CIMIS séquentiel officiel au format CIM-YYYYXXXX
 * Exemple: CIM-20260001, CIM-20260002, ...
 */
if (!function_exists('generateCIMISMatricule')) {
    function generateCIMISMatricule(): string {
        global $pdo;

        $prefix = 'CIM-';
        $year   = date('Y');

        try {
            if ($pdo) $pdo->query("SELECT GET_LOCK('cimis_matricule_lock', 5)")->closeCursor();
        } catch (Exception $e) {}

        try {
            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(CAST(SUBSTRING(matricule, 9) AS UNSIGNED)), 0) + 1 as next_seq
                FROM candidat
                WHERE matricule LIKE ?
            ");
            $stmt->execute([$prefix . $year . '%']);
            $row      = $stmt->fetch();
            $stmt->closeCursor();
            $next_num = (int)($row['next_seq'] ?? 1);
            $sequence = str_pad($next_num, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } finally {
            try {
                if ($pdo) $pdo->query("SELECT RELEASE_LOCK('cimis_matricule_lock')")->closeCursor();
            } catch (Exception $e) {}
        }

        return $prefix . $year . $sequence;
    }
}
?>
