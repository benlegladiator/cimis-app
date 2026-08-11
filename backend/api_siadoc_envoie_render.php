<?php
/**
 * API SIADOC ENVOIE - VERSION POUR RENDER
 * Optimisée pour Render + PostgreSQL
 * Compatible avec SIADOC (Java Spring Boot + Angular)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');
header('Access-Control-Max-Age: 86400');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration (config.php dynamique avec fallback config_render_postgresql.php)
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/config_render_postgresql.php')) {
    require_once __DIR__ . '/config_render_postgresql.php';
}

if (!defined('SIADOC_API_KEY')) {
    define('SIADOC_API_KEY', 'siadoc-2026-cimis-integration');
}

// Fonction de vérification stricte de la Clé API
function verifyApiKey() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $headers_lower = array_change_key_case($headers, CASE_LOWER);

    $api_key = $headers_lower['x-api-key']
        ?? $headers_lower['authorization']
        ?? $_SERVER['HTTP_X_API_KEY']
        ?? $_GET['api_key']
        ?? $_POST['api_key']
        ?? null;

    if ($api_key && str_starts_with($api_key, 'Bearer ')) {
        $api_key = substr($api_key, 7);
    }

    if (!$api_key || $api_key !== SIADOC_API_KEY) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error'   => 'Clé API invalide ou manquante',
            'code'    => 'INVALID_API_KEY',
            'timestamp' => date('c')
        ]);
        exit();
    }
}

// Fonction pour encoder une image en base64
function encodeImageToBase64($image_path) {
    if (empty($image_path)) {
        return null;
    }
    
    // Essayer plusieurs chemins possibles pour Render
    $paths_to_try = [
        $_SERVER['DOCUMENT_ROOT'] . '/' . $image_path,
        $_SERVER['DOCUMENT_ROOT'] . '/public/' . $image_path,
        $_SERVER['DOCUMENT_ROOT'] . '/src/' . $image_path,
        $image_path
    ];
    
    foreach ($paths_to_try as $path) {
        if (file_exists($path)) {
            $image_data = file_get_contents($path);
            $mime_type = mime_content_type($path);
            return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
        }
    }
    
    // Logger les chemins essayés pour débogage
    error_log("Image non trouvée: " . $image_path . " | Chemins essayés: " . implode(', ', $paths_to_try));
    return null;
}

// Fonction pour envoyer une réponse JSON
function sendResponse($data, $message = null, $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => true, 'data' => $data];
    if ($message) {
        $response['message'] = $message;
    }
    echo json_encode($response);
}

// Fonction pour envoyer une erreur
function sendError($message, $http_code = 400) {
    http_response_code($http_code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('c'),
        'environment' => 'Render'
    ]);
}

// Router les requêtes
$action = $_GET['action'] ?? 'help';

// SÉCURITÉ : Vérifier la clé API pour toutes les actions de données
if (in_array($action, ['carte', 'cartes', 'statistiques', 'debug', 'sync'])) {
    verifyApiKey();
}

switch ($action) {
    case 'help':
        sendResponse([
            'endpoints' => [
                'GET /api_siadoc_envoie.php?action=cartes' => 'Liste de tous les militaires',
                'GET /api_siadoc_envoie.php?action=carte&matricule=XXX' => 'Détails d\'un militaire',
                'GET /api_siadoc_envoie.php?action=statistiques' => 'Statistiques simples',
                'GET /api_siadoc_envoie.php?action=debug' => 'Débogage des chemins d\'images',
                'GET /api_siadoc_envoie.php?action=ping' => 'Test de connexion SIADOC ↔ CIMIS'
            ],
            'version' => '1.0.0-RENDER-POSTGRESQL',
            'system' => 'CIMIS',
            'environment' => 'Render + PostgreSQL',
            'note' => 'API optimisée pour Render + interconnexion SIADOC'
        ]);
        break;

    case 'ping':
        // Endpoint pour tester la connexion SIADOC ↔ CIMIS
        sendResponse([
            'status' => 'UP',
            'message' => 'Connexion SIADOC ↔ CIMIS opérationnelle',
            'timestamp' => date('c'),
            'system' => 'CIMIS sur Render',
            'siadoc_origin' => $_SERVER['HTTP_ORIGIN'] ?? 'unknown'
        ]);
        break;

    case 'carte':
        // Récupérer un militaire spécifique
        $matricule = $_GET['matricule'] ?? null;
        if (!$matricule) {
            sendError('Matricule requis');
            break;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.sexe,
                    c.grade,
                    c.unite,
                    c.photo,
                    c.code_qr,
                    c.groupe_sanguin,
                    c.taille,
                    c.poids
                FROM candidat c
                WHERE (c.matricule = ? OR c.matricule_militaire = ?)
                AND c.supprimer = 1
                LIMIT 1
            ");
            $stmt->execute([$matricule, $matricule]);
            $militaire = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$militaire) {
                sendError('Militaire non trouvé', 404);
                break;
            }

            // Encoder les images en base64
            if ($militaire['photo']) {
                $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $militaire['photo'];
                $militaire['photo_base64'] = encodeImageToBase64($militaire['photo']);
            }
            if ($militaire['code_qr']) {
                $qr_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $militaire['code_qr'];
                $militaire['qr_code_base64'] = encodeImageToBase64($militaire['code_qr']);
            }

            sendResponse($militaire, 'Militaire récupéré');

        } catch (PDOException $e) {
            sendError('Erreur base de données PostgreSQL: ' . $e->getMessage());
        }
        break;

    case 'cartes':
        // Liste de tous les militaires (données importantes seulement)
        try {
            $sql = "
                SELECT 
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.sexe,
                    c.grade,
                    c.unite,
                    c.photo,
                    c.code_qr,
                    c.groupe_sanguin,
                    c.taille,
                    c.poids
                FROM candidat c
                WHERE c.supprimer = 1
            ";
            $params = [];

            // Filtres simples
            if (isset($_GET['matricule'])) {
                $sql .= " AND c.matricule_militaire = ?";
                $params[] = $_GET['matricule'];
            }

            if (isset($_GET['grade'])) {
                $sql .= " AND c.grade = ?";
                $params[] = $_GET['grade'];
            }

            if (isset($_GET['unite'])) {
                $sql .= " AND c.unite = ?";
                $params[] = $_GET['unite'];
            }

            // Recherche par nom
            if (isset($_GET['search'])) {
                $sql .= " AND (c.nom LIKE ? OR c.prenom LIKE ?)";
                $search = '%' . $_GET['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = ($page - 1) * $limit;

            $sql .= " ORDER BY c.nom, c.prenom LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $militaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compter le total pour pagination
            $count_sql = str_replace("ORDER BY c.nom, c.prenom LIMIT " . (int)$limit . " OFFSET " . (int)$offset, "", $sql);
            $count_sql = str_replace("c.matricule, c.matricule_militaire, c.nom, c.prenom, c.date_naissance, c.sexe, c.grade, c.unite, c.photo, c.code_qr, c.groupe_sanguin, c.taille, c.poids", "COUNT(*) as total", $count_sql);
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $result = $count_stmt->fetch();
            $total = isset($result['total']) ? $result['total'] : 0;

            sendResponse([
                'militaires' => $militaires,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données PostgreSQL: ' . $e->getMessage());
        }
        break;

    case 'statistiques':
        // Statistiques simples
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_militaires,
                    COUNT(DISTINCT grade) as nb_grades,
                    COUNT(DISTINCT unite) as nb_unites,
                    COUNT(CASE WHEN photo IS NOT NULL THEN 1 END) as avec_photo,
                    COUNT(CASE WHEN code_qr IS NOT NULL THEN 1 END) as avec_qr
                FROM candidat 
                WHERE supprimer = 1
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            sendResponse([
                'statistiques' => $stats,
                'date_generation' => date('c'),
                'environment' => 'Render + PostgreSQL'
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données PostgreSQL: ' . $e->getMessage());
        }
        break;

    case 'debug':
        // Endpoint de débogage pour vérifier les chemins d'images
        try {
            $stmt = $pdo->prepare("SELECT matricule, photo, code_qr FROM candidat WHERE supprimer = 1 LIMIT 3");
            $stmt->execute();
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $debug_info = [];
            foreach ($samples as $sample) {
                $debug_info[] = [
                    'matricule' => $sample['matricule'],
                    'photo_path' => $sample['photo'],
                    'qr_path' => $sample['code_qr'],
                    'document_root' => $_SERVER['DOCUMENT_ROOT'],
                    'photo_exists' => $sample['photo'] ? file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $sample['photo']) : null,
                    'qr_exists' => $sample['code_qr'] ? file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $sample['code_qr']) : null
                ];
            }
            
            sendResponse([
                'debug_info' => $debug_info,
                'server_info' => [
                    'document_root' => $_SERVER['DOCUMENT_ROOT'],
                    'script_name' => $_SERVER['SCRIPT_NAME'],
                    'request_uri' => $_SERVER['REQUEST_URI'],
                    'environment' => 'Render + PostgreSQL'
                ]
            ]);
        } catch (PDOException $e) {
            sendError('Erreur base de données PostgreSQL: ' . $e->getMessage());
        }
        break;

    default:
        sendError('Endpoint non trouvé', 404);
        break;
}
?>
