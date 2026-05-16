<?php
session_start();

// Vérifier si l'utilisateur est authentifié via la porte de sécurité
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Rediriger vers la page de dissuasion 403
    header('Location: 403.php');
    exit();
}

// Initialiser le timestamp si inexistant (premier accès depuis 403.php)
if (!isset($_SESSION['last_access'])) {
    $_SESSION['last_access'] = time();
}

// Vérifier si c'est une actualisation (plus de 30 secondes depuis le dernier accès)
if ((time() - $_SESSION['last_access']) > 30) {
    // Si plus de 30 secondes, considérer comme session expirée
    session_destroy();
    session_unset();
    header('Location: 403.php');
    exit();
}

// Mettre à jour le timestamp d'accès
$_SESSION['last_access'] = time();

require_once 'backend/config.php';

// Gérer la déconnexion si le paramètre logout=1 est présent
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Détruire complètement la session
    session_destroy();
    session_unset();
    
    // Rediriger vers la page de dissuasion 403
    header('Location: 403.php');
    exit;
}

// Initialiser le compteur de tentatives si inexistant
if (!isset($_SESSION['failed_attempts'])) {
    $_SESSION['failed_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
    $_SESSION['lockout_time'] = 0;
    $_SESSION['countdown_start'] = 0;
}

// Créer le répertoire de logs s'il n'existe pas
$log_dir = 'logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Traitement du formulaire de connexion par code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $access_code = cleanInput($_POST['access_code'] ?? '');
    
    if (!empty($access_code)) {
        if (strlen($access_code) === 12) {
            if ($access_code === 'CIMIS2.02026') {
                // ✅ Code correct - réinitialiser les tentatives
                $_SESSION['failed_attempts'] = 0;
                $_SESSION['last_attempt_time'] = 0;
                $_SESSION['countdown_start'] = 0;
                
                // Stocker le code en session et rediriger vers login.php
                $_SESSION['access_code'] = $access_code;
                header('Location: Frontend/login.php');
                exit;
            } else {
                // ❌ Code incorrect de 12 caractères - incrémenter les tentatives
                $_SESSION['failed_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                
                if ($_SESSION['failed_attempts'] === 1) {
                    // 🟡 Première tentative - message d'avertissement orange
                    $error = '<strong>🟡 COMPORTEMENT ANORMAL DÉTECTÉ ⚠️</strong><br>Accès non autorisé. Première tentative d\'intrusion enregistrée.<br>Adresse IP: ' . $_SERVER['REMOTE_ADDR'] . '<br>Code saisi: ' . $access_code . '<br>Action: Surveillance activée.';
                    
                    // Log de sécurité
                    $log_entry = "[" . date('Y-m-d H:i:s') . "] NIVEAU_1 - Première tentative\n";
                    $log_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
                    $log_entry .= "Code: " . $access_code . "\n";
                    $log_entry .= "Action: Surveillance activée\n";
                    file_put_contents($log_dir . '/security_alerts.log', $log_entry, FILE_APPEND | LOCK_EX);
                    
                } elseif ($_SESSION['failed_attempts'] === 2) {
                    // 🔴 Deuxième tentative - message d'alerte rouge + décompte 30s
                    $_SESSION['countdown_start'] = time();
                    $error = '<strong>🔴 ALERTE SÉCURITÉ 🔴</strong><br>Log complet, mesures renforcées.<br>⏱️ 30 secondes avant blocage système.';
                    
                    // Log de sécurité
                    $log_entry = "[" . date('Y-m-d H:i:s') . "] NIVEAU_2 - Deuxième tentative\n";
                    $log_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
                    $log_entry .= "Code: " . $access_code . "\n";
                    $log_entry .= "Action: Décompte 30s démarré\n";
                    file_put_contents($log_dir . '/security_alerts.log', $log_entry, FILE_APPEND | LOCK_EX);
                    
                }
            }
        } else {
            // Code avec mauvaise longueur - PAS d'incrémentation des tentatives
            $error = 'Le code doit contenir exactement 12 caractères';
        }
    } else {
        // Champ vide - PAS d'incrémentation des tentatives
        $error = 'Veuillez renseigner le code d\'accès';
    }
}

// Vérifier si le décompte est terminé (niveau 3)
if ($_SESSION['failed_attempts'] === 2 && $_SESSION['countdown_start'] > 0) {
    $time_since_countdown = time() - $_SESSION['countdown_start'];
    if ($time_since_countdown >= 30) {
        // 🔒 Niveau 3 - Blocage système
        $_SESSION['failed_attempts'] = 3;
        $_SESSION['lockout_time'] = time();
        
        // Log de blocage
        $log_entry = "[" . date('Y-m-d H:i:s') . "] NIVEAU_3 - Blocage système\n";
        $log_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $log_entry .= "Action: Système bloqué après 30s\n";
        file_put_contents($log_dir . '/security_alerts.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Si déjà au niveau 3 (système bloqué), ne pas traiter d'autres soumissions
if ($_SESSION['failed_attempts'] >= 3) {
    // Rediriger pour éviter le traitement du formulaire normal
    header('Location: index.php');
    exit;
}

// Vérifier si c'est un déblocage secret
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_code'])) {
    $reset_code = cleanInput($_POST['reset_code'] ?? '');
    if ($reset_code === 'RESETRESET') {
        // 🔐 Déblocage secret - réinitialisation complète
        $_SESSION['failed_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
        $_SESSION['lockout_time'] = 0;
        $_SESSION['countdown_start'] = 0;
        
        // Log de déblocage
        $log_entry = "[" . date('Y-m-d H:i:s') . "] SYSTEM_RESET - Déblocage manuel\n";
        $log_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $log_entry .= "Action: Déblocage via code secret RESETRESET\n";
        file_put_contents($log_dir . '/security_resets.log', $log_entry, FILE_APPEND | LOCK_EX);
        
        // Rediriger pour éviter la double soumission
        header('Location: index.php');
        exit;
    }
}

// Redirection si déjà connecté
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: Frontend/dashboard.php');
    exit;
}

// Calculer le temps restant pour le décompte
$countdown_remaining = 0;
if ($_SESSION['failed_attempts'] === 2 && $_SESSION['countdown_start'] > 0) {
    $countdown_remaining = 30 - (time() - $_SESSION['countdown_start']);
    if ($countdown_remaining < 0) $countdown_remaining = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail d'Accès - CIMIS</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: #000;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow: hidden;
            position: relative;
        }
        
        .portal-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 2;
        }
        
        .space-field {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }
        
        .atom {
            position: absolute;
            width: 6px;
            height: 6px;
            background: radial-gradient(circle, rgba(74, 222, 128, 0.8) 0%, rgba(74, 222, 128, 0.4) 70%, transparent 100%);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(74, 222, 128, 0.6);
            pointer-events: none;
        }
        
        .molecule {
            position: absolute;
            pointer-events: none;
        }
        
        .molecule-atom {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(34, 197, 94, 0.8);
            border-radius: 50%;
            box-shadow: 0 0 5px rgba(34, 197, 94, 0.4);
        }
        
        .molecule-bond {
            position: absolute;
            width: 1px;
            height: 1px;
            background: linear-gradient(90deg, rgba(74, 222, 128, 0.3), rgba(74, 222, 128, 0.1));
            transform-origin: center;
        }
        
        .electron {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 3px rgba(74, 222, 128, 0.8);
            pointer-events: none;
        }
        
        .photon {
            position: absolute;
            width: 1px;
            height: 1px;
            background: rgba(255, 255, 100, 0.95);
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(255, 255, 100, 0.6);
            pointer-events: none;
        }
        
        .quark {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(147, 51, 234, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(147, 51, 234, 0.5);
            pointer-events: none;
        }
        
        .quantum-wave {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 50%;
            pointer-events: none;
            opacity: 0.3;
        }
        
        @keyframes photonPulse {
            0%, 100% { 
                transform: scale(1) rotate(0deg);
                opacity: 0.3;
            }
            50% { 
                transform: scale(2) rotate(180deg);
                opacity: 0.8;
            }
        }
        
        @keyframes quarkSpin {
            0% { 
                transform: rotate(0deg) scale(1);
                opacity: 0.4;
            }
            50% { 
                transform: rotate(180deg) scale(1.5);
                opacity: 0.8;
            }
            100% { 
                transform: rotate(360deg) scale(1);
                opacity: 0.4;
            }
        }
        
        @keyframes waveRipple {
            0% { 
                transform: scale(0.8);
                opacity: 0;
            }
            50% { 
                transform: scale(1.2);
                opacity: 0.6;
            }
            100% { 
                transform: scale(0.8);
                opacity: 0;
            }
        }
        
        .quantum-field {
            position: absolute;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 30% 40%, rgba(74, 222, 128, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, rgba(34, 197, 94, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.02) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            gap: 4rem;
            margin-bottom: 6rem;
            z-index: 3;
        }
        
        .logo-container {
            position: relative;
        }
        
        .logo-container.left {
            position: absolute;
            left: 10%;
            top: 20px;
        }
        
        .logo-container.right {
            position: absolute;
            left: 90%;
            top: 20px;
        }
        
        .logo-img {
            width: 150px;
            height: 150px;
            opacity: 0.9;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 0 40px rgba(74, 222, 128, 0.3));
        }
        
        .logo-img:hover {
            opacity: 1;
            transform: scale(1.1);
            filter: drop-shadow(0 0 80px rgba(74, 222, 128, 0.6));
        }
        
        .banner {
            text-align: center;
        }
        
        .banner-title {
            font-size: 5rem;
            font-weight: 200;
            color: #fff;
            margin: 0 0 1rem 0;
            letter-spacing: 0.08em;
            opacity: 0.9;
            transition: all 0.6s ease;
            text-shadow: 0 0 30px rgba(74, 222, 128, 0.4);
        }
        
        .banner-subtitle {
            font-size: 2rem;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
            letter-spacing: 0.02em;
        }
        
        .mindef-reference {
            font-size: 1.7rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 1.5rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        
        .main-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .main-title h1 {
            font-size: 8rem;
            font-weight: 200;
            color: #fff;
            margin: 0;
            letter-spacing: 0.15em;
            opacity: 0.9;
            text-transform: uppercase;
            text-shadow: 0 0 40px rgba(74, 222, 128, 0.3);
            animation: titleGlow 3s ease-in-out infinite, titleAppear 1.5s ease-out forwards;
        }
        
        @keyframes titleGlow {
            0%, 100% {
                text-shadow: 0 0 5px rgba(0, 255, 0, 0.3), 0 0 15px rgba(0, 255, 0, 0.15);
                transform: scale(1);
            }
            50% {
                text-shadow: 0 0 10px rgba(0, 255, 0, 0.6), 0 0 30px rgba(0, 255, 0, 0.3), 0 0 60px rgba(0, 255, 0, 0.1);
                transform: scale(1.02);
            }
        }
        
        @keyframes titleAppear {
            0% {
                opacity: 0;
                letter-spacing: 0.6em;
                filter: blur(8px);
            }
            100% {
                opacity: 0.9;
                letter-spacing: 0.15em;
                filter: blur(0);
            }
        }
        
        .main-title .subtitle {
            font-size: 1.8rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.7);
            margin: 1rem 0 0 0;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        
        .logo-container {
            position: fixed;
            z-index: 1;
        }
        
        .logo-container.left {
            left: 5%;
            top: 2%;
        }
        
        .logo-container.right {
            left: 80%;
            top: 2%;
        }
        
        .side-logo {
            width: 169px;  /* 135px * 1.25 = 169px (augmentation de 25%) */
            height: auto;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .side-logo:hover {
            opacity: 1;
        }
        
        .form-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .access-form {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 3;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 0 1px rgba(74, 222, 128, 0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        
        .form-input {
            width: 100%;
            padding: 1.125rem 1rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            transition: all 0.4s ease;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(74, 222, 128, 0.4);
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 
                0 0 0 1px rgba(74, 222, 128, 0.2),
                inset 0 0 20px rgba(74, 222, 128, 0.05);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 0.05em;
        }
        
        .submit-btn {
            width: 100%;
            padding: 1.125rem 1rem;
            background: linear-gradient(135deg, rgba(74, 222, 128, 0.9) 0%, rgba(74, 222, 128, 0.8) 100%);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 16px;
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, rgba(74, 222, 128, 1) 0%, rgba(74, 222, 128, 0.9) 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(74, 222, 128, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            color: rgba(239, 68, 68, 0.9);
            font-size: 0.85rem;
            font-weight: 400;
            text-align: center;
        }
        
        .security-badge {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: rgba(74, 222, 128, 0.6);
            backdrop-filter: blur(5px);
        }
        
        .blocked-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        
        .clickable-logo {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .clickable-logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 40px rgba(74, 222, 128, 0.6));
        }
        
        .blocked-message {
            margin-top: 3rem;
            text-align: center;
        }
        
        .blocked-message h2 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #dc3545;
            margin: 0 0 1rem 0;
            text-shadow: 0 0 20px rgba(220, 53, 69, 0.3);
        }
        
        .blocked-message p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
        }
        
        #secretFieldContainer {
            margin-top: 2rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }
        
        .secret-input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }
        
        .secret-input:focus {
            outline: none;
            border-color: rgba(74, 222, 128, 0.5);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .secret-btn {
            width: 100%;
            padding: 0.75rem;
            background: rgba(74, 222, 128, 0.8);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .secret-btn:hover {
            background: rgba(74, 222, 128, 1);
            transform: translateY(-2px);
        }
        
        .countdown {
            margin-top: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            animation: pulse 1s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @keyframes atomFloat {
            0%, 100% { 
                transform: translate(0, 0) scale(1);
                opacity: 0.8;
            }
            50% { 
                transform: translate(20px, -15px) scale(1.2);
                opacity: 1;
            }
        }
        
        @keyframes moleculeRotate {
            0% { 
                transform: rotate(0deg) scale(1);
                opacity: 0.6;
            }
            50% { 
                transform: rotate(180deg) scale(1.1);
                opacity: 0.8;
            }
            100% { 
                transform: rotate(360deg) scale(1);
                opacity: 0.6;
            }
        }
        
        @keyframes electronOrbit {
            0% { 
                transform: rotate(0deg) translateX(30px);
                opacity: 0.9;
            }
            100% { 
                transform: rotate(360deg) translateX(30px);
                opacity: 0.9;
            }
        }
        
        @keyframes moleculeFloat {
            0%, 100% { 
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.7;
            }
            25% { 
                transform: translate(30px, -20px) rotate(90deg);
                opacity: 0.9;
            }
            50% { 
                transform: translate(-20px, -30px) rotate(180deg);
                opacity: 0.6;
            }
            75% { 
                transform: translate(-40px, 10px) rotate(270deg);
                opacity: 0.8;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 2rem;
                margin-bottom: 3rem;
                padding: 0 1rem;
            }
            
            .logo-img { 
                width: 113px; 
                height: 113px; 
            }
            
            .banner-title { 
                font-size: 3.5rem; 
                text-align: center;
            }
            
            .banner-subtitle { 
                font-size: 1.5rem; 
                text-align: center;
            }
            
            .mindef-reference { 
                font-size: 1.3rem; 
                text-align: center;
            }
            
            .access-form { 
                padding: 2rem; 
                margin: 0 1rem; 
                max-width: 90%;
            }
            
            .form-input {
                font-size: 1rem;
                padding: 1rem 0.875rem;
            }
            
            .submit-btn {
                font-size: 0.9rem;
                padding: 1rem 0.875rem;
            }
            
            .security-badge {
                top: 1rem;
                right: 1rem;
                width: 35px;
                height: 35px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .header-container {
                gap: 1.5rem;
                margin-bottom: 2rem;
                padding: 0 0.5rem;
            }
            
            .logo-img { 
                width: 88px; 
                height: 88px; 
            }
            
            .banner-title { 
                font-size: 2.8rem; 
                line-height: 1.1;
            }
            
            .banner-subtitle { 
                font-size: 1.2rem; 
                line-height: 1.3;
            }
            
            .mindef-reference { 
                font-size: 1.1rem; 
            }
            
            .access-form { 
                padding: 1.5rem; 
                margin: 0 0.5rem; 
                border-radius: 15px;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .form-input {
                font-size: 0.95rem;
                padding: 0.875rem 0.75rem;
                border-radius: 12px;
            }
            
            .submit-btn {
                font-size: 0.85rem;
                padding: 0.875rem 0.75rem;
                border-radius: 12px;
            }
            
            .form-label {
                font-size: 0.75rem;
                margin-bottom: 0.75rem;
            }
            
            .error-message {
                font-size: 0.8rem;
                padding: 1rem;
                margin-bottom: 1.5rem;
                border-radius: 12px;
            }
            
            .security-badge {
                top: 0.5rem;
                right: 0.5rem;
                width: 30px;
                height: 30px;
                font-size: 0.7rem;
            }
            
            .blocked-message h2 {
                font-size: 2rem;
            }
            
            .blocked-message p {
                font-size: 1rem;
            }
            
            #secretFieldContainer {
                padding: 1.5rem;
                margin: 1rem 0.5rem;
                border-radius: 12px;
            }
            
            .secret-input {
                font-size: 0.9rem;
                padding: 0.75rem;
                border-radius: 6px;
            }
            
            .secret-btn {
                font-size: 0.8rem;
                padding: 0.6rem;
                border-radius: 6px;
            }
            
            .countdown {
                font-size: 0.8rem;
                padding: 0.6rem;
                border-radius: 6px;
            }
        }
        
        @media (max-width: 360px) {
            .logo-img { 
                width: 75px; 
                height: 75px; 
            }
            
            .banner-title { 
                font-size: 2.4rem; 
            }
            
            .banner-subtitle { 
                font-size: 1.1rem; 
            }
            
            .mindef-reference { 
                font-size: 1rem; 
            }
            
            .access-form { 
                padding: 1rem; 
                margin: 0 0.25rem; 
            }
            
            .form-input {
                font-size: 0.9rem;
                padding: 0.75rem 0.625rem;
            }
            
            .submit-btn {
                font-size: 0.8rem;
                padding: 0.75rem 0.625rem;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .header-container {
                gap: 3rem;
                margin-bottom: 4rem;
            }
            
            .logo-img { 
                width: 125px; 
                height: 125px; 
            }
            
            .banner-title { 
                font-size: 4rem; 
            }
            
            .banner-subtitle { 
                font-size: 1.7rem; 
            }
            
            .mindef-reference { 
                font-size: 1.5rem; 
            }
            
            .access-form { 
                padding: 2.5rem; 
                max-width: 450px;
            }
        }
        
        @media (min-width: 1025px) and (max-width: 1440px) {
            .header-container {
                gap: 4rem;
                margin-bottom: 5rem;
            }
            
            .logo-img { 
                width: 138px; 
                height: 138px; 
            }
            
            .banner-title { 
                font-size: 4.5rem; 
            }
            
            .banner-subtitle { 
                font-size: 1.8rem; 
            }
            
            .mindef-reference { 
                font-size: 1.6rem; 
            }
            
            .access-form { 
                padding: 3rem; 
                max-width: 480px;
            }
        }
        
        @media (orientation: landscape) and (max-height: 600px) {
            .header-container {
                margin-bottom: 2rem;
            }
            
            .logo-img { 
                width: 100px; 
                height: 100px; 
            }
            
            .banner-title { 
                font-size: 3rem; 
                margin: 0 0 0.5rem 0;
            }
            
            .banner-subtitle { 
                font-size: 1.3rem; 
                margin: 0 0 0.5rem 0;
            }
            
            .mindef-reference { 
                font-size: 1.2rem; 
                margin-top: 1rem;
            }
            
            .access-form { 
                padding: 1.5rem; 
                margin-top: 1rem;
            }
        }
        
        @media (prefers-reduced-motion: reduce) {
            .atom, .molecule, .electron, .photon, .quark {
                animation: none;
            }
            
            .logo-img:hover {
                transform: none;
            }
            
            .submit-btn:hover {
                transform: none;
            }
            
            .clickable-logo:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <!-- Effets de fond -->
    <div class="space-field" id="spaceField"></div>

    <!-- Badge de sécurité -->
    <div class="security-badge">
        <i class="fa-solid fa-shield-halved"></i>
    </div>

    <!-- Contenu principal -->
    <div class="portal-container">
        <?php if ($_SESSION['failed_attempts'] >= 3): ?>
            <!-- NIVEAU 3 - SYSTÈME BLOQUÉ -->
            <div class="blocked-container">
                <div class="logo-container">
                    <img src="img/cimis.png" alt="CIMIS" class="logo-img clickable-logo" onclick="showSecretField()">
                </div>
                <div class="blocked-message">
                    <h2> SYSTÈME BLOQUÉ </h2>
                    <p>Mesures de sécurité actives</p>
                </div>
                
                <!-- Champ secret invisible -->
                <div id="secretFieldContainer" style="display: none;">
                    <form method="POST" action="">
                        <input type="password" name="reset_code" id="resetCode" class="secret-input" placeholder="Code de déblocage">
                        <button type="submit" class="secret-btn">Débloquer</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            
            <!-- LOGOS AUX BORDS DE LA PAGE -->
            <div class="logo-container left">
                <img src="img/cimis.png" alt="Logo Gauche" class="side-logo">
            </div>
            <div class="logo-container right">
                <img src="img/cimis.png" alt="Logo Droit" class="side-logo">
            </div>
            
            <!-- TITRE CIMIS -->
            <div class="main-title">
                <h1 style="font-family: 'Black Ops One', cursive; font-weight: normal; color: #00ff00; text-shadow: 0 0 3px rgba(0, 255, 0, 0.4), 0 0 6px rgba(0, 255, 0, 0.2); letter-spacing: 0.15em;">CIMIS</h1>
                <p class="subtitle">Carte d'identité militaire informatisée et sécurisée</p>
            </div>
            
            <!-- CONTENEUR DU FORMULAIRE -->
            <div class="form-container">
                <!-- FORMULAIRE D'ACCÈS -->
                <form method="POST" action="" class="access-form">
                    <?php if (isset($error)): ?>
                    <div class="error-message" style="
                        <?php 
                        if ($_SESSION['failed_attempts'] === 1) {
                            echo 'background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.3); color: rgba(255, 193, 7, 0.9);';
                        } elseif ($_SESSION['failed_attempts'] === 2) {
                            echo 'background: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3); color: rgba(220, 53, 69, 0.9);';
                        }
                        ?>
                    ">
                        <?php echo $error; ?>
                        <?php if ($_SESSION['failed_attempts'] === 2 && $countdown_remaining > 0): ?>
                        <div class="countdown" id="countdown">
                            ⏱️ <span id="countdownTimer"><?php echo $countdown_remaining; ?></span> secondes restantes
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <input 
                            type="password" 
                            name="access_code" 
                            id="access_code" 
                            class="form-input" 
                            maxlength="12" 
                            required
                            autocomplete="off"
                            autofocus
                        >
                    </div>

                    <button type="submit" class="submit-btn">
                        Accéder au Portail
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Espace quantique avec atomes et molécules
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('access_code');
            const spaceField = document.getElementById('spaceField');
            const countdownTimer = document.getElementById('countdownTimer');
            
            // Gérer le décompte
            if (countdownTimer) {
                setInterval(function() {
                    const currentTime = parseInt(countdownTimer.textContent);
                    if (currentTime > 0) {
                        countdownTimer.textContent = currentTime - 1;
                    } else {
                        // Rediriger pour activer le blocage
                        window.location.reload();
                    }
                }, 1000);
            }
            
            // Fonction pour afficher le champ secret
            window.showSecretField = function() {
                const secretField = document.getElementById('secretFieldContainer');
                if (secretField) {
                    secretField.style.display = 'block';
                    document.getElementById('resetCode').focus();
                }
            };
            
            // Créer des atomes
            for (let i = 0; i < 25; i++) {
                const atom = document.createElement('div');
                atom.className = 'atom';
                atom.style.left = Math.random() * 100 + '%';
                atom.style.top = Math.random() * 100 + '%';
                atom.style.animationDelay = Math.random() * 10 + 's';
                atom.style.animationDuration = (Math.random() * 4 + 3) + 's';
                atom.style.animation = `atomFloat ${Math.random() * 3 + 2}s ease-in-out infinite`;
                spaceField.appendChild(atom);
            }
            
            // Créer des molécules
            for (let i = 0; i < 12; i++) {
                const molecule = document.createElement('div');
                molecule.className = 'molecule';
                
                // Position de la molécule
                const molX = Math.random() * 80 + 10 + '%';
                const molY = Math.random() * 80 + 10 + '%';
                molecule.style.left = molX;
                molecule.style.top = molY;
                
                // Créer 2-4 atomes pour la molécule
                const atomCount = Math.floor(Math.random() * 3) + 2;
                for (let j = 0; j < atomCount; j++) {
                    const molAtom = document.createElement('div');
                    molAtom.className = 'molecule-atom';
                    
                    // Position relative à la molécule
                    const angle = (j / atomCount) * Math.PI * 2;
                    const distance = 20 + Math.random() * 10;
                    const atomX = Math.cos(angle) * distance + 'px';
                    const atomY = Math.sin(angle) * distance + 'px';
                    
                    molAtom.style.left = atomX;
                    molAtom.style.top = atomY;
                    molAtom.style.animationDelay = Math.random() * 2 + 's';
                    molecule.appendChild(molAtom);
                }
                
                // Créer des liaisons
                for (let j = 0; j < atomCount; j++) {
                    const bond = document.createElement('div');
                    bond.className = 'molecule-bond';
                    
                    const angle1 = (j / atomCount) * Math.PI * 2;
                    const angle2 = ((j + 1) / atomCount) * Math.PI * 2;
                    const distance = 20 + Math.random() * 10;
                    
                    const x1 = Math.cos(angle1) * distance + 'px';
                    const y1 = Math.sin(angle1) * distance + 'px';
                    const x2 = Math.cos(angle2) * distance + 'px';
                    const y2 = Math.sin(angle2) * distance + 'px';
                    
                    const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
                    const rotation = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI + 90;
                    
                    bond.style.width = length + 'px';
                    bond.style.height = '1px';
                    bond.style.left = '50%';
                    bond.style.top = '50%';
                    bond.style.transform = `translate(-50%, -50%) rotate(${rotation}deg)`;
                    bond.style.animationDelay = Math.random() * 3 + 's';
                    
                    molecule.appendChild(bond);
                }
                
                molecule.style.animation = `moleculeFloat ${Math.random() * 20 + 15}s ease-in-out infinite`;
                spaceField.appendChild(molecule);
            }
            
            // Créer des électrons
            for (let i = 0; i < 40; i++) {
                const electron = document.createElement('div');
                electron.className = 'electron';
                electron.style.left = Math.random() * 100 + '%';
                electron.style.top = Math.random() * 100 + '%';
                electron.style.animationDelay = Math.random() * 8 + 's';
                electron.style.animationDuration = (Math.random() * 6 + 4) + 's';
                electron.style.animation = `electronOrbit ${Math.random() * 3 + 2}s linear infinite`;
                spaceField.appendChild(electron);
            }
            
            // Créer des photons
            for (let i = 0; i < 30; i++) {
                const photon = document.createElement('div');
                photon.className = 'photon';
                photon.style.left = Math.random() * 100 + '%';
                photon.style.top = Math.random() * 100 + '%';
                photon.style.animationDelay = Math.random() * 12 + 's';
                photon.style.animation = `photonPulse ${Math.random() * 8 + 6}s ease-in-out infinite`;
                spaceField.appendChild(photon);
            }
            
            // Créer des quarks
            for (let i = 0; i < 20; i++) {
                const quark = document.createElement('div');
                quark.className = 'quark';
                quark.style.left = Math.random() * 100 + '%';
                quark.style.top = Math.random() * 100 + '%';
                quark.style.animationDelay = Math.random() * 15 + 's';
                quark.style.animationDuration = (Math.random() * 10 + 8) + 's';
                quark.style.animation = `quarkSpin ${Math.random() * 10 + 8}s ease-in-out infinite`;
                spaceField.appendChild(quark);
            }
            
            // Créer des ondes quantiques
            for (let i = 0; i < 15; i++) {
                const wave = document.createElement('div');
                wave.className = 'quantum-wave';
                wave.style.left = Math.random() * 100 + '%';
                wave.style.top = Math.random() * 100 + '%';
                wave.style.animationDelay = Math.random() * 20 + 's';
                wave.style.animationDuration = (Math.random() * 15 + 10) + 's';
                wave.style.animation = `waveRipple ${Math.random() * 8 + 5}s ease-in-out infinite`;
                spaceField.appendChild(wave);
            }
            
            // Animation du logo
            const logo = document.querySelector('.logo-img');
            if (logo) {
                logo.addEventListener('mouseenter', function() {
                    this.style.filter = 'drop-shadow(0 0 40px rgba(74, 222, 128, 0.6))';
                });
                
                logo.addEventListener('mouseleave', function() {
                    this.style.filter = 'drop-shadow(0 0 20px rgba(74, 222, 128, 0.3))';
                });
            }
            
            // Animation d'entrée
            if (input) {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
                
                // Validation quantique
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        this.style.borderColor = 'rgba(74, 222, 128, 0.5)';
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
                    } else {
                        this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.04)';
                    }
                });
                
                // Animation du bouton submit
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const btn = this.querySelector('.submit-btn');
                        const originalText = btn.innerHTML;
                        
                        btn.innerHTML = 'Connexion quantique...';
                        btn.style.opacity = '0.7';
                        btn.disabled = true;
                        
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.style.opacity = '1';
                            btn.disabled = false;
                        }, 2500);
                    });
                }
            }
        });
    </script>
</body>
</html>
