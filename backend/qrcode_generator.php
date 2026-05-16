<?php
// Générateur de QR Code local basé sur le matricule militaire
require_once __DIR__ . '/phpqrcode/qrlib.php';

/**
 * Génère un QR code PNG basé sur le matricule militaire
 * @param string $matricule Le matricule militaire
 * @return string Le chemin vers le fichier QR généré
 */
function generateQRCodeForMatricule($matricule) {
    // Créer le répertoire si nécessaire
    $dir = __DIR__ . '/../img/qrcodes/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Nettoyer le matricule pour le nom de fichier
    $safe_matricule = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $matricule);
    
    // Générer un nom de fichier unique
    $filename = $safe_matricule . '_qr.png';
    $filepath = $dir . $filename;
    
    // Générer le QR code en taille 150x150 pixels
    QRcode::png($matricule, $filepath, QR_ECLEVEL_L, 3);
    
    // Retourner le chemin web (accessible depuis Frontend/)
    return '../img/qrcodes/' . $filename;
}
?>
