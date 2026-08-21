<?php
// pdf/CarteMilitaire.php - Génération PDF des cartes militaires

require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../Carte/confection_carte.php';

class CarteMilitaire {
    private $pdo;
    private $candidat;
    
    public function __construct($matricule) {
        global $pdo;
        $this->pdo = $pdo;
        
        // Récupérer le candidat
        $stmt = $this->pdo->prepare("SELECT * FROM candidat WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        $this->candidat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$this->candidat) {
            throw new Exception("Candidat non trouvé: " . $matricule);
        }
        
        // 🔐 Déchiffrer les données pour la génération PDF
        if (function_exists('decryptCandidatData')) {
            $this->candidat = decryptCandidatData($this->candidat);
        }
    }
    
    /**
     * Générer le PDF de la carte
     */
    public function genererPDF() {
        try {
            // Créer le HTML de la carte
            $carteHTML = $this->genererHTMLCarte();
            
            // Vérifier si DomPDF est disponible
            if (!class_exists('\Dompdf\Dompdf')) {
                throw new Exception("La bibliothèque DomPDF n'est pas installée. Veuillez installer composer require dompdf/dompdf");
            }
            
            // Options pour DomPDF
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('size', 'a4');
            $options->set('orientation', 'landscape');
            
            // Initialiser DomPDF
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Charger le HTML
            $dompdf->loadHtml($carteHTML);
            
            // Rendre le PDF
            $dompdf->render();
            
            // Nom du fichier
            $nomFichier = 'carte_' . $this->candidat['matricule'] . '_' . date('Y-m-d') . '.pdf';
            
            // Sortie du PDF
            $dompdf->stream($nomFichier, ['Attachment' => true]);
            
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la génération PDF: " . $e->getMessage());
        }
    }
    
    /**
     * Générer le HTML de la carte pour le PDF
     */
    private function genererHTMLCarte() {
        $carteHTML = renderCarte($this->candidat);
        
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte Militaire - ' . htmlspecialchars($this->candidat['matricule']) . '</title>
    <link rel="stylesheet" href="' . __DIR__ . '/../css/style_carte.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        
        body {
            margin: 0;
            padding: 20px;
            background: white;
            font-family: Arial, sans-serif;
        }
        
        .carte-militaire-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .cards-row {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        
        .card-subsection {
            page-break-inside: avoid;
        }
        
        .id-card {
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            border: 1px solid #000;
        }
        
        /* Styles specifiques pour l impression PDF */
        .card-details, .verso-content {
            color: #000 !important;
            font-weight: 500;
        }
        
        .label {
            font-weight: bold !important;
        }
        
        .header, .actions, .top-status-bar, .security-footer {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="carte-militaire-container">
        ' . $carteHTML . '
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Générer le PDF pour plusieurs cartes
     */
    public static function genererPDFMultiple($matricules) {
        global $pdo;
        
        try {
            $cartesHTML = [];
            
            foreach ($matricules as $matricule) {
                $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule = :matricule");
                $stmt->execute(['matricule' => $matricule]);
                $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($candidat) {
                    // 🔐 Déchiffrer les données pour chaque candidat
                    $candidat = decryptCandidatData($candidat);
                    $cartesHTML[] = [
                        'candidat' => $candidat,
                        'html' => renderCarte($candidat)
                    ];
                }
            }
            
            if (empty($cartesHTML)) {
                throw new Exception("Aucun candidat trouvé");
            }
            
            // Générer le HTML complet
            $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes Militaires Multiple</title>
    <link rel="stylesheet" href="' . __DIR__ . '/../css/style_carte.css">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body {
            margin: 0;
            padding: 20px;
            background: white;
            font-family: Arial, sans-serif;
        }
        
        .carte-militaire-container {
            page-break-inside: avoid;
            margin-bottom: 30px;
        }
        
        .cards-row {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        
        .card-subsection {
            page-break-inside: avoid;
        }
        
        .id-card {
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            border: 1px solid #000;
        }
        
        .card-details, .verso-content {
            color: #000 !important;
            font-weight: 500;
        }
        
        .label {
            font-weight: bold !important;
        }
        
        .candidat-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
    </style>
</head>
<body>';
            
            foreach ($cartesHTML as $carte) {
                $html .= '
    <div class="carte-militaire-container">
        <div class="candidat-header">
            ' . htmlspecialchars($carte['candidat']['nom'] . ' ' . $carte['candidat']['prenom']) . ' - 
            Matricule: ' . htmlspecialchars($carte['candidat']['matricule']) . '
        </div>
        ' . $carte['html'] . '
    </div>';
            }
            
            $html .= '</body></html>';
            
            // Options pour DomPDF
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('size', 'a4');
            
            // Initialiser DomPDF
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Charger le HTML
            $dompdf->loadHtml($html);
            
            // Rendre le PDF
            $dompdf->render();
            
            // Nom du fichier
            $nomFichier = 'cartes_multiples_' . date('Y-m-d_H-i-s') . '.pdf';
            
            // Sortie du PDF
            $dompdf->stream($nomFichier, ['Attachment' => true]);
            
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la génération PDF multiple: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir les informations du candidat
     */
    public function getCandidat() {
        return $this->candidat;
    }
}
?>
