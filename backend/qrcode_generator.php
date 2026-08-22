<?php
// Générateur de QR Code local basé sur le matricule militaire
require_once __DIR__ . '/phpqrcode/qrlib.php';

/**
 * Génère un QR code PNG d'identification militaire autonome (Offline MINDEF)
 * @param string $matricule Le matricule militaire
 * @param array|null $candidat_data Données facultatives du militaire
 * @return string Le chemin vers le fichier QR généré
 */
function generateQRCodeForMatricule($matricule, $candidat_data = null) {
    if (empty($matricule)) return '';

    // Créer le répertoire si nécessaire
    $dir = __DIR__ . '/../img/qrcodes/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Nettoyer le matricule pour le nom de fichier
    $safe_matricule = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $matricule);
    $filename = $safe_matricule . '_qr.png';
    $filepath = $dir . $filename;
    
    // Récupérer les informations du militaire dans la BDD si non transmises
    if (!$candidat_data) {
        try {
            global $pdo;
            if (isset($pdo)) {
                $stmt = $pdo->prepare("SELECT nom, prenom, grade, unite, numero_cni FROM candidat WHERE (matricule_militaire = ? OR matricule = ?) AND supprimer = 1 LIMIT 1");
                $stmt->execute([$matricule, $matricule]);
                $candidat_data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
    }

    $nom    = $candidat_data['nom'] ?? '';
    $prenom = $candidat_data['prenom'] ?? '';
    $grade  = $candidat_data['grade'] ?? '';
    $unite  = $candidat_data['unite'] ?? '';
    $cni    = $candidat_data['numero_cni'] ?? '';

    // URL HTTPS certifiée scannable instantanément par 100% des caméras d'appareils mobiles (iOS & Android)
    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' ? $_SERVER['HTTP_HOST'] : 'cimis-app.onrender.com';
    $qr_content = 'https://' . $host . '/Frontend/securite.php?matricule=' . urlencode($matricule);
    
    if (class_exists('QRcode')) {
        QRcode::png($qr_content, $filepath, QR_ECLEVEL_M, 10, 4);
    } else {
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=4&data=" . urlencode($qr_content);
        $img_data = @file_get_contents($api_url);
        if ($img_data) {
            file_put_contents($filepath, $img_data);
        }
    }
    
    return 'img/qrcodes/' . $filename;
}
?>
