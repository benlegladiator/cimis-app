<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../backend/config.php';

// Récupérer les informations de l'utilisateur
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Traitement de la restauration
if (isset($_POST['action']) && $_POST['action'] == 'restore') {
    try {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            // Construire les placeholders nommés
            $placeholders = [];
            foreach ($ids as $index => $id) {
                $placeholders[] = ":id$index";
            }
            $placeholders_str = implode(',', $placeholders);

            // Restaurer les cartes (supprimer = 1 = actif)
            $sql = "UPDATE candidat 
                    SET supprimer = 1, supprimer_par = NULL, date_suppression = NULL 
                    WHERE id IN ($placeholders_str) AND supprimer_par = :username";

            $stmt = $pdo->prepare($sql);

            // Binder les IDs
            foreach ($ids as $index => $id) {
                $stmt->bindValue(":id$index", $id, PDO::PARAM_INT);
            }
            $stmt->bindValue(":username", $username, PDO::PARAM_STR);

            $stmt->execute();

            $restoredCount = $stmt->rowCount();
            $_SESSION['success'] = "$restoredCount carte(s) restaurée(s) avec succès / $restoredCount card(s) restored successfully";
        } else {
            $_SESSION['error'] = "Aucune carte sélectionnée pour la restauration / No card selected for restoration";
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors de la restauration / Error during restoration: " . $e->getMessage();
    }
    
    header('Location: corbeille.php');
    exit;
}

// Récupérer les cartes supprimées par l'utilisateur
try {
    $sql = "SELECT id, matricule, nom, prenom, unite, grade, photo, numero_cni, date_dernier_grade, date_suppression 
            FROM candidat 
            WHERE supprimer = 0 AND supprimer_par = :username 
            ORDER BY date_suppression DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $deletedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $deletedCards = [];
    $_SESSION['error'] = "Erreur lors du chargement de la corbeille / Error loading trash: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille - CIMIS</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === IMPORTATIONS === */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        /* === VARIABLES CSS === */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --danger-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --dark-bg: #0f0f23;
            --card-bg: rgba(255, 255, 255, 0.05);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #b8bcc8;
            --accent: #667eea;
            --shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.3);
            --shadow-hard: 0 20px 40px rgba(0, 0, 0, 0.4);
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
            background: var(--danger-gradient);
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
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header-text p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .user-info i {
            color: var(--accent);
        }
        
        .back-btn {
            padding: 0.75rem 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .back-btn:hover {
            background: var(--primary-gradient);
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }
        
        /* === MAIN CONTENT === */
        .premium-main {
            grid-area: main;
            padding: 3rem;
            overflow-y: auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--danger-gradient);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hard);
            border-color: var(--accent);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--danger-gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-soft);
        }
        
        .stat-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* === ACTIONS BAR === */
        .actions-bar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: var(--dark-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box i {
            color: var(--text-secondary);
            margin-right: 1rem;
        }
        
        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            flex: 1;
        }
        
        .search-box input::placeholder {
            color: var(--text-secondary);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .premium-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .premium-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            transition: left 0.3s ease;
            z-index: -1;
        }
        
        .btn-select {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }
        
        .btn-select::before {
            background: var(--primary-gradient);
        }
        
        .btn-select:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }
        
        .btn-restore {
            background: var(--success-gradient);
            color: white;
        }
        
        .btn-restore::before {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn-restore:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }
        
        /* === CARDS GRID === */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .trash-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            backdrop-filter: blur(10px);
        }
        
        .trash-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--danger-gradient);
        }
        
        .trash-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hard);
            border-color: var(--accent);
        }
        
        .trash-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-checkbox {
            width: 24px;
            height: 24px;
            accent-color: var(--accent);
            cursor: pointer;
        }
        
        .card-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(245, 87, 108, 0.1);
            border: 1px solid rgba(245, 87, 108, 0.3);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #f5576c;
        }
        
        .card-status i {
            font-size: 0.7rem;
        }
        
        .trash-card-body {
            padding: 1.5rem;
        }
        
        .card-info {
            display: grid;
            gap: 1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--dark-bg);
            border-radius: 12px;
            border: 1px solid var(--card-border);
        }
        
        .info-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .info-value {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .card-actions {
            padding: 1.5rem;
            border-top: 1px solid var(--card-border);
            display: flex;
            gap: 1rem;
        }
        
        .card-action-btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-view {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }
        
        .btn-view:hover {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }
        
        .btn-restore-single {
            background: var(--success-gradient);
            color: white;
        }
        
        .btn-restore-single:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }
        
        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .empty-icon {
            width: 100px;
            height: 100px;
            background: var(--card-bg);
            border: 2px solid var(--card-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: var(--shadow-soft);
        }
        
        .empty-icon i {
            font-size: 2.5rem;
            color: var(--text-secondary);
        }
        
        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .empty-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        /* === NOTIFICATIONS === */
        .notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            box-shadow: var(--shadow-hard);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification.success {
            background: var(--success-gradient);
            color: white;
        }
        
        .notification.error {
            background: var(--danger-gradient);
            color: white;
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 1024px) {
            .premium-layout {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "header"
                    "main"
                    "footer";
            }
            
            .premium-sidebar {
                display: none;
            }
            
            .premium-header {
                padding: 1.5rem;
            }
            
            .premium-main {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-bar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-box {
                max-width: 100%;
            }
        }
        
        }
        
        .trash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .trash-item {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(220, 53, 69, 0.05));
            border: 2px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 2rem;
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .trash-item:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(220, 53, 69, 0.08));
            border-color: rgba(220, 53, 69, 0.7);
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 30px rgba(220, 53, 69, 0.2);
        }
        
        .trash-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(220, 53, 69, 0.1), transparent);
            border-radius: 15px;
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }
        
        .trash-item:hover::before {
            opacity: 1;
        }
        
        .trash-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .trash-item-info {
            flex: 1;
        }
        
        .trash-item-name {
            font-weight: bold;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .trash-item-details {
            font-size: 0.9rem;
            color: #ccc;
        }
        
        .trash-item-date {
            font-size: 0.8rem;
            color: var(--neon-red);
            margin-top: 0.5rem;
        }
        
        .trash-checkbox {
            margin-right: 1rem;
            transform: scale(1.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .trash-checkbox:hover {
            transform: scale(1.4);
        }
        
        .trash-checkbox:checked {
            accent-color: #28a745;
        }
        
        .restore-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .restore-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .restore-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .restore-btn:hover::before {
            left: 100%;
        }
        
        .restore-btn:active {
            transform: translateY(-1px) scale(0.98);
        }
        
        .batch-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:active {
            transform: translateY(-1px) scale(0.98);
        }
        
        .empty-trash {
            text-align: center;
            padding: 3rem;
            color: #ccc;
        }
        
        .empty-trash i {
            font-size: 4rem;
            color: var(--neon-red);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="premium-layout">
        <!-- SIDEBAR -->
        <aside class="premium-sidebar">
            <div class="sidebar-logo">
                <i class="fa-solid fa-trash-can"></i>
                <div class="sidebar-title">CORBEILLE</div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fa-solid fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="impression.php" class="nav-item">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Impression</span>
                </a>
                <a href="corbeille.php" class="nav-item active">
                    <i class="fa-solid fa-trash"></i>
                    <span>Corbeille</span>
                </a>
                <a href="securite_admin.php" class="nav-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Sécurité</span>
                </a>
            </nav>
        </aside>

        <!-- HEADER -->
        <header class="premium-header">
            <div class="header-title-section">
                <div class="header-icon">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <div class="header-text">
                    <h1>Corbeille</h1>
                    <p>Cartes supprimées par l'utilisateur</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <i class="fa-solid fa-user"></i>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="dashboard.php" class="back-btn">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="premium-main">

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-toast" style="position: fixed; top: 20px; right: 20px; background: var(--neon-green); color: black; padding: 1rem; border-radius: 5px; z-index: 9999;">
                        <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-toast" style="position: fixed; top: 20px; right: 20px; background: var(--neon-red); color: white; padding: 1rem; border-radius: 5px; z-index: 9999;">
                        <i class="fa-solid fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="trash-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($deletedCards); ?></div>
                        <div>Cartes dans la corbeille / Cards in trash</div>
                    </div>
                </div>

                <?php if (!empty($deletedCards)): ?>
                    <div class="actions-bar">
                        <div class="search-box">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" placeholder="Rechercher une carte..." id="searchInput" onkeyup="filterCards()">
                        </div>
                        <div class="action-buttons">
                            <button type="button" class="premium-btn btn-select" onclick="selectAll()">
                                <i class="fa-solid fa-check-square"></i>
                                <span>Sélectionner tout</span>
                            </button>
                            <form method="POST" id="restoreForm" style="display: contents;">
                                <input type="hidden" name="action" value="restore">
                                <button type="submit" class="premium-btn btn-restore">
                                    <i class="fa-solid fa-undo"></i>
                                    <span>Restaurer</span>
                                </button>
                            </form>
                        </div>
                    </div>

                        <div class="trash-grid">
                            <?php foreach ($deletedCards as $card): ?>
                                <div class="trash-item">
                                    <div class="trash-item-header">
                                        <input type="checkbox" name="ids[]" value="<?php echo $card['id']; ?>" class="trash-checkbox">
                                        <div class="trash-item-info">
                                            <div class="trash-item-name">
                                                <?php echo htmlspecialchars($card['nom'] . ' ' . $card['prenom']); ?>
                                            </div>
                                            <div class="trash-item-details">
                                                <i class="fa-solid fa-id-badge"></i> <?php echo htmlspecialchars($card['matricule']); ?>
                                                <br>
                                                <i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($card['grade']); ?>
                                                <br>
                                                <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($card['unite']); ?>
                                            </div>
                                            <div class="trash-item-date">
                                                <i class="fa-solid fa-clock"></i> Supprimé le / Deleted on: <?php echo date('d/m/Y H:i', strtotime($card['date_suppression'])); ?>
                                            </div>
                                        </div>
                                        <button type="button" class="restore-btn" onclick="restoreSingle(<?php echo $card['id']; ?>)">
                                            <i class="fa-solid fa-undo"></i> RESTAURER
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-trash">
                        <i class="fa-solid fa-trash-can"></i>
                        <h3>Corbeille vide / Empty trash</h3>
                        <p>Aucune carte supprimée / No deleted cards</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script>
        // === FONCTIONS PRINCIPALES === */
        
        // Fonction pour sélectionner/désélectionner toutes les cartes
        function selectAll() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const selectAllBtn = document.querySelector('button[onclick="selectAll()"]');
            
            if (selectAllBtn.textContent.includes('Sélectionner')) {
                checkboxes.forEach(cb => cb.checked = true);
                selectAllBtn.innerHTML = '<i class="fa-solid fa-check-square"></i><span>Désélectionner tout</span>';
                updateSelectionCount();
            } else {
                checkboxes.forEach(cb => cb.checked = false);
                selectAllBtn.innerHTML = '<i class="fa-solid fa-check-square"></i><span>Sélectionner tout</span>';
                updateSelectionCount();
            }
        }
        
        // Fonction pour restaurer une carte spécifique
        function restoreCard(id) {
            if (confirm('Voulez-vous restaurer cette carte ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="restore"><input type="hidden" name="ids[]" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Fonction pour voir les détails d'une carte
        function viewCard(id) {
            window.open('visualiser_carte.php?matricule=' + id, '_blank');
        }
        
        // Fonction pour filtrer les cartes
        function filterCards() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.trash-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'block';
                    // Animation d'apparition
                    card.style.animation = 'slideIn 0.3s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Fonction pour mettre à jour le compteur de sélection
        function updateSelectionCount() {
            const checkedBoxes = document.querySelectorAll('input[name="ids[]"]:checked');
            const count = checkedBoxes.length;
            
            // Mettre à jour le texte du bouton de restauration
            const restoreBtn = document.querySelector('.btn-restore');
            if (restoreBtn) {
                if (count > 0) {
                    restoreBtn.innerHTML = `<i class="fa-solid fa-undo"></i><span>Restaurer (${count})</span>`;
                    restoreBtn.disabled = false;
                } else {
                    restoreBtn.innerHTML = '<i class="fa-solid fa-undo"></i><span>Restaurer</span>';
                    restoreBtn.disabled = true;
                }
            }
        }
        
        // Fonction pour ajouter des animations aux cartes
        function addCardAnimations() {
            const cards = document.querySelectorAll('.trash-card');
            cards.forEach((card, index) => {
                // Animation d'entrée différée
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
        
        // Fonction pour gérer les notifications
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-suppression après 3 secondes
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Gestionnaire d'événements
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter les animations aux cartes
            addCardAnimations();
            
            // Gestion des changements de checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.type === 'checkbox' && e.target.name === 'ids[]') {
                    updateSelectionCount();
                    
                    // Animation de la carte cochée
                    const card = e.target.closest('.trash-card');
                    if (card) {
                        if (e.target.checked) {
                            card.style.borderColor = 'var(--accent)';
                        } else {
                            card.style.borderColor = 'var(--card-border)';
                        }
                    }
                }
            });
            
            // Animation des notifications existantes
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach((notification, index) => {
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, (index + 1) * 1000);
            });
            
            // Initialiser le compteur
            updateSelectionCount();
        });
        
        // Styles CSS dynamiques pour les animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Clock
        setInterval(() => {
            const now = new Date();
            const clockElement = document.getElementById('clock');
            if (clockElement) {
                clockElement.innerText = now.toLocaleTimeString('fr-FR');
            }
        }, 1000);

        // Particle system
        function initParticles() {
            const canvas = document.getElementById('particles-canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const particles = [];
            
            class Particle {
                constructor() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 2;
                    this.speedX = (Math.random() - 0.5) * 0.5;
                    this.speedY = (Math.random() - 0.5) * 0.5;
                    this.opacity = Math.random() * 0.5;
                }
                update() {
                    this.x += this.speedX;
                    this.y += this.speedY;
                    if (this.opacity > 0) this.opacity -= 0.002;
                    if (this.opacity <= 0) {
                        this.x = Math.random() * canvas.width;
                        this.y = Math.random() * canvas.height;
                        this.opacity = Math.random() * 0.5;
                    }
                }
                draw() {
                    ctx.fillStyle = `rgba(10, 255, 186, ${this.opacity})`;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
            
            for (let i = 0; i < 100; i++) particles.push(new Particle());
            
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => {
                    p.update();
                    p.draw();
                });
                requestAnimationFrame(animate);
            }
            animate();
        }

        initParticles();

        // Select all checkboxes
        function selectAll() {
            const checkboxes = document.querySelectorAll('.trash-checkbox');
            const selectAllBtn = event.target;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !checkbox.checked;
            });
            
            selectAllBtn.innerHTML = checkboxes[0].checked ? 
                '<i class="fa-solid fa-square"></i> DÉSÉLECTIONNER TOUT / DESELECT ALL' : 
                '<i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL';
        }

        // Restore single card
        function restoreSingle(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="ids[]" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.success-toast, .error-toast').forEach(toast => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
