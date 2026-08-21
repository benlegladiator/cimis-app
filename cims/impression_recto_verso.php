<?php
// impression_recto_verso.php - Système professionnel d'impression RECTO/VERSO

require_once 'backend/config.php';

// Vérification de session
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Configuration pour l'impression professionnelle
define('CARTE_WIDTH_MM', 85.6);   // Standard carte bancaire
define('CARTE_HEIGHT_MM', 53.98);  // Standard carte bancaire
define('CARTE_DPI', 300);
define('CARTE_WIDTH_PX', 1005);  // Pour impression haute qualité
define('CARTE_HEIGHT_PX', 637);

// Récupérer les candidats
$candidats = [];
if (isset($_GET['matricules'])) {
    $matricules = explode(',', $_GET['matricules']);
    $placeholders = str_repeat('?,', count($matricules) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule IN ($placeholders) ORDER BY nom, prenom");
    $stmt->execute($matricules);
    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (isset($_GET['matricule'])) {
    $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule = ?");
    $stmt->execute([$_GET['matricule']]);
    $candidats = [$stmt->fetch(PDO::FETCH_ASSOC)];
} else {
    $stmt = $pdo->prepare("SELECT * FROM candidat WHERE statut_carte = 'ACTIVE' ORDER BY nom, prenom LIMIT 20");
    $stmt->execute();
    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour générer le CSS d'impression
function getPrintCSS($format = 'A4') {
    $css = "
    @page {
        size: A4;
        margin: 10mm;
    }
    
    @media print {
        body {
            margin: 0;
            padding: 0;
            background: white;
        }
        
        .no-print {
            display: none !important;
        }
        
        .print-break {
            page-break-after: always;
        }
        
        .carte-sheet {
            page-break-inside: avoid;
        }
    }
    ";
    
    if ($format === 'PVC') {
        $css .= "
        @page {
            size: 100mm 65mm;
            margin: 5mm;
        }
        ";
    }
    
    return $css;
}

// Fonction pour générer le RECTO de la carte
function genererRecto($candidat) {
    $photo_src = $candidat['photo'] && file_exists($candidat['photo']) ? $candidat['photo'] : 'assets/default-avatar.png';
    $qr_src = $candidat['code_qr'] && file_exists($candidat['code_qr']) ? $candidat['code_qr'] : '';
    
    return "
    <div class='carte-recto'>
        <div class='carte-background'>
            <div class='carte-header'>
                <div class='republique'>RÉPUBLIQUE DU CAMEROUN</div>
                <div class='carte-titre'>CARTE D'IDENTITÉ MILITAIRE</div>
            </div>
            
            <div class='carte-content'>
                <div class='carte-photo-section'>
                    <img src='$photo_src' class='carte-photo' alt='Photo'>
                    <div class='carte-empreinte'>
                        <i class='fas fa-fingerprint'></i>
                    </div>
                </div>
                
                <div class='carte-info-section'>
                    <div class='carte-nom-complet'>" . strtoupper(htmlspecialchars($candidat['nom'])) . "</div>
                    <div class='carte-prenom'>" . ucfirst(strtolower(htmlspecialchars($candidat['prenom']))) . "</div>
                    <div class='carte-details'>
                        <div class='carte-grade'>" . strtoupper(htmlspecialchars($candidat['grade'] ?? '')) . "</div>
                        <div class='carte-unite'>" . strtoupper(htmlspecialchars($candidat['unite'] ?? '')) . "</div>
                        <div class='carte-matricule'>MAT: " . htmlspecialchars($candidat['matricule']) . "</div>
                    </div>
                </div>
            </div>
            
            <div class='carte-footer'>
                <div class='carte-validite'>
                    VALIDE JUSQU'AU: " . date('d/m/Y', strtotime('+5 years')) . "
                </div>
            </div>
            
            " . ($qr_src ? "<img src='$qr_src' class='carte-qrcode' alt='QR Code'>" : "") . "
        </div>
    </div>
    ";
}

// Fonction pour générer le VERSO de la carte
function genererVerso($candidat) {
    return "
    <div class='carte-verso'>
        <div class='verso-header'>
            <div class='verso-titre'>INFORMATIONS ADMINISTRATIVES</div>
        </div>
        
        <div class='verso-content'>
            <div class='verso-section'>
                <div class='verso-section-title'>IDENTITÉ</div>
                <div class='verso-info-grid'>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Nom:</span>
                        <span class='verso-value'>" . strtoupper(htmlspecialchars($candidat['nom'])) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Prénom:</span>
                        <span class='verso-value'>" . ucfirst(strtolower(htmlspecialchars($candidat['prenom']))) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Date de naissance:</span>
                        <span class='verso-value'>" . htmlspecialchars($candidat['date_naissance']) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Sexe:</span>
                        <span class='verso-value'>" . htmlspecialchars($candidat['sexe']) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Lieu de naissance:</span>
                        <span class='verso-value'>" . htmlspecialchars($candidat['lieu_naissance'] ?? 'Non spécifié') . "</span>
                    </div>
                </div>
            </div>
            
            <div class='verso-section'>
                <div class='verso-section-title'>INFORMATIONS MILITAIRES</div>
                <div class='verso-info-grid'>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Grade:</span>
                        <span class='verso-value'>" . strtoupper(htmlspecialchars($candidat['grade'] ?? '')) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Corps/Arme:</span>
                        <span class='verso-value'>" . strtoupper(htmlspecialchars($candidat['unite'] ?? '')) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Matricule militaire:</span>
                        <span class='verso-value'>" . htmlspecialchars($candidat['matricule_militaire'] ?? $candidat['matricule']) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Matricule CIMIS:</span>
                        <span class='verso-value'>" . htmlspecialchars($candidat['matricule']) . "</span>
                    </div>
                </div>
            </div>
            
            <div class='verso-section'>
                <div class='verso-section-title'>VALIDATION</div>
                <div class='verso-info-grid'>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Date d'émission:</span>
                        <span class='verso-value'>" . date('d/m/Y', strtotime($candidat['date_enrolement'])) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Date d'expiration:</span>
                        <span class='verso-value'>" . date('d/m/Y', strtotime('+5 years')) . "</span>
                    </div>
                    <div class='verso-info-item'>
                        <span class='verso-label'>Autorité émettrice:</span>
                        <span class='verso-value'>MINDEF/DSC</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class='verso-footer'>
            <div class='verso-signature'>
                <div class='signature-line'></div>
                <div class='signature-text'>SIGNATURE DU TITULAIRE</div>
            </div>
        </div>
    </div>
    ";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impression RECTO/VERSO - CIMIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php echo getPrintCSS(); ?>
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .carte-sheet {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .carte-recto {
            width: <?php echo CARTE_WIDTH_PX; ?>px;
            height: <?php echo CARTE_HEIGHT_PX; ?>px;
            position: relative;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e8ba3 100%);
            color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            margin: 0 auto 20px;
        }
        
        .carte-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('img/carte-pattern.png');
            opacity: 0.1;
            background-size: cover;
        }
        
        .carte-header {
            text-align: center;
            padding: 15px 10px 5px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        
        .republique {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        
        .carte-titre {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        .carte-content {
            display: flex;
            padding: 15px;
            height: calc(100% - 120px);
        }
        
        .carte-photo-section {
            position: relative;
            margin-right: 15px;
        }
        
        .carte-photo {
            width: 100px;
            height: 100px;
            border: 3px solid white;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .carte-empreinte {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 30px;
            height: 30px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e3c72;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .carte-info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .carte-nom-complet {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .carte-prenom {
            font-size: 14px;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        .carte-details {
            font-size: 11px;
            line-height: 1.4;
        }
        
        .carte-grade {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .carte-unite {
            opacity: 0.8;
            margin-bottom: 2px;
        }
        
        .carte-matricule {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
            font-weight: bold;
        }
        
        .carte-footer {
            position: absolute;
            bottom: 10px;
            left: 15px;
            right: 15px;
            text-align: center;
        }
        
        .carte-validite {
            font-size: 9px;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
            backdrop-filter: blur(10px);
        }
        
        .carte-qrcode {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
            background: white;
            padding: 5px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .carte-verso {
            width: <?php echo CARTE_WIDTH_PX; ?>px;
            height: <?php echo CARTE_HEIGHT_PX; ?>px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 0 auto 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .verso-header {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #1e3c72;
            margin-bottom: 15px;
        }
        
        .verso-titre {
            font-size: 14px;
            font-weight: bold;
            color: #1e3c72;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .verso-content {
            font-size: 10px;
        }
        
        .verso-section {
            margin-bottom: 15px;
        }
        
        .verso-section-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        
        .verso-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        
        .verso-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 0;
        }
        
        .verso-label {
            font-weight: bold;
            color: #555;
            font-size: 9px;
        }
        
        .verso-value {
            color: #333;
            font-weight: 500;
            text-align: right;
        }
        
        .verso-footer {
            margin-top: 20px;
            text-align: center;
        }
        
        .verso-signature {
            display: inline-block;
        }
        
        .signature-line {
            width: 150px;
            height: 1px;
            background: #333;
            margin: 0 auto 5px;
        }
        
        .signature-text {
            font-size: 8px;
            color: #666;
            text-align: center;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            z-index: 1000;
            min-width: 200px;
        }
        
        .print-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40,167,69,0.3);
        }
        
        .format-selector {
            margin-bottom: 15px;
        }
        
        .format-selector label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .format-selector select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .preview-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .cartes-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .cartes-container {
                grid-template-columns: 1fr;
            }
            
            .print-controls {
                position: static;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <h2 class="text-center mb-4">
                <i class="fas fa-id-card"></i> Impression Professionnelle RECTO/VERSO
            </h2>
            
            <div class="preview-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-ruler-combined"></i> Dimensions:</strong>
                        <?php echo CARTE_WIDTH_MM; ?>mm × <?php echo CARTE_HEIGHT_MM; ?>mm
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-print"></i> Qualité:</strong>
                        <?php echo CARTE_DPI; ?> DPI
                    </div>
                </div>
            </div>
            
            <?php if (empty($candidats)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Aucun candidat trouvé pour l'impression.
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong><?php echo count($candidats); ?></strong> carte(s) prête(s) pour l'impression.
                </div>
                
                <div class="cartes-container">
                    <?php foreach ($candidats as $index => $candidat): ?>
                        <div class="carte-sheet">
                            <div class="text-center mb-3">
                                <h5>
                                    <?php echo htmlspecialchars($candidat['nom'] . ' ' . $candidat['prenom']); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($candidat['matricule']); ?>)</small>
                                </h5>
                            </div>
                            
                            <?php echo genererRecto($candidat); ?>
                            <?php echo genererVerso($candidat); ?>
                            
                            <?php if ($index < count($candidats) - 1): ?>
                                <div class="print-break"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Contrôles d'impression -->
    <div class="print-controls no-print">
        <h5><i class="fas fa-cog"></i> Options d'impression</h5>
        
        <div class="format-selector">
            <label for="printFormat">Format:</label>
            <select id="printFormat" class="form-select" onchange="changePrintFormat(this.value)">
                <option value="A4">Format A4 (8 cartes/feuille)</option>
                <option value="PVC">Format PVC (impression directe)</option>
                <option value="ETIQUETTE">Format Étiquette (prédécoupé)</option>
            </select>
        </div>
        
        <button class="print-button" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>
        
        <button class="btn btn-outline-secondary btn-sm w-100" onclick="window.close()">
            <i class="fas fa-times"></i> Fermer
        </button>
        
        <div class="mt-3">
            <small class="text-muted">
                <strong>Raccourcis:</strong><br>
                Ctrl+P : Imprimer<br>
                Ctrl+W : Fermer
            </small>
        </div>
    </div>
    
    <script>
        function changePrintFormat(format) {
            // Mettre à jour le CSS pour le format sélectionné
            document.body.className = document.body.className.replace(/format-\w+/g, '');
            document.body.classList.add('format-' + format.toLowerCase());
            
            // Ajuster les marges selon le format
            const styleSheet = document.styleSheets[0];
            const rules = styleSheet.cssRules || styleSheet.rules;
            
            for (let i = 0; i < rules.length; i++) {
                if (rules[i].selectorText && rules[i].selectorText.includes('@page')) {
                    styleSheet.deleteRule(i);
                    break;
                }
            }
            
            let pageCSS = '@page { size: A4; margin: 10mm; }';
            
            if (format === 'PVC') {
                pageCSS = '@page { size: 100mm 65mm; margin: 5mm; }';
            } else if (format === 'ETIQUETTE') {
                pageCSS = '@page { size: A4; margin: 5mm; }';
            }
            
            styleSheet.insertRule(pageCSS, 0);
        }
        
        // Optimiser pour l'impression
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
        
        // Initialiser
        changePrintFormat('A4');
    </script>
</body>
</html>
