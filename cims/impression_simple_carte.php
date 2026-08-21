<?php
// impression_simple_carte.php - Version simplifiée pour éviter les erreurs

require_once 'backend/config.php';

// Vérification de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Récupérer le matricule
$matricule = $_GET['matricule'] ?? '';

if (empty($matricule)) {
    $_SESSION['error'] = "Matricule non spécifié";
    header('Location: impression.php');
    exit;
}

// Récupérer le candidat
$stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule = ?");
$stmt->execute([$matricule]);
$candidat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidat) {
    $_SESSION['error'] = "Candidat non trouvé: " . htmlspecialchars($matricule);
    header('Location: impression.php');
    exit;
}

// Configuration des dimensions
define('CARTE_WIDTH_MM', 85.6);
define('CARTE_HEIGHT_MM', 53.98);
define('CARTE_WIDTH_PX', 324);
define('CARTE_HEIGHT_PX', 204);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Militaire - <?php echo htmlspecialchars($candidat['matricule']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            
            .carte-container {
                page-break-inside: avoid;
            }
        }
        
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
        
        .carte-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 40px 0;
            page-break-inside: avoid;
        }
        
        .carte-recto, .carte-verso {
            width: <?php echo CARTE_WIDTH_PX; ?>px;
            height: <?php echo CARTE_HEIGHT_PX; ?>px;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .carte-recto {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }
        
        .carte-verso {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            font-size: 10px;
        }
        
        .verso-header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #1e3c72;
            margin-bottom: 10px;
        }
        
        .verso-content {
            font-size: 9px;
        }
        
        .verso-section {
            margin-bottom: 10px;
        }
        
        .verso-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .verso-label {
            font-weight: bold;
            color: #333;
        }
        
        .verso-value {
            color: #000;
            font-weight: 500;
        }
        
        .verso-footer {
            margin-top: 15px;
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #ddd;
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
        }
        
        .carte-header {
            text-align: center;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            font-size: 8px;
            font-weight: bold;
        }
        
        .carte-content {
            display: flex;
            padding: 10px;
            height: calc(100% - 60px);
        }
        
        .carte-photo {
            width: 60px;
            height: 60px;
            border: 2px solid white;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .carte-info {
            flex: 1;
            font-size: 10px;
        }
        
        .carte-nom {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .carte-grade {
            margin-bottom: 2px;
            opacity: 0.9;
        }
        
        .carte-matricule {
            background: rgba(255,255,255,0.2);
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            font-size: 8px;
        }
        
        .carte-qrcode {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 30px;
            height: 30px;
            background: white;
            padding: 3px;
            border-radius: 4px;
        }
        
        .carte-empreinte {
            position: absolute;
            bottom: 8px;
            left: 8px;
            width: 20px;
            height: 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .print-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 10px 20px;
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
        
        .info-panel {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <div class="info-panel">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-user"></i> Nom:</strong>
                        <?php echo htmlspecialchars($candidat['nom'] . ' ' . $candidat['prenom']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-id-card"></i> Matricule:</strong>
                        <?php echo htmlspecialchars($candidat['matricule']); ?>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <strong><i class="fas fa-ruler-combined"></i> Dimensions:</strong>
                        <?php echo CARTE_WIDTH_MM; ?>mm × <?php echo CARTE_HEIGHT_MM; ?>mm
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-print"></i> Format:</strong>
                        Carte PVC NFC
                    </div>
                </div>
            </div>
            
            <div class="carte-container">
                <!-- RECTO -->
                <div class="carte-recto">
                    <div class="carte-header">
                        RÉPUBLIQUE DU CAMEROUN<br>
                        CARTE D'IDENTITÉ MILITAIRE
                    </div>
                    
                    <div class="carte-content">
                        <div>
                            <?php if ($candidat['photo'] && file_exists($candidat['photo'])): ?>
                                <img src="<?php echo $candidat['photo']; ?>" class="carte-photo" alt="Photo">
                            <?php else: ?>
                                <div class="carte-photo" style="background: #ccc; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user" style="font-size: 20px; color: #999;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="carte-info">
                            <div class="carte-nom"><?php echo htmlspecialchars(strtoupper($candidat['nom'])); ?></div>
                            <div class="carte-grade"><?php echo htmlspecialchars(strtoupper($candidat['grade'] ?? '')); ?></div>
                            <div class="carte-matricule"><?php echo htmlspecialchars($candidat['matricule']); ?></div>
                        </div>
                    </div>
                    
                    <div class="carte-empreinte">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    
                    <?php if ($candidat['code_qr'] && file_exists($candidat['code_qr'])): ?>
                        <img src="<?php echo $candidat['code_qr']; ?>" class="carte-qrcode" alt="QR Code">
                    <?php endif; ?>
                </div>
                
                <!-- VERSO -->
                <div class="carte-verso">
                    <div class="verso-header">
                        <strong>INFORMATIONS ADMINISTRATIVES</strong>
                    </div>
                    
                    <div class="verso-content">
                        <div class="verso-section">
                            <div class="verso-row">
                                <span class="verso-label">Nom:</span>
                                <span class="verso-value"><?php echo htmlspecialchars(strtoupper($candidat['nom'])); ?></span>
                            </div>
                            <div class="verso-row">
                                <span class="verso-label">Prénom:</span>
                                <span class="verso-value"><?php echo htmlspecialchars(ucfirst(strtolower($candidat['prenom']))); ?></span>
                            </div>
                            <div class="verso-row">
                                <span class="verso-label">Date de naissance:</span>
                                <span class="verso-value"><?php echo htmlspecialchars($candidat['date_naissance'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="verso-row">
                                <span class="verso-label">Sexe:</span>
                                <span class="verso-value"><?php echo htmlspecialchars($candidat['sexe'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        
                        <div class="verso-section">
                            <div class="verso-row">
                                <span class="verso-label">Grade:</span>
                                <span class="verso-value"><?php echo htmlspecialchars(strtoupper($candidat['grade'] ?? '')); ?></span>
                            </div>
                            <div class="verso-row">
                                <span class="verso-label">Unité:</span>
                                <span class="verso-value"><?php echo htmlspecialchars(strtoupper($candidat['unite'] ?? '')); ?></span>
                            </div>
                            <div class="verso-row">
                                <span class="verso-label">Matricule:</span>
                                <span class="verso-value"><?php echo htmlspecialchars($candidat['matricule']); ?></span>
                            </div>
                        </div>
                        
                        <div class="verso-section">
                            <div class="verso-row">
                                <span class="verso-label">Date d'émission:</span>
                                <span class="verso-value"><?php echo !empty($candidat['date_enrolement']) ? date('d/m/Y', strtotime($candidat['date_enrolement'])) : 'N/A'; ?></span>
                            </div>
                            <div class="verso-row">
                                <span class="verso-label">Date d'expiration:</span>
                                <span class="verso-value"><?php echo date('d/m/Y', strtotime('+5 years')); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="verso-footer">
                        <div class="signature-line"></div>
                        <div class="signature-text">SIGNATURE DU TITULAIRE</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contrôles d'impression -->
    <div class="print-controls no-print">
        <h6><i class="fas fa-print"></i> Impression</h6>
        <button class="print-button" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>
        
        <button class="btn btn-outline-secondary btn-sm w-100" onclick="window.close()">
            <i class="fas fa-times"></i> Fermer
        </button>
        
        <div class="mt-3">
            <small class="text-muted">
                <strong>Instructions:</strong><br>
                • Papier PVC adhésif<br>
                • 300 DPI minimum<br>
                • Sans marges
            </small>
        </div>
    </div>
</body>
</html>
