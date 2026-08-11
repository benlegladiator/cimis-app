<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../backend/config.php';
require_once '../Carte/confection_carte.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Traitement AJAX pour la prévisualisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'preview':
            $matricule = $_POST['matricule'] ?? '';
            
            if (!empty($matricule)) {
                $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule = :matricule");
                $stmt->execute(['matricule' => $matricule]);
                $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($candidat) {
                    echo renderCarte($candidat);
                } else {
                    echo '<div class="preview-error">Candidat non trouvé</div>';
                }
            }
            exit;
            
        case 'clear_session':
            unset($_SESSION['cartes_confectionnees']);
            $_SESSION['success'] = 'Session des cartes vidée avec succès';
            header('Location: visualiser_carte.php');
            exit;
    }
}

// Récupération des cartes confectionnées depuis la session
$cartes_confectionnees = $_SESSION['cartes_confectionnees'] ?? [];

// Si pas de cartes en session, essayer de récupérer depuis l'URL
if (empty($cartes_confectionnees)) {
    $matricules = $_GET['matricules'] ?? (isset($_GET['matricule']) ? [$_GET['matricule']] : []);
    if (!is_array($matricules)) {
        $matricules = explode(',', $matricules);
    }
    
    if (!empty($matricules)) {
        $placeholders = implode(',', array_fill(0, count($matricules), '?'));
        $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule IN ($placeholders)");
        $stmt->execute($matricules);
        $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Générer les cartes à la volée
        foreach ($candidats as $candidat) {
            $cartes_confectionnees[] = [
                'candidat' => $candidat,
                'carte_html' => renderCarte($candidat)
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation Carte(s) / Card(s) Visualization - CIMIS</title>
    <link rel="stylesheet" href="../css/enrolement.css">
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="../css/bouton-retour.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .visualization-container {
            background: rgba(0, 0, 0, 0.05);
            min-height: 100vh;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 15px;
            padding: 1rem;
            margin: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            color: var(--neon-green);
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .cards-wrapper {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }
        
        .candidat-header {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            color: var(--neon-green);
            font-weight: bold;
        }
        
        @media print {
            .header, .actions {
                display: none;
            }
            
            .visualization-container {
                background: white;
                padding: 10mm;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content {
                flex-direction: column;
                gap: 2rem;
                text-align: center;
            }
            
            .hero-logo {
                width: 180px;
                height: 180px;
            }
            
            .hero-text h1 {
                font-size: 2.5rem;
            }
            
            .hero-text h2 {
                font-size: 1.2rem;
            }
            
            .top-status-bar {
                flex-direction: column;
                gap: 0.5rem;
                padding: 0.75rem;
            }
            
            .status-left, .status-right {
                width: 100%;
                justify-content: center;
            }
            
            .status-item {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
            }
            
            .cards-wrapper {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 1.5rem;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-content {
                gap: 1.5rem;
                padding: 0 1rem;
            }
            
            .hero-logo {
                width: 150px;
                height: 150px;
            }
            
            .hero-text h1 {
                font-size: 2rem;
            }
            
            .hero-text h2 {
                font-size: 1rem;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .cards-wrapper {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .header {
                padding: 0.75rem;
            }
            
            .candidat-header {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .btn-back {
                font-size: 0.85rem;
                padding: 0.75rem 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content {
                gap: 1rem;
                padding: 0 0.5rem;
            }
            
            .hero-logo {
                width: 120px;
                height: 120px;
            }
            
            .hero-text h1 {
                font-size: 1.6rem;
            }
            
            .hero-text h2 {
                font-size: 0.9rem;
            }
            
            .main-content {
                padding: 0.5rem;
            }
            
            .cards-wrapper {
                gap: 0.75rem;
            }
            
            .header {
                padding: 0.5rem;
            }
            
            .candidat-header {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            
            .btn-back {
                font-size: 0.8rem;
                padding: 0.625rem 0.875rem;
            }
            
            .empty-state h3 {
                font-size: 1.2rem;
            }
            
            .empty-state p {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 360px) {
            .hero-logo {
                width: 100px;
                height: 100px;
            }
            
            .hero-text h1 {
                font-size: 1.4rem;
            }
            
            .hero-text h2 {
                font-size: 0.8rem;
            }
            
            .candidat-header {
                padding: 0.375rem;
                font-size: 0.75rem;
            }
            
            .btn-back {
                font-size: 0.75rem;
                padding: 0.5rem 0.75rem;
            }
        }
        
        @media (orientation: landscape) and (max-height: 600px) {
            .hero-section {
                padding: 1rem 0;
            }
            
            .hero-content {
                gap: 1rem;
            }
            
            .hero-logo {
                width: 120px;
                height: 120px;
            }
            
            .hero-text h1 {
                font-size: 1.8rem;
                margin-bottom: 0.5rem;
            }
            
            .hero-text h2 {
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }
            
            .main-content {
                padding: 1rem 0;
            }
            
            .cards-wrapper {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (prefers-reduced-motion: reduce) {
            .hero-logo {
                animation: none;
            }
            
            .btn-back:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- TOP STATUS BAR -->
        <div class="top-status-bar">
            <div class="status-left">
                <span class="status-item warning-flash"><i class="fa-solid fa-triangle-exclamation"></i> SYSTÈME CLASSÉ SECRET DÉFENSE / CLASSIFIED DEFENSE SYSTEM</span>
                <span class="status-item"><i class="fa-solid fa-globe"></i> RÉSEAU SÉCURISÉ / SECURED NETWORK</span>
                <span class="status-item"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="status-right">
                <span id="clock" class="text-mono">12:00:00</span>
                <a href="../logout.php" class="btn-logout-styled">
                    <i class="fa-solid fa-power-off"></i> DÉCONNEXION / LOGOUT
                </a>
            </div>
        </div>

        <!-- HERO SECTION -->
        <div class="hero-section">
            <div class="hero-content">
                <img src="../img/cimis1.png" alt="CIMIS Logo" class="hero-logo">
                <div class="hero-text">
                    <h1>VISUALISATION DES CARTES / CARDS VISUALIZATION</h1>
                    <div class="hero-divider"></div>
                    <h2>Système d'Identification Militaire / Military Identification System</h2>
                    <p>Affichage des cartes d'identité militaire au format PVC / Display military ID cards in PVC format</p>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="visualization-container">
                <!-- Header -->
                <div class="header">
                    <!-- BOUTON RETOUR -->
                    <div class="back-button-container">
                        <a href="impression.php" class="btn-back btn-back-list">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>RETOUR À LA LISTE / BACK TO LIST</span>
                        </a>
                    </div>
                    <div class="header-title">
                        <?php echo count($cartes_confectionnees); ?> carte(s) à visualiser / card(s) to visualize
                    </div>
                    <div class="header-right">
                        <span id="clock" class="text-mono">12:00:00</span>
                    </div>
                </div>

                <!-- Cartes -->
                <div class="cards-wrapper">
                    <?php if (empty($cartes_confectionnees)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-id-card"></i>
                            <h3>Aucune carte à visualiser / No card to visualize</h3>
                            <p>Allez d'abord dans la section "Confection des Cartes" pour créer des cartes. / First go to the "Card Creation" section to create cards.</p>
                            <a href="../Carte/confection_carte.php" class="btn">
                                <i class="fa-solid fa-magic"></i> ALLER À LA CONFECTION / GO TO CREATION
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cartes_confectionnees as $index => $carte_data): ?>
                            <div class="candidat-header">
                                <?php echo htmlspecialchars($carte_data['candidat']['nom'] . ' ' . $carte_data['candidat']['prenom']); ?> - 
                                Matricule: <?php echo htmlspecialchars($carte_data['candidat']['matricule']); ?> - 
                                <?php echo htmlspecialchars($carte_data['candidat']['unite']); ?>
                            </div>
                            <?php echo $carte_data['carte_html']; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <button class="btn" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> IMPRIMER TOUTES LES CARTES / PRINT ALL CARDS
                    </button>
                    <div class="action-buttons">
                        <a href="../Carte/confection_carte.php" class="btn">
                            <i class="fa-solid fa-magic"></i> CONFECTIONNER D'AUTRES CARTES / CREATE OTHER CARDS
                        </a>
                        <div class="back-button-container" style="position: static; display: inline-block;">
                            <a href="impression.php" class="btn-back btn-back-list">
                                <i class="fa-solid fa-arrow-left"></i>
                                <span>RETOUR À LA LISTE / BACK TO LIST</span>
                            </a>
                        </div>
                        <a href="../securite.php" class="btn" style="background: linear-gradient(45deg, #d4af37, #b8941f); color: #000;">
                            <i class="fa-solid fa-shield-alt"></i> VÉRIFIER LA SÉCURITÉ / CHECK SECURITY
                        </a>
                    </div>
                    <?php if (!empty($cartes_confectionnees)): ?>
                        <button onclick="clearSession()" class="btn btn-logout">
                            <i class="fa-solid fa-trash"></i> VIDER LA SESSION / CLEAR SESSION
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <footer class="security-footer">
            <div class="footer-left">
                <span><i class="fa-solid fa-shield-alt"></i> SYSTÈME CIMIS NUMÉRISATION / CIMIS DIGITIZATION SYSTEM</span>
                <span><i class="fa-solid fa-lock"></i> Connexion sécurisée / Secure connection</span>
            </div>
            <div class="footer-right">
                <span id="footer-clock" class="text-mono">00:00:00</span>
                <span><i class="fa-solid fa-server"></i> Serveur: ACTIF / Server: ACTIVE</span>
            </div>
        </footer>
    </div>

    <script>
        function clearSession() {
            if (confirm('Êtes-vous sûr de vouloir vider la session des cartes confectionnées ? / Are you sure you want to clear the created cards session?')) {
                fetch('../visualiser_carte.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear_session'
                })
                .then(() => {
                    window.location.reload();
                });
            }
        }

        // --- CLOCK ---
        setInterval(() => {
            const now = new Date();
            const clockElement = document.getElementById('clock');
            const footerClock = document.getElementById('footer-clock');
            if (clockElement) clockElement.innerText = now.toLocaleTimeString('fr-FR');
            if (footerClock) footerClock.innerText = now.toLocaleTimeString('fr-FR');
        }, 1000);
    </script>
</body>
</html>
