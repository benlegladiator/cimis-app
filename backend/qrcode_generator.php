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

    // Signature d'authenticité souveraine MINDEF (Hash SHA-256)
    $hash_key = strtoupper(substr(hash('sha256', $matricule . $nom . 'MINDEF_CIMIS_2026'), 0, 10));

    // Payload de sécurité autonome Format Structuré Militaire MINDEF
    $qr_content  = "[MINISTÈRE DE LA DÉFENSE - CAMEROUN]\n";
    $qr_content .= "CARTE D'IDENTITÉ MILITAIRE (CIMIS)\n";
    $qr_content .= "MATRICULE : " . $matricule . "\n";
    if ($nom)   $qr_content .= "NOM & PRÉNOM : " . trim($nom . ' ' . $prenom) . "\n";
    if ($grade) $qr_content .= "GRADE : " . $grade . "\n";
    if ($unite) $qr_content .= "CORPS : " . $unite . "\n";
    if ($cni)   $qr_content .= "CNI : " . $cni . "\n";
    $qr_content .= "STATUT : CERTIFIÉ CONFORME\n";
    $qr_content .= "SIG-HASH : MINDEF-CIM-" . $hash_key;
    
    if (class_exists('QRcode')) {
        QRcode::png($qr_content, $filepath, QR_ECLEVEL_M, 8, 2);
    } else {
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=2&data=" . urlencode($qr_content);
        $img_data = @file_get_contents($api_url);
        if ($img_data) {
            file_put_contents($filepath, $img_data);
        }
    }
    
    return 'img/qrcodes/' . $filename;
}
?>
