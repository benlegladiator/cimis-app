<?php
/**
 * SIADOC IMPORT - Importation des données SIADOC vers CIMIS
 * Ce fichier appelle les données SIADOC et les enregistre automatiquement en base
 */

require_once 'config.php';

// Configuration SIADOC
define('SIADOC_API_URL', 'https://siadoc.gt.tc/api/');
define('SIADOC_API_KEY', 'siadoc-2026-cimis-integration');

// Fonction pour appeler l'API SIADOC
function callSIADOCAPI($endpoint, $params = [], $method = 'GET') {
    $url = SIADOC_API_URL . $endpoint;
    
    $ch = curl_init();
    
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . SIADOC_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erreur cURL: $error");
    }
    
    return [
        'data' => json_decode($response, true),
        'http_code' => $http_code,
        'raw_response' => $response
    ];
}

// Fonction pour générer un matricule CIMIS
function generateCIMISMatricule() {
    $prefix = 'CIM-';
    $year = date('Y');
    
    // Récupérer le dernier numéro de séquence
    global $pdo;
    $stmt = $pdo->prepare("SELECT MAX(SUBSTRING(matricule, -4)) as max_seq FROM candidat WHERE matricule LIKE ?");
    $stmt->execute([$prefix . $year . '%']);
    $result = $stmt->fetch();
    
    $sequence = $result['max_seq'] ? $result['max_seq'] + 1 : 1;
    $sequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);
    
    return $prefix . $year . $sequence;
}

// Fonction pour générer un QR code
function generateQRCode($matricule, $matricule_cimis) {
    $qr_data = "https://cimis.cm/verify/" . $matricule;
    $qr_filename = '../img/qrcodes/' . $matricule . '_qr.png';
    
    // Créer le répertoire si nécessaire
    if (!file_exists('../img/qrcodes')) {
        mkdir('../img/qrcodes', 0777, true);
    }
    
    // Simulation de génération de QR code (à remplacer par vraie librairie)
    $qr_image = imagecreatetruecolor(200, 200);
    $bg_color = imagecolorallocate($qr_image, 255, 255, 255);
    $fg_color = imagecolorallocate($qr_image, 0, 0, 0);
    
    imagefill($qr_image, 0, 0, $bg_color);
    imagestring($qr_image, 5, 30, 90, "QR: " . substr($matricule, -10), $fg_color);
    
    imagepng($qr_image, $qr_filename);
    imagedestroy($qr_image);
    
    return [
        'image_path' => $qr_filename,
        'content' => $qr_data
    ];
}

// Fonction pour logger les opérations
function logOperation($operation, $details, $status = 'SUCCESS') {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO api_sync_log (system, action, status, details, last_sync) 
        VALUES ('SIADOC_IMPORT', ?, ?, ?, NOW())
    ");
    $stmt->execute([$operation, $details, $status]);
}

// Fonction pour logger les détails de synchronisation
function logSyncDetails($candidat_id, $matricule_militaire, $operation_type, $status, $details = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO siadoc_sync_details (candidat_id, matricule_militaire, operation_type, operation_status, details, operation_date) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$candidat_id, $matricule_militaire, $operation_type, $status, $details]);
}

// Fonction principale pour importer un militaire
function importerMilitaire($militaire_data) {
    global $pdo;
    
    try {
        // Vérifier si le militaire existe déjà
        $stmt = $pdo->prepare("SELECT id FROM candidat WHERE matricule_militaire = ?");
        $stmt->execute([$militaire_data['matricule']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Militaire déjà existant',
                'matricule' => $militaire_data['matricule']
            ];
        }
        
        // Générer matricule CIMIS et QR code
        $matricule_cimis = generateCIMISMatricule();
        $qr_data = generateQRCode($militaire_data['matricule'], $matricule_cimis);
        
        // Insérer dans la base CIMIS
        $stmt = $pdo->prepare("
            INSERT INTO candidat (
                matricule, matricule_militaire, nom, prenom, 
                date_naissance, sexe, grade, unite, 
                code_qr, source_system, date_enrolement, type_personnel,
                statut_carte, supprimer, siadoc_sync_date, siadoc_sync_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'MILITAIRE', 'ACTIVE', 1, NOW(), 'SYNCED')
        ");
        
        $stmt->execute([
            $matricule_cimis,
            $militaire_data['matricule'],
            strtoupper($militaire_data['nom']),
            ucfirst(strtolower($militaire_data['prenom'])),
            $militaire_data['dateNaissance'],
            strtoupper($militaire_data['sexe']) === 'M' ? 'MASCULIN' : 'FEMININ',
            strtoupper($militaire_data['grade']),
            $militaire_data['corps'] ?? $militaire_data['unite'],
            $qr_data['image_path'],
            'SIADOC'
        ]);
        
        $candidat_id = $pdo->lastInsertId();
        
        // Logger les détails
        logSyncDetails($candidat_id, $militaire_data['matricule'], 'IMPORT', 'SUCCESS', 'Importé depuis SIADOC');
        
        return [
            'success' => true,
            'message' => 'Militaire importé avec succès',
            'matricule_militaire' => $militaire_data['matricule'],
            'matricule_cimis' => $matricule_cimis,
            'qr_code' => $qr_data['image_path'],
            'candidat_id' => $candidat_id
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'import: ' . $e->getMessage(),
            'matricule' => $militaire_data['matricule'] ?? 'Unknown'
        ];
    }
}

// Fonction pour importer plusieurs militaires
function importerMilitairesMultiples($matricules) {
    $resultats = [];
    $succes = 0;
    $erreurs = 0;
    
    foreach ($matricules as $matricule) {
        try {
            // Appeler SIADOC pour récupérer les infos du militaire
            $siadoc_result = callSIADOCAPI('export/militaire/info', [
                'matricule' => $matricule
            ]);
            
            if ($siadoc_result['http_code'] === 200 && $siadoc_result['data']) {
                $resultat = importerMilitaire($siadoc_result['data']);
                $resultats[] = $resultat;
                
                if ($resultat['success']) {
                    $succes++;
                } else {
                    $erreurs++;
                }
            } else {
                $resultats[] = [
                    'success' => false,
                    'message' => 'Militaire non trouvé dans SIADOC',
                    'matricule' => $matricule
                ];
                $erreurs++;
            }
            
        } catch (Exception $e) {
            $resultats[] = [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'matricule' => $matricule
            ];
            $erreurs++;
        }
    }
    
    // Logger l'opération globale
    logOperation('IMPORT_MULTIPLE', "Import de " . count($matricules) . " militaires: $succes succès, $erreurs erreurs", $erreurs === 0 ? 'SUCCESS' : 'PARTIAL');
    
    return [
        'total' => count($matricules),
        'succes' => $succes,
        'erreurs' => $erreurs,
        'resultats' => $resultats
    ];
}

// Fonction pour importer par période
function importerParPeriode($date_debut, $date_fin) {
    try {
        // Appeler SIADOC pour récupérer les militaires de la période
        $siadoc_result = callSIADOCAPI('export/militaires/periode', [
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ]);
        
        if ($siadoc_result['http_code'] !== 200) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'appel API SIADOC: ' . ($siadoc_result['data']['error'] ?? 'Unknown error')
            ];
        }
        
        $militaires = $siadoc_result['data']['militaires'] ?? [];
        
        if (empty($militaires)) {
            return [
                'success' => true,
                'message' => 'Aucun militaire trouvé pour cette période',
                'total' => 0,
                'periode' => "$date_debut au $date_fin"
            ];
        }
        
        // Importer tous les militaires
        $matricules = array_column($militaires, 'matricule');
        $resultat = importerMilitairesMultiples($matricules);
        
        $resultat['periode'] = "$date_debut au $date_fin";
        
        return $resultat;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'import par période: ' . $e->getMessage()
        ];
    }
}

// Fonction pour importer tous les militaires
function importerTousLesMilitaires() {
    try {
        // Appeler SIADOC pour récupérer tous les militaires
        $siadoc_result = callSIADOCAPI('export/militaires/tous');
        
        if ($siadoc_result['http_code'] !== 200) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'appel API SIADOC: ' . ($siadoc_result['data']['error'] ?? 'Unknown error')
            ];
        }
        
        $militaires = $siadoc_result['data']['militaires'] ?? [];
        
        if (empty($militaires)) {
            return [
                'success' => true,
                'message' => 'Aucun militaire trouvé dans SIADOC',
                'total' => 0
            ];
        }
        
        // Importer tous les militaires
        $matricules = array_column($militaires, 'matricule');
        $resultat = importerMilitairesMultiples($matricules);
        
        return $resultat;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'import global: ' . $e->getMessage()
        ];
    }
}

// Router les requêtes
$request_method = $_SERVER['REQUEST_METHOD'];
$path_info = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path_info, '/'));

$action = $path_parts[1] ?? 'help';

// Configuration de la réponse en JSON
header('Content-Type: application/json');

switch ($action) {
    case 'help':
        echo json_encode([
            'endpoints' => [
                'GET /siadoc_import.php/help' => 'Documentation des endpoints',
                'POST /siadoc_import.php/importer' => 'Importer un militaire spécifique',
                'POST /siadoc_import.php/importer_multiple' => 'Importer plusieurs militaires',
                'POST /siadoc_import.php/importer_periode' => 'Importer par période',
                'POST /siadoc_import.php/importer_tous' => 'Importer tous les militaires',
                'GET /siadoc_import.php/statistiques' => 'Statistiques d\'importation'
            ],
            'version' => '1.0.0',
            'system' => 'SIADOC_IMPORT',
            'description' => 'API d\'importation des données SIADOC vers CIMIS'
        ]);
        break;

    case 'importer':
        // Importer un militaire spécifique
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['matricule'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Matricule requis'
            ]);
            break;
        }
        
        try {
            $siadoc_result = callSIADOCAPI('export/militaire/info', [
                'matricule' => $input['matricule']
            ]);
            
            if ($siadoc_result['http_code'] === 200 && $siadoc_result['data']) {
                $resultat = importerMilitaire($siadoc_result['data']);
                echo json_encode($resultat);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Militaire non trouvé dans SIADOC',
                    'matricule' => $input['matricule']
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
        break;

    case 'importer_multiple':
        // Importer plusieurs militaires
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['matricules']) || !is_array($input['matricules'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Liste de matricules requise'
            ]);
            break;
        }
        
        $resultat = importerMilitairesMultiples($input['matricules']);
        echo json_encode($resultat);
        break;

    case 'importer_periode':
        // Importer par période
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['date_debut']) || !isset($input['date_fin'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Date de début et de fin requises'
            ]);
            break;
        }
        
        $resultat = importerParPeriode($input['date_debut'], $input['date_fin']);
        echo json_encode($resultat);
        break;

    case 'importer_tous':
        // Importer tous les militaires
        if ($request_method !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Méthode POST requise'
            ]);
            break;
        }
        
        $resultat = importerTousLesMilitaires();
        echo json_encode($resultat);
        break;

    case 'statistiques':
        // Statistiques d'importation
        try {
            global $pdo;
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_imports,
                    COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc,
                    COUNT(CASE WHEN siadoc_sync_status = 'SYNCED' THEN 1 END) as synchronises,
                    MAX(siadoc_sync_date) as derniere_sync
                FROM candidat 
                WHERE source_system = 'SIADOC'
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("
                SELECT action, COUNT(*) as nombre, MAX(last_sync) as derniere_operation
                FROM api_sync_log 
                WHERE system = 'SIADOC_IMPORT'
                GROUP BY action
                ORDER BY derniere_operation DESC
            ");
            $stmt->execute();
            $operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'generales' => $stats,
                'operations' => $operations,
                'date_generation' => date('c')
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint non trouvé',
            'available_endpoints' => [
                'help', 'importer', 'importer_multiple', 'importer_periode', 'importer_tous', 'statistiques'
            ]
        ]);
        break;
}
?>
