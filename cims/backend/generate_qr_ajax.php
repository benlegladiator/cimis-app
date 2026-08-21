<?php
// Endpoint AJAX pour générer un QR code
header('Content-Type: application/json');

require_once 'qrcode_generator.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricule'])) {
        $matricule = trim($_POST['matricule']);
        
        if (empty($matricule)) {
            throw new Exception('Matricule vide');
        }
        
        // Générer le QR code
        $qrPath = generateQRCodeForMatricule($matricule);
        
        // Vérifier que le fichier a été créé
        $fullPath = __DIR__ . '/../' . $qrPath;
        if (!file_exists($fullPath)) {
            throw new Exception('Fichier QR non généré');
        }
        
        echo json_encode([
            'success' => true,
            'matricule' => $matricule,
            'qr_path' => $qrPath,
            'full_path' => $fullPath
        ]);
        
    } else {
        throw new Exception('Requête invalide');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
