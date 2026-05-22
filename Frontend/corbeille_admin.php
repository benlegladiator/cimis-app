<?php
session_start();

// Vérifier si l'utilisateur est SUPER_ADMIN
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['role'] !== 'SUPER_ADMIN') {
    header('Location: dashboard.php');
    exit;
}

require_once '../backend/config.php';

// Traitement de la restauration
if (isset($_POST['action']) && $_POST['action'] == 'restore') {
    try {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $placeholders = str_repeat('?,', count($ids));
            $placeholders = rtrim($placeholders, ',');
            
            // Restaurer les cartes
            $sql = "UPDATE candidat SET supprimer = 1, supprimer_par = NULL, date_suppression = NULL WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            
            $_SESSION['success'] = count($ids) . " carte(s) restaurée(s) avec succès";
        } else {
            $_SESSION['error'] = "Aucune carte sélectionnée pour la restauration";
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors de la restauration: " . $e->getMessage();
    }
    
    header('Location: corbeille_admin_new.php');
    exit;
}

// Traitement du vidage complet de la corbeille
if (isset($_POST['action']) && $_POST['action'] == 'empty_trash') {
    try {
        // Suppression réelle de toutes les cartes dans la corbeille
        $stmt = $pdo->prepare("DELETE FROM candidat WHERE supprimer = 0");
        $stmt->execute();
        $count = $stmt->rowCount();
        
        $_SESSION['success'] = $count . " carte(s) supprimée(s) définitivement";
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors du vidage de la corbeille: " . $e->getMessage();
    }
    
    header('Location: corbeille_admin.php');
    exit;
}

// Récupérer toutes les cartes supprimées
try {
    $sql = "SELECT id, matricule, nom, prenom, unite, grade, photo, numero_cni, date_dernier_grade, date_suppression, supprimer_par 
            FROM candidat 
            WHERE supprimer = 0 
            ORDER BY date_suppression DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $deletedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $deletedCards = [];
    $_SESSION['error'] = "Erreur lors du chargement de la corbeille: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration Corbeille - CIMIS</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === IMPORTATIONS === */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        /* === VARIABLES CSS === */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --danger-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --warning-gradient: linear-gradient(135deg, #32ff7e 0%, #00d9ff 100%);
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
            background: var(--warning-gradient);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .admin-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: var(--warning-gradient);
            border-radius: 12px;
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-soft);
        }
        
        .admin-badge i {
            font-size: 1rem;
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
            background: var(--warning-gradient);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hard);
            border-color: var(--accent);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--warning-gradient);
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
        
        .btn-danger {
            background: var(--danger-gradient);
            color: white;
        }
        
        .btn-danger::before {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft);
        }
        
        /* === CARDS GRID === */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
        }
        
        .admin-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            backdrop-filter: blur(10px);
        }
        
        .admin-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--warning-gradient);
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hard);
            border-color: var(--accent);
        }
        
        .admin-card-header {
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
            background: rgba(250, 112, 154, 0.1);
            border: 1px solid rgba(250, 112, 154, 0.3);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #667eea;
        }
        
        .card-status i {
            font-size: 0.7rem;
        }
        
        .admin-card-body {
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
    </style>
</head>
<body>
    <div class="premium-layout">
        <!-- SIDEBAR -->
        <aside class="premium-sidebar">
            <div class="sidebar-logo">
                <i class="fa-solid fa-shield-halved"></i>
                <div class="sidebar-title">ADMIN</div>
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
                <a href="corbeille.php" class="nav-item">
                    <i class="fa-solid fa-trash"></i>
                    <span>Ma corbeille</span>
                </a>
                <a href="corbeille_admin_new.php" class="nav-item active">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Admin corbeille</span>
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
                    <h1>Administration Corbeille</h1>
                    <p>Gestion globale des cartes supprimées</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="admin-badge">
                    <i class="fa-solid fa-crown"></i>
                    <span>SUPER_ADMIN</span>
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
                <div class="notification success">
                    <i class="fa-solid fa-check-circle"></i>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-trash"></i>
                    </div>
                    <div class="stat-value"><?php echo count($deletedCards); ?></div>
                    <div class="stat-label">Cartes supprimées</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo count(array_unique(array_column($deletedCards, 'supprimer_par'))); ?></div>
                    <div class="stat-label">Utilisateurs concernés</div>
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
                        <form method="POST" id="emptyTrashForm" style="display: contents;" onsubmit="return confirmEmptyTrash()">
                            <input type="hidden" name="action" value="empty_trash">
                            <button type="submit" class="premium-btn btn-danger">
                                <i class="fa-solid fa-trash-alt"></i>
                                <span>Vider corbeille</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="cards-grid">
                    <?php foreach ($deletedCards as $card): ?>
                        <div class="admin-card" data-id="<?php echo $card['id']; ?>">
                            <div class="admin-card-header">
                                <input type="checkbox" class="card-checkbox" name="ids[]" value="<?php echo $card['id']; ?>">
                                <div class="card-status">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Supprimé</span>
                                </div>
                            </div>
                            <div class="admin-card-body">
                                <div class="card-info">
                                    <div class="info-row">
                                        <span class="info-label">Nom complet</span>
                                        <span class="info-value"><?php echo htmlspecialchars($card['nom'] . ' ' . $card['prenom']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Matricule</span>
                                        <span class="info-value"><?php echo htmlspecialchars($card['matricule']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Unité</span>
                                        <span class="info-value"><?php echo htmlspecialchars($card['unite']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Grade</span>
                                        <span class="info-value"><?php echo htmlspecialchars($card['grade']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Supprimé par</span>
                                        <span class="info-value"><?php echo htmlspecialchars($card['supprimer_par']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Date suppression</span>
                                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($card['date_suppression'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-actions">
                                <button type="button" class="card-action-btn btn-view" onclick="viewCard(<?php echo $card['id']; ?>)">
                                    <i class="fa-solid fa-eye"></i>
                                    <span>Voir</span>
                                </button>
                                <button type="button" class="card-action-btn btn-restore-single" onclick="restoreCard(<?php echo $card['id']; ?>)">
                                    <i class="fa-solid fa-undo"></i>
                                    <span>Restaurer</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <div class="empty-title">Corbeille vide</div>
                    <div class="empty-text">Aucune carte supprimée pour le moment. Les cartes supprimées par les utilisateurs apparaîtront ici.</div>
                </div>
            <?php endif; ?>
        </main>

        <!-- FOOTER -->
        <footer class="premium-footer">
            <div class="footer-content">
                <div class="footer-info">
                    <span>&copy; 2026 CIMIS - Administration Système</span>
                </div>
                <div class="footer-actions">
                    <span class="footer-text">SUPER_ADMIN MODE</span>
                </div>
            </div>
        </footer>
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
            const cards = document.querySelectorAll('.admin-card');
            
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
        
        // Fonction pour confirmer le vidage de la corbeille
        function confirmEmptyTrash() {
            return confirm('ATTENTION ! Cette action supprimera DÉFINITIVEMENT toutes les cartes dans la corbeille. Cette action est irréversible.\n\nVoulez-vous continuer ?');
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
            const cards = document.querySelectorAll('.admin-card');
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
        
        // Gestionnaire d'événements
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter les animations aux cartes
            addCardAnimations();
            
            // Gestion des changements de checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.type === 'checkbox' && e.target.name === 'ids[]') {
                    updateSelectionCount();
                    
                    // Animation de la carte cochée
                    const card = e.target.closest('.admin-card');
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
    </script>
</body>
</html>
