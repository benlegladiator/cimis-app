<?php
/**
 * API SIADOC ENVOIE - VERSION COMPATIBLE CIMIS.SQL
 * Ce fichier permet à SIADOC de récupérer les données des cartes CIMIS
 * Optimisé pour la structure de base de données cimis.sql
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration
require_once 'config.php';

// Fonction pour encoder une image en base64
function encodeImageToBase64($image_path) {
    if (file_exists($image_path)) {
        $image_data = file_get_contents($image_path);
        $mime_type = mime_content_type($image_path);
        return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
    }
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
        'timestamp' => date('c')
    ]);
}

// Router les requêtes
$request_method = $_SERVER['REQUEST_METHOD'];
$path_info = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path_info, '/'));

// Récupérer l'action depuis le path ou le paramètre GET
$action = $_GET['action'] ?? 'help';

switch ($action) {
    case 'help':
        sendResponse([
            'endpoints' => [
                'GET /api_siadoc_envoie.php/help' => 'Documentation des endpoints',
                'GET /api_siadoc_envoie.php/carte/{matricule}' => 'Récupérer une carte par matricule',
                'GET /api_siadoc_envoie.php/cartes' => 'Récupérer toutes les cartes actives',
                'GET /api_siadoc_envoie.php/statistiques' => 'Statistiques des cartes'
            ],
            'version' => '1.0.0-CIMIS-SQL',
            'system' => 'CIMIS',
            'note' => 'Version compatible avec la structure de base de données cimis.sql'
        ]);
        break;

    case 'carte':
        // Récupérer une carte spécifique
        $matricule = $path_parts[2] ?? null;
        if (!$matricule) {
            sendError('Matricule requis');
            break;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.sexe,
                    c.numero_cni,
                    c.date_enrolement,
                    c.date_dernier_grade,
                    c.annee_dernier_galon,
                    c.grade,
                    c.categorie_civil,
                    c.unite,
                    c.photo,
                    c.taille,
                    c.poids,
                    c.groupe_sanguin,
                    c.code_qr,
                    c.empreinte_data,
                    c.source_system,
                    c.statut_militaire
                FROM candidat c
                WHERE (c.matricule = ? OR c.matricule_militaire = ?)
                AND c.suspendus = 0
            ");
            $stmt->execute([$matricule, $matricule]);
            $carte = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$carte) {
                sendError('Carte non trouvée', 404);
                break;
            }

            // Encoder les images en base64
            if ($carte['photo']) {
                $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $carte['photo'];
                $carte['photo_base64'] = encodeImageToBase64($photo_path);
            }
            if ($carte['code_qr']) {
                $qr_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $carte['code_qr'];
                $carte['qr_code_base64'] = encodeImageToBase64($qr_path);
            }

            sendResponse($carte, 'Carte récupérée avec succès');

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'cartes':
        // Récupérer toutes les cartes avec filtres de base
        try {
            $sql = "
                SELECT 
                    c.id,
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.sexe,
                    c.numero_cni,
                    c.date_enrolement,
                    c.date_dernier_grade,
                    c.annee_dernier_galon,
                    c.grade,
                    c.categorie_civil,
                    c.unite,
                    c.photo,
                    c.taille,
                    c.poids,
                    c.groupe_sanguin,
                    c.code_qr,
                    c.empreinte_data,
                    c.source_system,
                    c.statut_militaire
                FROM candidat c
                WHERE c.suspendus = 0
            ";
            $params = [];

            // Filtres de base
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

            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = ($page - 1) * $limit;

            $sql .= " ORDER BY c.nom, c.prenom LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $cartes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compter le total pour pagination
            $count_sql = str_replace("ORDER BY c.nom, c.prenom LIMIT " . (int)$limit . " OFFSET " . (int)$offset, "", $sql);
            $count_sql = str_replace("c.id, c.matricule, c.matricule_militaire, c.nom, c.prenom, c.date_naissance, c.sexe, c.numero_cni, c.date_enrolement, c.date_dernier_grade, c.annee_dernier_galon, c.grade, c.categorie_civil, c.unite, c.photo, c.taille, c.poids, c.groupe_sanguin, c.code_qr, c.empreinte_data, c.source_system, c.statut_militaire", "COUNT(*) as total", $count_sql);
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $result = $count_stmt->fetch();
            $total = isset($result['total']) ? $result['total'] : 0;

            sendResponse([
                'cartes' => $cartes,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'statistiques':
        // Statistiques simples
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_cartes,
                    COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc,
                    COUNT(CASE WHEN source_system = 'CIMIS' THEN 1 END) as venus_de_cimis,
                    COUNT(DISTINCT unite) as unites_differentes,
                    COUNT(DISTINCT grade) as grades_differents,
                    COUNT(CASE WHEN code_qr IS NOT NULL THEN 1 END) as avec_qr_code
                FROM candidat 
                WHERE suspendus = 0
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            sendResponse([
                'generales' => $stats,
                'date_generation' => date('c')
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    default:
        sendError('Endpoint non trouvé', 404);
        break;
}
?>
