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
                $stmt = $pdo->prepare("SELECT matricule_militaire, matricule, nom, prenom, sexe, date_naissance, numero_cni, grade, unite, suspendus FROM candidat WHERE (matricule_militaire = ? OR matricule = ?) AND supprimer = 1 LIMIT 1");
                $stmt->execute([$matricule, $matricule]);
                $candidat_data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
    }

    $mat_mil = $candidat_data['matricule_militaire'] ?? $candidat_data['matricule'] ?? $matricule;
    $nom     = $candidat_data['nom'] ?? 'CANDIDAT';
    $prenom  = $candidat_data['prenom'] ?? '';
    $sexe    = $candidat_data['sexe'] ?? 'MASCULIN';
    $dob     = $candidat_data['date_naissance'] ?? '';
    $cni     = $candidat_data['numero_cni'] ?? '';
    $grade   = $candidat_data['grade'] ?? 'MILITAIRE';
    $unite   = $candidat_data['unite'] ?? 'MINDEF';
    $statut  = (!empty($candidat_data['suspendus']) && $candidat_data['suspendus'] == 1) ? 'SUSPENDU' : 'ACTIF';
    $time    = time();
    $sig     = hash('sha256', $mat_mil . $nom . 'MINDEF_CIMIS_2026');

    // Objet JSON identique au prototype d'origine (Haute Densité de Motifs)
    $qr_payload = [
        'matricule'      => $mat_mil,
        'nom'            => $nom,
        'prenom'         => $prenom,
        'sexe'           => $sexe,
        'date_naissance' => $dob,
        'cni'            => $cni,
        'grade'          => $grade,
        'unite'          => $unite,
        'statut'         => $statut,
        'timestamp'      => $time,
        'signature'      => $sig
    ];

    $json_content = json_encode($qr_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Forcer la suppression de l'ancien fichier image en cache s'il existe
    if (file_exists($filepath)) {
        @unlink($filepath);
    }

    if (class_exists('QRcode')) {
        QRcode::png($json_content, $filepath, QR_ECLEVEL_M, 8, 4);
    } else {
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=4&data=" . urlencode($json_content);
        $img_data = @file_get_contents($api_url);
        if ($img_data) {
            file_put_contents($filepath, $img_data);
        }
    }
    
    return 'img/qrcodes/' . $filename;
}
?>
