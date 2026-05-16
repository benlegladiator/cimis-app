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

// Récupération des candidats
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
        
        foreach ($candidats as $candidat) {
            $cartes_confectionnees[] = [
                'candidat' => $candidat,
                'carte_html' => renderCarte($candidat)
            ];
        }
    }
}

// Fonds disponibles pour le mode uniforme
$fonds_disponibles = [
    '101.png' => 'Fond 1',
    '102.png' => 'Fond 2',
    '103.png' => 'Fond 3',
    '104.png' => 'Fond 4',
    '105.png' => 'Fond 5'
];

// Fond par défaut
$fond_actuel = $_GET['fond'] ?? '101.png';
$fond_chemin = '../img/fonds_preview/' . $fond_actuel;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation Uniforme des Cartes - CIMIS</title>
    <link rel="stylesheet" href="../css/enrolement.css">
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="../css/preview_uniforme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- TOP STATUS BAR -->
        <div class="top-status-bar">
            <div class="status-left">
                <span class="status-item warning-flash"><i class="fa-solid fa-triangle-exclamation"></i> SYSTÈME CLASSÉ SECRET DÉFENSE</span>
                <span class="status-item"><i class="fa-solid fa-globe"></i> RÉSEAU SÉCURISÉ</span>
                <span class="status-item"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="status-right">
                <span id="clock" class="text-mono">12:00:00</span>
                <a href="logout.php" class="btn-logout-styled">
                    <i class="fa-solid fa-power-off"></i> DÉCONNEXION
                </a>
            </div>
        </div>

        <!-- BOUTON RETOUR -->
        <div style="padding: 20px; text-align: left;">
            <a href="../impression.php" class="btn" style="font-size: 16px; padding: 12px 24px;">
                <i class="fa-solid fa-arrow-left"></i> RETOUR À L'IMPRESSION
            </a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="visualization-container">
                
                <!-- Header -->
                <div class="header" style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(74, 222, 128, 0.3); border-radius: 15px; padding: 1rem; margin: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <div class="header-title" style="color: var(--neon-green); font-size: 1.5rem; font-weight: bold;">
                        <i class="fa-solid fa-palette"></i> MODE PREVIEW UNIFORME
                    </div>
                    <div>
                        <span id="clock" class="text-mono">12:00:00</span>
                    </div>
                </div>

                <!-- SÉLECTEUR DE FONDS -->
                <div class="selector-section" style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(74, 222, 128, 0.3); border-radius: 15px; padding: 2rem; margin: 1rem;">
                    <h3 style="color: var(--neon-green); margin-bottom: 1.5rem; text-align: center;">
                        <i class="fa-solid fa-image"></i> CHOISIR LE FOND UNIFORME
                    </h3>
                    <div class="fonds-grid" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                        <?php foreach ($fonds_disponibles as $fond_file => $fond_name): 
                            $is_active = ($fond_actuel === $fond_file);
                            $fond_path = '../img/fonds_preview/' . $fond_file;
                        ?>
                        <div class="fond-option <?php echo $is_active ? 'active' : ''; ?>" 
                             data-fond="<?php echo htmlspecialchars($fond_file); ?>"
                             style="cursor: pointer; border: 3px solid <?php echo $is_active ? '#4ade80' : 'transparent'; ?>; border-radius: 10px; padding: 10px; transition: all 0.3s ease; background: rgba(0,0,0,0.5);">
                            <div style="width: 150px; height: 100px; background: linear-gradient(135deg, #1e3a5f 0%, #0f2027 100%); border-radius: 5px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; overflow: hidden;">
                                <?php if (file_exists($fond_path)): ?>
                                <img src="<?php echo $fond_path; ?>" alt="<?php echo $fond_name; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                <span style="color: #4ade80; font-size: 12px;"><?php echo $fond_name; ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: center; color: <?php echo $is_active ? '#4ade80' : '#fff'; ?>; font-weight: <?php echo $is_active ? 'bold' : 'normal'; ?>;">
                                <?php echo $fond_name; ?>
                                <?php if ($is_active): ?> <i class="fa-solid fa-check"></i><?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1.5rem; color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                        <i class="fa-solid fa-info-circle"></i> Cliquez sur un fond pour prévisualiser toutes les cartes avec ce fond uniforme
                    </div>
                </div>

                <!-- Cartes avec fond uniforme -->
                <div class="cards-wrapper" style="padding: 2rem; display: flex; flex-direction: column; align-items: center; gap: 2rem;">
                    <?php if (empty($cartes_confectionnees)): ?>
                        <div class="empty-state" style="text-align: center; padding: 3rem; color: rgba(255,255,255,0.7);">
                            <i class="fa-solid fa-id-card" style="font-size: 4rem; color: var(--neon-green); opacity: 0.5; margin-bottom: 1rem;"></i>
                            <h3>Aucune carte à visualiser</h3>
                            <p>Allez d'abord dans la section "Confection des Cartes" pour créer des cartes.</p>
                            <a href="../impression.php" class="btn" style="margin-top: 1rem;">
                                <i class="fa-solid fa-list"></i> ALLER À LA LISTE DES CANDIDATS
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.3); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; text-align: center; color: var(--neon-green);">
                            <i class="fa-solid fa-eye"></i> <?php echo count($cartes_confectionnees); ?> carte(s) avec fond uniforme: <strong><?php echo $fonds_disponibles[$fond_actuel] ?? $fond_actuel; ?></strong>
                        </div>
                        
                        <?php foreach ($cartes_confectionnees as $index => $carte_data): ?>
                            <div class="candidat-header" style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(74, 222, 128, 0.3); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; text-align: center; color: var(--neon-green); font-weight: bold;">
                                <?php echo htmlspecialchars($carte_data['candidat']['nom'] . ' ' . $carte_data['candidat']['prenom']); ?> - 
                                Matricule: <?php echo htmlspecialchars($carte_data['candidat']['matricule']); ?> - 
                                <?php echo htmlspecialchars($carte_data['candidat']['unite']); ?>
                            </div>
                            <?php 
                            // Utiliser la fonction renderCarteUniforme pour forcer le fond uniforme
                            echo renderCarteUniforme($carte_data['candidat'], $fond_chemin);
                            ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <?php if (!empty($cartes_confectionnees)): ?>
                <div class="actions" style="padding: 2rem; text-align: center;">
                    <button class="btn" onclick="window.print()" style="margin-right: 10px;">
                        <i class="fa-solid fa-print"></i> IMPRIMER AVEC CE FOND
                    </button>
                    <a href="../impression.php" class="btn" style="background: linear-gradient(45deg, #d4af37, #b8941f); color: #000;">
                        <i class="fa-solid fa-arrow-left"></i> RETOUR À L'IMPRESSION STANDARD
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FOOTER -->
        <footer class="security-footer">
            <div class="footer-left">
                <span><i class="fa-solid fa-shield-alt"></i> SYSTÈME CIMIS NUMÉRISATION</span>
                <span><i class="fa-solid fa-lock"></i> Connexion sécurisée</span>
            </div>
            <div class="footer-right">
                <span id="footer-clock" class="text-mono">00:00:00</span>
                <span><i class="fa-solid fa-server"></i> Serveur: ACTIF</span>
            </div>
        </footer>
    </div>

    <style>
        /* Responsive Design */
        @media (max-width: 1024px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .header-title {
                font-size: 1.2rem;
            }
            
            .selector-section {
                padding: 1.5rem;
            }
            
            .fonds-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .fond-option {
                padding: 0.75rem;
            }
            
            .fond-option div {
                width: 120px;
                height: 80px;
            }
            
            .cards-wrapper {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .selector-section {
                padding: 1rem;
            }
            
            .fonds-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 0.75rem;
            }
            
            .fond-option {
                padding: 0.5rem;
            }
            
            .fond-option div {
                width: 100px;
                height: 60px;
            }
            
            .cards-wrapper {
                padding: 1rem;
            }
            
            .candidat-header {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .header-title {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .selector-section {
                padding: 0.75rem;
            }
            
            .fonds-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .fond-option {
                padding: 0.375rem;
            }
            
            .fond-option div {
                width: 80px;
                height: 50px;
            }
            
            .cards-wrapper {
                padding: 0.75rem;
            }
            
            .candidat-header {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            
            .header-title {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 360px) {
            .fond-option div {
                width: 60px;
                height: 40px;
            }
            
            .candidat-header {
                font-size: 0.75rem;
            }
            
            .header-title {
                font-size: 0.8rem;
            }
        }
        
        @media (orientation: landscape) and (max-height: 600px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .selector-section {
                padding: 0.75rem;
            }
            
            .fonds-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 0.5rem;
            }
            
            .cards-wrapper {
                padding: 0.75rem;
            }
        }
    </style>
    
    <script src="../js/preview_uniforme.js"></script>
    <script>
        // --- CLOCK ---
        setInterval(() => {
            const now = new Date();
            const clockElements = document.querySelectorAll('#clock, #footer-clock');
            clockElements.forEach(el => {
                if (el) el.innerText = now.toLocaleTimeString('fr-FR');
            });
        }, 1000);
    </script>
</body>
</html>
