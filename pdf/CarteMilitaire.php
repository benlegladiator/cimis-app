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
        $stmt = $this->pdo->prepare("SELECT * FROM candidat WHERE matricule = :matricule OR matricule_militaire = :matricule");
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
        // Tenter d'abord de charger Composer autoloader
        if (!class_exists('\Dompdf\Dompdf') && file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        // Si DomPDF n'est pas installé, basculer sur le rendu HD HTML Print
        if (!class_exists('\Dompdf\Dompdf')) {
            echo $this->genererHTMLCartePrintable();
            exit;
        }

        try {
            $carteHTML = $this->genererHTMLCarte();
            
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('size', 'a4');
            $options->set('orientation', 'landscape');
            
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($carteHTML);
            $dompdf->render();
            
            $nomFichier = 'carte_' . ($this->candidat['matricule'] ?? 'CIMIS') . '_' . date('Y-m-d') . '.pdf';
            $dompdf->stream($nomFichier, ['Attachment' => false]);
            exit;
            
        } catch (Exception $e) {
            // En cas d'erreur de Dompdf, basculer proprement sur l'impression navigateur
            echo $this->genererHTMLCartePrintable();
            exit;
        }
    }
    
    /**
     * Rendu HTML HD avec impression automatique
     */
    public function genererHTMLCartePrintable() {
        $carteHTML = renderCarte($this->candidat);
        $nom = htmlspecialchars(($this->candidat['nom'] ?? '') . ' ' . ($this->candidat['prenom'] ?? ''));
        $mat = htmlspecialchars($this->candidat['matricule'] ?? '');

        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impression Carte PVC - ' . $nom . ' (' . $mat . ')</title>
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #0f172a;
            color: #fff;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .print-toolbar {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 2rem;
            background: rgba(30, 41, 59, 0.9);
            padding: 1rem 2rem;
            border-radius: 14px;
            border: 1px solid rgba(74, 222, 128, 0.3);
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        .btn-print-now {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            font-weight: 700;
            border: none;
            padding: 0.85rem 1.75rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-print-now:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #34d399, #10b981);
        }
        .btn-close-print {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            text-decoration: none;
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        @media print {
            .print-toolbar { display: none !important; }
            body { background: white !important; padding: 0 !important; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button class="btn-print-now" onclick="window.print()">
            <i class="fa-solid fa-print"></i> IMPRIMER LA CARTE PVC / ENREGISTRER EN PDF
        </button>
        <a href="impression.php" class="btn-close-print">
            <i class="fa-solid fa-arrow-left"></i> RETOUR À LA LISTE
        </a>
    </div>
    <div class="carte-wrapper">
        ' . $carteHTML . '
    </div>
    <script>
        window.addEventListener("DOMContentLoaded", () => {
            setTimeout(() => { window.print(); }, 600);
        });
    </script>
</body>
</html>';
    }

    /**
     * Générer le HTML de la carte pour le PDF
     */
    private function genererHTMLCarte() {
        $carteHTML = renderCarte($this->candidat);
        
        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte Militaire - ' . htmlspecialchars($this->candidat['matricule']) . '</title>
    <link rel="stylesheet" href="' . __DIR__ . '/../css/styles_carte.css">
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { margin: 0; padding: 20px; background: white; font-family: Arial, sans-serif; }
        .carte-militaire-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .cards-row { display: flex; gap: 20px; justify-content: center; }
        .card-subsection { page-break-inside: avoid; }
        .id-card { box-shadow: 0 0 10px rgba(0,0,0,0.3); border: 1px solid #000; }
        .card-details, .verso-content { color: #000 !important; font-weight: 500; }
        .label { font-weight: bold !important; }
        .header, .actions, .top-status-bar, .security-footer { display: none !important; }
    </style>
</head>
<body>
    <div class="carte-militaire-container">
        ' . $carteHTML . '
    </div>
</body>
</html>';
    }
    
    /**
     * Générer le PDF pour plusieurs cartes
     */
    public static function genererPDFMultiple($matricules) {
        global $pdo;
        
        if (!class_exists('\Dompdf\Dompdf') && file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        // Si DomPDF n'est pas disponible, rediriger vers la prévisualisation multiple
        if (!class_exists('\Dompdf\Dompdf')) {
            $mats = implode(',', array_map('urlencode', (array)$matricules));
            header('Location: visualiser_carte.php?matricules=' . $mats);
            exit;
        }

        try {
            $cartesHTML = [];
            
            foreach ((array)$matricules as $matricule) {
                $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule = :matricule OR matricule_militaire = :matricule");
                $stmt->execute(['matricule' => $matricule]);
                $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($candidat) {
                    if (function_exists('decryptCandidatData')) {
                        $candidat = decryptCandidatData($candidat);
                    }
                    $cartesHTML[] = [
                        'candidat' => $candidat,
                        'html' => renderCarte($candidat)
                    ];
                }
            }
            
            if (empty($cartesHTML)) {
                header('Location: impression.php');
                exit;
            }
            
            $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes Militaires Multiple</title>
    <link rel="stylesheet" href="' . __DIR__ . '/../css/styles_carte.css">
    <style>
        @page { size: A4; margin: 10mm; }
        body { margin: 0; padding: 20px; background: white; font-family: Arial, sans-serif; }
        .carte-militaire-container { page-break-inside: avoid; margin-bottom: 30px; }
        .cards-row { display: flex; gap: 20px; justify-content: center; }
        .card-subsection { page-break-inside: avoid; }
        .id-card { box-shadow: 0 0 10px rgba(0,0,0,0.3); border: 1px solid #000; }
        .card-details, .verso-content { color: #000 !important; font-weight: 500; }
        .label { font-weight: bold !important; }
        .candidat-header { text-align: center; font-weight: bold; margin-bottom: 10px; color: #333; }
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
            
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('size', 'a4');
            
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->render();
            
            $nomFichier = 'cartes_multiples_' . date('Y-m-d_H-i-s') . '.pdf';
            $dompdf->stream($nomFichier, ['Attachment' => false]);
            exit;
            
        } catch (Exception $e) {
            $mats = implode(',', array_map('urlencode', (array)$matricules));
            header('Location: visualiser_carte.php?matricules=' . $mats);
            exit;
        }
    }
    
    public function getCandidat() {
        return $this->candidat;
    }
}
?>
