<?php
// API SIADOC - Interface selon contrat d'interface officiel
require_once '../backend/config.php';

// Configuration de la réponse en JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration SIADOC - Selon documentation officielle
define('SIADOC_API_URL', 'https://siadoc.onrender.com');
define('SIADOC_API_KEY', 'siadoc-2026-cimis-integration'); // Clé officielle CIMIS

// Fonction pour envoyer une réponse d'erreur
function sendErrorResponse($message, $http_code = 400) {
    http_response_code($http_code);
    echo json_encode([
        'error' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

// Fonction pour envoyer une réponse de succès
function sendSuccessResponse($data, $message = null) {
    $response = $data;
    if ($message) {
        $response['message'] = $message;
    }
    echo json_encode($response);
}

// Fonction pour appeler l'API SIADOC
function callSIADOCAPI($endpoint, $params = []) {
    $url = SIADOC_API_URL . $endpoint;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . SIADOC_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erreur cURL: $error");
    }
    
    return [
        'data' => json_decode($response, true),
        'http_code' => $http_code,
        'raw_response' => $response
    ];
}

// Fonction pour appeler l'API backend CIMIS
function callCIMISAPI($endpoint, $params = [], $method = 'GET') {
    $url = '../backend/api_siadoc_envoie.php/' . $endpoint;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: siadoc-2026-cimis-integration',
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
    } else {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erreur cURL: $error");
    }
    
    return [
        'data' => json_decode($response, true),
        'http_code' => $http_code,
        'raw_response' => $response
    ];
}

// Fonction pour générer un matricule CIMIS
function generateCIMISMatricule() {
    $prefix = 'CIM-';
    $year = date('Y');
    $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $year . $sequence;
}

// Fonction pour générer un QR code
function generateQRCode($matricule, $matricule_cimis) {
    $qr_data = "https://cimis.cm/verify/" . $matricule;
    $qr_filename = '../img/qrcodes/' . $matricule . '_qr.png';
    
    // Créer le répertoire si nécessaire
    if (!file_exists('../img/qrcodes')) {
        mkdir('../img/qrcodes', 0777, true);
    }
    
    // Simulation de génération de QR code
    $qr_image = imagecreatetruecolor(200, 200);
    $bg_color = imagecolorallocate($qr_image, 255, 255, 255);
    $fg_color = imagecolorallocate($qr_image, 0, 0, 0);
    
    imagefill($qr_image, 0, 0, $bg_color);
    imagestring($qr_image, 5, 30, 90, "QR: " . substr($matricule, -10), $fg_color);
    
    imagepng($qr_image, $qr_filename);
    imagedestroy($qr_image);
    
    return [
        'image_path' => $qr_filename,
        'content' => $qr_data
    ];
}

// Fonction pour encoder une image en base64 brut
function encodeImageToBase64Raw($image_path) {
    if (file_exists($image_path)) {
        $image_data = file_get_contents($image_path);
        return base64_encode($image_data);
    }
    return null;
}

// Router API simplifié
$request_method = $_SERVER['REQUEST_METHOD'];

// Endpoint principal: Interface web pour tester l'API SIADOC
if ($request_method === 'GET' && !isset($_GET['action'])) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API SIADOC - Interface CIMIS</title>
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
            background: linear-gradient(180deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
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
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
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
        
        /* === CARDS === */
        .premium-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .premium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.8s ease;
        }
        
        .premium-card:hover::before {
            left: 100%;
        }
        
        .premium-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-soft);
            border-color: var(--accent);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .card-subtitle {
            font-size: 0.9rem;
            color: white;
            margin-top: 0.25rem;
            opacity: 0.9;
        }
        
        /* === STATS CARDS === */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.8s ease;
        }
        
        .stat-card:hover::before {
            left: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-soft);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* === FORMS === */
        .premium-form {
            display: grid;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .form-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1rem;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        select.form-input {
            background: rgba(10, 25, 41, 0.9);
            color: #ffffff;
            border: 1px solid rgba(50, 255, 126, 0.3);
        }
        
        select.form-input option {
            background: #0a1929;
            color: #ffffff;
            padding: 0.5rem;
        }
        
        select.form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 20px rgba(50, 255, 126, 0.3);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
        }
        
        /* === BUTTONS === */
        .btn-premium {
            background: linear-gradient(135deg, rgba(15, 35, 22, 0.98) 0%, rgba(15, 35, 22, 0.95) 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 20px rgba(15, 35, 22, 0.6);
        }
        
        .btn-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, rgba(15, 35, 22, 0.95) 0%, rgba(15, 35, 22, 0.98) 100%);
            box-shadow: 0 0 30px rgba(15, 35, 22, 0.6);
            text-shadow: 0 0 15px rgba(255, 255, 255, 1);
        }
        
        .btn-premium:hover::before {
            left: 100%;
        }
        
        /* === TABLE === */
        .premium-table {
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .premium-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .premium-table th {
            background: rgba(102, 126, 234, 0.1);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 1px solid var(--card-border);
        }
        
        .premium-table td {
            padding: 1rem;
            color: var(--text-primary);
            border-bottom: 1px solid var(--card-border);
        }
        
        .premium-table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        /* === FOOTER === */
        .premium-footer {
            grid-area: footer;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-top: 1px solid var(--card-border);
            padding: 1.5rem 3rem;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .footer-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .api-container {
            background: linear-gradient(135deg, 
                rgba(20, 40, 25, 0.98) 0%, 
                rgba(10, 30, 20, 0.98) 50%,
                rgba(15, 35, 22, 0.98) 100%);
            border: 3px solid transparent;
            border-image: linear-gradient(45deg, 
                var(--neon-green) 0%, 
                var(--neon-gold) 25%, 
                var(--neon-blue) 50%, 
                var(--neon-gold) 75%, 
                var(--neon-green) 100%) 1;
            border-radius: 25px;
            padding: 3rem;
            margin: 1.5rem;
            width: calc(100% - 3rem);
            max-width: none;
            backdrop-filter: blur(20px) saturate(1.3);
            box-shadow: 
                0 0 40px rgba(74, 222, 128, 0.6),
                0 0 80px rgba(74, 222, 128, 0.3),
                0 0 120px rgba(74, 222, 128, 0.1),
                inset 0 0 30px rgba(74, 222, 128, 0.15);
            position: relative;
            overflow: hidden;
            animation: containerGlow 4s ease-in-out infinite alternate;
        }
        
        .api-header {
            text-align: center;
            margin-bottom: 3rem;
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(90deg, transparent, var(--neon-gold), transparent) 1;
            padding-bottom: 2.5rem;
            position: relative;
            background: linear-gradient(180deg, 
                rgba(74, 222, 128, 0.08) 0%, 
                rgba(34, 211, 238, 0.05) 50%, 
                rgba(74, 222, 128, 0.08) 100%);
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 
                0 0 30px rgba(74, 222, 128, 0.2),
                inset 0 0 30px rgba(74, 222, 128, 0.1);
        }
        
        .api-header::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--neon-green), var(--neon-blue), var(--neon-gold));
            border-radius: 15px;
            z-index: -1;
            opacity: 0.3;
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        .api-header h1 {
            color: var(--neon-green);
            font-family: var(--font-heading);
            font-size: 3rem;
            font-weight: 700;
            text-shadow: 
                0 0 10px rgba(74, 222, 128, 0.8),
                0 0 20px rgba(74, 222, 128, 0.6),
                0 0 30px rgba(74, 222, 128, 0.4),
                0 0 40px rgba(74, 222, 128, 0.2);
            margin-bottom: 1.5rem;
            letter-spacing: 3px;
            position: relative;
            animation: float 3s ease-in-out infinite;
        }
        
        .api-section {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.8) 0%, rgba(20, 40, 25, 0.6) 100%);
            border: 1px solid transparent;
            border-image: linear-gradient(45deg, var(--neon-green), var(--neon-blue), var(--neon-green)) 1;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px) saturate(1.1);
            width: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 0 20px rgba(74, 222, 128, 0.2),
                inset 0 0 20px rgba(74, 222, 128, 0.05);
            transition: all 0.3s ease;
            clear: both;
            display: block;
        }
        
        .api-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(74, 222, 128, 0.1), transparent);
            transition: left 0.8s ease;
        }
        
        .api-section:hover::before {
            left: 100%;
        }
        
        .api-section:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 5px 30px rgba(74, 222, 128, 0.3),
                inset 0 0 20px rgba(74, 222, 128, 0.1);
            border-color: var(--neon-gold);
        }
        
        .api-section h4 {
            color: var(--neon-green);
            font-family: var(--font-heading);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="premium-layout">
        <!-- SIDEBAR -->
        <aside class="premium-sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-shield-alt"></i>
                <div class="sidebar-title">SIADOC</div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="impression.php" class="nav-item">
                    <i class="fas fa-print"></i>
                    <span>Impression</span>
                </a>
                <a href="corbeille.php" class="nav-item">
                    <i class="fas fa-trash"></i>
                    <span>Corbeille</span>
                </a>
                <a href="securite_admin.php" class="nav-item">
                    <i class="fa-solid fa-shield-alt"></i>
                    <span>Sécurité</span>
                </a>
                <a href="api_siadoc.php" class="nav-item active">
                    <i class="fas fa-code"></i>
                    <span>API SIADOC</span>
                </a>
                <a href="siadoc_integration.php" class="nav-item">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Intégration SIADOC</span>
                </a>
            </nav>
        </aside>
        
        <!-- HEADER -->
        <header class="premium-header">
            <div class="header-title-section">
                <div class="header-icon">
                    <i class="fas fa-code"></i>
                </div>
                <div class="header-text">
                    <h1>API SIADOC</h1>
                    <p>Interface d'interopérabilité militaire</p>
                </div>
            </div>
            <div class="header-user">
                <div class="user-avatar">
                    <?php echo substr($_SESSION['user_name'] ?? 'U', 0, 1); ?>
                </div>
                <a href="dashboard.php" class="btn-premium" style="padding: 0.8rem 1.5rem; font-size: 0.95rem; background: linear-gradient(135deg, rgba(50, 255, 126, 0.8) 0%, rgba(0, 217, 255, 0.8) 100%); border: 2px solid transparent; border-image: linear-gradient(45deg, #32ff7e, #00d9ff, #32ff7e) 1; box-shadow: 0 0 15px rgba(50, 255, 126, 0.3);">
                    <i class="fas fa-arrow-left"></i> Retour au Dashboard
                </a>
                <a href="../logout.php" class="btn-premium" style="padding: 0.8rem 1.5rem; font-size: 0.95rem; margin-left: 0.5rem; background: linear-gradient(135deg, rgba(0, 217, 255, 0.8) 0%, rgba(50, 255, 126, 0.8) 100%); border: 2px solid transparent; border-image: linear-gradient(45deg, #00d9ff, #32ff7e, #00d9ff) 1; box-shadow: 0 0 15px rgba(0, 217, 255, 0.3);">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </header>
        
        <!-- MAIN CONTENT -->
        <main class="premium-main">
            <!-- STATISTICS CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="totalMilitaires">0</div>
                        <div class="stat-label">Total Militaires / Total Personnel</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="syncCount">0</div>
                        <div class="stat-label">Synchronisations / Synchronizations</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="avgResponseTime">0ms</div>
                        <div class="stat-label">Temps Réponse / Response Time</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="venusDeSiadoc">0</div>
                        <div class="stat-label">Venus de SIADOC / From SIADOC</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="cartesGenerees">0</div>
                        <div class="stat-label">Cartes Générées / Generated Cards</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <div class="stat-number" id="enAttente">0</div>
                        <div class="stat-label">En Attente / Pending</div>
                    </div>
                </div>
            </div>
            
                        
            <!-- CONFIGURATION SECTION -->
            <div class="api-section">
                <h4 class="section-title">
                    <i class="fas fa-cog"></i>
                    Configuration SIADOC / SIADOC Configuration
                </h4>
                
                <div class="premium-form">
                    <div class="form-group">
                        <label class="form-label">URL API SIADOC / SIADOC API URL</label>
                        <input type="text" class="form-input" id="apiUrl" value="https://siadoc.onrender.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Clé API / API Key</label>
                        <input type="text" class="form-input" id="apiKey" value="siadoc-2026-cimis-integration">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Synchronisation automatique / Auto Synchronization</label>
                        <select class="form-input" id="autoSync">
                            <option value="enabled">Activée / Enabled</option>
                            <option value="disabled">Désactivée / Disabled</option>
                        </select>
                    </div>
                    <button onclick="saveConfig()" class="btn-premium">
                        <i class="fas fa-save"></i>
                        Sauvegarder / Save
                    </button>
                </div>
            </div>    
            
            <!-- ACTIONS CARD -->
            <div class="premium-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div>
                        <div class="card-title">Actions d'Intégration</div>
                        <div class="card-subtitle">Opérations de synchronisation SIADOC-CIMIS</div>
                    </div>
                </div>
                
                <div class="premium-form">
                    <div class="form-group">
                        <label class="form-label">Recherche Individuelle / Individual Search</label>
                        <input type="text" id="matriculeInput" class="form-input" placeholder="Matricule militaire (ex: MAT-2023-12345) / Military ID (ex: MAT-2023-12345)">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">PAR GRADE / BY RANK</label>
                        <select id="gradeFilter" class="form-input">
                            <option value="">Tous les grades / All Ranks</option>
                            <option value="GENDARME-MAJOR">GENDARME-MAJOR</option>
                            <option value="COLONEL">COLONEL</option>
                            <option value="LIEUTENANT-COLONEL">LIEUTENANT-COLONEL</option>
                            <option value="CAPITAINE">CAPITAINE</option>
                            <option value="LIEUTENANT">LIEUTENANT</option>
                            <option value="ADJUDANT-CHEF-MAJOR">ADJUDANT-CHEF-MAJOR</option>
                            <option value="ADJUDANT-CHEF">ADJUDANT-CHEF</option>
                            <option value="SERGENT-CHEF">SERGENT-CHEF</option>
                            <option value="SERGENT">SERGENT</option>
                            <option value="CAPORAL">CAPORAL</option>
                            <option value="SOLDAT">SOLDAT</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">PAR UNITÉ / BY UNIT</label>
                        <select id="uniteFilter" class="form-input">
                            <option value="">Toutes les unités / All Units</option>
                            <option value="ARMÉE DE TERRE">ARMÉE DE TERRE</option>
                            <option value="ARMÉE DE L'AIR">ARMÉE DE L'AIR</option>
                            <option value="MARINE NATIONALE">MARINE NATIONALE</option>
                            <option value="GENDARMERIE">GENDARMERIE</option>
                            <option value="GENDARMERIE NATIONALE">GENDARMERIE NATIONALE</option>
                            <option value="CIVIL">CIVIL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ANNÉE ENTRÉE / ENTRY YEAR</label>
                        <input type="number" id="anneeEntreeFilter" class="form-input" placeholder="ex: 2023 / e.g.: 2023" min="2000" max="<?php echo date('Y'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ANNÉE DERNIER GALON / LAST PROMOTION YEAR</label>
                        <input type="number" id="anneeGalonFilter" class="form-input" placeholder="ex: 2023 / e.g.: 2023" min="2000" max="<?php echo date('Y'); ?>">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <button onclick="getMilitaire()" class="btn-premium">
                            <i class="fas fa-search"></i> Recherche Individuelle / Individual Search
                        </button>
                        <button onclick="getMilitairesFiltres()" class="btn-premium">
                            <i class="fas fa-filter"></i> Appliquer Filtres / Apply Filters
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- BULK ACTIONS CARD -->
            <div class="premium-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div>
                        <div class="card-title">Actions Groupées / Bulk Actions</div>
                        <div class="card-subtitle">Opérations en lot sur les militaires / Batch Operations on Personnel</div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button onclick="genererMatriculesCIMIS()" class="btn-premium">
                        <i class="fas fa-id-card"></i> Génération Matricules CIMIS / Generate CIMIS Matricules
                    </button>
                    <button onclick="genererQRCodes()" class="btn-premium">
                        <i class="fas fa-qrcode"></i> Génération QR Codes / Generate QR Codes
                    </button>
                    <button onclick="enregistrerEnBase()" class="btn-premium">
                        <i class="fas fa-database"></i> Enregistrement en Base / Database Registration
                    </button>
                    <button onclick="envoyerBiometrie()" class="btn-premium">
                        <i class="fas fa-fingerprint"></i> Envoi Biométrie / Send Biometrics
                    </button>
                </div>
            </div>
            
            <!-- HISTORY CARD -->
            <div class="premium-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <div class="card-title">Historique des Opérations / Operations History</div>
                        <div class="card-subtitle">Journal des synchronisations / Synchronization Log</div>
                    </div>
                </div>
                
                <button onclick="getHistorique()" class="btn-premium" style="width: 100%;">
                    <i class="fas fa-history"></i> Consulter l'Historique / View History
                </button>
                
                <div id="historiqueResult" style="margin-top: 1.5rem;"></div>
            </div>
            
            <!-- RESULTS SECTION -->
            <div id="militaireResult" class="premium-card" style="display: none;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="card-title">Résultats de Recherche</div>
                        <div class="card-subtitle">Militaires trouvés</div>
                    </div>
                </div>
                <div id="militaireResultContent"></div>
            </div>
            
            <div id="filtresResult" class="premium-card" style="display: none;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <div>
                        <div class="card-title">Résultats des Filtres</div>
                        <div class="card-subtitle">Militaires correspondants</div>
                    </div>
                </div>
                <div id="filtresResultContent"></div>
            </div>
        </main>
        
        <!-- FOOTER -->
        <footer class="premium-footer">
            <div class="footer-text">
                © 2026 CIMIS - API SIADOC<br>
                Interface d'Interopérabilité Militaire
            </div>
        </footer>
    </div>
    
    <script>
        // Variables globales
        let militairesData = [];
        let selectedMilitaires = [];
        
        // Fonction de recherche individuelle
        function getMilitaire() {
            const matricule = document.getElementById('matriculeInput').value;
            if (!matricule) {
                showNotification('Veuillez entrer un matricule', 'warning');
                return;
            }
            
            fetch('api_siadoc.php?action=get_militaire&matricule=' + encodeURIComponent(matricule))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMilitaireResult(data.data);
                    } else {
                        showNotification(data.message || 'Erreur lors de la recherche', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur de connexion: ' + error.message, 'error');
                });
        }
        
        // Fonction de recherche avec filtres
        function getMilitairesFiltres() {
            const params = new URLSearchParams();
            const grade = document.getElementById('gradeFilter').value;
            const unite = document.getElementById('uniteFilter').value;
            const anneeEntree = document.getElementById('anneeEntreeFilter').value;
            const anneeGalon = document.getElementById('anneeGalonFilter').value;
            
            if (grade) params.append('grade', grade);
            if (unite) params.append('unite', unite);
            if (anneeEntree) params.append('annee_entree', anneeEntree);
            if (anneeGalon) params.append('annee_galon', anneeGalon);
            
            fetch('api_siadoc.php?action=get_filtres&' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayFiltresResult(data.data);
                    } else {
                        showNotification(data.message || 'Erreur lors de la recherche', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur de connexion: ' + error.message, 'error');
                });
        }
        
        // Fonctions d'actions groupées
        function genererMatriculesCIMIS() {
            showNotification('Génération des matricules CIMIS en cours...', 'info');
            // Implémentation à venir
        }
        
        function genererQRCodes() {
            showNotification('Génération des QR codes en cours...', 'info');
            // Implémentation à venir
        }
        
        function enregistrerEnBase() {
            showNotification('Enregistrement en base en cours...', 'info');
            // Implémentation à venir
        }
        
        function envoyerBiometrie() {
            showNotification('Envoi biométrie en cours...', 'info');
            // Implémentation à venir
        }
        
        // Fonction d'historique
        function getHistorique() {
            fetch('api_siadoc.php?action=get_historique')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayHistoriqueResult(data.data);
                    } else {
                        showNotification(data.message || 'Erreur lors de la récupération de l\'historique', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur de connexion: ' + error.message, 'error');
                });
        }
        
        // Fonctions d'affichage
        function displayMilitaireResult(data) {
            const resultDiv = document.getElementById('militaireResult');
            const contentDiv = document.getElementById('militaireResultContent');
            
            contentDiv.innerHTML = `
                <div class="premium-table">
                    <table>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Grade</th>
                            <th>Unité</th>
                        </tr>
                        <tr>
                            <td>${data.matricule_militaire || 'N/A'}</td>
                            <td>${data.nom || 'N/A'}</td>
                            <td>${data.prenom || 'N/A'}</td>
                            <td>${data.grade || 'N/A'}</td>
                            <td>${data.unite || 'N/A'}</td>
                        </tr>
                    </table>
                </div>
            `;
            
            resultDiv.style.display = 'block';
        }
        
        function displayFiltresResult(data) {
            const resultDiv = document.getElementById('filtresResult');
            const contentDiv = document.getElementById('filtresResultContent');
            
            if (data.length === 0) {
                contentDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Aucun militaire trouvé</p>';
            } else {
                let tableHTML = `
                    <div class="premium-table">
                        <table>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Grade</th>
                                <th>Unité</th>
                            </tr>
                `;
                
                data.forEach(militaire => {
                    tableHTML += `
                        <tr>
                            <td>${militaire.matricule_militaire || 'N/A'}</td>
                            <td>${militaire.nom || 'N/A'}</td>
                            <td>${militaire.prenom || 'N/A'}</td>
                            <td>${militaire.grade || 'N/A'}</td>
                            <td>${militaire.unite || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                tableHTML += '</table></div>';
                contentDiv.innerHTML = tableHTML;
            }
            
            resultDiv.style.display = 'block';
        }
        
        function displayHistoriqueResult(data) {
            const contentDiv = document.getElementById('historiqueResult');
            
            if (data.length === 0) {
                contentDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Aucun historique trouvé</p>';
            } else {
                let tableHTML = `
                    <div class="premium-table">
                        <table>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Statut</th>
                                <th>Détails</th>
                            </tr>
                `;
                
                data.forEach(log => {
                    tableHTML += `
                        <tr>
                            <td>${new Date(log.date).toLocaleString()}</td>
                            <td>${log.action}</td>
                            <td><span style="color: ${log.status === 'success' ? '#4facfe' : '#f5576c'}">${log.status}</span></td>
                            <td>${log.details || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                tableHTML += '</table></div>';
                contentDiv.innerHTML = tableHTML;
            }
        }
        
        // Fonction de notification
        function showNotification(message, type = 'info') {
            const colors = {
                info: '#4facfe',
                success: '#4ade80',
                warning: '#fee140',
                error: '#f5576c'
            };
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, ${colors[type]} 0%, ${colors[type]}dd 100%);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                max-width: 300px;
                font-weight: 500;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Animations CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
}
?>

            function getMilitaire() {
                const matricule = document.getElementById('matriculeInput').value;
                if (!matricule) {
                    alert('Veuillez entrer un matricule');
                    return;
                }
                
                fetch(`api_siadoc.php?action=get_militaire&matricule=${encodeURIComponent(matricule)}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('militaireResult');
                        if (data.error) {
                            resultDiv.innerHTML = `<div class="alert-danger">${data.error}</div>`;
                        } else {
                            militairesData = [data];
                            selectedMilitaires = [data.matricule];
                            resultDiv.innerHTML = `
                                <div style="color: var(--neon-green); font-weight: bold; margin-bottom: 1rem;">
                                    <i class="fas fa-check-circle"></i> MILITAIRE TROUVÉ / MILITARY FOUND
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div><strong style="color: var(--text-muted);">NOM / NAME:</strong> ${data.nom || ''}</div>
                                    <div><strong style="color: var(--text-muted);">PRÉNOM / FIRST NAME:</strong> ${data.prenom || ''}</div>
                                    <div><strong style="color: var(--text-muted);">MATRICULE / ID:</strong> ${data.matricule || ''}</div>
                                    <div><strong style="color: var(--text-muted);">GRADE / RANK:</strong> ${data.grade || ''}</div>
                                    <div><strong style="color: var(--text-muted);">CORPS / CORPS:</strong> ${data.corps || ''}</div>
                                    <div><strong style="color: var(--text-muted);">DATE NAISSANCE / BIRTH DATE:</strong> ${data.dateNaissance || ''}</div>
                                </div>
                                <div style="margin-top: 1rem; padding: 1rem; background: rgba(74, 222, 128, 0.1); border-radius: 5px;">
                                    <input type="checkbox" id="selectMilitaire_${data.matricule}" class="checkbox-custom" checked>
                                    <label for="selectMilitaire_${data.matricule}" style="color: var(--text-main); margin-left: 0.5rem;">
                                        SÉLECTIONNER POUR ACTIONS / SELECT FOR ACTIONS
                                    </label>
                                </div>
                            `;
                        }
                        resultDiv.style.display = 'block';
                    })
                    .catch(error => {
                        document.getElementById('militaireResult').innerHTML = 
                            `<div class="alert-danger">Erreur: ${error.message}</div>`;
                        document.getElementById('militaireResult').style.display = 'block';
                    });
            }
            
            function getMilitairesFiltres() {
                const grade = document.getElementById('gradeFilter').value;
                const unite = document.getElementById('uniteFilter').value;
                const anneeEntree = document.getElementById('anneeEntreeFilter').value;
                const anneeGalon = document.getElementById('anneeGalonFilter').value;
                
                if (!grade && !unite && !anneeEntree && !anneeGalon) {
                    alert('Veuillez sélectionner au moins un filtre / Please select at least one filter');
                    return;
                }
                
                // Construire les paramètres
                let params = {};
                if (grade) params.grade = grade;
                if (unite) params.unite = unite;
                if (anneeEntree) params.annee_entree = anneeEntree;
                if (anneeGalon) params.annee_galon = anneeGalon;
                
                // Appeler l'API avec les filtres
                const queryString = new URLSearchParams(params).toString();
                
                fetch(`api_siadoc.php?action=get_militaires_filtres&${queryString}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('filtresResult');
                        if (data.error) {
                            resultDiv.innerHTML = `<div class="alert-danger">${data.error}</div>`;
                        } else {
                            militairesData = data.militaires || [];
                            afficherListeMilitaires(resultDiv, data.militaires || [], 'filtres');
                        }
                        resultDiv.style.display = 'block';
                    })
                    .catch(error => {
                        document.getElementById('filtresResult').innerHTML = 
                            `<div class="alert-danger">Erreur: ${error.message}</div>`;
                        document.getElementById('filtresResult').style.display = 'block';
                    });
            }

            function afficherListeMilitaires(resultDiv, militaires, type) {
                let html = `<div style="color: var(--neon-green); font-weight: bold; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> ${militaires.length} MILITAIRE(S) TROUVÉ(S) / MILITARY FOUND
                </div>`;
                html += '<div style="overflow-x: auto;"><table class="table">';
                html += '<thead><tr><th><input type="checkbox" id="selectAll" class="checkbox-custom" onchange="toggleAllMilitaires()"></th><th>MATRICULE / ID</th><th>NOM / NAME</th><th>PRÉNOM / FIRST NAME</th><th>GRADE / RANK</th><th>CORPS / CORPS</th></tr></thead>';
                html += '<tbody>';
                
                militaires.forEach(militaire => {
                    html += `<tr>
                        <td><input type="checkbox" class="militaire-checkbox checkbox-custom" value="${militaire.matricule}" onchange="updateSelectedMilitaires()"></td>
                        <td style="color: var(--neon-green); font-family: var(--font-mono);">${militaire.matricule}</td>
                        <td>${militaire.nom}</td>
                        <td>${militaire.prenom}</td>
                        <td><span class="badge">${militaire.grade}</span></td>
                        <td>${militaire.corps}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                html += `
                    <div style="margin-top: 1rem; padding: 1rem; background: rgba(74, 222, 128, 0.1); border-radius: 5px;">
                        <small style="color: var(--neon-green); font-weight: bold;">
                            <i class="fas fa-users"></i> <strong>${selectedMilitaires.length}</strong> MILITAIRE(S) SÉLECTIONNÉ(S) / SELECTED
                        </small>
                    </div>
                `;
                
                resultDiv.innerHTML = html;
            }
            
            function toggleAllMilitaires() {
                const selectAll = document.getElementById('selectAll');
                const checkboxes = document.querySelectorAll('.militaire-checkbox');
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                
                updateSelectedMilitaires();
            }
            
            function updateSelectedMilitaires() {
                const checkboxes = document.querySelectorAll('.militaire-checkbox:checked');
                selectedMilitaires = Array.from(checkboxes).map(cb => cb.value);
                
                // Mettre à jour les compteurs
                const counters = document.querySelectorAll('.text-info strong, .fa-users + strong');
                counters.forEach(counter => {
                    if (counter) counter.textContent = selectedMilitaires.length;
                });
            }
            
            function genererMatriculesCIMIS() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire / Please select at least one military');
                    return;
                }
                
                if (confirm(`Générer les matricules CIMIS pour ${selectedMilitaires.length} militaire(s)?\n\nGenerate CIMIS matricules for ${selectedMilitaires.length} military personnel?`)) {
                    fetch(`api_siadoc.php?action=generer_matricules_cimis`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'génération des matricules CIMIS / CIMIS matricules generation');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'génération des matricules CIMIS / CIMIS matricules generation');
                    });
                }
            }
            
            function genererQRCodes() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire / Please select at least one military');
                    return;
                }
                
                if (confirm(`Générer les QR codes pour ${selectedMilitaires.length} militaire(s)?\n\nGenerate QR codes for ${selectedMilitaires.length} military personnel?`)) {
                    fetch(`api_siadoc.php?action=generer_qr_codes`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'génération des QR codes / QR codes generation');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'génération des QR codes / QR codes generation');
                    });
                }
            }
            
            function enregistrerEnBase() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire / Please select at least one military');
                    return;
                }
                
                if (confirm(`Enregistrer en base ${selectedMilitaires.length} militaire(s)?\n\n⚠️  IMPORTANT: Les militaires seront enregistrés AVEC matricules CIMIS générés!\n\nRegister ${selectedMilitaires.length} military personnel in database?\n\n⚠️  IMPORTANT: Military personnel will be registered WITH generated CIMIS matricules!`)) {
                    fetch(`api_siadoc.php?action=enregistrer_en_base`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'enregistrement en base / database registration');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'enregistrement en base / database registration');
                    });
                }
            }
            
            function envoyerBiometrie() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire / Please select at least one military');
                    return;
                }
                
                if (confirm(`Envoyer les données biométriques à SIADOC pour ${selectedMilitaires.length} militaire(s)?\n\nSend biometric data to SIADOC for ${selectedMilitaires.length} military personnel?`)) {
                    fetch(`api_siadoc.php?action=envoyer_biometrie_massive`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'envoi biométrie à SIADOC / biometry sending to SIADOC');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'envoi biométrie à SIADOC / biometry sending to SIADOC');
                    });
                }
            }
            
            function afficherResultatActions(data, action) {
                const resultDiv = document.getElementById('actionsResult');
                if (data.error) {
                    resultDiv.innerHTML = `<div class="alert-danger">Erreur lors de ${action}: ${data.error}</div>`;
                } else {
                    let html = `<div class="alert-success" style="margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i> <strong>${action.toUpperCase()} RÉUSSIE / SUCCESSFUL</strong>
                    </div>`;
                    
                    if (data.resultats) {
                        html += '<div style="margin-top: 1rem;"><h6 style="color: var(--neon-green);">RÉSULTATS DÉTAILLÉS / DETAILED RESULTS:</h6>';
                        html += '<div style="overflow-x: auto;"><table class="table">';
                        html += '<thead><tr><th>MATRICULE / ID</th><th>MATRICULE CIMIS / CIMIS ID</th><th>STATUT / STATUS</th><th>QR CODE</th></tr></thead>';
                        html += '<tbody>';
                        
                        data.resultats.forEach(resultat => {
                            const statusClass = resultat.statut === 'success' ? 'success' : 'warning';
                            html += `<tr>
                                <td style="color: var(--neon-green); font-family: var(--font-mono);">${resultat.matricule}</td>
                                <td style="color: var(--neon-blue); font-family: var(--font-mono);">${resultat.matricule_cimis || '-'}</td>
                                <td><span class="badge" style="background: var(--camo-medium);">${resultat.statut}</span></td>
                                <td>${resultat.qr_code ? '✅' : '-'}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div></div>';
                    }
                    
                    resultDiv.innerHTML = html;
                }
                resultDiv.style.display = 'block';
            }
            
            function getHistorique() {
                fetch(`api_siadoc.php?action=get_historique`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('historiqueResult');
                        if (data.error) {
                            resultDiv.innerHTML = `<div class="alert-danger">${data.error}</div>`;
                        } else {
                            let html = `<div style="color: var(--neon-green); font-weight: bold; margin-bottom: 1rem;">
                                <i class="fas fa-history"></i> HISTORIQUE DES OPÉRATIONS / OPERATIONS HISTORY
                            </div>`;
                            html += '<div style="overflow-x: auto;"><table class="table">';
                            html += '<thead><tr><th>DATE / DATE</th><th>ACTION / ACTION</th><th>DÉTAILS / DETAILS</th><th>STATUT / STATUS</th><th>UTILISATEUR / USER</th></tr></thead>';
                            html += '<tbody>';
                            
                            data.operations.forEach(operation => {
                                const date = new Date(operation.date).toLocaleString('fr-FR');
                                const statutClass = operation.statut === 'success' ? 'success' : 'warning';
                                
                                html += `<tr>
                                    <td style="font-family: var(--font-mono); font-size: 0.8rem;">${date}</td>
                                    <td><span class="badge">${operation.action}</span></td>
                                    <td>${operation.details}</td>
                                    <td><span class="badge" style="background: var(--camo-medium);">${operation.statut}</span></td>
                                    <td>${operation.utilisateur || 'Système / System'}</td>
                                </tr>`;
                            });
                            
                            html += '</tbody></table></div>';
                            
                            if (data.operations.length === 0) {
                                html += '<div class="alert-success" style="margin-top: 1rem;"><i class="fas fa-info-circle"></i> Aucune opération dans l\'historique / No operations in history</div>';
                            }
                            
                            resultDiv.innerHTML = html;
                        }
                        resultDiv.style.display = 'block';
                    })
                    .catch(error => {
                        document.getElementById('historiqueResult').innerHTML = 
                            `<div class="alert-danger">Erreur: ${error.message}</div>`;
                        document.getElementById('historiqueResult').style.display = 'block';
                    });
            }

            function getStats() {
                fetch(`api_siadoc.php?action=stats`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('statsResult');
                        if (data.error) {
                            resultDiv.innerHTML = `<div class="alert-danger">${data.error}</div>`;
                        } else {
                            resultDiv.innerHTML = `
                                <div style="color: var(--neon-green); font-weight: bold; margin-bottom: 1.5rem;">
                                    <i class="fas fa-chart-bar"></i> STATISTIQUES CIMIS / CIMIS STATISTICS
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="stats-card">
                                            <h5>${data.total_militaires || 0}</h5>
                                            <small>MILITAIRES TOTAL / TOTAL MILITARY</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="stats-card">
                                            <h5>${data.cartes_generees || 0}</h5>
                                            <small>CARTES GÉNÉRÉES / CARDS GENERATED</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="stats-card">
                                            <h5>${data.envois_siadoc || 0}</h5>
                                            <small>ENVOIS SIADOC / SIADOC SENDS</small>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        resultDiv.style.display = 'block';
                    })
                    .catch(error => {
                        document.getElementById('statsResult').innerHTML = 
                            `<div class="alert-danger">Erreur: ${error.message}</div>`;
                        document.getElementById('statsResult').style.display = 'block';
                    });
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}

// API endpoints
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get_militaire':
            // Appeler la vraie API SIADOC pour récupérer un militaire spécifique
            if (!isset($_GET['matricule'])) {
                sendErrorResponse('Matricule obligatoire');
                break;
            }
            
            try {
                $matricule = $_GET['matricule'];
                
                // Appeler l'API SIADOC officielle
                $result = callSIADOCAPI('/api/export/militaire/info', ['matricule' => $matricule]);
                
                if ($result['http_code'] === 200) {
                    sendSuccessResponse($result['data'], 'Militaire trouvé dans SIADOC');
                } else {
                    sendErrorResponse('Militaire non trouvé dans SIADOC', $result['http_code']);
                }
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'appel API SIADOC: ' . $e->getMessage());
            }
            break;

        case 'get_all_militaires':
            // Appeler l'API SIADOC pour lister tout le personnel
            try {
                $result = callSIADOCAPI('/api/export/militaire/info/all');
                
                if ($result['http_code'] === 200) {
                    sendSuccessResponse($result['data'], 'Liste du personnel SIADOC récupérée');
                } else {
                    sendErrorResponse('Erreur lors de la récupération du personnel SIADOC', $result['http_code']);
                }
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'appel API SIADOC: ' . $e->getMessage());
            }
            break;

        case 'send_biometrie_to_siadoc':
            // Envoyer des données biométriques à SIADOC
            try {
                $json_input = file_get_contents('php://input');
                $data = json_decode($json_input, true);
                
                if (!$data || !isset($data['data'])) {
                    sendErrorResponse('Données biométriques invalides');
                    break;
                }
                
                // Appeler l'API SIADOC pour envoyer les données biométriques
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => SIADOC_API_URL . '/api/cimis/recevoir_carte',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => [
                        'X-API-KEY: ' . SIADOC_API_KEY,
                        'Content-Type: application/json'
                    ],
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_TIMEOUT => 30
                ]);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($error) {
                    throw new Exception("Erreur cURL: $error");
                }
                
                $result_data = json_decode($response, true);
                
                if ($http_code === 200) {
                    sendSuccessResponse($result_data, 'Données biométriques envoyées à SIADOC');
                } else {
                    sendErrorResponse('Erreur lors de l\'envoi à SIADOC: ' . ($result_data['error'] ?? 'Unknown error'), $http_code);
                }
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'envoi à SIADOC: ' . $e->getMessage());
            }
            break;

        case 'get_militaires_filtres':
            // Appeler l'API SIADOC avec filtres avancés
            try {
                $params = [];
                
                // Récupérer les filtres
                if (isset($_GET['grade']) && !empty($_GET['grade'])) {
                    $params['grade'] = $_GET['grade'];
                }
                if (isset($_GET['unite']) && !empty($_GET['unite'])) {
                    $params['unite'] = $_GET['unite'];
                }
                if (isset($_GET['annee_entree']) && !empty($_GET['annee_entree'])) {
                    $params['annee_entree'] = $_GET['annee_entree'];
                }
                if (isset($_GET['annee_galon']) && !empty($_GET['annee_galon'])) {
                    $params['annee_galon'] = $_GET['annee_galon'];
                }
                
                if (empty($params)) {
                    sendErrorResponse('Veuillez spécifier au moins un filtre');
                    break;
                }
                
                // Pour l'instant, simuler la réponse (à adapter avec vrai appel SIADOC)
                $militaires = [
                    [
                        'matricule' => 'MAT-2023-001',
                        'nom' => 'ESSOMBA',
                        'prenom' => 'Jean-Pierre',
                        'dateNaissance' => '1985-03-15',
                        'corps' => 'AA',
                        'grade' => 'Sergent',
                        'dateGrade' => '2020-07-01',
                        'sexe' => 'M'
                    ],
                    [
                        'matricule' => 'MAT-2023-002',
                        'nom' => 'DUPONT',
                        'prenom' => 'Marie',
                        'dateNaissance' => '1990-05-20',
                        'corps' => 'GG',
                        'grade' => 'Caporal',
                        'dateGrade' => '2018-01-01',
                        'sexe' => 'F'
                    ],
                    [
                        'matricule' => 'MAT-2023-003',
                        'nom' => 'MARTIN',
                        'prenom' => 'Pierre',
                        'dateNaissance' => '1988-11-10',
                        'corps' => 'TT',
                        'grade' => 'Adjudant',
                        'dateGrade' => '2015-03-15',
                        'sexe' => 'M'
                    ]
                ];
                
                // Appliquer les filtres (simulation)
                $militaires_filtres = $militaires;
                
                if (isset($params['grade'])) {
                    $militaires_filtres = array_filter($militaires_filtres, function($m) use ($params) {
                        return $m['grade'] === $params['grade'];
                    });
                }
                
                if (isset($params['unite'])) {
                    $militaires_filtres = array_filter($militaires_filtres, function($m) use ($params) {
                        return $m['corps'] === $params['unite'];
                    });
                }
                
                if (isset($params['annee_entree'])) {
                    $militaires_filtres = array_filter($militaires_filtres, function($m) use ($params) {
                        return substr($m['matricule'], 4, 4) === $params['annee_entree'];
                    });
                }
                
                if (isset($params['annee_galon'])) {
                    $militaires_filtres = array_filter($militaires_filtres, function($m) use ($params) {
                        return substr($m['dateGrade'], 0, 4) === $params['annee_galon'];
                    });
                }
                
                sendSuccessResponse([
                    'militaires' => array_values($militaires_filtres),
                    'filtres_appliques' => $params,
                    'total' => count($militaires_filtres),
                    'message' => 'Militaires filtrés avec succès'
                ]);
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'appel API SIADOC: ' . $e->getMessage());
            }
            break;

        case 'get_historique':
            // Récupérer l'historique des opérations
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        action,
                        details,
                        status,
                        last_sync as date,
                        system as utilisateur
                    FROM api_sync_log 
                    WHERE system LIKE 'SIADOC%'
                    ORDER BY last_sync DESC
                    LIMIT 50
                ");
                $stmt->execute();
                $operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Formatter les opérations
                $formatted_operations = [];
                foreach ($operations as $operation) {
                    $formatted_operations[] = [
                        'date' => $operation['date'],
                        'action' => $operation['action'],
                        'details' => $operation['details'],
                        'statut' => $operation['status'],
                        'utilisateur' => $operation['utilisateur']
                    ];
                }
                
                sendSuccessResponse([
                    'operations' => $formatted_operations,
                    'total' => count($formatted_operations)
                ]);
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la récupération de l\'historique: ' . $e->getMessage());
            }
            break;

        case 'help':
            // Documentation de l'API
            sendSuccessResponse([
                'api_name' => 'API SIADOC - Interface CIMIS',
                'description' => 'API pour l\'interopérabilité entre SIADOC et CIMIS',
                'endpoints' => [
                    'GET /api_siadoc.php?action=help' => 'Documentation de l\'API',
                    'GET /api_siadoc.php?action=get_militaire&matricule=XXX' => 'Récupérer un militaire SIADOC par matricule',
                    'GET /api_siadoc.php?action=get_all_militaires' => 'Lister tout le personnel SIADOC',
                    'POST /api_siadoc.php?action=send_biometrie_to_siadoc' => 'Envoyer des données biométriques à SIADOC',
                    'GET /api_siadoc.php?action=get_militaires_filtres&grade=XXX&unite=XXX&annee_entree=XXX&annee_galon=XXX' => 'Filtrer les militaires',
                    'POST /api_siadoc.php?action=generer_matricules_cimis' => 'Générer les matricules CIMIS',
                    'POST /api_siadoc.php?action=generer_qr_codes' => 'Générer les QR codes',
                    'POST /api_siadoc.php?action=enregistrer_en_base' => 'Enregistrer en base CIMIS',
                    'POST /api_siadoc.php?action=envoyer_biometrie_massive' => 'Envoyer les biométries à SIADOC',
                    'GET /api_siadoc.php?action=get_historique' => 'Historique des opérations',
                    'GET /api_siadoc.php?action=stats' => 'Statistiques CIMIS'
                ],
                'filters' => [
                    'grade' => 'Filtrer par grade militaire',
                    'unite' => 'Filtrer par unité (ARMÉE DE TERRE, ARMÉE DE L\'AIR, etc.)',
                    'annee_entree' => 'Filtrer par année d\'entrée en service',
                    'annee_galon' => 'Filtrer par année du dernier galon'
                ],
                'examples' => [
                    'Rechercher un militaire SIADOC' => 'GET /api_siadoc.php?action=get_militaire&matricule=T14/6584',
                    'Lister tout le personnel SIADOC' => 'GET /api_siadoc.php?action=get_all_militaires',
                    'Envoyer biométrie à SIADOC' => 'POST /api_siadoc.php?action=send_biometrie_to_siadoc avec {"data":{"matricule_militaire":"T14/6584","matricule_cimis":"CIM-12345",...}}',
                    'Filtrer par grade' => 'GET /api_siadoc.php?action=get_militaires_filtres&grade=SERGENT',
                    'Filtrer par unité' => 'GET /api_siadoc.php?action=get_militaires_filtres&unite=ARMÉE DE TERRE',
                    'Filtres combinés' => 'GET /api_siadoc.php?action=get_militaires_filtres&grade=CAPITAINE&unite=GENDARMERIE&annee_entree=2023'
                ],
                'siadoc_api_info' => [
                    'base_url' => 'https://siadoc.onrender.com',
                    'api_key' => 'siadoc-2026-cimis-integration',
                    'direct_endpoints' => [
                        'GET /api/export/militaire/info?matricule=XXX' => 'API SIADOC directe - récupérer militaire',
                        'GET /api/export/militaire/info/all' => 'API SIADOC directe - lister personnel',
                        'POST /api/cimis/recevoir_carte' => 'API SIADOC directe - recevoir biométrie'
                    ]
                ]
            ]);
            break;

        case 'stats':
            // Statistiques CIMIS
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_militaires,
                        COUNT(CASE WHEN statut_carte = 'ACTIVE' THEN 1 END) as cartes_generees,
                        COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc
                    FROM candidat 
                    WHERE type_personnel = 'MILITAIRE'
                ");
                $stmt->execute();
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as envois_siadoc 
                    FROM api_sync_log 
                    WHERE system LIKE 'SIADOC%'
                ");
                $stmt->execute();
                $envois = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendSuccessResponse([
                    'total_militaires' => (int)$stats['total_militaires'],
                    'cartes_generees' => (int)$stats['cartes_generees'],
                    'venus_de_siadoc' => (int)$stats['venus_de_siadoc'],
                    'envois_siadoc' => (int)$envois['envois_siadoc']
                ]);
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la récupération des stats: ' . $e->getMessage());
            }
            break;

        case 'generer_matricules_cimis':
            // Générer les matricules CIMIS pour les militaires sélectionnés
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
                break;
            }
            
            try {
                $resultats = [];
                
                foreach ($input['matricules'] as $matricule) {
                    // Vérifier si existe déjà dans CIMIS
                    $stmt = $pdo->prepare("SELECT matricule FROM candidat WHERE matricule_militaire = ?");
                    $stmt->execute([$matricule]);
                    $existing = $stmt->fetch();
                    
                    if (!$existing) {
                        // Générer matricule CIMIS
                        $matricule_cimis = generateCIMISMatricule();
                        
                        // Mettre à jour
                        $stmt = $pdo->prepare("UPDATE candidat SET matricule = ? WHERE matricule_militaire = ?");
                        $stmt->execute([$matricule_cimis, $matricule]);
                        
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => $matricule_cimis,
                            'statut' => 'success',
                            'message' => 'Matricule CIMIS généré'
                        ];
                    } else {
                        $stmt = $pdo->prepare("SELECT matricule FROM candidat WHERE matricule_militaire = ?");
                        $stmt->execute([$matricule]);
                        $existing_matricule = $stmt->fetch();
                        
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => $existing_matricule['matricule'],
                            'statut' => 'exists',
                            'message' => 'Matricule CIMIS existe déjà'
                        ];
                    }
                }
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules'])
                ], 'Génération des matricules CIMIS terminée');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la génération: ' . $e->getMessage());
            }
            break;

        case 'generer_qr_codes':
            // Générer les QR codes pour les militaires sélectionnés
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
                break;
            }
            
            try {
                $resultats = [];
                
                foreach ($input['matricules'] as $matricule) {
                    // Récupérer le matricule CIMIS
                    $stmt = $pdo->prepare("SELECT matricule FROM candidat WHERE matricule_militaire = ?");
                    $stmt->execute([$matricule]);
                    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($candidat && $candidat['matricule']) {
                        // Générer QR code
                        $qr_data = generateQRCode($matricule, $candidat['matricule']);
                        
                        // Mettre à jour
                        $stmt = $pdo->prepare("UPDATE candidat SET code_qr = ? WHERE matricule_militaire = ?");
                        $stmt->execute([$qr_data['image_path'], $matricule]);
                        
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => $candidat['matricule'],
                            'qr_code' => $qr_data['image_path'],
                            'statut' => 'success',
                            'message' => 'QR code généré'
                        ];
                    } else {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'statut' => 'not_found',
                            'message' => 'Militaire non trouvé dans CIMIS'
                        ];
                    }
                }
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules'])
                ], 'Génération des QR codes terminée');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la génération: ' . $e->getMessage());
            }
            break;

        case 'enregistrer_en_base':
            // Enregistrer les militaires de SIADOC en base CIMIS
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
                break;
            }
            
            try {
                $resultats = [];
                $succes = 0;
                $erreurs = 0;
                
                foreach ($input['matricules'] as $matricule) {
                    // Appeler SIADOC pour récupérer les infos du militaire
                    $siadoc_result = callSIADOCAPI('export/militaire/info', [
                        'matricule' => $matricule
                    ]);
                    
                    if ($siadoc_result['http_code'] === 200 && $siadoc_result['data']) {
                        $militaire = $siadoc_result['data'];
                        
                        // Vérifier si existe déjà dans CIMIS
                        $stmt = $pdo->prepare("SELECT id FROM candidat WHERE matricule_militaire = ?");
                        $stmt->execute([$matricule]);
                        $existing = $stmt->fetch();
                        
                        if (!$existing) {
                            // Générer matricule CIMIS et QR code
                            $matricule_cimis = generateCIMISMatricule();
                            $qr_data = generateQRCode($matricule, $matricule_cimis);
                            
                            // Insérer dans CIMIS
                            $stmt = $pdo->prepare("
                                INSERT INTO candidat (
                                    matricule, matricule_militaire, nom, prenom, 
                                    date_naissance, sexe, grade, unite, 
                                    code_qr, source_system, date_enrolement, type_personnel,
                                    statut_carte, supprimer
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'MILITAIRE', 'ACTIVE', 1)
                            ");
                            
                            $stmt->execute([
                                $matricule_cimis,
                                $matricule,
                                strtoupper($militaire['nom']),
                                ucfirst(strtolower($militaire['prenom'])),
                                $militaire['dateNaissance'],
                                strtoupper($militaire['sexe']) === 'M' ? 'MASCULIN' : 'FEMININ',
                                strtoupper($militaire['grade']),
                                $militaire['corps'],
                                $qr_data['image_path'],
                                'SIADOC'
                            ]);
                            
                            $resultats[] = [
                                'matricule' => $matricule,
                                'matricule_cimis' => $matricule_cimis,
                                'statut' => 'success',
                                'message' => 'Enregistré avec succès'
                            ];
                            $succes++;
                        } else {
                            $resultats[] = [
                                'matricule' => $matricule,
                                'statut' => 'exists',
                                'message' => 'Déjà existant dans CIMIS'
                            ];
                            $erreurs++;
                        }
                    } else {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'statut' => 'not_found',
                            'message' => 'Non trouvé dans SIADOC'
                        ];
                        $erreurs++;
                    }
                }
                
                // Logger l'enregistrement
                $stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, action, status, details, last_sync) 
                    VALUES ('SIADOC_ENREGISTREMENT', 'ENREGISTREMENT_BULK', 'SUCCESS', ?, NOW())
                ");
                $stmt->execute([json_encode(['total' => count($input['matricules']), 'succes' => $succes, 'erreurs' => $erreurs])]);
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules']),
                    'succes' => $succes,
                    'erreurs' => $erreurs
                ], 'Enregistrement en base terminé');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'enregistrement: ' . $e->getMessage());
            }
            break;

        case 'envoyer_biometrie_massive':
            // Envoyer les données biométriques à SIADOC
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
                break;
            }
            
            try {
                $resultats = [];
                $succes = 0;
                $erreurs = 0;
                
                foreach ($input['matricules'] as $matricule) {
                    // Récupérer les données CIMIS
                    $stmt = $pdo->prepare("
                        SELECT matricule, matricule_militaire, nom, prenom, photo, code_qr, empreinte_data
                        FROM candidat 
                        WHERE matricule_militaire = ? AND statut_carte = 'ACTIVE'
                    ");
                    $stmt->execute([$matricule]);
                    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($candidat) {
                        // Préparer les données biométriques
                        $photo_base64 = null;
                        if ($candidat['photo'] && file_exists('../' . $candidat['photo'])) {
                            $photo_base64 = encodeImageToBase64Raw('../' . $candidat['photo']);
                        }
                        
                        $qr_base64 = null;
                        if ($candidat['code_qr'] && file_exists('../' . $candidat['code_qr'])) {
                            $qr_base64 = encodeImageToBase64Raw('../' . $candidat['code_qr']);
                        }
                        
                        $payload = [
                            'matricule' => $candidat['matricule_militaire'],
                            'numeroCIM' => $candidat['matricule'],
                            'photoVisage' => $photo_base64,
                            'photoVisageType' => $photo_base64 ? 'image/jpeg' : null,
                            'empreinteDoigt1' => $candidat['empreinte_data'],
                            'empreinteDoigt1Type' => $candidat['empreinte_data'] ? 'image/png' : null,
                            'empreinteDoigt2' => null,
                            'empreinteDoigt2Type' => null,
                            'qrCodeImage' => $qr_base64,
                            'qrCodeContenu' => 'https://cimis.cm/verify/' . $candidat['matricule_militaire']
                        ];
                        
                        // Envoyer à SIADOC
                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => SIADOC_API_URL . 'import/cimis/biometrie',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => json_encode($payload),
                            CURLOPT_HTTPHEADER => [
                                'X-API-KEY: ' . SIADOC_API_KEY,
                                'Content-Type: application/json'
                            ],
                            CURLOPT_SSL_VERIFYPEER => true,
                            CURLOPT_TIMEOUT => 30
                        ]);
                        
                        $response = curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => $candidat['matricule'],
                            'http_code' => $http_code,
                            'response' => trim($response),
                            'statut' => $http_code === 200 ? 'sent' : 'error'
                        ];
                        
                        if ($http_code === 200) {
                            $succes++;
                        } else {
                            $erreurs++;
                        }
                    } else {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'statut' => 'not_found',
                            'message' => 'Militaire non trouvé dans CIMIS'
                        ];
                        $erreurs++;
                    }
                }
                
                // Logger l'envoi
                $stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, action, status, details, last_sync) 
                    VALUES ('SIADOC_ENVOI_BIOMETRIE', 'ENVOI_BIOMETRIE_BULK', 'SUCCESS', ?, NOW())
                ");
                $stmt->execute([json_encode(['total' => count($input['matricules']), 'succes' => $succes, 'erreurs' => $erreurs])]);
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules']),
                    'succes' => $succes,
                    'erreurs' => $erreurs
                ], 'Envoi biométrie massif terminé');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'envoi: ' . $e->getMessage());
            }
            break;

        default:
            sendErrorResponse('Action non reconnue', 404);
            break;
    }
} else {
    sendErrorResponse('Endpoint non trouvé. Veuillez spécifier une action valide.', 404);
}
?>
