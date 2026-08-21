<?php
// API SIADOC Envoi - Envoi des données CIMIS à SIADOC
require_once 'backend/config.php';

// Configuration de la réponse en JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration SIADOC
define('SIADOC_API_URL', 'https://siadoc.gt.tc/api/');
define('SIADOC_API_KEY', 'SIADOC_CIMIS_2026_KEY');
define('CIMIS_API_KEY', 'CIMIS_SIADOC_2026_KEY');

// Fonction pour envoyer une réponse d'erreur
function sendErrorResponse($message, $http_code = 400) {
    http_response_code($http_code);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

// Fonction pour envoyer une réponse de succès
function sendSuccessResponse($data, $message = 'Opération réussie') {
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
}

// Fonction pour encoder une image en base64
function encodeImageToBase64($image_path) {
    if (file_exists($image_path)) {
        $image_data = file_get_contents($image_path);
        return 'data:image/png;base64,' . base64_encode($image_data);
    }
    return null;
}

// Fonction pour envoyer des données à SIADOC
function sendToSIADOC($endpoint, $data) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => SIADOC_API_URL . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . CIMIS_API_KEY,
            'X-API-Key: ' . CIMIS_API_KEY
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erreur cURL: $error");
    }
    
    return [
        'response' => json_decode($response, true),
        'http_code' => $http_code,
        'raw_response' => $response
    ];
}

// Fonction pour préparer les données de carte pour SIADOC
function prepareCarteData($candidat) {
    $qr_code_base64 = null;
    $empreinte_base64 = null;
    
    // Encoder le QR code en base64
    if ($candidat['code_qr'] && file_exists($candidat['code_qr'])) {
        $qr_code_base64 = encodeImageToBase64($candidat['code_qr']);
    }
    
    // Encoder l'empreinte (si disponible)
    if ($candidat['empreinte_data']) {
        // Si c'est déjà en base64, le garder tel quel
        if (strpos($candidat['empreinte_data'], 'base64') !== false) {
            $empreinte_base64 = $candidat['empreinte_data'];
        } else {
            // Sinon, encoder en base64
            $empreinte_base64 = 'data:application/octet-stream;base64,' . base64_encode($candidat['empreinte_data']);
        }
    }
    
    return [
        'matricule_militaire' => $candidat['matricule_militaire'],
        'matricule_cimis' => $candidat['matricule'],
        'nom' => $candidat['nom'],
        'prenom' => $candidat['prenom'],
        'qr_code' => $qr_code_base64,
        'empreinte' => $empreinte_base64,
        'date_generation_carte' => $candidat['date_creation_carte'],
        'date_expiration_carte' => $candidat['date_expiration_carte'],
        'statut_carte' => $candidat['statut_carte'],
        'timestamp_envoi' => date('c')
    ];
}

// Router API
$request_method = $_SERVER['REQUEST_METHOD'];

// Endpoint principal pour envoyer les données à SIADOC
if ($request_method === 'POST') {
    try {
        // Récupérer les données de la requête
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendErrorResponse('Données JSON invalides');
        }
        
        // Valider les paramètres
        if (!isset($input['action'])) {
            sendErrorResponse('Action non spécifiée');
        }
        
        switch ($input['action']) {
            case 'envoyer_carte':
                // Envoyer les données d'une carte spécifique
                if (!isset($input['matricule_militaire'])) {
                    sendErrorResponse('Matricule militaire non spécifié');
                }
                
                // Récupérer le candidat
                $stmt = $pdo->prepare("
                    SELECT * FROM candidat 
                    WHERE matricule_militaire = ? AND statut_carte = 'ACTIVE'
                ");
                $stmt->execute([$input['matricule_militaire']]);
                $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$candidat) {
                    sendErrorResponse('Candidat non trouvé ou carte non active', 404);
                }
                
                // Préparer les données
                $carte_data = prepareCarteData($candidat);
                
                // Envoyer à SIADOC
                $siadoc_response = sendToSIADOC('cimis/recevoir_carte', [
                    'source' => 'CIMIS',
                    'type' => 'CARTE_GENEREE',
                    'timestamp' => date('c'),
                    'data' => $carte_data
                ]);
                
                // Logger l'envoi
                $stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, last_sync) 
                    VALUES ('SIADOC_ENVOI', NOW())
                ");
                $stmt->execute();
                
                sendSuccessResponse([
                    'matricule_militaire' => $candidat['matricule_militaire'],
                    'matricule_cimis' => $candidat['matricule'],
                    'siadoc_response' => $siadoc_response,
                    'envoi_timestamp' => date('c')
                ], 'Carte envoyée à SIADOC avec succès');
                
                break;
                
            case 'envoyer_toutes_cartes':
                // Envoyer toutes les cartes actives
                $stmt = $pdo->prepare("
                    SELECT * FROM candidat 
                    WHERE statut_carte = 'ACTIVE' AND code_qr IS NOT NULL
                    ORDER BY date_creation_carte DESC
                    LIMIT 50
                ");
                $stmt->execute();
                $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($candidats)) {
                    sendErrorResponse('Aucune carte active à envoyer');
                }
                
                $envois_reussis = [];
                $envois_echoues = [];
                
                foreach ($candidats as $candidat) {
                    try {
                        $carte_data = prepareCarteData($candidat);
                        
                        $siadoc_response = sendToSIADOC('cimis/recevoir_carte', [
                            'source' => 'CIMIS',
                            'type' => 'CARTE_GENEREE',
                            'timestamp' => date('c'),
                            'data' => $carte_data
                        ]);
                        
                        $envois_reussis[] = [
                            'matricule_militaire' => $candidat['matricule_militaire'],
                            'matricule_cimis' => $candidat['matricule'],
                            'response' => $siadoc_response['http_code']
                        ];
                        
                    } catch (Exception $e) {
                        $envois_echoues[] = [
                            'matricule_militaire' => $candidat['matricule_militaire'],
                            'matricule_cimis' => $candidat['matricule'],
                            'error' => $e->getMessage()
                        ];
                    }
                }
                
                // Logger l'envoi
                $stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, last_sync) 
                    VALUES ('SIADOC_ENVOI_MASSE', NOW())
                ");
                $stmt->execute();
                
                sendSuccessResponse([
                    'total_cartes' => count($candidats),
                    'envois_reussis' => count($envois_reussis),
                    'envois_echoues' => count($envois_echoues),
                    'details_reussis' => $envois_reussis,
                    'details_echoues' => $envois_echoues,
                    'envoi_timestamp' => date('c')
                ], 'Envoi massif terminé');
                
                break;
                
            case 'envoyer_par_periode':
                // Envoyer les cartes créées dans une période
                if (!isset($input['date_debut']) || !isset($input['date_fin'])) {
                    sendErrorResponse('Date de début et de fin requises');
                }
                
                $stmt = $pdo->prepare("
                    SELECT * FROM candidat 
                    WHERE statut_carte = 'ACTIVE' 
                    AND date_creation_carte BETWEEN ? AND ?
                    AND code_qr IS NOT NULL
                    ORDER BY date_creation_carte DESC
                ");
                $stmt->execute([$input['date_debut'], $input['date_fin']]);
                $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($candidats)) {
                    sendErrorResponse('Aucune carte trouvée dans cette période');
                }
                
                $envois_reussis = [];
                $envois_echoues = [];
                
                foreach ($candidats as $candidat) {
                    try {
                        $carte_data = prepareCarteData($candidat);
                        
                        $siadoc_response = sendToSIADOC('cimis/recevoir_carte', [
                            'source' => 'CIMIS',
                            'type' => 'CARTE_GENEREE',
                            'timestamp' => date('c'),
                            'data' => $carte_data
                        ]);
                        
                        $envois_reussis[] = [
                            'matricule_militaire' => $candidat['matricule_militaire'],
                            'matricule_cimis' => $candidat['matricule'],
                            'response' => $siadoc_response['http_code']
                        ];
                        
                    } catch (Exception $e) {
                        $envois_echoues[] = [
                            'matricule_militaire' => $candidat['matricule_militaire'],
                            'matricule_cimis' => $candidat['matricule'],
                            'error' => $e->getMessage()
                        ];
                    }
                }
                
                sendSuccessResponse([
                    'periode' => $input['date_debut'] . ' au ' . $input['date_fin'],
                    'total_cartes' => count($candidats),
                    'envois_reussis' => count($envois_reussis),
                    'envois_echoues' => count($envois_echoues),
                    'details_reussis' => $envois_reussis,
                    'details_echoues' => $envois_echoues,
                    'envoi_timestamp' => date('c')
                ], 'Envoi par période terminé');
                
                break;
                
            default:
                sendErrorResponse('Action non reconnue');
        }
        
    } catch (Exception $e) {
        sendErrorResponse('Erreur lors du traitement: ' . $e->getMessage(), 500);
    }
}

// Endpoint GET pour consulter les statistiques d'envoi
elseif ($request_method === 'GET') {
    try {
        $action = $_GET['action'] ?? 'stats';
        
        switch ($action) {
            case 'stats':
                // Statistiques des envois
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_envois,
                        MAX(last_sync) as dernier_envoi,
                        COUNT(CASE WHEN system LIKE '%MASSE%' THEN 1 END) as envois_masses,
                        COUNT(CASE WHEN system = 'SIADOC_ENVOI' THEN 1 END) as envois_individuels
                    FROM api_sync_log 
                    WHERE system LIKE 'SIADOC_ENVOI%'
                ");
                $stmt->execute();
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Statistiques des cartes
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_cartes,
                        COUNT(CASE WHEN statut_carte = 'ACTIVE' THEN 1 END) as cartes_actives,
                        COUNT(CASE WHEN code_qr IS NOT NULL THEN 1 END) as cartes_avec_qr,
                        COUNT(CASE WHEN empreinte_data IS NOT NULL THEN 1 END) as cartes_avec_empreinte
                    FROM candidat 
                    WHERE type_personnel = 'MILITAIRE'
                ");
                $stmt->execute();
                $cartes_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendSuccessResponse([
                    'envois_siadoc' => $stats,
                    'cartes_cimis' => $cartes_stats,
                    'timestamp' => date('c')
                ], 'Statistiques récupérées');
                
                break;
                
            case 'cartes_a_envoyer':
                // Lister les cartes prêtes à envoyer
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $stmt = $pdo->prepare("
                    SELECT 
                        matricule_militaire, matricule, nom, prenom, grade, unite,
                        date_creation_carte, statut_carte, code_qr, empreinte_data
                    FROM candidat 
                    WHERE statut_carte = 'ACTIVE' AND code_qr IS NOT NULL
                    ORDER BY date_creation_carte DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$limit, $offset]);
                $cartes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Compter le total
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM candidat 
                    WHERE statut_carte = 'ACTIVE' AND code_qr IS NOT NULL
                ");
                $stmt->execute();
                $total = $stmt->fetch()['total'];
                
                sendSuccessResponse([
                    'cartes' => $cartes,
                    'pagination' => [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                ], 'Liste des cartes à envoyer');
                
                break;
                
            default:
                sendErrorResponse('Action GET non reconnue');
        }
        
    } catch (Exception $e) {
        sendErrorResponse('Erreur lors de la récupération: ' . $e->getMessage(), 500);
    }
}

// Endpoint non trouvé
else {
    sendErrorResponse('Endpoint non trouvé ou méthode non autorisée', 404);
}
?>
