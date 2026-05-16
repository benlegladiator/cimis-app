<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../403.php');
    exit();
}

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['SUPER_ADMIN', 'ADMIN_ENROLEMENT', 'ADMIN_IMPRESSION'])) {
    header('Location: dashboard.php');
    exit();
}

require_once '../backend/config.php';

// Vérifier si l'utilisateur est SUPER_ADMIN pour la désactivation
if ($_SESSION['role'] !== 'SUPER_ADMIN') {
    header('Location: dashboard.php');
    exit();
}

// GESTION DE LA DÉSACTIVATION D'UTILISATEURS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'deactivate_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $motif = $_POST['motif'] ?? '';
        
        try {
            // Vérifier que l'utilisateur n'est pas le super admin actuel
            if ($user_id === $_SESSION['user_id']) {
                $error = "Vous ne pouvez pas désactiver votre propre compte.";
            } else {
                // Récupérer les infos de l'utilisateur
                $stmt = $pdo->prepare("SELECT username, role FROM utilisateur WHERE id = ?");
                $stmt->execute([$user_id]);
                $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($target_user) {
                    // Désactiver l'utilisateur
                    $stmt = $pdo->prepare("UPDATE utilisateur SET actif = 0, date_verrouillage = NOW(), motif_desactivation = ? WHERE id = ?");
                    $stmt->execute([$motif, $user_id]);
                    
                    // Logger l'action
                    $stmt = $pdo->prepare("
                        INSERT INTO activity_log (user_id, action, description, ip_address, date_action) 
                        VALUES (?, 'DESACTIVATION_UTILISATEUR', ?, ?, NOW())
                    ");
                    $description = "Désactivation de l'utilisateur {$target_user['username']} ({$target_user['role']})";
                    if (!empty($motif)) {
                        $description .= " - Motif: $motif";
                    }
                    $stmt->execute([$_SESSION['user_id'], $description, $_SERVER['REMOTE_ADDR']]);
                    
                    $success = "Utilisateur {$target_user['username']} désactivé avec succès.";
                } else {
                    $error = "Utilisateur non trouvé.";
                }
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de la désactivation: " . $e->getMessage();
        }
    }
    
    if ($action === 'activate_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        try {
            // Réactiver l'utilisateur
            $stmt = $pdo->prepare("UPDATE utilisateur SET actif = 1, date_verrouillage = NULL, motif_desactivation = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Récupérer les infos pour le log
            $stmt = $pdo->prepare("SELECT username FROM utilisateur WHERE id = ?");
            $stmt->execute([$user_id]);
            $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($target_user) {
                // Logger l'action
                $stmt = $pdo->prepare("
                    INSERT INTO activity_log (user_id, action, description, ip_address, date_action) 
                    VALUES (?, 'REACTIVATION_UTILISATEUR', ?, ?, NOW())
                ");
                $description = "Réactivation de l'utilisateur {$target_user['username']}";
                $stmt->execute([$_SESSION['user_id'], $description, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Utilisateur {$target_user['username']} réactivé avec succès.";
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de la réactivation: " . $e->getMessage();
        }
    }
}

// Récupérer la liste des utilisateurs pour la section de gestion
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, role, actif, date_creation, date_derniere_connexion, 
               compte_verrouille, date_verrouillage, nombre_echecs
        FROM utilisateur 
        ORDER BY date_creation DESC
    ");
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs";
    $utilisateurs = [];
}

// MOTEUR DE DÉTECTION D'ANOMALIES
class SecurityEngine {
    private $pdo;
    private $threat_level = 'LOW';
    private $alerts = [];
    private $data_masked = false;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->scanForThreats();
    }
    
    private function scanForThreats() {
        $this->detectMultipleFailedLogins();
        $this->detectRapidConnections();
        $this->detectUnusualHours();
        $this->detectMultipleSessions();
        $this->calculateThreatLevel();
    }
    
    private function detectMultipleFailedLogins() {
        try {
            // Plus de 5 échecs dans la dernière heure
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count, ip_adresse 
                FROM login_attempts 
                WHERE statut = 'ECHEC' 
                AND tentative_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY ip_adresse
                HAVING count > 5
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $result) {
                $this->alerts[] = [
                    'type' => 'BRUTE_FORCE',
                    'severity' => 'HIGH',
                    'message' => "Attaque par force brute détectée depuis IP: {$result['ip_adresse']} ({$result['count']} tentatives)",
                    'ip' => $result['ip_adresse'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        } catch(PDOException $e) {
            error_log("Erreur détection brute force: " . $e->getMessage());
        }
    }
    
    private function detectRapidConnections() {
        try {
            // Plus de 10 connexions réussies en 5 minutes depuis même IP
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count, ip_adresse 
                FROM login_attempts 
                WHERE statut = 'SUCCES' 
                AND tentative_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                GROUP BY ip_adresse
                HAVING count > 10
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $result) {
                $this->alerts[] = [
                    'type' => 'RAPID_CONNECTIONS',
                    'severity' => 'MEDIUM',
                    'message' => "Connexions anormalement rapides depuis IP: {$result['ip_adresse']} ({$result['count']} connexions/5min)",
                    'ip' => $result['ip_adresse'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        } catch(PDOException $e) {
            error_log("Erreur détection connexions rapides: " . $e->getMessage());
        }
    }
    
    private function detectUnusualHours() {
        try {
            // Connexions entre 2h et 5h du matin
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM login_attempts 
                WHERE statut = 'SUCCES' 
                AND HOUR(tentative_time) BETWEEN 2 AND 5
                AND DATE(tentative_time) = CURDATE()
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $this->alerts[] = [
                    'type' => 'UNUSUAL_HOURS',
                    'severity' => 'MEDIUM',
                    'message' => "Connexions inhabituelles détectées ({$result['count']} connexions entre 2h-5h)",
                    'ip' => 'multiple',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        } catch(PDOException $e) {
            error_log("Erreur détection heures inhabituelles: " . $e->getMessage());
        }
    }
    
    private function detectMultipleSessions() {
        try {
            // Plus de 3 sessions actives pour le même utilisateur
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count, us.utilisateur_id, u.username 
                FROM user_sessions us 
                LEFT JOIN utilisateur u ON us.utilisateur_id = u.id 
                WHERE us.actif = 1 AND us.date_expiration > NOW()
                GROUP BY us.utilisateur_id, u.username
                HAVING count > 3
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $result) {
                $this->alerts[] = [
                    'type' => 'MULTIPLE_SESSIONS',
                    'severity' => 'MEDIUM',
                    'message' => "Sessions multiples pour utilisateur: {$result['username']} ({$result['count']} sessions)",
                    'ip' => 'multiple',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        } catch(PDOException $e) {
            error_log("Erreur détection sessions multiples: " . $e->getMessage());
        }
    }
    
    private function calculateThreatLevel() {
        $high_count = 0;
        $medium_count = 0;
        
        foreach ($this->alerts as $alert) {
            if ($alert['severity'] === 'HIGH') $high_count++;
            elseif ($alert['severity'] === 'MEDIUM') $medium_count++;
        }
        
        if ($high_count > 0) {
            $this->threat_level = 'CRITICAL';
            $this->data_masked = true;
        } elseif ($medium_count > 2) {
            $this->threat_level = 'HIGH';
            $this->data_masked = true;
        } elseif ($medium_count > 0 || $high_count > 0) {
            $this->threat_level = 'MEDIUM';
        } else {
            $this->threat_level = 'LOW';
        }
    }
    
    public function getThreatLevel() {
        return $this->threat_level;
    }
    
    public function getAlerts() {
        return $this->alerts;
    }
    
    public function isDataMasked() {
        return $this->data_masked;
    }
    
    public function getThreatColor() {
        return match($this->threat_level) {
            'CRITICAL' => '#ff3333',
            'HIGH' => '#ff8800',
            'MEDIUM' => '#ffaa00',
            'LOW' => '#00ff41',
            default => '#00ff41'
        };
    }
    
    public function getThreatIcon() {
        return match($this->threat_level) {
            'CRITICAL' => 'fa-exclamation-triangle',
            'HIGH' => 'fa-shield-alt',
            'MEDIUM' => 'fa-exclamation-circle',
            'LOW' => 'fa-check-circle',
            default => 'fa-check-circle'
        };
    }
}

// Initialiser le moteur de sécurité
$securityEngine = new SecurityEngine($pdo);
$threat_level = $securityEngine->getThreatLevel();
$security_alerts = $securityEngine->getAlerts();
$threat_color = $securityEngine->getThreatColor();
$threat_icon = $securityEngine->getThreatIcon();

// Déterminer si les données doivent être masquées
$data_masked = $securityEngine->isDataMasked() && !$security_unlocked;

// Traitement pour ajouter un admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = cleanInput($_POST['username'] ?? '');
    $password = cleanInput($_POST['password'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $role = cleanInput($_POST['role'] ?? 'ADMIN_ENROLEMENT');
    
    if (!empty($username) && !empty($password)) {
        // Vérifier si le username existe déjà
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM utilisateur WHERE username = ?");
            $stmt->execute([$username]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing['count'] > 0) {
                $error = "Ce nom d'utilisateur existe déjà";
            } else {
                // Hasher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO utilisateur (username, email, password, role, actif, date_creation) VALUES (?, ?, ?, ?, 1, NOW())");
                $stmt->execute([$username, $email, $hashed_password, $role]);
                $success = "Administrateur ajouté avec succès";
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de l'ajout: " . $e->getMessage();
        }
    }
}

// Traitement pour le déblocage de sécurité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_security'])) {
    $unlock_code = cleanInput($_POST['unlock_code'] ?? '');
    $unlock_reason = cleanInput($_POST['unlock_reason'] ?? '');
    
    // Vérifier si l'utilisateur est SUPER_ADMIN
    if ($_SESSION['role'] === 'SUPER_ADMIN') {
        // Code de déblocage sécurisé
        if ($unlock_code === 'AUTHENTIFICATION') {
            // Logger l'action de déblocage
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO logs (utilisateur_id, username, action, module, details, ip_adresse, date_action, statut) 
                    VALUES (?, ?, 'SECURITY_UNLOCK', 'securite_admin', ?, ?, NOW(), 'SUCCES')
                ");
                $stmt->execute([
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['username'] ?? 'SUPER_ADMIN',
                    json_encode(['reason' => $unlock_reason, 'threat_level' => $threat_level])
                ]);
                
                // Créer une session de déblocage temporaire
                $_SESSION['security_unlocked'] = true;
                $_SESSION['unlock_time'] = time();
                $_SESSION['unlock_reason'] = $unlock_reason;
                
                $success = "Système de sécurité débloqué avec succès. Accès temporaire de 15 minutes.";
                
                // Recharger la page pour actualiser l'état
                header("Location: securite_admin.php");
                exit();
                
            } catch(PDOException $e) {
                $error = "Erreur lors du déblocage: " . $e->getMessage();
            }
        } else {
            $error = "Code de déblocage incorrect. Accès refusé.";
            
            // Logger la tentative échouée
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO logs (utilisateur_id, username, action, module, details, ip_adresse, date_action, statut) 
                    VALUES (?, ?, 'SECURITY_UNLOCK_FAILED', 'securite_admin', ?, ?, NOW(), 'ERREUR')
                ");
                $stmt->execute([
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['username'] ?? 'SUPER_ADMIN',
                    json_encode(['attempted_code' => substr($unlock_code, 0, 3) . '***'])
                ]);
            } catch(PDOException $e) {
                error_log("Erreur logging tentative déblocage: " . $e->getMessage());
            }
        }
    } else {
        $error = "Accès refusé. Seuls les SUPER_ADMIN peuvent débloquer le système.";
    }
}

// Vérifier si le déblocage est encore actif (15 minutes max)
$security_unlocked = false;
if (isset($_SESSION['security_unlocked']) && $_SESSION['security_unlocked'] === true) {
    if (isset($_SESSION['unlock_time']) && (time() - $_SESSION['unlock_time']) < 900) { // 15 minutes = 900 secondes
        $security_unlocked = true;
    } else {
        // Expiré, supprimer la session
        unset($_SESSION['security_unlocked']);
        unset($_SESSION['unlock_time']);
        unset($_SESSION['unlock_reason']);
    }
}

// Récupérer les statistiques réelles
$stats = [];
try {
    // Nombre total de cartes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidat");
    $stats['cartes_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Nombre d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateur WHERE actif = 1");
    $stats['utilisateurs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Sessions actives
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_sessions WHERE actif = 1 AND date_expiration > NOW()");
    $stats['sessions_actives'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Tentatives de connexion échouées aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM login_attempts WHERE statut = 'ECHEC' AND DATE(tentative_time) = CURDATE()");
    $stats['tentatives_echec'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Demandes d'impression en attente
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM demandes_impression WHERE statut = 'EN_ATTENTE'");
    $stats['demandes_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Cartes suspendues
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidat WHERE suspendus = 1");
    $stats['cartes_suspendues'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
}

// Récupérer les logs récents
$recent_logs = [];
try {
    $stmt = $pdo->query("SELECT * FROM logs ORDER BY date_action DESC LIMIT 10");
    $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des logs";
}

// Récupérer les tentatives de connexion récentes
$login_attempts = [];
try {
    $stmt = $pdo->query("SELECT * FROM login_attempts ORDER BY tentative_time DESC LIMIT 10");
    $login_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des tentatives";
}

// Récupérer les sessions actives avec détails
$active_sessions = [];
try {
    $stmt = $pdo->query("
        SELECT us.*, u.username, u.role 
        FROM user_sessions us 
        LEFT JOIN utilisateur u ON us.utilisateur_id = u.id 
        WHERE us.actif = 1 AND us.date_expiration > NOW() 
        ORDER BY us.date_derniere_activite DESC 
        LIMIT 10
    ");
    $active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des sessions";
}

// Récupérer la liste des utilisateurs
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, role, actif, date_creation, date_derniere_connexion, dernier_ip, nombre_echecs, compte_verrouille FROM utilisateur ORDER BY date_creation DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs";
}

// Récupérer les demandes d'impression récentes
$demandes_recentes = [];
try {
    $stmt = $pdo->query("
        SELECT di.*, c.nom, c.prenom, c.matricule 
        FROM demandes_impression di 
        LEFT JOIN candidat c ON di.candidat_id = c.id 
        ORDER BY di.date_demande DESC 
        LIMIT 5
    ");
    $demandes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des demandes";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sécurité Administrateur - CIMIS</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === IMPORTATIONS === */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        /* === VARIABLES CSS === */
        :root {
            --primary-gradient: linear-gradient(135deg, #32ff7e 0%, #00d9ff 100%);
            --success-gradient: linear-gradient(135deg, #32ff7e 0%, #00d9ff 100%);
            --warning-gradient: linear-gradient(135deg, #32ff7e 0%, #00d9ff 100%);
            --danger-gradient: linear-gradient(135deg, #00d9ff 0%, #32ff7e 100%);
            --dark-bg: #0f0f23;
            --card-bg: rgba(255, 255, 255, 0.05);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #b8bcc8;
            --accent: #32ff7e;
            --shadow-soft: 0 8px 32px rgba(50, 255, 126, 0.3);
            --shadow-hard: 0 20px 40px rgba(0, 217, 255, 0.4);
        }
        
        /* === RESET ET BASE === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* === LAYOUT PRINCIPAL === */
        .premium-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            grid-template-rows: auto 1fr auto;
            grid-template-areas:
                "sidebar header header"
                "sidebar main main"
                "sidebar footer footer";
            min-height: 100vh;
            gap: 0;
        }
        
        /* === SIDEBAR === */
        .premium-sidebar {
            grid-area: sidebar;
            background: linear-gradient(180deg, rgba(50, 255, 126, 0.1) 0%, rgba(0, 217, 255, 0.05) 100%);
            border-right: 1px solid var(--card-border);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--card-border);
        }
        
        .sidebar-logo i {
            font-size: 2rem;
            color: var(--accent);
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .sidebar-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar-nav {
            flex: 1;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            transition: left 0.3s ease;
            z-index: -1;
        }
        
        .nav-item:hover {
            color: var(--text-primary);
            transform: translateX(5px);
        }
        
        .nav-item:hover::before {
            left: 0;
        }
        
        .nav-item.active {
            color: var(--text-primary);
            background: var(--primary-gradient);
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        /* === HEADER === */
        .premium-header {
            grid-area: header;
            background: linear-gradient(135deg, rgba(50, 255, 126, 0.1) 0%, rgba(0, 217, 255, 0.05) 100%);
            border-bottom: 1px solid var(--card-border);
            padding: 2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }
        
        .header-title-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .header-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-soft);
            animation: iconRotate 20s linear infinite;
        }
        
        @keyframes iconRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .header-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .header-text h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .header-text p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0;
        }
        
        .header-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        /* === MAIN CONTENT === */
        .premium-main {
            grid-area: main;
            padding: 2rem 3rem;
            overflow-y: auto;
        }
        
        /* === FOOTER === */
        .premium-footer {
            grid-area: footer;
            background: linear-gradient(135deg, rgba(50, 255, 126, 0.1) 0%, rgba(0, 217, 255, 0.05) 100%);
            border-top: 1px solid var(--card-border);
            padding: 1.5rem 3rem;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .footer-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .security-container {
            max-width: none;
            margin: 0;
            padding: 0;
        }
        
        .security-header {
            text-align: center;
            margin-bottom: 3rem;
            border-bottom: 2px solid #00ff41;
            padding-bottom: 1rem;
        }
        
        .security-header h1 {
            color: #00ff41;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(0, 255, 65, 0.5);
        }
        
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .security-card {
            background: rgba(0, 255, 65, 0.1);
            border: 1px solid #00ff41;
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .security-card h3 {
            color: #00ff41;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .security-card h3 i {
            font-size: 1.2rem;
        }
        
        .log-entry {
            background: rgba(0, 0, 0, 0.5);
            border-left: 3px solid #00ff41;
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #ccc;
        }
        
        .user-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .user-item {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
        }
        
        .user-item strong {
            color: #00ff41;
        }
        
        .add-admin-form {
            background: rgba(0, 255, 65, 0.05);
            border: 1px solid #00ff41;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            color: #00ff41;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff41;
            border-radius: 5px;
            color: #fff;
            font-family: 'Courier New', monospace;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #00ff66;
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.3);
        }
        
        .submit-btn {
            background: #00ff41;
            color: #000;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #00ff66;
            transform: translateY(-2px);
        }
        
        .alert-success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(0, 255, 65, 0.1);
            border: 1px solid #00ff41;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #00ff41;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #ccc;
            font-size: 0.9rem;
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0, 255, 65, 0.1);
            border: 2px solid #00ff41;
            color: #00ff41;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(0, 255, 65, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="premium-layout">
        <!-- SIDEBAR -->
        <aside class="premium-sidebar">
            <div class="sidebar-logo">
                <i class="fa-solid fa-shield-alt"></i>
                <div class="sidebar-title">SÉCURITÉ</div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fa-solid fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="impression.php" class="nav-item">
                    <i class="fa-solid fa-print"></i>
                    <span>Impression</span>
                </a>
                <a href="corbeille_admin.php" class="nav-item">
                    <i class="fa-solid fa-trash"></i>
                    <span>Corbeille</span>
                </a>
                <a href="securite_admin.php" class="nav-item active">
                    <i class="fa-solid fa-shield-alt"></i>
                    <span>Sécurité</span>
                </a>
                <a href="api_siadoc.php" class="nav-item">
                    <i class="fa-solid fa-code"></i>
                    <span>API SIADOC</span>
                </a>
                <a href="siadoc_integration.php" class="nav-item">
                    <i class="fa-solid fa-exchange-alt"></i>
                    <span>Intégration SIADOC</span>
                </a>
            </nav>
        </aside>
        
        <!-- HEADER -->
        <header class="premium-header">
            <div class="header-title-section">
                <div class="header-icon">
                    <i class="fa-solid fa-shield-alt"></i>
                </div>
                <div class="header-text">
                    <h1>Sécurité Administrateur</h1>
                    <p>Centre de contrôle de sécurité CIMIS</p>
                </div>
            </div>
            <div class="header-user">
                <div class="user-avatar">
                    <?php echo substr($_SESSION['username'] ?? 'U', 0, 1); ?>
                </div>
                <a href="dashboard.php" class="btn-premium" style="padding: 0.8rem 1.5rem; font-size: 0.95rem; background: linear-gradient(135deg, rgba(50, 255, 126, 0.8) 0%, rgba(0, 217, 255, 0.8) 100%); border: 2px solid transparent; border-image: linear-gradient(45deg, #32ff7e, #00d9ff, #32ff7e) 1; box-shadow: 0 0 15px rgba(50, 255, 126, 0.3);">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </a>
                <a href="../logout.php" class="btn-premium" style="padding: 0.8rem 1.5rem; font-size: 0.95rem; margin-left: 0.5rem; background: linear-gradient(135deg, rgba(0, 217, 255, 0.8) 0%, rgba(50, 255, 126, 0.8) 100%); border: 2px solid transparent; border-image: linear-gradient(45deg, #00d9ff, #32ff7e, #00d9ff) 1; box-shadow: 0 0 15px rgba(0, 217, 255, 0.3);">
                    <i class="fa-solid fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </header>
        
        <!-- MAIN CONTENT -->
        <main class="premium-main">
            <div class="security-container">
        <!-- BANNIÈRE D'ALERTE DE MENACE -->
        <?php if ($threat_level !== 'LOW'): ?>
        <div class="threat-banner" style="background: <?php echo $threat_color; ?>20; border: 2px solid <?php echo $threat_color; ?>; border-radius: 10px; padding: 1rem; margin-bottom: 2rem; text-align: center;">
            <h2 style="color: <?php echo $threat_color; ?>; margin: 0; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i class="fa-solid <?php echo $threat_icon; ?>"></i>
                NIVEAU DE MENACE: <?php echo $threat_level; ?> / THREAT LEVEL: <?php echo $threat_level; ?>
                <?php if ($data_masked): ?>
                    <span style="background: rgba(255, 0, 0, 0.2); color: #ff3333; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem;">
                        <i class="fa-solid fa-eye-slash"></i> DONNÉES MASQUÉES / DATA MASKED
                    </span>
                <?php endif; ?>
                <?php if ($security_unlocked): ?>
                    <span style="background: rgba(0, 255, 65, 0.2); color: #00ff41; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem;">
                        <i class="fa-solid fa-unlock"></i> DÉBLOQUÉ / UNLOCKED
                    </span>
                <?php endif; ?>
            </h2>
            <?php if ($security_unlocked): ?>
                <p style="color: #00ff41; margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                    Accès temporaire activé par <?php echo htmlspecialchars($_SESSION['username']); ?> / Temporary access activated by <?php echo htmlspecialchars($_SESSION['username']); ?>
                    (<?php echo ceil((900 - (time() - $_SESSION['unlock_time'])) / 60); ?> minutes restantes / <?php echo ceil((900 - (time() - $_SESSION['unlock_time'])) / 60); ?> minutes remaining)
                    <?php if ($_SESSION['unlock_reason']): ?>
                        - Raison: <?php echo htmlspecialchars($_SESSION['unlock_reason']); ?> / Reason: <?php echo htmlspecialchars($_SESSION['unlock_reason']); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- INTERFACE DE DÉBLOCAGE (SUPER_ADMIN SEULEMENT) -->
        <?php if ($data_masked && $_SESSION['role'] === 'SUPER_ADMIN'): ?>
        <div class="unlock-panel" style="background: rgba(255, 0, 0, 0.1); border: 2px solid #ff3333; border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem;">
            <h3 style="color: #ff3333; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-unlock-alt"></i>
                DÉBLOCAGE DE SÉCURITÉ REQUIS / SECURITY UNLOCK REQUIRED
            </h3>
            <p style="color: #ccc; margin-bottom: 1rem;">
                Le système a détecté des menaces et a masqué les données sensibles. / The system has detected threats and masked sensitive data.
                En tant que SUPER_ADMIN, vous pouvez débloquer temporairement l'accès pour investigation. / As SUPER_ADMIN, you can temporarily unlock access for investigation.
            </p>
            <form method="POST" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; color: #ff3333; margin-bottom: 0.5rem; font-weight: bold;">Code de déblocage / Unlock Code:</label>
                    <input type="password" name="unlock_code" required placeholder="Code sécurisé... / Secure code..." style="width: 100%; padding: 0.8rem; background: rgba(0, 0, 0, 0.5); border: 1px solid #ff3333; border-radius: 5px; color: #fff; font-family: 'Courier New', monospace;">
                </div>
                <div style="flex: 2; min-width: 300px;">
                    <label style="display: block; color: #ff3333; margin-bottom: 0.5rem; font-weight: bold;">Raison du déblocage / Unlock Reason:</label>
                    <input type="text" name="unlock_reason" required placeholder="Ex: Investigation manuelle, vérification utilisateur... / Ex: Manual investigation, user verification..." style="width: 100%; padding: 0.8rem; background: rgba(0, 0, 0, 0.5); border: 1px solid #ff3333; border-radius: 5px; color: #fff; font-family: 'Courier New', monospace;">
                </div>
                <button type="submit" name="unlock_security" style="background: #ff3333; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; font-weight: bold; cursor: pointer; transition: all 0.3s ease;">
                    <i class="fa-solid fa-unlock"></i> DÉBLOQUER / UNLOCK
                </button>
            </form>
            <p style="color: #ff6666; font-size: 0.8rem; margin-top: 1rem;">
                <i class="fa-solid fa-info-circle"></i> 
                Le déblocage sera valide pendant 15 minutes maximum. Toutes les actions seront enregistrées. / Unlock will be valid for maximum 15 minutes. All actions will be logged.
            </p>
        </div>
        <?php elseif ($data_masked && $_SESSION['role'] !== 'SUPER_ADMIN'): ?>
        <div class="unlock-panel" style="background: rgba(255, 0, 0, 0.1); border: 2px solid #ff3333; border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem;">
            <h3 style="color: #ff3333; margin-bottom: 1rem;">
                <i class="fa-solid fa-lock"></i> ACCÈS RESTREINT / ACCESS RESTRICTED
            </h3>
            <p style="color: #ccc;">
                Le système a détecté des menaces et a masqué les données sensibles. / The system has detected threats and masked sensitive data.
                Seul un SUPER_ADMIN peut débloquer l'accès pour investigation. / Only a SUPER_ADMIN can unlock access for investigation.
            </p>
        </div>
        <?php endif; ?>

        <!-- ALERTES DE SÉCURITÉ -->
        <?php if (!empty($security_alerts)): ?>
        <div class="security-alerts" style="margin-bottom: 2rem;">
            <h3 style="color: <?php echo $threat_color; ?>; margin-bottom: 1rem;">
                <i class="fa-solid fa-exclamation-triangle"></i> Alertes de Sécurité Actives / Active Security Alerts
            </h3>
            <?php foreach ($security_alerts as $alert): ?>
            <div class="alert-item" style="background: rgba(255, 0, 0, 0.1); border-left: 4px solid <?php echo $alert['severity'] === 'HIGH' ? '#ff3333' : '#ffaa00'; ?>; padding: 1rem; margin-bottom: 0.5rem; border-radius: 5px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem;">
                    <span style="background: <?php echo $alert['severity'] === 'HIGH' ? '#ff3333' : '#ffaa00'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.7rem; font-weight: bold;">
                        <?php echo $alert['severity']; ?>
                    </span>
                    <span style="color: #ccc; font-size: 0.8rem;">
                        <?php echo $alert['timestamp']; ?>
                    </span>
                </div>
                <div style="color: #fff; font-weight: bold;">
                    <?php echo htmlspecialchars($alert['message']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="security-header">
            <h1><i class="fa-solid fa-shield-halved"></i> SECURITY CONTROL CENTER / CENTRE DE CONTRÔLE DE SÉCURITÉ</h1>
            <p>Tour de Contrôle - Surveillance Système CIMIS / Control Tour - CIMIS System Monitoring</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['cartes_total'] ?? 0; ?></div>
                <div class="stat-label">Nombre Total des Cartes / Total Number of Cards</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['utilisateurs'] ?? 0; ?></div>
                <div class="stat-label">Utilisateurs Actifs / Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['sessions_actives'] ?? 0; ?></div>
                <div class="stat-label">Sessions Actives / Active Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['tentatives_echec'] ?? 0; ?></div>
                <div class="stat-label">Tentatives Échouées / Failed Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['demandes_attente'] ?? 0; ?></div>
                <div class="stat-label">Demandes en Attente / Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['cartes_suspendues'] ?? 0; ?></div>
                <div class="stat-label">Cartes Suspendues / Suspended Cards</div>
            </div>
        </div>

        <!-- Grille de surveillance -->
        <div class="security-grid">
            <!-- Logs système -->
            <div class="security-card" <?php echo $data_masked ? 'style="filter: blur(5px); pointer-events: none;"' : ''; ?>>
                <h3><i class="fa-solid fa-list"></i> Logs Système / System Logs</h3>
                <div class="log-container">
                    <?php if (!empty($recent_logs)): ?>
                        <?php foreach ($recent_logs as $log): ?>
                            <div class="log-entry">
                                <strong>[<?php echo htmlspecialchars($log['statut']); ?>]</strong>
                                <?php echo $data_masked ? substr(htmlspecialchars($log['username'] ?? 'System'), 0, 3) . '***' : htmlspecialchars($log['username'] ?? 'System'); ?> - 
                                <?php echo htmlspecialchars($log['action']); ?>
                                <br><small><?php echo htmlspecialchars($log['date_action']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="log-entry">Aucun log récent / No recent logs</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gestion des Utilisateurs (SUPER ADMIN) -->
            <?php if ($_SESSION['role'] === 'SUPER_ADMIN'): ?>
            <div class="security-card">
                <h3><i class="fa-solid fa-user-shield"></i> Gestion des Utilisateurs / User Management</h3>
                
                <?php if (isset($success)): ?>
                    <div class="alert-success" style="background: rgba(0, 255, 65, 0.2); border: 1px solid #00ff41; color: #00ff41; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert-error" style="background: rgba(255, 0, 0, 0.2); border: 1px solid #ff0000; color: #ff0000; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="user-list">
                    <?php if (!empty($utilisateurs)): ?>
                        <?php foreach ($utilisateurs as $user): ?>
                            <div class="user-item" style="border-left: 3px solid <?php echo $user['actif'] ? '#00ff41' : '#dc3545'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div style="flex: 1;">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <span style="color: #888; font-size: 0.9em; margin-left: 10px;">(<?php echo htmlspecialchars($user['role']); ?>)</span>
                                        <?php if ($user['email']): ?>
                                            <br><small style="color: #ccc;"><?php echo htmlspecialchars($user['email']); ?></small>
                                        <?php endif; ?>
                                        <br><small style="color: #888;">
                                            Créé: <?php echo date('d/m/Y H:i', strtotime($user['date_creation'])); ?>
                                            <?php if ($user['date_derniere_connexion']): ?>
                                                | Dernière connexion: <?php echo date('d/m/Y H:i', strtotime($user['date_derniere_connexion'])); ?>
                                            <?php endif; ?>
                                        </small>
                                        <?php if ($user['compte_verrouille']): ?>
                                            <br><small style="color: #ff6b6b;">🔒 Compte verrouillé</small>
                                        <?php endif; ?>
                                        <?php if ($user['nombre_echecs'] > 0): ?>
                                            <br><small style="color: #ffa500;">⚠️ <?php echo $user['nombre_echecs']; ?> échecs</small>
                                        <?php endif; ?>
                                        <?php if (!$user['actif'] && $user['date_verrouillage']): ?>
                                            <br><small style="color: #dc3545;">❌ Désactivé le: <?php echo date('d/m/Y H:i', strtotime($user['date_verrouillage'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                                        <?php if ($user['actif']): ?>
                                            <span class="status-badge" style="background: #00ff41; color: #000; padding: 3px 8px; border-radius: 3px; font-size: 0.8em; font-weight: bold;">ACTIF</span>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <button class="btn-deactivate" onclick="showDeactivateModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                        style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 0.8em;">
                                                    <i class="fas fa-ban"></i> Désactiver
                                                </button>
                                            <?php else: ?>
                                                <small style="color: #888; font-size: 0.7em;">Votre compte</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #6c757d; color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.8em; font-weight: bold;">INACTIF</span>
                                            <button class="btn-activate" onclick="activateUser(<?php echo $user['id']; ?>)" 
                                                    style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 0.8em;">
                                                <i class="fas fa-check"></i> Réactiver
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="log-entry">Aucun utilisateur trouvé</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Utilisateurs (lecture seule pour autres admins) -->
            <div class="security-card" <?php echo $data_masked ? 'style="filter: blur(5px); pointer-events: none;"' : ''; ?>>
                <h3><i class="fa-solid fa-users"></i> Utilisateurs Système</h3>
                <div class="user-list">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <div class="user-item">
                                <strong><?php echo $data_masked ? substr(htmlspecialchars($user['username']), 0, 3) . '***' : htmlspecialchars($user['username']); ?></strong>
                                <br><small>Rôle: <?php echo htmlspecialchars($user['role']); ?>
                                <?php if ($user['compte_verrouille']): ?> | 🔒 VERROUILLÉ<?php endif; ?>
                                <?php if (!$user['actif']): ?> | ❌ INACTIF<?php endif; ?>
                                </small>
                                <br><small>Dernière connexion: <?php echo $user['date_derniere_connexion'] ? ($data_masked ? '***' : htmlspecialchars($user['date_derniere_connexion'])) : 'Jamais'; ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="log-entry">Aucun utilisateur trouvé</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sessions actives -->
            <div class="security-card" <?php echo $data_masked ? 'style="filter: blur(5px); pointer-events: none;"' : ''; ?>>
                <h3><i class="fa-solid fa-window-restore"></i> Sessions Actives</h3>
                <div class="log-container">
                    <?php if (!empty($active_sessions)): ?>
                        <?php foreach ($active_sessions as $session): ?>
                            <div class="log-entry">
                                <strong><?php echo $data_masked ? substr(htmlspecialchars($session['username'] ?? 'Session anonyme'), 0, 3) . '***' : htmlspecialchars($session['username'] ?? 'Session anonyme'); ?></strong>
                                <br><small>IP: <?php echo $data_masked ? '***.***.***.***' : (htmlspecialchars($session['ip_adresse'] ?? 'Inconnue')); ?></small>
                                <br><small>Dernière activité: <?php echo htmlspecialchars($session['date_derniere_activite']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="log-entry">Aucune session active</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tentatives de connexion -->
            <div class="security-card">
                <h3><i class="fa-solid fa-shield-alt"></i> Tentatives de Connexion</h3>
                <div class="log-container">
                    <?php if (!empty($login_attempts)): ?>
                        <?php foreach ($login_attempts as $attempt): ?>
                            <div class="log-entry" style="border-left-color: <?php echo $attempt['statut'] === 'ECHEC' ? '#ff3333' : '#00ff41'; ?>;">
                                <strong>[<?php echo htmlspecialchars($attempt['statut']); ?>]</strong>
                                <?php echo $data_masked ? substr(htmlspecialchars($attempt['username']), 0, 3) . '***' : htmlspecialchars($attempt['username']); ?>
                                <br><small>IP: <?php echo $data_masked ? '***.***.***.***' : (htmlspecialchars($attempt['ip_adresse'] ?? 'Inconnue')); ?></small>
                                <br><small><?php echo htmlspecialchars($attempt['tentative_time']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="log-entry">Aucune tentative récente</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Demandes d'impression récentes -->
        <div class="security-card" style="grid-column: 1 / -1;" <?php echo $data_masked ? 'style="filter: blur(5px); pointer-events: none;"' : ''; ?>>
            <h3><i class="fa-solid fa-print"></i> Demandes d'Impression Récentes</h3>
            <div class="log-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <?php if (!empty($demandes_recentes)): ?>
                    <?php foreach ($demandes_recentes as $demande): ?>
                        <div class="log-entry">
                            <strong><?php echo $data_masked ? substr(htmlspecialchars($demande['matricule']), 0, 3) . '***' : htmlspecialchars($demande['matricule']); ?></strong>
                            <?php echo $data_masked ? substr(htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']), 0, 5) . '***' : htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']); ?>
                            <br><small>Motif: <?php echo htmlspecialchars($demande['motif_demande']); ?></small>
                            <br><small>Statut: <span style="color: <?php 
                                echo match($demande['statut']) {
                                    'EN_ATTENTE' => '#ffaa00',
                                    'APPROUVEE' => '#00ff00',
                                    'REFUSEE' => '#ff3333',
                                    'TRAITEE' => '#00aaff',
                                    default => '#ccc'
                                }; 
                            ?>"><?php echo htmlspecialchars($demande['statut']); ?></span></small>
                            <br><small><?php echo htmlspecialchars($demande['date_demande']); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="log-entry">Aucune demande récente</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- API GESMIL -->
        <div class="security-card" style="grid-column: 1 / -1;">
            <h3><i class="fa-solid fa-database"></i> API GESMIL2.0</h3>
            <p style="margin-bottom: 15px; color: #666;">
                Synchronisation avec la base de données GESMIL2.0 pour récupérer les informations militaires 
                et générer automatiquement les matricules CIMIS.
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="../api.php" class="btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-sync"></i>
                    Accéder à l'API GESMIL
                </a>
                <div style="background: #e3f2fd; padding: 12px 16px; border-radius: 5px; border-left: 4px solid #2196f3;">
                    <small style="color: #1976d2; font-weight: bold;">
                        <i class="fa-solid fa-info-circle"></i> 
                        Permet de récupérer les 52 attributs militaires et générer les matricules CIMIS
                    </small>
                </div>
            </div>
        </div>

        <!-- API SIADOC -->
        <div class="security-card" style="grid-column: 1 / -1;">
            <h3><i class="fa-solid fa-exchange-alt"></i> API SIADOC</h3>
            <p style="margin-bottom: 15px; color: #666;">
                Échange d'informations avec le système SIADOC pour synchroniser les données militaires 
                et partager les QR codes, empreintes et numéros CIMIS.
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="api_siadoc.php" class="btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-exchange-alt"></i>
                    Accéder à l'API SIADOC
                </a>
                <div style="background: #d4edda; padding: 12px 16px; border-radius: 5px; border-left: 4px solid #28a745;">
                    <small style="color: #155724; font-weight: bold;">
                        <i class="fa-solid fa-info-circle"></i> 
                        Permet d'échanger les données militaires et de partager les QR codes, empreintes et numéros CIMIS
                    </small>
                </div>
            </div>
        </div>

        <!-- Formulaire d'ajout d'admin -->
        <div class="add-admin-form">
            <h3><i class="fa-solid fa-user-plus"></i> Ajouter un Administrateur</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Rôle:</label>
                    <select id="role" name="role">
                        <option value="ADMIN_ENROLEMENT">Admin Enrôlement</option>
                        <option value="ADMIN_IMPRESSION">Admin Impression</option>
                        <option value="SUPER_ADMIN">Super Administrateur</option>
                    </select>
                </div>
                <button type="submit" name="add_admin" class="submit-btn">
                    <i class="fa-solid fa-plus"></i> Ajouter l'Administrateur
                </button>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="premium-footer">
        <div class="footer-text">
            2026 CIMIS - Système de Sécurité<br>
            Centre de Contrôle Administratif
        </div>
    </footer>

    <script>
        // Animation particules (réutiliser depuis dashboard)
        const canvas = document.getElementById('particles-canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        const particles = [];
        const particleCount = 50;
        
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 1;
                this.speedX = Math.random() * 0.5 - 0.25;
                this.speedY = Math.random() * 0.5 - 0.25;
                this.opacity = Math.random() * 0.5 + 0.2;
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                
                if (this.x > canvas.width) this.x = 0;
                if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                if (this.y < 0) this.y = canvas.height;
            }
            
            draw() {
                ctx.fillStyle = `rgba(0, 255, 65, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }
        
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
        
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
            
            requestAnimationFrame(animate);
        }
        
        animate();
        
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    
    // Modal de désactivation
    function showDeactivateModal(userId, username) {
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        `;
        
        modal.innerHTML = `
            <div style="background: rgba(0, 0, 0, 0.95); border: 2px solid #00ff41; border-radius: 15px; padding: 2rem; max-width: 500px; color: #fff;">
                <h3 style="color: #00ff41; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i> Confirmation de désactivation
                </h3>
                <p style="margin-bottom: 1rem;">
                    Êtes-vous sûr de vouloir désactiver l'utilisateur <strong>${username}</strong> ?
                </p>
                <p style="color: #ffa500; margin-bottom: 1rem;">
                    <i class="fas fa-info-circle"></i> L'utilisateur ne pourra plus se connecter au système.
                </p>
                <form method="POST" style="margin-bottom: 1rem;">
                    <input type="hidden" name="action" value="deactivate_user">
                    <input type="hidden" name="user_id" value="${userId}">
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: #00ff41;">Motif de la désactivation (optionnel):</label>
                        <textarea name="motif" rows="3" style="width: 100%; background: rgba(0, 255, 65, 0.1); border: 1px solid #00ff41; color: #fff; padding: 0.5rem; border-radius: 5px; resize: vertical;" placeholder="Ex: Abus constaté, violation des règles, etc."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="this.closest('div[style*=fixed]').remove()" 
                                style="background: #6c757d; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" 
                                style="background: #dc3545; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-ban"></i> Désactiver
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Fermer le modal en cliquant à l'extérieur
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    // Réactiver un utilisateur
    function activateUser(userId) {
        if (confirm('Êtes-vous sûr de vouloir réactiver cet utilisateur ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="activate_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Animation pour les boutons
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.btn-deactivate, .btn-activate');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.3)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    });
    
    </script>

    <!-- Footer avec bouton corbeille admin -->
    <footer class="security-footer" style="
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 20px;
        margin-top: 40px;
        border-radius: 0 0 10px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    ">
        <div class="footer-left">
            <span><i class="fa-solid fa-shield-alt"></i> PANNEAU ADMINISTRATION CIMIS</span>
            <span><i class="fa-solid fa-lock"></i> Connexion sécurisée</span>
        </div>
        
        <div class="footer-center">
            <!-- Bouton Corbeille Admin -->
            <a href="../corbeille_admin.php" class="trash-btn-admin" title="Corbeille Globale / Global Trash">
                <i class="fa-solid fa-trash-can"></i>
                <span id="admin-trash-count" style="display: none;" class="trash-count">0</span>
            </a>
        </div>
        
        <div class="footer-right">
            <span id="footer-clock" class="text-mono">00:00:00</span>
            <span><i class="fa-solid fa-server"></i> Serveur: ACTIF</span>
        </div>
    </footer>

    <style>
    .footer-center {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .trash-btn-admin {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #ff6b6b, #dc3545);
        color: white;
        border: 3px solid #dc3545;
        border-radius: 50%;
        text-decoration: none;
        font-size: 22px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }

    .trash-btn-admin:hover {
        background: linear-gradient(135deg, #ff5252, #c82333);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.5);
        color: white;
        text-decoration: none;
    }

    .trash-count {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #ff3838;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 50%;
        border: 3px solid white;
        min-width: 22px;
        text-align: center;
        line-height: 1;
    }

    .security-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 40px;
        background: rgba(30, 60, 114, 0.95);
        backdrop-filter: blur(10px);
        border-top: 3px solid rgba(212, 175, 55, 0.4);
    }

    .security-footer span {
        margin: 0 15px;
        font-size: 15px;
    }
    </style>

    <script>
    // Fonction pour charger le compteur global de la corbeille
    function loadAdminTrashCount() {
        fetch('get_admin_trash_count.php')
            .then(response => response.json())
            .then(data => {
                const trashCount = document.getElementById('admin-trash-count');
                if (data.count > 0) {
                    trashCount.textContent = data.count;
                    trashCount.style.display = 'block';
                } else {
                    trashCount.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement du compteur de corbeille admin:', error);
            });
    }

    // Clock pour le footer
    setInterval(() => {
        const now = new Date();
        const footerClock = document.getElementById('footer-clock');
        if (footerClock) {
            footerClock.innerText = now.toLocaleTimeString('fr-FR');
        }
    }, 1000);

    // Charger le compteur au chargement de la page
    document.addEventListener('DOMContentLoaded', loadAdminTrashCount);
    </script>
</body>
</html>
