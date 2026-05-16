<?php
/**
 * API SIADOC ENVOIE - VERSION SIMPLE POUR INFINITYFREE
 * Envoie seulement les données importantes du militaire
 * Compatible avec la base de données InfinityFree (supprimer = 1)
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
$action = $_GET['action'] ?? 'help';

switch ($action) {
    case 'help':
        sendResponse([
            'endpoints' => [
                'GET /api_siadoc_envoie.php?action=cartes' => 'Liste de tous les militaires',
                'GET /api_siadoc_envoie.php?action=carte&matricule=XXX' => 'Détails d\'un militaire',
                'GET /api_siadoc_envoie.php?action=statistiques' => 'Statistiques simples'
            ],
            'version' => '1.0.0-SIMPLE-INFINITYFREE',
            'note' => 'API simplifiée pour InfinityFree - seulement les données importantes'
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
                $militaire['photo_base64'] = encodeImageToBase64($photo_path);
            }
            if ($militaire['code_qr']) {
                $qr_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $militaire['code_qr'];
                $militaire['qr_code_base64'] = encodeImageToBase64($qr_path);
            }

            sendResponse($militaire, 'Militaire récupéré');

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
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

            // Compter le total
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
            sendError('Erreur base de données: ' . $e->getMessage());
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
                WHERE c.supprimer = 1
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            sendResponse([
                'statistiques' => $stats,
                'date' => date('c')
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
