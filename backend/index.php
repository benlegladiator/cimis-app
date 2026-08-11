<?php
/**
 * Page d'interopérabilité - Récupération des données CIMIS
 * Cette page consomme l'API CIMIS pour afficher les données des militaires
 */

// Configuration de l'API CIMIS
define('CIMIS_API_URL', 'http://localhost/api/cimis/backend/api_siadoc_envoie.php');
define('CIMIS_API_KEY', 'siadoc-2026-cimis-integration');

// Fonction pour faire des requêtes HTTP vers l'API CIMIS
function callCimisApi($endpoint, $params = []) {
    $url = CIMIS_API_URL . '?action=' . $endpoint;
    
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . CIMIS_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        return ['error' => 'Erreur cURL: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => 'Erreur HTTP ' . $httpCode];
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'Erreur JSON: ' . json_last_error_msg(),
            'debug_response' => substr($response, 0, 500) // Affiche les 500 premiers caractères
        ];
    }
    
    return $data;
}

// Récupérer les paramètres de la requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$unite = isset($_GET['unite']) ? $_GET['unite'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Paramètres pour l'API
$params = [
    'page' => $page,
    'limit' => $limit
];

if (!empty($grade)) {
    $params['grade'] = $grade;
}
if (!empty($unite)) {
    $params['unite'] = $unite;
}

// Appeler l'API pour récupérer les cartes
$result = callCimisApi('cartes', $params);

// Appeler l'API pour les statistiques
$stats = callCimisApi('statistiques');

// Récupérer les militaires
$militaires = [];
$pagination = [];
$filtres = [];

if (isset($result['success']) && $result['success'] && isset($result['data'])) {
    $militaires = $result['data']['cartes'] ?? [];
    $pagination = $result['data']['pagination'] ?? [];
    $filtres = $result['data']['filtres_appliques'] ?? [];
}

// Statistiques
$statistiques = [];
if (isset($stats['success']) && $stats['success'] && isset($stats['data'])) {
    $statistiques = $stats['data'];
}

// Fonction pour extraire les grades uniques pour le filtre
function getUniqueGrades($militaires) {
    $grades = [];
    foreach ($militaires as $m) {
        if (!empty($m['grade']) && !in_array($m['grade'], $grades)) {
            $grades[] = $m['grade'];
        }
    }
    sort($grades);
    return $grades;
}

// Fonction pour extraire les unités uniques pour le filtre
function getUniqueUnites($militaires) {
    $unites = [];
    foreach ($militaires as $m) {
        if (!empty($m['unite']) && !in_array($m['unite'], $unites)) {
            $unites[] = $m['unite'];
        }
    }
    sort($unites);
    return $unites;
}

$grades_list = getUniqueGrades($militaires);
$unites_list = getUniqueUnites($militaires);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interopérabilité - Données CIMIS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #2a5298;
        }
        
        .filters {
            padding: 20px 30px;
            background: #e9ecef;
            border-bottom: 1px solid #dee2e6;
        }
        
        .filters h3 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
            min-width: 200px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #2a5298;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e3c72;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .content {
            padding: 30px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        thead {
            background: #2a5298;
            color: white;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-actif {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-suspendu {
            background: #fff3cd;
            color: #856404;
        }
        
        .photo-thumb {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }
        
        .photo-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.2rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }
        
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #2a5298;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #2a5298;
            color: white;
        }
        
        .pagination .current {
            padding: 8px 12px;
            background: #2a5298;
            color: white;
            border-radius: 4px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 0.85rem;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Interopérabilité CIMIS</h1>
            <p>Récupération des données des cartes militaires via l'API CIMIS</p>
        </div>
        
        <?php if (isset($result['error'])): ?>
        <div class="alert alert-error">
            <strong>Erreur:</strong> <?php echo htmlspecialchars($result['error']); ?>
            <?php if (isset($result['debug_response'])): ?>
            <br><br>
            <strong>Réponse brute de l'API:</strong><br>
            <pre style="background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;margin-top:10px;"><?php echo htmlspecialchars($result['debug_response']); ?></pre>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <?php if (!empty($statistiques)): ?>
        <div class="stats-bar">
            <div class="stat-card">
                <h3>Total Cartes</h3>
                <div class="value"><?php echo number_format($statistiques['generales']['total_cartes'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Unités</h3>
                <div class="value"><?php echo $statistiques['generales']['unites_differentes'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Grades</h3>
                <div class="value"><?php echo $statistiques['generales']['grades_differents'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Avec QR Code</h3>
                <div class="value"><?php echo number_format($statistiques['generales']['avec_qr_code'] ?? 0); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filtres -->
        <div class="filters">
            <h3>🔍 Filtres de recherche</h3>
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Grade</label>
                    <select name="grade">
                        <option value="">Tous les grades</option>
                        <?php foreach ($grades_list as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $grade === $g ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Unité</label>
                    <select name="unite">
                        <option value="">Toutes les unités</option>
                        <?php foreach ($unites_list as $u): ?>
                        <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $unite === $u ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Par page</label>
                    <select name="limit">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="?" class="btn btn-secondary">Réinitialiser</a>
            </form>
        </div>
        
        <!-- Contenu -->
        <div class="content">
            <?php if (empty($militaires)): ?>
            <div class="empty-state">
                <h3>Aucune donnée disponible</h3>
                <p>Impossible de récupérer les données de l'API CIMIS ou aucun militaire trouvé.</p>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <strong>Page <?php echo $pagination['page'] ?? 1; ?></strong> - 
                Affichage de <?php echo count($militaires); ?> militaires sur <?php echo number_format($pagination['total'] ?? 0); ?> au total
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Grade</th>
                            <th>Unité</th>
                            <th>Sexe</th>
                            <th>Statut</th>
                            <th>Date Enrôlement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($militaires as $m): ?>
                        <tr>
                            <td>
                                <?php if (!empty($m['photo']) && file_exists('cimis/' . $m['photo'])): ?>
                                <img src="cimis/<?php echo htmlspecialchars($m['photo']); ?>" alt="Photo" class="photo-thumb">
                                <?php else: ?>
                                <div class="photo-placeholder">👤</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($m['matricule_militaire'] ?? $m['matricule'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($m['nom'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($m['prenom'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($m['grade'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($m['unite'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($m['sexe'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $statut = $m['statut_militaire'] ?? 'ACTIF';
                                $badgeClass = ($statut === 'ACTIF') ? 'badge-actif' : 'badge-suspendu';
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($statut); ?>
                                </span>
                            </td>
                            <td><?php echo !empty($m['date_enrolement']) ? date('d/m/Y', strtotime($m['date_enrolement'])) : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (!empty($pagination) && $pagination['pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                <a href="?page=<?php echo $pagination['page'] - 1; ?>&limit=<?php echo $limit; ?>&grade=<?php echo urlencode($grade); ?>&unite=<?php echo urlencode($unite); ?>">← Précédent</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                    <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&grade=<?php echo urlencode($grade); ?>&unite=<?php echo urlencode($unite); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['page'] < $pagination['pages']): ?>
                <a href="?page=<?php echo $pagination['page'] + 1; ?>&limit=<?php echo $limit; ?>&grade=<?php echo urlencode($grade); ?>&unite=<?php echo urlencode($unite); ?>">Suivant →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>🔗 Système d'interopérabilité - Données synchronisées depuis CIMIS | <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
