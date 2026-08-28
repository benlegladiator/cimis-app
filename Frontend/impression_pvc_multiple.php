<?php
// impression_pvc_multiple.php - Impression PVC multiple optimisée
// Affiche tous les candidats sélectionnés sur une seule page au format PVC

require_once '../backend/config.php';
require_once '../Carte/confection_carte.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Récupérer les matricules depuis GET
$matricules = $_GET['matricules'] ?? '';
if (empty($matricules)) {
    $_SESSION['error'] = "Aucun matricule spécifié";
    header('Location: ../impression.php');
    exit;
}

// Séparer les matricules
$matricule_array = explode(',', $matricules);
$matricule_array = array_filter(array_map('trim', $matricule_array));

if (empty($matricule_array)) {
    $_SESSION['error'] = "Aucun matricule valide";
    header('Location: ../impression.php');
    exit;
}

// Récupérer tous les candidats par matricule ou matricule_militaire
$placeholders_mult = str_repeat('?,', count($matricule_array));
$placeholders_mult = rtrim($placeholders_mult, ',');
$allParams_mult = array_merge($matricule_array, $matricule_array);

$stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule IN ($placeholders_mult) OR matricule_militaire IN ($placeholders_mult)");
$stmt->execute($allParams_mult);
$candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($candidats)) {
    $_SESSION['error'] = "Aucun candidat trouvé";
    header('Location: ../impression.php');
    exit;
}

// 1. Enregistrement direct et garanti dans la file d'attente des reçus en session PHP
if (!isset($_SESSION['pending_receipts'])) {
    $_SESSION['pending_receipts'] = [];
}
$candidat_ids = [];
foreach ($candidats as $cand) {
    if (!empty($cand['id'])) {
        $c_id = (int)$cand['id'];
        $candidat_ids[] = $c_id;
        if (!in_array($c_id, $_SESSION['pending_receipts'])) {
            $_SESSION['pending_receipts'][] = $c_id;
        }
    }
}

// 2. Mettre à jour le compteur d'impression et la date de dernière réimpression par ID primaire
try {
    if (!empty($candidat_ids)) {
        $id_in = str_repeat('?,', count($candidat_ids) - 1) . '?';
        $update_stmt = $pdo->prepare("UPDATE candidat SET nb_reimpressions = COALESCE(nb_reimpressions, 0) + 1, date_derniere_reimpression = CURDATE() WHERE id IN ($id_in)");
        $update_stmt->execute($candidat_ids);
    }
} catch (Exception $e) {}

// Récupérer config unités
$config_unites = include '../Carte/config_unites.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Impression PVC Multiple - <?php echo count($candidats); ?> cartes</title>
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === CONFIGURATION IMPRESSION PVC CRITIQUE === */
        
        /* Définir la taille de page = taille carte PVC (85.60mm × 53.98mm) */
        @page {
            size: 85.60mm 53.98mm;
            margin: 0;
            padding: 0;
        }
        
        /* En impression: cacher tout sauf les cartes */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
            }
            
            /* Cacher tous les éléments UI */
            .no-print,
            .print-controls,
            .btn,
            h1, h2, h3,
            .alert,
            .header,
            .menu,
            .navbar,
            .sidebar {
                display: none !important;
            }
            
            /* Afficher uniquement les cartes */
            .print-only {
                display: block !important;
            }
            
            /* Chaque carte sur une page séparée */
            .pvc-card-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                page-break-after: always;
                page-break-inside: avoid;
                overflow: hidden;
                box-shadow: none !important;
                border: none !important;
            }
            
            .pvc-card-wrapper:last-child {
                page-break-after: auto;
            }
            
            /* Forcer les dimensions exactes de la carte */
            .id-card {
                width: 85.60mm !important;
                height: 53.98mm !important;
                margin: 0 !important;
                border-radius: 3mm !important;
                box-shadow: none !important;
                border: none !important;
                transform: none !important;
            }
        }
        
        /* === STYLE ÉCRAN (PREVIEW) === */
        body {
            background: #2c3e50;
            padding: 20px;
            font-family: Arial, sans-serif;
            margin: 0;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 1000;
            width: 250px;
        }
        
        .print-controls h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 11px;
            margin-top: 10px;
            border-left: 3px solid #28a745;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .preview-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            padding: 20px;
            margin-top: 80px;
        }
        
        .pvc-card-wrapper {
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-radius: 3mm;
            margin: 20px auto;
            width: fit-content;
        }
        
        .card-label {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .print-instructions {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
            max-width: 600px;
        }
        
        .print-instructions h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .print-instructions ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }
        
        .print-instructions li {
            margin-bottom: 5px;
        }
        
        .cards-counter {
            background: #007bff;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Panneau de contrôle (caché à l'impression) -->
    <div class="print-controls no-print">
        <h3><i class="fas fa-print"></i> Impression PVC Multiple</h3>
        
        <div class="cards-counter">
            <i class="fas fa-id-card"></i> <?php echo count($candidats); ?> carte(s)
        </div>
        
        <div>
            <button class="btn btn-primary" onclick="printCards()">
                <i class="fas fa-print"></i> Imprimer tout (Ctrl+P)
            </button>
            <?php
            $ids_cands_mult = array_column($candidats, 'id');
            $ids_str_mult = implode(',', $ids_cands_mult);
            ?>
            <a href="../backend/generer_recu.php?mode=batch&ids=<?php echo $ids_str_mult; ?>" target="_blank" 
               class="btn btn-success" style="background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; font-weight: bold; margin-left: 5px;">
                <i class="fas fa-file-invoice"></i> Imprimer les Reçus A4
            </a>
            <button class="btn btn-secondary" onclick="window.close()" style="margin-left: 5px;">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
        
        <div class="info-box">
            <strong><i class="fas fa-ruler"></i> Format:</strong> 85.60mm × 53.98mm<br>
            <strong><i class="fas fa-microchip"></i> NFC:</strong> Zone marquée<br>
            <strong><i class="fas fa-file"></i> Pages:</strong> <?php echo count($candidats); ?> pages<br>
            <strong>Imprimante:</strong> PVC Card Printer
        </div>
    </div>
    
    <!-- Instructions -->
    <div class="print-instructions no-print" style="margin: 100px auto 20px;">
        <h4><i class="fas fa-info-circle"></i> Configuration requise</h4>
        <ul>
            <li><strong>Taille papier:</strong> Définir "Carte PVC" ou "Custom" (85.60mm × 53.98mm)</li>
            <li><strong>Marges:</strong> Aucune (0mm)</li>
            <li><strong>Échelle:</strong> 100% (pas de mise à l'échelle)</li>
            <li><strong>Recto-verso:</strong> Activer l'impression duplex manuel ou automatique</li>
            <li><strong>NFC:</strong> Ne pas imprimer sur la zone inférieure droite (position puce)</li>
            <li><strong>Pages:</strong> <?php echo count($candidats); ?> pages - 1 carte par page</li>
        </ul>
    </div>
    
    <!-- Zone d'impression (seul contenu imprimé) -->
    <div class="preview-container" id="print-area">
        
        <?php 
        foreach ($candidats as $index => $candidat): 
            // Récupérer config unité pour ce candidat
            $unite = $candidat['unite'] ?? 'ARMÉE DE TERRE';
            $config = $config_unites[$unite] ?? $config_unites['ARMÉE DE TERRE'];
            
            $fond_image = file_exists('../' . $config['fond']) ? '../' . $config['fond'] : '../img/default_fond.png';
            $logo_unit = !empty($config['logo']) && file_exists('../' . $config['logo']) ? '../' . $config['logo'] : '../img/cimis.png';
        ?>
        
        <!-- CARTE <?php echo $index + 1; ?> -->
        <div class="pvc-card-wrapper print-only" data-carte="<?php echo $index + 1; ?>">
            <div class="card-label no-print">
                CARTE <?php echo $index + 1; ?> - <?php echo htmlspecialchars($candidat['matricule']); ?>
            </div>
            
            <!-- RECTO -->
            <div class="id-card" data-face="recto">
                <?php 
                echo renderRecto($candidat, $config, $unite, $fond_image, $logo_unit); 
                ?>
            </div>
        </div>
        
        <!-- VERSO (page séparée) -->
        <div class="pvc-card-wrapper print-only" data-carte="<?php echo $index + 1; ?>-verso">
            <div class="card-label no-print">
                CARTE <?php echo $index + 1; ?> VERSO - <?php echo htmlspecialchars($candidat['matricule']); ?>
            </div>
            
            <div class="id-card" data-face="verso">
                <?php 
                echo renderVerso($candidat, $config, $unite, $fond_image, $logo_unit); 
                ?>
            </div>
        </div>
        
        <?php endforeach; ?>
        
    </div>
    
    <!-- Modal de confirmation post-impression -->
    <div id="modalConfirmImpression" style="
        display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
        background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center;">
        <div style="
            background:#1a1a2e; border:2px solid #4ade80; border-radius:16px; 
            padding:2rem; max-width:460px; width:90%; text-align:center; box-shadow:0 0 40px rgba(74,222,128,0.3);">
            <div style="font-size:3rem; margin-bottom:0.5rem;">✅</div>
            <h4 style="color:#4ade80; margin-bottom:0.5rem;">Impression PVC lancée !</h4>
            <p style="color:#ccc; margin-bottom:1.5rem; font-size:0.9rem;">
                <?php echo count($candidats); ?> carte(s) envoyée(s) à l'imprimante.<br>
                Les cartes sont enregistrées pour la délivrance du reçu officiel signé.
            </p>
            <div style="display:flex; flex-direction:column; gap:0.6rem;">
                <?php
                $ids_candidats = array_column($candidats, 'id');
                $ids_str = implode(',', $ids_candidats);
                ?>
                <a href="../backend/generer_recu.php?mode=batch&ids=<?php echo $ids_str; ?>" target="_blank" 
                   style="background:linear-gradient(135deg,#28a745,#20c997); color:white; padding:0.6rem 1rem; border-radius:8px; text-decoration:none; font-weight:bold;">
                    <i class="fas fa-file-alt"></i> Imprimer le Reçu A4 maintenant
                </a>
                <a href="impression.php" 
                   style="background:linear-gradient(135deg,#007bff,#0056b3); color:white; padding:0.6rem 1rem; border-radius:8px; text-decoration:none; font-weight:bold;">
                    <i class="fas fa-list"></i> Retour à la liste des cartes
                </a>
                <button onclick="document.getElementById('modalConfirmImpression').style.display='none'" 
                   style="background:transparent; border:1px solid #666; color:#aaa; padding:0.5rem 1rem; border-radius:8px; cursor:pointer;">
                    <i class="fas fa-times"></i> Fermer ce message
                </button>
            </div>
        </div>
    </div>

    <script>
        function printCards() {
            const count = <?php echo count($candidats); ?>;
            const message = `Confirmer l'impression de ${count} carte(s) PVC ?\n\n` +
                          `Format: 85.60mm × 53.98mm\n` +
                          `Pages: ${count * 2} (recto + verso)\n` +
                          `Pour recto-verso automatique : utilisez l'option duplex de votre imprimante.`;
            
            if (confirm(message)) {
                // Cacher les labels avant impression
                document.querySelectorAll('.card-label').forEach(el => el.style.display = 'none');
                
                // Lancer l'impression
                window.print();
                
                // Restaurer après impression et afficher la confirmation
                setTimeout(() => {
                    document.querySelectorAll('.card-label').forEach(el => el.style.display = 'block');
                    const m = document.getElementById('modalConfirmImpression');
                    if (m) m.style.display = 'flex';
                }, 1200);
            }
        }
        
        // Raccourci clavier Ctrl+P
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printCards();
            }
        });
        
        // Détection avant impression pour ajuster
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
            // Afficher le modal de confirmation
            document.getElementById('modalConfirmImpression').style.display = 'flex';
        });
    </script>
</body>
</html>

