<?php
// Générateur de QR Code local basé sur le matricule militaire
require_once __DIR__ . '/phpqrcode/qrlib.php';

/**
 * Génère un QR code PNG scannable par tout smartphone
 * @param string $matricule Le matricule militaire
 * @return string Le chemin vers le fichier QR généré
 */
function generateQRCodeForMatricule($matricule) {
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
    
    // URL HTTPS scannable par tout smartphone
    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' ? $_SERVER['HTTP_HOST'] : 'cimis-app.onrender.com';
    $qr_url = 'https://' . $host . '/Frontend/securite.php?matricule=' . urlencode($matricule);
    
    if (class_exists('QRcode')) {
        QRcode::png($qr_url, $filepath, QR_ECLEVEL_M, 8, 2);
    } else {
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=2&data=" . urlencode($qr_url);
        $img_data = @file_get_contents($api_url);
        if ($img_data) {
            file_put_contents($filepath, $img_data);
        }
    }
    
    return 'img/qrcodes/' . $filename;
}
?>
