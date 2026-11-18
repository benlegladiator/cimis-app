<?php
// Démarrer la session AVANT tout
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

// Activer l'affichage des erreurs AVANT tout (critique pour InfinityFree)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers CORS pour InfinityFree (APRÈS les tests)
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('X-Frame-Options: SAMEORIGIN');
}

// Fonction simple pour afficher les erreurs avec lignes (compatible InfinityFree)
if (!function_exists('showError')) {
    function showError($message, $file = '', $line = 0) {
        echo "<div style='background: #ff6b6b; color: white; padding: 15px; margin: 10px; border-radius: 5px; font-family: monospace;'>";
        echo "<strong>ERREUR DÉTECTÉE:</strong><br>";
        echo "Message: $message<br>";
        if ($file) echo "Fichier: $file<br>";
        if ($line) echo "Ligne: $line<br>";
        echo "<small>URL: " . $_SERVER['REQUEST_URI'] . "</small>";
        echo "<br><strong>CONSEIL:</strong> Vérifiez cette ligne dans le fichier";
        echo "</div>";
    }
}

// Test simple pour vérifier si le fichier fonctionne
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true); // Mettre à false en production
}

// Activer l'affichage des erreurs AVANT tout (critique pour InfinityFree)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test des require avec chemins absolus
try {
    require_once dirname(__DIR__, 3) . '/config/config.php';
} catch (Exception $e) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR CONFIG.PHP: " . $e->getMessage() . "<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

try {
    require_once dirname(__DIR__, 3) . '/app/Services/CartePdfService.php';
    require_once dirname(__DIR__, 3) . '/app/Services/CartePvcService.php';
    require_once dirname(__DIR__, 3) . '/app/Services/ImpressionService.php';
} catch (Exception $e) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR CARTEMILITAIRE.PHP: " . $e->getMessage() . "<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

if (!function_exists('resolvePersonnelPhoto')) {
    function resolvePersonnelPhoto($photo) {
        $basePrefix = '/cimcim/public/';
        $fallbackUrl = $basePrefix . 'img/cimis.png';

        if (empty($photo)) {
            return [
                'src' => $fallbackUrl,
                'fallback' => $fallbackUrl
            ];
        }

        $clean = preg_replace('#^(\.\./|\./|/cimcim/public/|/cimcim/|public/)+#', '', trim($photo));
        $clean = ltrim($clean, '/\\');
        $baseName = basename($clean);

        // 1. Priorité absolue : Vérifier dans storage/app/public/candidats/ (via storage_path)
        if (function_exists('storage_path') && file_exists(storage_path('app/public/candidats/' . $baseName))) {
            return [
                'src' => $basePrefix . 'storage/candidats/' . $baseName,
                'fallback' => $fallbackUrl
            ];
        }

        // 2. Vérifier via public_path ou chemin relatif standard
        $publicDir = dirname(__DIR__, 3) . '/public/';
        if (file_exists($publicDir . 'storage/candidats/' . $baseName)) {
            return [
                'src' => $basePrefix . 'storage/candidats/' . $baseName,
                'fallback' => $fallbackUrl
            ];
        }

        if (file_exists($publicDir . $clean)) {
            return [
                'src' => $basePrefix . $clean,
                'fallback' => $fallbackUrl
            ];
        }

        if (file_exists($publicDir . 'img/candidats/' . $baseName)) {
            return [
                'src' => $basePrefix . 'img/candidats/' . $baseName,
                'fallback' => $fallbackUrl
            ];
        }

        if (file_exists($publicDir . 'img/' . $baseName)) {
            return [
                'src' => $basePrefix . 'img/' . $baseName,
                'fallback' => $fallbackUrl
            ];
        }

        return [
            'src' => $fallbackUrl,
            'fallback' => $fallbackUrl
        ];
    }
}

if (!function_exists('renderImpressionPaginationHtml')) {
    function renderImpressionPaginationHtml($page, $totalPages, $totalCount, $fromCount, $toCount, $perPage) {
        ob_start();
        ?>
        <div id="impression-pagination" class="cimis-pagination no-print">
            <div class="cimis-pagination-info">
                <span>Affichage de <strong><?php echo $fromCount; ?></strong> à <strong><?php echo $toCount; ?></strong> sur <strong><?php echo $totalCount; ?></strong> militaires</span>
                <span style="color: #64748b;">•</span>
                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                    Par page :
                    <select class="cimis-per-page-select" onchange="changePerPage(this.value);">
                        <?php foreach ([12, 24, 48, 96] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $perPage === $opt ? 'selected' : ''; ?>>
                                <?php echo $opt; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <span id="selected-badge-info" style="display: none; background: rgba(0, 255, 157, 0.15); border: 1px solid var(--neon-green); color: var(--neon-green); padding: 2px 8px; border-radius: 12px; font-size: 0.78rem; font-weight: 600;">
                    <i class="fa-solid fa-check"></i> <span id="selected-badge-count">0</span> sélectionné(s)
                </span>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="cimis-pagination-nav">
                <button type="button" onclick="goToImpressionPage(1)" class="cimis-page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="Première page">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
                <button type="button" onclick="goToImpressionPage(<?php echo max(1, $page - 1); ?>)" class="cimis-page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="Page précédente">
                    <i class="fa-solid fa-angle-left"></i>
                </button>

                <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    if ($startPage > 1): ?>
                        <button type="button" onclick="goToImpressionPage(1)" class="cimis-page-btn">1</button>
                        <?php if ($startPage > 2): ?>
                            <span class="cimis-page-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <button type="button" onclick="goToImpressionPage(<?php echo $p; ?>)" class="cimis-page-btn <?php echo $p === $page ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </button>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="cimis-page-ellipsis">...</span>
                        <?php endif; ?>
                        <button type="button" onclick="goToImpressionPage(<?php echo $totalPages; ?>)" class="cimis-page-btn"><?php echo $totalPages; ?></button>
                    <?php endif; ?>

                <button type="button" onclick="goToImpressionPage(<?php echo min($totalPages, $page + 1); ?>)" class="cimis-page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" title="Page suivante">
                    <i class="fa-solid fa-angle-right"></i>
                </button>
                <button type="button" onclick="goToImpressionPage(<?php echo $totalPages; ?>)" class="cimis-page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" title="Dernière page">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

global $pdo;
if (!isset($pdo) || !$pdo) {
    if (isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
    }
}

// Test de connexion à la base de données
if (!isset($pdo) || !$pdo) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR PDO: PDO non défini après config.php<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

// Test simple de connexion BDD
try {
    $test = $pdo->query("SELECT 1");
    if (!$test) {
        echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
        echo "ERREUR CONNEXION BDD: query('SELECT 1') a échoué<br>";
        echo "Ligne: " . __LINE__ . " dans " . __FILE__;
        echo "</div>";
        exit;
    }
} catch (PDOException $e) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR PDO: " . $e->getMessage() . "<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Traitement pour la file des reçus A4 (vider ou ajouter)
if (isset($_GET['action']) && $_GET['action'] === 'clear_receipts_queue') {
    $_SESSION['pending_receipts'] = [];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'add_receipt_queue') {
    $ids = explode(',', $_GET['ids'] ?? '');
    if (!isset($_SESSION['pending_receipts']) || !is_array($_SESSION['pending_receipts'])) {
        $_SESSION['pending_receipts'] = [];
    }
    foreach ($ids as $id) {
        $id = trim($id);
        if (!empty($id) && !in_array($id, $_SESSION['pending_receipts'])) {
            $_SESSION['pending_receipts'][] = $id;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'count' => count($_SESSION['pending_receipts']),
        'ids' => array_values($_SESSION['pending_receipts'])
    ]);
    exit;
}

// Traitement de la visualisation
if (isset($_GET['action']) && $_GET['action'] == 'visualize') {
    $matricule = $_GET['matricule'] ?? '';
    
    if (!empty($matricule)) {
        // Rediriger vers la page de visualisation avec le matricule
        header('Location: /cimcim/carte/visualiser?matricule=' . urlencode($matricule));
        exit;
    }
    
    header('Location: impression.php');
    exit;
}

// Traitement de la génération PDF
if (isset($_GET['action']) && $_GET['action'] == 'generate') {
    $matricule = $_GET['matricule'] ?? '';
    
    if (!empty($matricule)) {
        // Utiliser la nouvelle classe CarteMilitaire
        $carte = new CarteMilitaire($matricule);
        $carte->genererPDF();
        exit;
    }
    
    header('Location: /cimcim/impression');
    exit;
}

// Traitement de la génération PDF multiple
if (isset($_GET['action']) && $_GET['action'] == 'generate_batch') {
    $matricules = $_GET['matricules'] ?? '';
    if (!empty($matricules)) {
        $matriculesArray = is_array($matricules) ? $matricules : explode(',', $matricules);
        
        // Utiliser la méthode statique pour générer le PDF multiple
        CarteMilitaire::genererPDFMultiple($matriculesArray);
        exit;
    }
    
    header('Location: /cimcim/impression');
    exit;
}

// Traitement de la suppression multiple
if (isset($_POST['action']) && $_POST['action'] == 'delete_multiple') {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Erreur de sécurité CSRF : tentative de suppression massive non autorisée.";
        header('Location: impression.php');
        exit;
    }

    $ids = $_POST['ids'] ?? [];
    if (!empty($ids) && is_array($ids)) {
        $placeholders = str_repeat('?,', count($ids));
        $placeholders = rtrim($placeholders, ',');
        
        // Suppression soft : mettre à jour les attributs au lieu de supprimer (déplacer vers corbeille supprimer = 1)
        $sql = "UPDATE candidat SET supprimer = 1, supprime_par = ?, date_suppression = NOW() WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $params = array_merge([$_SESSION['username']], $ids);
        $stmt->execute($params);
        
        $_SESSION['success'] = count($ids) . " carte(s) déplacée(s) dans la corbeille / " . count($ids) . " card(s) moved to trash";
    } else {
        $_SESSION['error'] = "Aucune carte sélectionnée pour la suppression / No card selected for deletion";
    }
    
    header('Location: impression.php');
    exit;
}

// Traitement AJAX pour la recherche dynamique
if (isset($_POST['ajax_search']) || isset($_GET['ajax_search'])) {
    $req = isset($_POST['ajax_search']) ? $_POST : $_GET;
    // Récupérer les personnels avec filtres dynamiques fiables
    list($where, $params) = buildImpressionFilterQuery($req);

    // Pagination
    $page = max(1, intval($req['page'] ?? 1));
    $perPage = max(6, min(200, intval($req['per_page'] ?? 24)));

    // Compter le total sur toute la base de données
    $countSql = "SELECT COUNT(*) as total FROM candidat WHERE (supprimer = 0 OR supprimer IS NULL)";
    if (!empty($where)) {
        $countSql .= " AND " . implode(' AND ', $where);
    }
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $totalCount = (int)$stmtCount->fetchColumn();

    $totalPages = max(1, (int)ceil($totalCount / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;
    $fromCount = $totalCount > 0 ? $offset + 1 : 0;
    $toCount = min($offset + $perPage, $totalCount);

    // Construire la requête
    $sql = "SELECT id, matricule, matricule_militaire, nom, prenom, unite, grade, COALESCE(photo, photo_path) AS photo, photo_path, numero_cni, date_dernier_grade, suspendus, statut_militaire, date_changement_statut, motif_changement_statut, autorite_changement_statut, nb_reimpressions, date_derniere_reimpression FROM candidat WHERE (supprimer = 0 OR supprimer IS NULL)";
    if (!empty($where)) {
        $sql .= " AND " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Afficher la grille des personnels avec métadonnées de comptage total
    echo '<div id="personnelsContainer" data-total-count="' . $totalCount . '" data-page="' . $page . '" data-total-pages="' . $totalPages . '" data-from="' . $fromCount . '" data-to="' . $toCount . '">';
    if (empty($personnels)) {
        echo '<div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <h4>Aucun personnel trouvé / No personnel found</h4>
                <p>Essayez de modifier vos critères de recherche / Try modifying your search criteria</p>
              </div>';
    } else {
        foreach ($personnels as $personnel) {
            $st_mil = strtoupper($personnel['statut_militaire'] ?? '');
            $is_suspended = ($personnel['suspendus'] == 1) || str_contains($st_mil, 'SUSPENDU') || in_array($st_mil, ['DESERTEUR', 'REVOQUE']);
            $nb_reimp = intval($personnel['nb_reimpressions'] ?? 0);
            $d_reimp_fmt = !empty($personnel['date_derniere_reimpression']) ? date('d/m/Y', strtotime($personnel['date_derniere_reimpression'])) : '';

            $matAff = htmlspecialchars($personnel['matricule_militaire'] ?: $personnel['matricule']);
            $nomComplet = htmlspecialchars($personnel['nom'] . ' ' . $personnel['prenom']);
            $gradeTxt = htmlspecialchars($personnel['grade'] ?: 'Non spécifié');
            $uniteTxt = htmlspecialchars(function_exists('normaliserUniteMilitaire') ? normaliserUniteMilitaire($personnel['unite']) : ($personnel['unite'] ?: 'Non spécifiée'));
            $isSiadocSource = (($personnel['source_system'] ?? '') === 'SIADOC' || ($personnel['siadoc_sync_status'] ?? '') === 'SYNCED' || str_starts_with($personnel['matricule_militaire'] ?? '', 'SIA'));

            echo '<div class="personnel-item' . ($is_suspended ? ' suspended' : '') . '">
                    <div class="personnel-card-top">
                        <div class="personnel-checkbox-wrapper">
                            <input type="checkbox" name="ids[]" value="' . htmlspecialchars($personnel['id']) . '" 
                                   data-matricule="' . htmlspecialchars($personnel['matricule']) . '" 
                                   data-nb-reimpressions="' . $nb_reimp . '"
                                   data-date-reimpression="' . $d_reimp_fmt . '"
                                   class="personnel-checkbox" id="personnel_' . $personnel['id'] . '">
                            <label for="personnel_' . $personnel['id'] . '" class="checkbox-label"></label>
                        </div>';
                    
                    // Indicateur de suspension
                    if ($is_suspended) {
                        echo '<div class="suspension-indicator" title="Carte militaire suspendue / désactivée">
                                <i class="fa-solid fa-ban"></i>
                              </div>';
                    }
                    
                    $pPhoto = resolvePersonnelPhoto($personnel['photo'] ?? ($personnel['photo_path'] ?? ''));
                    echo '<div class="personnel-photo">';
                    echo '<img src="' . htmlspecialchars($pPhoto['src']) . '" alt="Photo" onerror="this.onerror=null; this.src=\'' . htmlspecialchars($pPhoto['fallback']) . '\';">';
                    echo '</div>
                    <div class="personnel-info">
                        <div class="personnel-name" title="' . $nomComplet . '">' . $nomComplet . '</div>
                        <div class="personnel-details">
                            <span><span class="badge-matricule">' . $matAff . '</span></span>
                            <span><i class="fa-solid fa-award" style="color: #fbbf24;"></i> ' . $gradeTxt . '</span>
                            <span><i class="fa-solid fa-shield" style="color: #38bdf8;"></i> ' . $uniteTxt . ($isSiadocSource ? ' <span style="background: rgba(14,165,233,0.18); border: 1px solid #0ea5e9; color: #38bdf8; font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; font-weight: 700; margin-left: 4px; vertical-align: middle;" title="Dossier militaire importé depuis SIADOC DRH"><i class="fa-solid fa-cloud-arrow-down"></i> SIADOC</span>' : '') . '</span>
                        </div>';
            if ($is_suspended) {
                echo '<div style="margin-top: 4px;">
                        <span class="suspension-badge-tag"><i class="fa-solid fa-ban"></i> CARTE SUSPENDUE (' . $st_mil . ')</span>
                      </div>';
            }
            if ($nb_reimp >= 1) {
                echo '<div style="margin-top: 4px;">
                        <span style="background: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #fbbf24; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;" title="Carte déjà imprimée ' . $nb_reimp . ' fois">
                            <i class="fa-solid fa-triangle-exclamation"></i> Déjà imprimée (' . $nb_reimp . 'x)
                        </span>
                      </div>';
            }
            echo '</div>
                  </div>
                  <div class="personnel-actions">
                        <div class="card-status-info"></div>
                        <div class="card-actions-row">
                            <a href="/cimcim/carte/visualiser?matricule=' . urlencode($personnel['matricule']) . '" 
                               class="btn-card-action btn-card-vis" title="Visualiser la carte">
                                <i class="fa-solid fa-id-card"></i>
                            </a>
                            <a href="/cimcim/candidat/edit?id=' . $personnel['id'] . '" 
                               class="btn-card-action btn-card-edit" title="Modifier les informations">
                                <i class="fa-solid fa-edit"></i>
                            </a>
                            <a href="/cimcim/impression?action=generate&matricule=' . urlencode($personnel['matricule']) . '" 
                               class="btn-card-action btn-card-pdf" title="Imprimer la carte (PDF)" target="_blank">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                            <button type="button" onclick="imprimerPVCEtQueue(\'' . $personnel['id'] . '\', \'/cimcim/impression/pvc?matricule=' . urlencode($personnel['matricule']) . '&mode=recto-verso\', ' . $nb_reimp . ', \'' . $d_reimp_fmt . '\')" 
                                class="btn-card-action btn-card-pvc" title="Impression PVC Optimisée (85.60×53.98mm - 0 marge)">
                                <i class="fa-solid fa-credit-card"></i>
                            </button>
                            <a href="/cimcim/carte/nfc?matricule=' . urlencode($personnel['matricule']) . '" 
                               class="btn-card-action" style="background: rgba(14, 116, 144, 0.2); border: 1px solid rgba(14, 116, 144, 0.4); color: #22d3ee;" title="Encoder puce NFC" target="_blank">
                                <i class="fa-solid fa-wifi"></i>
                            </a>
                            <a href="/cimcim/carte/3d?matricule=' . urlencode($personnel['matricule']) . '" 
                               class="btn-card-action btn-card-3d" title="Visualiser la carte en 3D" target="_blank">
                                <i class="fa-solid fa-cube"></i>
                            </a>
                            <button type="button" class="btn-card-action btn-card-del" title="Déplacer dans la corbeille"
                                    onclick="confirmDelete(\'' . $personnel['id'] . '\')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                  </div>
                </div>';
        }
    }
    echo '</div>'; // Fermer le conteneur
    
    // Pagination AJAX
    echo renderImpressionPaginationHtml($page, $totalPages, $totalCount, $fromCount, $toCount, $perPage);
    exit;
}

// Traitement export CSV
if (isset($_POST['export'])) {
    // Récupérer les candidats avec filtres
    list($where, $params) = buildImpressionFilterQuery($_POST);

    $sql = "SELECT matricule, nom, prenom, grade, unite, numero_cni, date_dernier_grade FROM candidat WHERE (supprimer = 0 OR supprimer IS NULL)";
    if (!empty($where)) {
        $sql .= " AND " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY date_enrolement DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Générer le CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="candidats_cimis_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, ['Matricule', 'Nom', 'Prénom', 'Grade', 'Unité', 'CNI', 'Date Dernier Grade']);
    
    // Données
    foreach ($personnels as $candidat) {
        fputcsv($output, [
            $candidat['matricule'],
            $candidat['nom'],
            $candidat['prenom'],
            $candidat['grade'],
            $candidat['unite'],
            $candidat['numero_cni'] ?? '',
            $candidat['date_dernier_grade'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}
// Fonction pour obtenir le nombre total de personnels
if (!function_exists('getTotalPersonnels')) {
    function getTotalPersonnels() {
        global $pdo;
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidat WHERE (supprimer = 0 OR supprimer IS NULL)");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}

$distinctGrades = [];
try {
    $stG = $pdo->query("SELECT DISTINCT grade FROM candidat WHERE grade IS NOT NULL AND TRIM(grade) != '' ORDER BY grade ASC");
    if ($stG) {
        $distinctGrades = $stG->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (\Throwable $e) {}

list($where, $params) = buildImpressionFilterQuery($_GET);

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = max(6, min(200, intval($_GET['per_page'] ?? 24)));

// Compter le total
$countSql = "SELECT COUNT(*) as total FROM candidat WHERE (supprimer = 0 OR supprimer IS NULL)";
if (!empty($where)) {
    $countSql .= " AND " . implode(' AND ', $where);
}
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalCount = (int)$stmtCount->fetchColumn();

$totalPages = max(1, (int)ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;
$fromCount = $totalCount > 0 ? $offset + 1 : 0;
$toCount = min($offset + $perPage, $totalCount);

// Construire la requête
$sql = "SELECT id, matricule, matricule_militaire, nom, prenom, unite, grade, COALESCE(photo, photo_path) AS photo, photo_path, numero_cni, date_dernier_grade, suspendus, statut_militaire, date_changement_statut, motif_changement_statut, autorite_changement_statut, nb_reimpressions, date_derniere_reimpression FROM candidat WHERE (supprimer = 0 OR supprimer IS NULL)";
if (!empty($where)) {
    $sql .= " AND " . implode(' AND ', $where);
}
$sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les cartes dans la corbeille personnelle de l'utilisateur
$trashCount = 0;
try {
    $stmtTrash = $pdo->prepare("SELECT COUNT(*) as total FROM candidat WHERE supprimer = 1 AND (supprime_par = :op OR supprime_par IS NULL)");
    $stmtTrash->execute(['op' => $_SESSION['username'] ?? '']);
    $trashCount = (int)($stmtTrash ? $stmtTrash->fetchColumn() : 0);
} catch (Exception $e) {
    $trashCount = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre d'Impression - CIMIS</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(function_exists('csrf_token') ? csrf_token() : ($_SESSION['csrf_token'] ?? ($_SESSION['_cimis_csrf_token'] ?? 'cimis_token'))); ?>">
    <link rel="stylesheet" href="/cimcim/public/css/enrolement.css">
    <link rel="stylesheet" href="/cimcim/public/css/bouton-retour.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .candidat-item.suspended {
            border: 2px solid #dc3545 !important;
            background: rgba(220, 53, 69, 0.1) !important;
            position: relative;
        }
        
        .candidat-item.suspended::before {
            content: 'SUSPENDU';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        
        .suspension-indicator {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #dc3545;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            z-index: 10;
        }
        
        .personnel-item.suspended {
            border: 2px solid #ef4444 !important;
            background: rgba(239, 68, 68, 0.08) !important;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.3) !important;
        }

        .personnel-item.suspended .personnel-photo img {
            filter: grayscale(85%);
            opacity: 0.75;
        }
        
        .personnel-item.suspended .personnel-name {
            color: #ef4444 !important;
            font-weight: 800;
        }

        .suspension-badge-tag {
            background: #dc2626;
            color: white;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ──── STYLES RESPONSIFS AVANCÉS (MOBILE / TABLETTE / DESKTOP) ──── */
        body {
            overflow-x: hidden;
        }

        .app-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem 2rem 1rem;
        }

        .top-status-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(15, 23, 42, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .status-left, .status-right {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
            gap: 1.25rem !important;
            margin-top: 0 !important;
        }

        .search-group {
            display: flex !important;
            flex-direction: column !important;
            gap: 6px !important;
        }

        .search-group label {
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            color: #94a3b8 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.6px !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            margin-left: 2px !important;
            transition: color 0.2s ease !important;
        }

        .search-group:focus-within label {
            color: #38bdf8 !important;
        }

        .search-input-wrapper {
            position: relative !important;
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .search-input-wrapper > i:first-child {
            position: absolute !important;
            left: 14px !important;
            top: 22px !important;
            transform: translateY(-50%) !important;
            color: #38bdf8 !important;
            font-size: 0.95rem !important;
            pointer-events: none !important;
            transition: all 0.2s ease !important;
            z-index: 2 !important;
            opacity: 0.85 !important;
        }

        .search-input-wrapper:focus-within > i:first-child {
            color: #00ff9d !important;
            opacity: 1 !important;
            transform: translateY(-50%) scale(1.1) !important;
        }

        .search-input-wrapper input,
        .search-input-wrapper select {
            width: 100% !important;
            height: 44px !important;
            padding: 0 14px 0 42px !important;
            background: rgba(10, 15, 30, 0.75) !important;
            border: 1px solid rgba(56, 189, 248, 0.25) !important;
            border-radius: 9px !important;
            color: #f8fafc !important;
            font-size: 0.9rem !important;
            font-family: inherit !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            outline: none !important;
            box-sizing: border-box !important;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25) !important;
        }

        .search-input-wrapper select {
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2338bdf8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 14px center !important;
            background-size: 16px !important;
            padding-right: 40px !important;
            cursor: pointer !important;
        }

        .search-input-wrapper select option {
            background: #0f172a !important;
            color: #f8fafc !important;
            padding: 10px !important;
        }

        .search-input-wrapper input:hover,
        .search-input-wrapper select:hover {
            border-color: rgba(56, 189, 248, 0.5) !important;
            background: rgba(10, 15, 30, 0.85) !important;
        }

        .search-input-wrapper input:focus,
        .search-input-wrapper select:focus {
            border-color: #00d9ff !important;
            box-shadow: 0 0 0 3px rgba(0, 217, 255, 0.18), inset 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            background: rgba(15, 23, 42, 0.95) !important;
            color: #ffffff !important;
        }

        .search-input-wrapper input::placeholder {
            color: #64748b !important;
            font-size: 0.85rem !important;
        }

        /* ──── BARRE D'ACTIONS GROUPÉES (DESIGN ULTRA-PREMIUM CYBER-MILITAIRE) ──── */
        .main-actions,
        .batch-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 12px !important;
            padding: 1.1rem 1.4rem !important;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.85) 0%, rgba(17, 24, 39, 0.95) 100%) !important;
            border: 1px solid rgba(56, 189, 248, 0.25) !important;
            border-radius: 16px !important;
            margin-bottom: 1.75rem !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
            backdrop-filter: blur(16px) !important;
        }

        .main-actions .btn,
        .batch-actions .btn {
            position: relative !important;
            overflow: hidden !important;
            flex: 1 1 200px !important;
            min-width: 195px !important;
            max-width: 250px !important;
            margin: 0 !important;
            padding: 0.85rem 1.25rem !important;
            border-radius: 10px !important;
            font-weight: 800 !important;
            font-size: 0.82rem !important;
            letter-spacing: 0.8px !important;
            text-transform: uppercase !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 9px !important;
            cursor: pointer !important;
            border: 1px solid transparent !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            white-space: nowrap !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
        }

        .main-actions .btn i,
        .batch-actions .btn i {
            font-size: 1rem !important;
            transition: transform 0.25s ease !important;
        }

        .main-actions .btn:hover i,
        .batch-actions .btn:hover i {
            transform: scale(1.18) !important;
        }

        .main-actions .btn:active,
        .batch-actions .btn:active {
            transform: translateY(1px) scale(0.98) !important;
        }

        /* 1. SÉLECTIONNER TOUT (CYBER CYAN / BLEU NÉON) */
        .btn-select-all {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2) 0%, rgba(14, 116, 144, 0.35) 100%) !important;
            color: #38bdf8 !important;
            border: 1px solid #06b6d4 !important;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
        }
        .btn-select-all:hover {
            background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%) !important;
            color: #ffffff !important;
            border-color: #38bdf8 !important;
            box-shadow: 0 6px 22px rgba(6, 182, 212, 0.55), 0 0 12px rgba(56, 189, 248, 0.6) !important;
            transform: translateY(-2px) !important;
        }

        /* 2. VISUALISER SÉLECTION (ÉMERAUDE / VERT NÉON MILITAIRE) */
        .btn-visualize {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.35) 100%) !important;
            color: #34d399 !important;
            border: 1px solid #10b981 !important;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
        }
        .btn-visualize:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: #ffffff !important;
            border-color: #6ee7b7 !important;
            box-shadow: 0 6px 22px rgba(16, 185, 129, 0.55), 0 0 12px rgba(52, 211, 153, 0.6) !important;
            transform: translateY(-2px) !important;
        }

        /* 3. GÉNÉRER PDF MULTIPLE (AMBRE / OR MILITAIRE) */
        .btn-pdf-batch {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(217, 119, 6, 0.35) 100%) !important;
            color: #fbbf24 !important;
            border: 1px solid #f59e0b !important;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
        }
        .btn-pdf-batch:hover {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: #000000 !important;
            font-weight: 900 !important;
            border-color: #fde68a !important;
            box-shadow: 0 6px 22px rgba(245, 158, 11, 0.6), 0 0 14px rgba(251, 191, 36, 0.7) !important;
            transform: translateY(-2px) !important;
        }

        /* 4. IMPRESSION PVC MULTIPLE (INDIGO / VIOLET SATELLITE COMMAND) */
        .btn-pvc-batch {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(79, 70, 229, 0.35) 100%) !important;
            color: #a5b4fc !important;
            border: 1px solid #6366f1 !important;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
        }
        .btn-pvc-batch:hover {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
            color: #ffffff !important;
            border-color: #c7d2fe !important;
            box-shadow: 0 6px 22px rgba(99, 102, 241, 0.6), 0 0 14px rgba(165, 180, 252, 0.65) !important;
            transform: translateY(-2px) !important;
        }

        /* 5. SUPPRIMER SÉLECTION (ROUGE RUBIS ALERTE CORBEILLE) */
        .btn-delete-batch {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.18) 0%, rgba(185, 28, 28, 0.35) 100%) !important;
            color: #fca5a5 !important;
            border: 1px solid #ef4444 !important;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
        }
        .btn-delete-batch:hover {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: #ffffff !important;
            border-color: #fecaca !important;
            box-shadow: 0 6px 22px rgba(239, 68, 68, 0.6), 0 0 14px rgba(252, 165, 165, 0.7) !important;
            transform: translateY(-2px) !important;
        }


        /* ──── CARTES OFFICIELLES STYLE CORBEILLE ──── */
        .cards-grid,
        .personnels-grid,
        .personnels-grid-cards,
        #personnelsContainer {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)) !important;
            gap: 1.25rem !important;
            margin-top: 1rem !important;
            width: 100% !important;
        }

        .personnel-item,
        .trash-card {
            background: rgba(17, 24, 39, 0.9) !important;
            border: 1px solid rgba(0, 217, 255, 0.25) !important;
            border-radius: 16px !important;
            overflow: hidden !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            backdrop-filter: blur(12px) !important;
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
            min-height: 180px !important;
        }

        .personnel-item:hover,
        .trash-card:hover {
            border-color: #00d9ff !important;
            box-shadow: 0 8px 30px rgba(0, 217, 255, 0.25) !important;
            transform: translateY(-3px) !important;
        }

        .personnel-item.selected {
            border-color: #00ff9d !important;
            box-shadow: 0 0 20px rgba(0, 255, 157, 0.35) !important;
        }

        .personnel-card-top,
        .trash-card-top {
            padding: 1.2rem !important;
            display: flex !important;
            flex-direction: row !important;
            gap: 1rem !important;
            align-items: flex-start !important;
        }

        .personnel-checkbox-wrapper {
            margin-top: 2px !important;
            flex-shrink: 0 !important;
        }

        .personnel-checkbox,
        .trash-checkbox {
            width: 20px !important;
            height: 20px !important;
            accent-color: #00d9ff !important;
            cursor: pointer !important;
        }

        .personnel-photo,
        .candidate-avatar {
            width: 68px !important;
            height: 68px !important;
            border-radius: 12px !important;
            object-fit: cover !important;
            border: 2px solid rgba(255, 255, 255, 0.15) !important;
            background: #1e293b !important;
            flex-shrink: 0 !important;
            overflow: hidden !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35) !important;
        }

        .personnel-photo img,
        .candidate-avatar img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        .personnel-info,
        .card-details {
            flex: 1 !important;
            min-width: 0 !important;
        }

        .personnel-name,
        .cand-name {
            font-size: 1.02rem !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            margin-bottom: 0.35rem !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            letter-spacing: 0.3px !important;
        }

        .personnel-details,
        .cand-meta {
            font-size: 0.82rem !important;
            color: #94a3b8 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.25rem !important;
        }

        .personnel-details span,
        .cand-meta span {
            display: flex !important;
            align-items: center !important;
            gap: 0.45rem !important;
        }

        .badge-matricule {
            font-family: 'JetBrains Mono', monospace !important;
            background: rgba(0, 217, 255, 0.15) !important;
            color: #38bdf8 !important;
            padding: 2px 8px !important;
            border-radius: 6px !important;
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            display: inline-block !important;
            border: 1px solid rgba(0, 217, 255, 0.3) !important;
            width: fit-content !important;
        }

        .personnel-actions,
        .trash-audit-footer {
            background: rgba(0, 0, 0, 0.35) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 0.65rem 1rem !important;
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin-top: auto !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .card-actions-row {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 6px !important;
            margin-left: auto !important;
        }

        .btn-card-action {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 34px !important;
            height: 34px !important;
            padding: 0 !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            text-decoration: none !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
            border: 1px solid transparent !important;
        }

        .btn-card-action:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35) !important;
        }

        .btn-card-vis { background: rgba(56, 189, 248, 0.15) !important; color: #38bdf8 !important; border-color: rgba(56, 189, 248, 0.35) !important; }
        .btn-card-vis:hover { background: #38bdf8 !important; color: #0f172a !important; }

        .btn-card-edit { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; border-color: rgba(245, 158, 11, 0.35) !important; }
        .btn-card-edit:hover { background: #f59e0b !important; color: #0f172a !important; }

        .btn-card-pdf { background: rgba(168, 85, 247, 0.15) !important; color: #c084fc !important; border-color: rgba(168, 85, 247, 0.35) !important; }
        .btn-card-pdf:hover { background: #a855f7 !important; color: #ffffff !important; }

        .btn-card-pvc { background: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; border-color: rgba(16, 185, 129, 0.35) !important; }
        .btn-card-pvc:hover { background: #10b981 !important; color: #ffffff !important; }

        .btn-card-3d { background: rgba(6, 182, 212, 0.15) !important; color: #67e8f9 !important; border-color: rgba(6, 182, 212, 0.35) !important; }
        .btn-card-3d:hover { background: #06b6d4 !important; color: #0f172a !important; }

        .btn-card-del { background: rgba(239, 68, 68, 0.15) !important; color: #f87171 !important; border-color: rgba(239, 68, 68, 0.35) !important; }
        .btn-card-del:hover { background: #ef4444 !important; color: #ffffff !important; }

        /* ──── VUE LISTE (LIGNES HORIZONTALES MODERNES) ──── */
        #personnelsContainer.view-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.65rem !important;
            width: 100% !important;
        }

        #personnelsContainer.view-list .personnel-item {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0.65rem 1.25rem !important;
            min-height: 64px !important;
            border-radius: 12px !important;
            background: rgba(17, 24, 39, 0.88) !important;
            border: 1px solid rgba(0, 217, 255, 0.2) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25) !important;
            transition: all 0.2s ease !important;
        }

        #personnelsContainer.view-list .personnel-item:hover {
            transform: translateX(4px) !important;
            border-color: #00d9ff !important;
            box-shadow: 0 4px 20px rgba(0, 217, 255, 0.25) !important;
        }

        #personnelsContainer.view-list .personnel-card-top {
            padding: 0 !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 1rem !important;
            flex: 1 !important;
            min-width: 0 !important;
        }

        #personnelsContainer.view-list .personnel-checkbox-wrapper {
            margin: 0 !important;
            display: flex !important;
            align-items: center !important;
        }

        #personnelsContainer.view-list .personnel-photo {
            width: 46px !important;
            height: 46px !important;
            border-radius: 8px !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            flex-shrink: 0 !important;
        }

        #personnelsContainer.view-list .personnel-info {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 1.2rem !important;
            flex: 1 !important;
            min-width: 0 !important;
        }

        #personnelsContainer.view-list .personnel-name {
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            min-width: 180px !important;
            max-width: 240px !important;
            margin-bottom: 0 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        #personnelsContainer.view-list .personnel-details {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 0.85rem !important;
            flex: 1 !important;
            flex-wrap: nowrap !important;
        }

        #personnelsContainer.view-list .personnel-details span {
            white-space: nowrap !important;
        }

        #personnelsContainer.view-list .personnel-actions {
            background: transparent !important;
            border-top: none !important;
            padding: 0 !important;
            margin: 0 0 0 1rem !important;
            width: auto !important;
            flex-shrink: 0 !important;
        }

        #personnelsContainer.view-list .card-status-info {
            display: none !important;
        }

        #personnelsContainer.view-list .card-actions-row {
            margin-left: auto !important;
            gap: 5px !important;
        }

        #personnelsContainer.view-list .btn-card-action {
            width: 32px !important;
            height: 32px !important;
            font-size: 12px !important;
        }

        /* Mode Switch Toggle Button */
        .view-mode-toggle {
            display: inline-flex !important;
            align-items: center !important;
            background: rgba(15, 23, 42, 0.85) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 10px !important;
            padding: 4px !important;
            gap: 4px !important;
        }

        .view-mode-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 6px 14px !important;
            border-radius: 7px !important;
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            color: #94a3b8 !important;
            background: transparent !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }

        .view-mode-btn:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }

        .view-mode-btn.active {
            background: linear-gradient(135deg, #0284c7, #0369a1) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 10px rgba(2, 132, 199, 0.4) !important;
        }

        /* Navigation Buttons & Shortcuts */
        .back-button-container {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 12px !important;
            max-width: 1400px !important;
            margin: 1.25rem auto 0 auto !important;
            padding: 0 1rem !important;
            width: 100% !important;
        }

        .nav-buttons-group {
            display: flex !important;
            gap: 10px !important;
            flex-wrap: wrap !important;
            align-items: center !important;
        }

        .btn-back {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 9px 18px !important;
            background: rgba(15, 23, 42, 0.85) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #e2e8f0 !important;
            text-decoration: none !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: 0.82rem !important;
            letter-spacing: 0.6px !important;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            cursor: pointer !important;
            text-transform: uppercase !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        }

        .btn-back:hover {
            transform: translateY(-2px) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4) !important;
        }

        .btn-back-dashboard {
            background: rgba(56, 189, 248, 0.12) !important;
            border: 1px solid #38bdf8 !important;
            color: #38bdf8 !important;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.2) !important;
        }
        .btn-back-dashboard:hover {
            background: rgba(56, 189, 248, 0.25) !important;
            color: #ffffff !important;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.45) !important;
        }

        .btn-back-corbeille {
            background: rgba(239, 68, 68, 0.15) !important;
            border: 1px solid #ef4444 !important;
            color: #f87171 !important;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.2) !important;
        }
        .btn-back-corbeille:hover {
            background: rgba(239, 68, 68, 0.3) !important;
            color: #ffffff !important;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.45) !important;
        }

        /* Trash button in footer */
        .security-footer {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 1rem 1.5rem !important;
            background: rgba(15, 23, 42, 0.95) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
            margin-top: 2.5rem !important;
            position: relative !important;
            z-index: 10 !important;
        }

        .footer-center {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .trash-btn {
            position: relative !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 48px !important;
            height: 48px !important;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%) !important;
            color: white !important;
            border: 2px solid rgba(239, 68, 68, 0.6) !important;
            border-radius: 50% !important;
            text-decoration: none !important;
            font-size: 19px !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4) !important;
            cursor: pointer !important;
        }

        .trash-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%) !important;
            transform: translateY(-3px) scale(1.08) !important;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.65) !important;
            color: white !important;
            border-color: #f87171 !important;
        }

        .trash-count {
            position: absolute !important;
            top: -6px !important;
            right: -6px !important;
            background: #fbbf24 !important;
            color: #0f172a !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            padding: 2px 6px !important;
            border-radius: 9999px !important;
            border: 2px solid #0f172a !important;
            min-width: 20px !important;
            text-align: center !important;
            line-height: 1.2 !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4) !important;
            font-family: 'JetBrains Mono', monospace !important;
        }

        @media (max-width: 768px) {
            .cards-grid,
            .personnels-grid,
            .personnels-grid-cards,
            #personnelsContainer {
                grid-template-columns: 1fr !important;
            }
        }
    }

    .plateau-page-btn:hover, .plateau-page-btn.active {
        background: rgba(111,66,193,.5);
        color: white;
    }

    /* PAGINATION CIMIS */
    .cimis-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1.25rem 1rem;
        background: rgba(15, 23, 42, 0.7);
        border: 1px solid rgba(0, 217, 255, 0.2);
        border-radius: 12px;
        margin-top: 1.5rem;
    }

    .cimis-pagination-info {
        font-size: 0.85rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .cimis-pagination-info strong {
        color: var(--neon-cyan);
        font-family: 'JetBrains Mono', monospace;
    }

    .cimis-per-page-select {
        background: #0f172a;
        border: 1px solid rgba(0, 217, 255, 0.4);
        color: #f8fafc;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        outline: none;
    }

    .cimis-per-page-select:focus {
        border-color: var(--neon-green);
    }

    .cimis-pagination-nav {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .cimis-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 8px;
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(0, 217, 255, 0.25);
        color: #cbd5e1;
        border-radius: 6px;
        font-size: 0.85rem;
        font-family: 'JetBrains Mono', monospace;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .cimis-page-btn:hover {
        border-color: var(--neon-green);
        color: var(--neon-green);
        background: rgba(0, 255, 157, 0.12);
        box-shadow: 0 0 10px rgba(0, 255, 157, 0.25);
    }

    .cimis-page-btn.active {
        background: rgba(0, 255, 157, 0.2);
        border-color: var(--neon-green);
        color: var(--neon-green);
        font-weight: bold;
        box-shadow: 0 0 12px rgba(0, 255, 157, 0.35);
    }

    .cimis-page-btn.disabled {
        opacity: 0.35;
        cursor: not-allowed;
        pointer-events: none;
        border-color: #334155;
        color: #64748b;
    }

    .cimis-page-ellipsis {
        color: #64748b;
        padding: 0 4px;
        font-family: 'JetBrains Mono', monospace;
    }

    /* Impression propre A4 sans diagrammes ni cartes graphiques */
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm 8mm 12mm 8mm;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        body {
            background: #ffffff !important;
            color: #000000 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 8.5pt !important;
        }

        .personnels-plateau,
        .personnels-grid,
        .personnels-grid-cards {
            background: #ffffff !important;
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .personnel-item {
            border: 1px solid #000000 !important;
            background: #ffffff !important;
            page-break-inside: avoid !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 6px 10px !important;
            margin-bottom: 4px !important;
            color: #000000 !important;
        }

        .personnel-item .personnel-name,
        .personnels-plateau .personnel-name {
            color: #000000 !important;
            font-weight: 700 !important;
        }

        .personnel-item .personnel-grade,
        .personnels-plateau .personnel-grade {
            color: #222222 !important;
            font-weight: 600 !important;
        }

        .personnels-plateau .personnel-checkbox-wrapper,
        .personnel-checkbox,
        .personnel-actions,
        .photo-container,
        .personnel-photo,
        #plateau-info-bar,
        #plateau-pagination,
        .cimis-pagination,
        .top-status-bar,
        .back-button-container,
        .search-section,
        .search-toggle-bar,
        #recu-notification-banner,
        .main-actions,
        .batch-actions,
        .candidats-header,
        .results-section,
        .security-footer,
        #particles-canvas,
        canvas,
        img,
        button,
        .no-print {
            display: none !important;
        }
    }
    </style>
</head>
<body>

    <!-- Canvas Background -->
    <canvas id="particles-canvas"></canvas>

    <div class="app-container">

        <!-- TOP STATUS BAR -->
        <div class="top-status-bar">
            <div class="status-left">
                <span class="status-item warning-flash"><i class="fa-solid fa-triangle-exclamation"></i> SYSTÈME CLASSÉ SECRET DÉFENSE</span>
                <span class="status-item"><i class="fa-solid fa-globe"></i> RÉSEAU SÉCURISÉ</span>
                <span class="status-item"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="status-right">
                <span id="clock" class="text-mono">12:00:00</span>
            </div>
        </div>

        <!-- BOUTON RETOUR VERS DASHBOARD -->
        <div class="back-button-container" style="display: flex; justify-content: flex-start; align-items: center; width: 100%; margin: 1rem auto 0 auto; max-width: 1400px; padding: 0 1rem; position: relative;">
            <a href="/cimcim/dashboard" class="btn-back btn-back-dashboard">
                <i class="fa-solid fa-arrow-left"></i>
                <span>RETOUR TABLEAU DE BORD</span>
            </a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="container">
                
                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-check-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- BANDEAU NOTIFICATION PRIORITAIRE IMPRESSION REÇUS A4 -->
                <?php 
                $pending_count = count($_SESSION['pending_receipts'] ?? []);
                $pending_ids_str = implode(',', $_SESSION['pending_receipts'] ?? []);
                ?>
                <div id="recu-notification-banner" style="background: linear-gradient(135deg, #1e293b, #0f172a); border: 2px solid #10b981; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: <?php echo $pending_count > 0 ? 'flex' : 'none'; ?>; align-items: center; justify-content: space-between; gap: 1rem; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);">
                    <div style="display: flex; align-items: center; gap: 1rem; color: #fff;">
                        <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="fa-solid fa-print"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; color: #34d399; font-size: 1.05rem;"><i class="fa-solid fa-bell warning-flash"></i> REÇUS PAPIER A4 EN ATTENTE D'IMPRESSION</h4>
                            <p style="margin: 3px 0 0 0; color: #94a3b8; font-size: 0.85rem;"><span id="recu-count-badge" style="font-weight: bold; color: #fff;"><?php echo $pending_count; ?></span> carte(s) PVC imprimée(s) nécessitant la délivrance du reçu A4 signé.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <a id="btn-print-recu-batch" href="/cimcim/impression/recu?mode=batch&ids=<?php echo $pending_ids_str; ?>" target="_blank" onclick="viderQueueApresImpression()" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; font-weight: 700; text-decoration: none; padding: 0.65rem 1.2rem; border-radius: 8px; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-file-invoice"></i> IMPRIMER PLANCHE A4 (4 REÇUS/PAGE)
                        </a>
                        <button onclick="viderQueueRecus()" class="btn" style="background: #334155; color: #cbd5e1; border: 1px solid #475569; font-weight: 600; padding: 0.65rem 1rem; border-radius: 8px; font-size: 0.85rem; cursor: pointer;">
                            <i class="fa-solid fa-trash-can"></i> Vider la file
                        </button>
                    </div>
                </div>

                <!-- BARRE DE COMMANDE & BASCULE DE RECHERCHE AVANCÉE (MASQUÉE PAR DÉFAUT) -->
                <?php 
                $has_active_filters = !empty($_GET['search_nom']) || !empty($_GET['search_matricule']) || !empty($_GET['search_grade']) || !empty($_GET['search_unite']) || !empty($_GET['search_annee']) || !empty($_GET['search_cni']);
                ?>
                <div class="search-toggle-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 0.75rem 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fa-solid fa-filter" style="color: #38bdf8; font-size: 1.1rem;"></i>
                        <span style="font-weight: 700; color: #f8fafc; font-size: 0.95rem;">FILTRES & RECHERCHE AVANCÉE</span>
                        <span id="active-filters-badge" style="display: <?php echo $has_active_filters ? 'inline-block' : 'none'; ?>; background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: 600;">
                            <i class="fa-solid fa-check"></i> Filtres actifs
                        </span>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button type="button" id="toggleSearchBtn" onclick="toggleSearchSection()" class="btn" style="background: rgba(56, 189, 248, 0.15); border: 1px solid #38bdf8; color: #38bdf8; font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">
                            <i class="fa-solid fa-sliders"></i>
                            <span id="toggleSearchText"><?php echo $has_active_filters ? 'Masquer la recherche' : 'Afficher la recherche avancée'; ?></span>
                            <i id="toggleSearchIcon" class="fa-solid fa-chevron-<?php echo $has_active_filters ? 'up' : 'down'; ?>"></i>
                        </button>
                        <button type="button" onclick="clearSearch()" class="btn" style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; font-weight: 600; padding: 0.5rem 0.85rem; border-radius: 8px; cursor: pointer;" title="Réinitialiser tous les filtres">
                            <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- SEARCH AND FILTER SECTION (MASQUÉE PAR DÉFAUT) -->
                <div class="search-section" id="searchSection" style="display: <?php echo $has_active_filters ? 'block' : 'none'; ?>; margin-bottom: 1.5rem;">
                    <div class="search-header" style="text-align: center; margin-bottom: 1rem;">
                        <h3 style="color: #38bdf8;"><i class="fa-solid fa-magnifying-glass"></i> CRITÈRES DE RECHERCHE & FILTRAGE</h3>
                    </div>
                    
                    <div class="search-grid">
                        <!-- LIGNE 1 : 3 champs -->
                        <div class="search-group">
                            <label for="search_nom">Recherche Nom/Prénom</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-search"></i>
                                <input type="text" id="search_nom" name="search_nom" 
                                       placeholder="Saisir au moins 2 caractères..." 
                                       value="<?php echo htmlspecialchars($_GET['search_nom'] ?? ''); ?>">
                                <div class="search-results" id="nom_results"></div>
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_matricule">Matricule Militaire / CIM</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-id-card"></i>
                                <input type="text" id="search_matricule" name="search_matricule" 
                                       placeholder="Ex: T16/1527 ou CIM-20260001" 
                                       value="<?php echo htmlspecialchars($_GET['search_matricule'] ?? ''); ?>">
                                <div class="unit-indicator" id="unit_indicator"></div>
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_grade"><i class="fa-solid fa-award" style="color: #fbbf24;"></i> Grade Militaire</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-award"></i>
                                <select id="search_grade" name="search_grade">
                                    <option value="">Tous les grades</option>
                                    <?php
                                    $officialGrades = [
                                        'Soldat de 2E Classe', 'Soldat de 1E Classe', 'Caporal', 'Caporal-Chef',
                                        'Sergent', 'Sergent-Chef', 'Adjudant', 'Adjudant-Chef', 'Adjudant-Chef Major',
                                        'Major', 'Sous-Lieutenant', 'Lieutenant', 'Capitaine', 'Commandant',
                                        'Chef d\'Escadron', 'Lieutenant-Colonel', 'Colonel', 'Général de Brigade',
                                        'Général de Division', 'Général de Corps d\'Armée', 'Général d\'Armée',
                                        'Matelot de 2E Classe', 'Matelot de 1E Classe', 'Quartier-Maître de 2E Classe',
                                        'Quartier-Maître de 1E Classe', 'Second Maître', 'Maître', 'Premier Maître',
                                        'Maître Principal', 'Lieutenant de Vaisseau', 'Capitaine de Corvette',
                                        'Capitaine de Frégate', 'Capitaine de Vaisseau', 'Enseigne de Vaisseau de 1E Classe',
                                        'Maréchal des Logis', 'Maréchal des Logis-Chef',
                                        'CIVIL', 'AGENT', 'TECHNICIEN', 'INGÉNIEUR', 'CHEF DE SERVICE', 'DIRECTEUR'
                                    ];
                                    $allGrades = array_unique(array_merge($distinctGrades, $officialGrades));
                                    $allGrades = array_filter($allGrades, function($g) {
                                        return !empty(trim($g)) && !str_contains($g, '?');
                                    });
                                    sort($allGrades, SORT_NATURAL | SORT_FLAG_CASE);
                                    $selectedGrade = $_GET['search_grade'] ?? '';
                                    foreach ($allGrades as $grade) {
                                        if (empty(trim($grade))) continue;
                                        $sel = (strcasecmp($selectedGrade, $grade) === 0) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($grade) . "' $sel>" . htmlspecialchars($grade) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_unite"><i class="fa-solid fa-shield" style="color: #38bdf8;"></i> Unité / Corps</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-shield"></i>
                                <select id="search_unite" name="search_unite">
                                    <option value="">Toutes les unités</option>
                                    <option value="SIADOC" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'SIADOC') ? 'selected' : ''; ?>>SIADOC (IMPORTÉS SIADOC)</option>
                                    <option value="GENDARMERIE NATIONALE" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'GENDARMERIE NATIONALE') ? 'selected' : ''; ?>>GENDARMERIE NATIONALE</option>
                                    <option value="ARMÉE DE TERRE" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'ARMÉE DE TERRE') ? 'selected' : ''; ?>>ARMÉE DE TERRE</option>
                                    <option value="ARMÉE DE L'AIR" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'ARMÉE DE L\'AIR') ? 'selected' : ''; ?>>ARMÉE DE L'AIR</option>
                                    <option value="MARINE NATIONALE" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'MARINE NATIONALE') ? 'selected' : ''; ?>>MARINE NATIONALE</option>
                                    <option value="CIVIL" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'CIVIL') ? 'selected' : ''; ?>>PERSONNEL CIVIL</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_annee"><i class="fa-solid fa-calendar" style="color: #a78bfa;"></i> Année Promotion Grade</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-calendar"></i>
                                <input type="number" id="search_annee" name="search_annee" 
                                       placeholder="Ex: 2024" min="1960" max="<?php echo date('Y'); ?>"
                                       value="<?php echo htmlspecialchars($_GET['search_annee'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_cni"><i class="fa-solid fa-id-badge" style="color: #34d399;"></i> Numéro CNI</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-id-badge"></i>
                                <input type="text" id="search_cni" name="search_cni" 
                                       placeholder="Ex: 100234567" 
                                       value="<?php echo htmlspecialchars($_GET['search_cni'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS PRINCIPALES -->
                <div class="main-actions">
                    <button type="button" class="btn btn-select-all" onclick="selectAll()" title="Sélectionner ou désélectionner tous les militaires">
                        <i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL
                    </button>
                    <button type="button" class="btn btn-visualize" onclick="visualizeMultiple()" title="Visualiser les cartes sélectionnées">
                        <i class="fa-solid fa-eye"></i> VISUALISER SÉLECTION
                    </button>
                    <button type="button" class="btn btn-pdf-batch" onclick="generateBatch()" title="Générer les cartes PDF sélectionnées">
                        <i class="fa-solid fa-file-pdf"></i> GÉNÉRER PDF MULTIPLE
                    </button>
                    <button type="button" class="btn btn-pvc-batch" onclick="generateBatchPVC()" title="Impression PVC directe des cartes sélectionnées">
                        <i class="fa-solid fa-credit-card"></i> IMPRESSION PVC MULTIPLE
                    </button>
                    <button type="button" class="btn btn-delete-batch" onclick="deleteSelected()" title="Déplacer la sélection dans la corbeille">
                        <i class="fa-solid fa-trash"></i> SUPPRIMER SÉLECTION
                    </button>
                </div>

                <!-- COMPTEUR DE RÉSULTATS -->
                <div class="results-section">
                    <div class="result-count-display">
                        <span id="result-count" class="result-number"><?php echo $totalCount; ?></span>
                        <span class="result-text">résultats trouvés / results found</span>
                    </div>
                </div>

                <!-- Liste des candidats -->
                <div class="module-card">
                    <div class="candidats-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                        <h3 style="margin: 0;"><i class="fa-solid fa-id-card"></i> CARTES DISPONIBLES / AVAILABLE CARDS</h3>
                        <div class="view-mode-toggle">
                            <button type="button" class="view-mode-btn active" id="btnViewGrid" onclick="setViewMode('grid')" title="Affichage sous forme de cartes (Grille style Corbeille)">
                                <i class="fa-solid fa-table-cells-large"></i> CARTES
                            </button>
                            <button type="button" class="view-mode-btn" id="btnViewList" onclick="setViewMode('list')" title="Affichage sous forme de liste horizontale">
                                <i class="fa-solid fa-list-ul"></i> LISTE
                            </button>
                        </div>
                    </div>

                    <div class="cards-grid" id="personnelsContainer" data-total-count="<?php echo $totalCount; ?>" data-page="<?php echo $page; ?>" data-total-pages="<?php echo $totalPages; ?>" data-from="<?php echo $fromCount; ?>" data-to="<?php echo $toCount; ?>">
                        <?php if (empty($personnels)): ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <h4>Aucun personnel trouvé / No personnel found</h4>
                                <p>Essayez de modifiéer vos critères de recherche ou <a href="../enrolement.php">ajoutez un nouveau personnel</a> / Try modifying your search criteria or <a href="../enrolement.php">add new personnel</a></p>
                                <div class="empty-stats">
                                    <span><i class="fa-solid fa-database"></i> Base de données: <?php echo getTotalPersonnels(); ?> personnels / Database: <?php echo getTotalPersonnels(); ?> personnel</span>
                                    <span><i class="fa-solid fa-clock"></i> Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?> / Last update: <?php echo date('d/m/Y H:i'); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($personnels as $personnel): ?>
                                <?php
                                $pPhoto = resolvePersonnelPhoto($personnel['photo'] ?? ($personnel['photo_path'] ?? ''));
                                $st_mil_st = strtoupper($personnel['statut_militaire'] ?? '');
                                $is_suspended_st = ($personnel['suspendus'] == 1) || str_contains($st_mil_st, 'SUSPENDU') || in_array($st_mil_st, ['DESERTEUR', 'REVOQUE']);
                                $nb_reimp_st = intval($personnel['nb_reimpressions'] ?? 0);
                                $d_reimp_st_fmt = !empty($personnel['date_derniere_reimpression']) ? date('d/m/Y', strtotime($personnel['date_derniere_reimpression'])) : '';
                                $matAffSt = htmlspecialchars($personnel['matricule_militaire'] ?: $personnel['matricule']);
                                $nomCompletSt = htmlspecialchars($personnel['nom'] . ' ' . $personnel['prenom']);
                                $gradeSt = htmlspecialchars($personnel['grade'] ?: 'Non spécifié');
                                $uniteSt = htmlspecialchars(function_exists('normaliserUniteMilitaire') ? normaliserUniteMilitaire($personnel['unite']) : ($personnel['unite'] ?: 'Non spécifiée'));
                                $is_siadoc_st = (($personnel['source_system'] ?? '') === 'SIADOC' || ($personnel['siadoc_sync_status'] ?? '') === 'SYNCED' || str_starts_with($personnel['matricule_militaire'] ?? '', 'SIA'));
                                ?>
                                 <div class="personnel-item<?php echo $is_suspended_st ? ' suspended' : ''; ?>">
                                     <div class="personnel-card-top">
                                         <div class="personnel-checkbox-wrapper">
                                             <input type="checkbox" name="selected_personnels[]"
                                                    value="<?php echo $personnel['id']; ?>"
                                                    data-matricule="<?php echo htmlspecialchars($personnel['matricule']); ?>"
                                                    data-nb-reimpressions="<?php echo $nb_reimp_st; ?>"
                                                    data-date-reimpression="<?php echo $d_reimp_st_fmt; ?>"
                                                    class="personnel-checkbox" id="personnel_<?php echo $personnel['id']; ?>"
                                                    onchange="onCheckboxChange(event)">
                                             <label for="personnel_<?php echo $personnel['id']; ?>" class="checkbox-label"></label>
                                         </div>

                                         <?php if ($is_suspended_st): ?>
                                             <div class="suspension-indicator" title="Carte militaire suspendue / désactivée">
                                                 <i class="fa-solid fa-ban"></i>
                                             </div>
                                         <?php endif; ?>

                                         <div class="personnel-photo">
                                             <img src="<?php echo htmlspecialchars($pPhoto['src']); ?>" alt="Photo" onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($pPhoto['fallback']); ?>';">
                                         </div>
                                         <div class="personnel-info">
                                             <div class="personnel-name" title="<?php echo $nomCompletSt; ?>"><?php echo $nomCompletSt; ?></div>
                                             <div class="personnel-details">
                                                 <span><span class="badge-matricule"><?php echo $matAffSt; ?></span></span>
                                                 <span><i class="fa-solid fa-award" style="color: #fbbf24;"></i> <?php echo $gradeSt; ?></span>
                                                 <span><i class="fa-solid fa-shield" style="color: #38bdf8;"></i> <?php echo $uniteSt; ?><?php if ($is_siadoc_st): ?> <span style="background: rgba(14,165,233,0.18); border: 1px solid #0ea5e9; color: #38bdf8; font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; font-weight: 700; margin-left: 4px; vertical-align: middle;" title="Dossier militaire importé depuis SIADOC DRH"><i class="fa-solid fa-cloud-arrow-down"></i> SIADOC</span><?php endif; ?></span>
                                             </div>
                                             <?php if ($is_suspended_st): ?>
                                                 <?php 
                                                 $date_susp_st = !empty($personnel['date_changement_statut']) ? date('d/m/Y à H:i', strtotime($personnel['date_changement_statut'])) : date('d/m/Y');
                                                 $op_susp_st   = !empty($personnel['autorite_changement_statut']) ? htmlspecialchars($personnel['autorite_changement_statut']) : 'SUPER_ADMIN';
                                                 $mo_susp_st   = !empty($personnel['motif_changement_statut']) ? htmlspecialchars($personnel['motif_changement_statut']) : 'Suspension administrative (' . $st_mil_st . ')';
                                                 ?>
                                                 <div style="margin-top: 4px;">
                                                     <span class="suspension-badge-tag"><i class="fa-solid fa-ban"></i> CARTE SUSPENDUE (<?php echo $st_mil_st; ?>)</span>
                                                 </div>
                                             <?php endif; ?>
                                             <?php if ($nb_reimp_st >= 1): ?>
                                                 <div style="margin-top: 4px;">
                                                     <span style="background: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #fbbf24; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;" title="Carte déjà imprimée <?php echo $nb_reimp_st; ?> fois">
                                                         <i class="fa-solid fa-triangle-exclamation"></i> Déjà imprimée (<?php echo $nb_reimp_st; ?>x)
                                                     </span>
                                                 </div>
                                             <?php endif; ?>
                                         </div>
                                     </div>
                                     <div class="personnel-actions">
                                         <div class="card-status-info"></div>
                                         <div class="card-actions-row">
                                             <a href="/cimcim/carte/visualiser?matricule=<?php echo urlencode($personnel['matricule']); ?>" 
                                                class="btn-card-action btn-card-vis" title="Visualiser la carte">
                                                 <i class="fa-solid fa-id-card"></i>
                                             </a>
                                             <a href="/cimcim/candidat/edit?id=<?php echo $personnel['id']; ?>" 
                                                class="btn-card-action btn-card-edit" title="Modifier les informations">
                                                 <i class="fa-solid fa-edit"></i>
                                             </a>
                                             <a href="/cimcim/impression?action=generate&matricule=<?php echo urlencode($personnel['matricule']); ?>" 
                                                class="btn-card-action btn-card-pdf" title="Imprimer la carte (PDF)" target="_blank">
                                                 <i class="fa-solid fa-file-pdf"></i>
                                             </a>
                                             <button type="button" onclick="imprimerPVCEtQueue('<?php echo $personnel['id']; ?>', '/cimcim/impression/pvc?matricule=<?php echo urlencode($personnel['matricule']); ?>&mode=recto-verso', <?php echo $nb_reimp_st; ?>, '<?php echo $d_reimp_st_fmt; ?>')" 
                                                class="btn-card-action btn-card-pvc" title="Impression PVC Optimisée (85.60×53.98mm - 0 marge)">
                                                 <i class="fa-solid fa-credit-card"></i>
                                             </button>
                                             <a href="/cimcim/carte/3d?matricule=<?php echo urlencode($personnel['matricule']); ?>" 
                                                class="btn-card-action btn-card-3d" title="Visualiser la carte en 3D" target="_blank">
                                                 <i class="fa-solid fa-cube"></i>
                                             </a>
                                             <button type="button" class="btn-card-action btn-card-del" title="Déplacer dans la corbeille"
                                                     onclick="confirmDelete('<?php echo $personnel['id']; ?>')">
                                                 <i class="fa-solid fa-trash"></i>
                                             </button>
                                         </div>
                                     </div>
                                 </div>
                             <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- PAGINATION PRINCIPALE CIMIS -->
                    <?php echo renderImpressionPaginationHtml($page, $totalPages, $totalCount, $fromCount, $toCount, $perPage); ?>
                    <div id="plateau-pagination"></div>

                    <?php if (!empty($personnels)): ?>
                        <div class="batch-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px dashed rgba(56, 189, 248, 0.3);">
                            <button type="button" class="btn btn-select-all" onclick="selectAll()" title="Sélectionner ou désélectionner tous les militaires">
                                <i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL
                            </button>
                            <button type="button" class="btn btn-visualize" onclick="visualizeMultiple()" title="Visualiser les cartes sélectionnées">
                                <i class="fa-solid fa-eye"></i> VISUALISER SÉLECTION
                            </button>
                            <button type="button" class="btn btn-pdf-batch" onclick="generateBatch()" title="Générer les cartes PDF sélectionnées">
                                <i class="fa-solid fa-file-pdf"></i> GÉNÉRER PDF MULTIPLE
                            </button>
                            <button type="button" class="btn btn-pvc-batch" onclick="generateBatchPVC()" title="Impression PVC directe des cartes sélectionnées">
                                <i class="fa-solid fa-credit-card"></i> IMPRESSION PVC MULTIPLE
                            </button>
                            <button type="button" class="btn btn-delete-batch" onclick="deleteSelected()" title="Déplacer la sélection dans la corbeille">
                                <i class="fa-solid fa-trash"></i> SUPPRIMER SÉLECTION
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <footer class="security-footer">
            <div class="footer-version">SYSTÈME SÉCURISÉ</div>
            <div class="footer-left">
                <span><i class="fa-solid fa-shield-alt"></i> SYSTÈME DE GESTION DES CARTES D'IDENTITÉ</span>
                <span><i class="fa-solid fa-lock"></i> Connexion sécurisée</span>
            </div>
            <div class="footer-center">
                <a href="/cimcim/corbeille?mode=perso" class="trash-btn" title="Accéder à ma corbeille personnelle">
                    <i class="fa-solid fa-trash-can"></i>
                    <span class="trash-count" id="trash-count" style="<?php echo ($trashCount > 0) ? 'display: block;' : 'display: none;'; ?>">
                        <?php echo $trashCount; ?>
                    </span>
                </a>
            </div>
            <div class="footer-right">
                <span id="footer-clock" class="text-mono">00:00:00</span>
                <span><i class="fa-solid fa-server"></i> Serveur: ACTIF</span>
            </div>
        </footer>

        <style>
        .footer-center {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .trash-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 2px solid #dc3545;
            border-radius: 50%;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .trash-btn:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
            text-decoration: none;
        }

        .trash-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff6b6b;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            border: 2px solid white;
            min-width: 18px;
            text-align: center;
            line-height: 1;
        }
        </style>

        <script>
        // Fonction pour charger le compteur de la corbeille
        function loadTrashCount() {
            fetch('get_trash_count.php')
                .then(response => response.json())
                .then(data => {
                    const trashCount = document.getElementById('trash-count');
                    if (data.count > 0) {
                        trashCount.textContent = data.count;
                        trashCount.style.display = 'block';
                    } else {
                        trashCount.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement du compteur de corbeille:', error);
                });
        }

        // Charger le compteur au chargement de la page
        document.addEventListener('DOMContentLoaded', loadTrashCount);
        </script>

    </div>

    <script>
        // --- CLOCK ---
        setInterval(() => {
            const now = new Date();
            const clockElement = document.getElementById('clock');
            const footerClock = document.getElementById('footer-clock');
            if (clockElement) clockElement.innerText = now.toLocaleTimeString('fr-FR');
            if (footerClock) footerClock.innerText = now.toLocaleTimeString('fr-FR');
        }, 1000);

        // --- PARTICLE SYSTEM ---
        const canvas = document.getElementById('particles-canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        const particles = [];

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2;
                this.speedX = (Math.random() - 0.5) * 0.5;
                this.speedY = (Math.random() - 0.5) * 0.5;
                this.opacity = Math.random() * 0.5;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.opacity > 0) this.opacity -= 0.002;
                if (this.opacity <= 0) {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.opacity = Math.random() * 0.5;
                }
            }
            draw() {
                ctx.fillStyle = `rgba(10, 255, 186, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }
        
        for (let i = 0; i < 100; i++) particles.push(new Particle());

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }
        animate();

        // Handle window resize
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });



        function confirmDelete(id) {
            // Créer une modal de confirmation moderne
            const modal = document.createElement('div');
            modal.className = 'confirm-delete-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: #1a1a1a; padding: 2rem; border-radius: 10px; border: 2px solid var(--neon-red); max-width: 400px; text-align: center;">
                    <h3 style="color: var(--neon-red); margin-bottom: 1rem; font-size: 1.2rem;">
                        <i class="fa-solid fa-exclamation-triangle"></i> DÉPLACER DANS LA CORBEILLE / MOVE TO TRASH
                    </h3>
                    <p style="color: white; margin-bottom: 1.5rem; font-size: 1.1rem;">
                        Êtes-vous sûr de vouloir déplacer cette carte dans la corbeille ?<br>
                        Are you sure you want to move this card to trash?<br><br>
                        <strong>Vous pourrez la restaurer depuis la corbeille / You can restore it from trash.</strong>
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button onclick="this.closest('.confirm-delete-modal').remove()" style="background: #666; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                            <i class="fa-solid fa-times"></i> ANNULER
                        </button>
                        <button onclick="executeDelete(${id})" style="background: var(--neon-red); color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                            <i class="fa-solid fa-trash"></i> SUPPRIMER
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Fermer la modal en cliquant à l'extérieur
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        function executeDelete(id) {
            // Afficher un indicateur de chargement
            const loadingModal = document.createElement('div');
            loadingModal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10001;
            `;
            
            loadingModal.innerHTML = `
                <div style="background: #1a1a1a; padding: 2rem; border-radius: 10px; border: 2px solid var(--neon-green); text-align: center;">
                    <i class="fa-solid fa-spinner fa-spin" style="color: var(--neon-green); font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p style="color: white;">Suppression de la carte en cours...</p>
                </div>
            `;
            
            document.body.appendChild(loadingModal);
            
            // Fermer la modal de confirmation
            document.querySelector('[style*="z-index: 10000"]').remove();
            
            // Effectuer la suppression via AJAX en POST
            const formData = new FormData();
            formData.append('id', id);
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) formData.append('csrf_token', csrfMeta.getAttribute('content'));
            
            fetch('/cimcim/candidat/delete', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingModal.remove();
                
                if (data.success) {
                    showNotification(data.message || 'Carte déplacée dans la corbeille avec succès', 'success');
                    
                    const candidatCard = document.querySelector(`#personnel_${id}`)?.closest('.personnel-item') 
                                      || document.querySelector(`[onclick*="${id}"]`)?.closest('.personnel-item');
                    if (candidatCard) {
                        candidatCard.style.transition = 'all 0.3s ease';
                        candidatCard.style.opacity = '0';
                        candidatCard.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            candidatCard.remove();
                            updateResultCount();
                        }, 300);
                    }
                    const tb = document.getElementById('trash-count');
                    if (tb) {
                        let c = parseInt(tb.innerText || '0', 10);
                        tb.innerText = isNaN(c) ? 1 : c + 1;
                        tb.style.display = 'inline-block';
                    }
                } else {
                    showNotification(data.message || 'Erreur lors de la suppression de la carte', 'error');
                }
            })
            .catch(error => {
                loadingModal.remove();
                console.error('Erreur:', error);
                showNotification('Erreur lors de la suppression de la carte', 'error');
            });
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--neon-green)' : 'var(--neon-red)'};
                color: ${type === 'success' ? 'black' : 'white'};
                padding: 1rem;
                border-radius: 5px;
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2rem;">
                    <i class="fa-solid fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
        
        // Animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);

        // --- BASCULE DU FORMULAIRE DE RECHERCHE AVANCÉE (MASQUÉ PAR DÉFAUT) ---
        function toggleSearchSection() {
            const searchSec = document.getElementById('searchSection');
            const toggleText = document.getElementById('toggleSearchText');
            const toggleIcon = document.getElementById('toggleSearchIcon');
            const toggleBtn = document.getElementById('toggleSearchBtn');
            
            if (!searchSec) return;
            const isHidden = (searchSec.style.display === 'none' || getComputedStyle(searchSec).display === 'none');
            
            if (isHidden) {
                searchSec.style.display = 'block';
                searchSec.style.animation = 'slideIn 0.3s ease';
                if (toggleText) toggleText.textContent = 'Masquer la recherche';
                if (toggleIcon) toggleIcon.className = 'fa-solid fa-chevron-up';
                if (toggleBtn) {
                    toggleBtn.style.background = 'rgba(56, 189, 248, 0.3)';
                    toggleBtn.style.color = '#fff';
                    toggleBtn.style.borderColor = '#38bdf8';
                }
            } else {
                searchSec.style.display = 'none';
                if (toggleText) toggleText.textContent = 'Afficher la recherche avancée';
                if (toggleIcon) toggleIcon.className = 'fa-solid fa-chevron-down';
                if (toggleBtn) {
                    toggleBtn.style.background = 'rgba(56, 189, 248, 0.15)';
                    toggleBtn.style.color = '#38bdf8';
                }
            }
        }

        // --- GESTION DES 2 MODES D'AFFICHAGE : LISTE & TABLEAU ---
        let searchTimeout;
        let currentView = 'list';
        let selectedCandidates = new Set();
        let selectedCandidateIds = new Set();
        let selectedCandidateDetails = new Map();
        let currentImpressionPage = <?php echo (int)$page; ?>;
        let currentImpressionPerPage = <?php echo (int)$perPage; ?>;

        function goToImpressionPage(p) {
            currentImpressionPage = p;
            performSearch(p);
            const container = document.getElementById('personnelsContainer');
            if (container) {
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function changePerPage(val) {
            currentImpressionPerPage = parseInt(val, 10);
            currentImpressionPage = 1;
            performSearch(1);
        }
        
        // Mettre à jour le compteur de résultats
        function updateResultCount() {
            const container = document.getElementById('personnelsContainer');
            const pag = document.getElementById('impression-pagination');
            const resultCountEl = document.getElementById('result-count');
            if (resultCountEl) {
                const totalFromContainer = container?.getAttribute('data-total-count');
                const totalFromPag = pag?.getAttribute('data-total-count');
                if (totalFromContainer !== null && totalFromContainer !== undefined && totalFromContainer !== '') {
                    resultCountEl.textContent = totalFromContainer;
                } else if (totalFromPag !== null && totalFromPag !== undefined && totalFromPag !== '') {
                    resultCountEl.textContent = totalFromPag;
                } else if (container) {
                    resultCountEl.textContent = container.querySelectorAll('.personnel-item').length;
                }
            }
        }
        
        let currentViewMode = localStorage.getItem('cimis_impression_view_mode') || 'grid';

        function setViewMode(mode) {
            currentViewMode = (mode === 'list') ? 'list' : 'grid';
            const container = document.getElementById('personnelsContainer');
            const btnGrid = document.getElementById('btnViewGrid');
            const btnList = document.getElementById('btnViewList');

            if (container) {
                if (currentViewMode === 'list') {
                    container.classList.remove('cards-grid');
                    container.classList.add('view-list');
                } else {
                    container.classList.remove('view-list');
                    container.classList.add('cards-grid');
                }
            }

            if (btnGrid && btnList) {
                if (currentViewMode === 'list') {
                    btnList.classList.add('active');
                    btnGrid.classList.remove('active');
                } else {
                    btnGrid.classList.add('active');
                    btnList.classList.remove('active');
                }
            }

            try {
                localStorage.setItem('cimis_impression_view_mode', currentViewMode);
            } catch(e) {}
        }

        function toggleView(view) {
            setViewMode(view);
        }

        document.addEventListener('DOMContentLoaded', function() {
            setViewMode(currentViewMode);
        });

        /* ── PAGINATION PLATEAU (50 cartes max/page) ── */
        let _plateauPage = 1;
        const PLATEAU_PER_PAGE = 50;

        function applyPlateauPagination() {
            const container  = document.getElementById('personnelsContainer');
            const infoBar    = document.getElementById('plateau-info-bar');
            const pagBar     = document.getElementById('plateau-pagination');
            const allItems   = Array.from(container.querySelectorAll('.personnel-item'));
            const total      = allItems.length;
            const totalPages = Math.ceil(total / PLATEAU_PER_PAGE);

            if (total === 0) return;

            // Clamp la page courante
            _plateauPage = Math.max(1, Math.min(_plateauPage, totalPages));

            const start = (_plateauPage - 1) * PLATEAU_PER_PAGE;
            const end   = Math.min(start + PLATEAU_PER_PAGE, total);

            // Afficher/masquer les items
            allItems.forEach((item, i) => {
                item.style.display = (i >= start && i < end) ? '' : 'none';
            });

            // Barre d'info
            if (infoBar) {
                infoBar.classList.add('show');
                infoBar.innerHTML = `
                    <span><i class="fa-solid fa-border-all" style="color:#a78bfa"></i>
                    <strong>Plateau d'impression</strong> —
                    ${end - start} carte(s) affichée(s)
                    sur ${total} au total
                    (page ${_plateauPage}/${totalPages})
                    </span>
                    <span style="display:flex;gap:8px;align-items:center">
                        <button class="plateau-page-btn" onclick="window.print()" title="Imprimer ce plateau">
                            <i class="fa-solid fa-print"></i> Imprimer
                        </button>
                        <button class="plateau-page-btn" onclick="plateauSelectAll()" title="Tout sélectionner">
                            <i class="fa-solid fa-check-square"></i> Tout sélectionner
                        </button>
                    </span>`;
            }

            // Barre de pagination
            if (pagBar) {
                if (totalPages > 1) {
                    pagBar.classList.add('show');
                    let html = `<span style="color:#94a3b8;font-size:.75rem">Page :</span>`;
                    for (let p = 1; p <= totalPages; p++) {
                        html += `<button class="plateau-page-btn ${p === _plateauPage ? 'active' : ''}"
                            onclick="_plateauPage=${p};applyPlateauPagination()">${p}</button>`;
                    }
                    pagBar.innerHTML = html;
                } else {
                    pagBar.classList.remove('show');
                }
            }
        }

        function plateauSelectAll() {
            const container = document.getElementById('personnelsContainer');
            const visible   = Array.from(container.querySelectorAll('.personnel-item'))
                              .filter(el => el.style.display !== 'none');
            const allChecked = visible.every(el => el.querySelector('.personnel-checkbox')?.checked);
            visible.forEach(el => {
                const cb = el.querySelector('.personnel-checkbox');
                if (cb) cb.checked = !allChecked;
            });
        }
        
        // Imprimer la sélection au format PVC optimisé
        function printSelected() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            // Utiliser data-matricule
            const matricules = Array.from(selected).map(cb => cb.getAttribute('data-matricule'));
            const ids = Array.from(selected).map(cb => cb.value);
            if (confirm(`Imprimer les ${selected.length} carte(s) sélectionnée(s) au format PVC (85.60×53.98mm) ?\n\nFormat: Carte PVC sans marges\nPages: ${selected.length * 2} (recto + verso)`)) {
                // Enregistrer dans la file d'attente des reçus
                fetch('../backend/queue_recu.php?action=add&ids=' + encodeURIComponent(ids.join(',')))
                    .finally(() => {
                        window.open(`impression_pvc_multiple.php?matricules=${encodeURIComponent(matricules.join(','))}`, '_blank');
                    });
            }
        }
        
        // Visualiser 3D la sélection (Studio 3D)
        function visualize3DSelected() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                showNotification('Veuillez sélectionner au moins une carte pour la vue 3D', 'error');
                return;
            }

            const matricules = Array.from(selected)
                .map(cb => cb.getAttribute('data-matricule'))
                .filter(m => m && m.trim() !== '');

            if (matricules.length === 0) {
                showNotification('Aucun matricule valide trouvé dans la sélection', 'error');
                return;
            }

            window.open(`/cimcim/carte/3d?matricules=${encodeURIComponent(matricules.join(','))}`, '_blank');
        }
        
        // Suppression multiple des éléments sélectionnés
        function deleteSelected() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                showNotification('Veuillez sélectionner au moins une carte à supprimer', 'error');
                return;
            }

            const count = selected.length;
            if (!confirm(`Êtes-vous sûr de vouloir déplacer les ${count} carte(s) sélectionnée(s) dans la corbeille ?`)) {
                return;
            }

            const ids = Array.from(selected).map(cb => cb.value);

            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) formData.append('csrf_token', csrfMeta.getAttribute('content'));

            fetch('/cimcim/candidat/delete', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || `${count} carte(s) déplacée(s) dans la corbeille`, 'success');
                    ids.forEach(id => {
                        const item = document.querySelector(`#personnel_${id}`)?.closest('.personnel-item');
                        if (item) {
                            item.style.transition = 'all 0.3s ease';
                            item.style.opacity = '0';
                            item.style.transform = 'scale(0.8)';
                            setTimeout(() => item.remove(), 300);
                        }
                    });
                    const batchActions = document.querySelector('.batch-actions');
                    if (batchActions) batchActions.style.display = 'none';
                    const tb = document.getElementById('trash-count');
                    if (tb) {
                        let c = parseInt(tb.innerText || '0', 10);
                        tb.innerText = isNaN(c) ? count : c + count;
                        tb.style.display = 'inline-block';
                    }
                    setTimeout(updateResultCount, 350);
                } else {
                    showNotification(data.message || 'Erreur lors de la suppression multiple', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors de la suppression des éléments sélectionnés', 'error');
            });
        }

        // Sélectionner / Désélectionner toutes les cartes de la page
        function selectAll() {
            const checkboxes = document.querySelectorAll('.personnel-checkbox');
            if (checkboxes.length === 0) return;
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
                const mat = cb.getAttribute('data-matricule');
                const id = cb.value;
                if (!allChecked) {
                    selectedCandidateIds.add(id);
                    if (mat) selectedCandidates.add(mat);
                    selectedCandidateDetails.set(id, {
                        id: id,
                        matricule: mat,
                        nbReimpressions: parseInt(cb.getAttribute('data-nb-reimpressions') || '0', 10),
                        dateReimpression: cb.getAttribute('data-date-reimpression') || ''
                    });
                } else {
                    selectedCandidateIds.delete(id);
                    if (mat) selectedCandidates.delete(mat);
                    selectedCandidateDetails.delete(id);
                }
            });
            updateSelection();
        }

        // Visualiser toutes les cartes sélectionnées (Galerie 2D)
        function visualizeMultiple() {
            if (selectedCandidates.size === 0) {
                showNotification('Veuillez sélectionner au moins une carte à visualiser', 'error');
                return;
            }

            const matricules = Array.from(selectedCandidates)
                .filter(m => m && m.trim() !== '');

            if (matricules.length === 0) {
                showNotification('Aucun matricule valide trouvé dans la sélection', 'error');
                return;
            }

            window.open(`/cimcim/carte/visualiser?matricules=${encodeURIComponent(matricules.join(','))}`, '_blank');
        }

        // Gestion de la sélection multiple (mise à jour UI)
        function updateSelection() {
            const batchActions = document.querySelector('.batch-actions');
            if (batchActions) {
                batchActions.style.display = selectedCandidateIds.size > 0 ? 'flex' : 'none';
            }
            const badgeInfo = document.getElementById('selected-badge-info');
            const badgeCount = document.getElementById('selected-badge-count');
            if (badgeInfo && badgeCount) {
                badgeCount.textContent = selectedCandidateIds.size;
                badgeInfo.style.display = selectedCandidateIds.size > 0 ? 'inline-flex' : 'none';
            }
        }

        function onCheckboxChange(e) {
            const cb = e.target;
            const mat = cb.getAttribute('data-matricule');
            const id = cb.value;
            if (cb.checked) {
                selectedCandidateIds.add(id);
                if (mat) selectedCandidates.add(mat);
                selectedCandidateDetails.set(id, {
                    id: id,
                    matricule: mat,
                    nbReimpressions: parseInt(cb.getAttribute('data-nb-reimpressions') || '0', 10),
                    dateReimpression: cb.getAttribute('data-date-reimpression') || ''
                });
            } else {
                selectedCandidateIds.delete(id);
                if (mat) selectedCandidates.delete(mat);
                selectedCandidateDetails.delete(id);
            }
            updateSelection();
        }
        
        // Recherche instantanée par nom/prénom
        document.getElementById('search_nom').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(1);
            }, 250);
        });

        // Recherche instantanée par matricule militaire ou CIM
        document.getElementById('search_matricule').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim().toUpperCase();
            
            // Détecter l'unité selon le préfixe indicatif militaire officiel
            const unitIndicator = document.getElementById('unit_indicator');
            if (value.length >= 1 && unitIndicator) {
                const prefix = value.charAt(0);
                let unite = '';
                
                if (value.startsWith('CIM') || /^\d{2}CIM/.test(value)) {
                    // C'est un matricule de carte CIMIS, ne pas déduire arbitrairement CIVIL
                    unite = '';
                } else if (prefix === 'T') unite = 'ARMÉE DE TERRE';
                else if (prefix === 'M') unite = 'MARINE NATIONALE';
                else if (prefix === 'A') unite = "ARMÉE DE L'AIR";
                else if (prefix === 'G') unite = 'GENDARMERIE NATIONALE';
                else if (prefix === 'C' && !value.startsWith('CIM')) unite = 'PERSONNEL CIVIL';
                else if (value.startsWith('SIA')) unite = 'IMPORTÉ SIADOC';
                
                if (unite) {
                    unitIndicator.innerHTML = `<span style="display:inline-flex;align-items:center;gap:4px;color:#38bdf8;font-size:0.75rem;font-weight:600;padding:2px 8px;border-radius:4px;background:rgba(56,189,248,0.15);margin-top:4px;"><i class="fa-solid fa-shield"></i> ${unite}</span>`;
                } else {
                    unitIndicator.innerHTML = '';
                }
            } else if (unitIndicator) {
                unitIndicator.innerHTML = '';
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(1);
            }, 250);
        });

        // Recherche instantanée par grade
        document.getElementById('search_grade').addEventListener('change', function() {
            performSearch(1);
        });

        // Recherche instantanée par unité
        document.getElementById('search_unite').addEventListener('change', function() {
            performSearch(1);
        });

        // Recherche instantanée par année
        document.getElementById('search_annee').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(1);
            }, 250);
        });

        // Recherche instantanée par CNI
        document.getElementById('search_cni').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(1);
            }, 250);
        });

        // Effacer la recherche sans recharger la page
        function clearSearch() {
            document.getElementById('search_nom').value = '';
            document.getElementById('search_matricule').value = '';
            document.getElementById('search_grade').value = '';
            document.getElementById('search_unite').value = '';
            document.getElementById('search_annee').value = '';
            document.getElementById('search_cni').value = '';
            const unitInd = document.getElementById('unit_indicator');
            if (unitInd) unitInd.innerHTML = '';
            const activeBadge = document.getElementById('active-filters-badge');
            if (activeBadge) activeBadge.style.display = 'none';

            // Nettoyer l'URL sans rechargement pour éviter la rémanence des anciens filtres GET
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            performSearch(1);
        }

        // Fonction de recherche dynamique sur toute la base avec pagination robuste
        function performSearch(page = 1) {
            showLoading();
            currentImpressionPage = page;
            
            const formData = new FormData();
            formData.append('ajax_search', '1');
            formData.append('page', page);
            formData.append('per_page', currentImpressionPerPage);
            
            // Jeton CSRF pour compatibilité Laravel
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            formData.append('csrf_token', csrfToken);
            formData.append('_token', csrfToken);
            
            // Ajouter tous les critères de recherche
            const searchFields = ['search_nom', 'search_matricule', 'search_grade', 'search_unite', 'search_annee', 'search_cni'];
            let hasFilters = false;
            searchFields.forEach(field => {
                const element = document.getElementById(field);
                if (element && element.value.trim() !== '') {
                    formData.append(field, element.value.trim());
                    hasFilters = true;
                }
            });

            const activeBadge = document.getElementById('active-filters-badge');
            if (activeBadge) {
                activeBadge.style.display = hasFilters ? 'inline-block' : 'none';
            }

            const targetUrl = window.location.pathname.includes('/cimcim') ? '/cimcim/impression' : '/impression';

            function handleSearchHtml(html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newGrid = doc.querySelector('#personnelsContainer');
                const pag = doc.querySelector('#impression-pagination');
                const currentContainer = document.querySelector('#personnelsContainer');
                
                if (newGrid && currentContainer) {
                    currentContainer.innerHTML = newGrid.innerHTML;
                    
                    // Récupérer le nombre total réel de résultats depuis les métadonnées serveur
                    const totalFound = parseInt(newGrid.getAttribute('data-total-count') || pag?.getAttribute('data-total-count') || '0', 10);
                    currentContainer.setAttribute('data-total-count', totalFound);
                    
                    const resultCountEl = document.getElementById('result-count');
                    if (resultCountEl) {
                        resultCountEl.textContent = totalFound;
                    }
                    
                    // Mettre à jour la pagination
                    const existingPag = document.querySelector('#impression-pagination');
                    if (pag && existingPag) {
                        existingPag.outerHTML = pag.outerHTML;
                    } else if (pag) {
                        currentContainer.insertAdjacentElement('afterend', pag);
                    }
                    
                    // Réattacher les événements et restaurer les sélections
                    attachCheckboxListeners();

                    // Réappliquer le mode d'affichage actuel (Cartes ou Liste)
                    setViewMode(currentViewMode);
                } else if (currentContainer) {
                    currentContainer.innerHTML = 
                        '<div class="empty-state" style="padding: 2.5rem; text-align: center; color: #94a3b8;"><i class="fa-solid fa-inbox" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #38bdf8; display: block;"></i><h4>Aucun personnel trouvé</h4><p>Essayez de modifier vos critères de recherche</p></div>';
                    const resultCountEl = document.getElementById('result-count');
                    if (resultCountEl) resultCountEl.textContent = '0';
                    const existingPag = document.querySelector('#impression-pagination');
                    if (existingPag) existingPag.innerHTML = '';
                }
                
                hideLoading();
            }

            // Exécution principale via POST avec bascule automatique vers GET en secours
            fetch(targetUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return '';
                }
                if (!response.ok) {
                    throw new Error('POST failed status ' + response.status);
                }
                return response.text();
            })
            .then(html => {
                if (html) handleSearchHtml(html);
            })
            .catch(err => {
                console.warn('Bascule recherche GET:', err);
                const params = new URLSearchParams();
                for (const [k, v] of formData.entries()) {
                    params.append(k, v);
                }
                fetch(targetUrl + '?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => {
                    if (!r.ok) throw new Error('GET failed status ' + r.status);
                    return r.text();
                })
                .then(html => handleSearchHtml(html))
                .catch(error => {
                    console.error('Erreur finale recherche:', error);
                    const currentContainer = document.querySelector('#personnelsContainer');
                    if (currentContainer) {
                        currentContainer.innerHTML = 
                            '<div class="search-error" style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 1.25rem; border-radius: 10px; text-align: center; margin: 1rem 0;"><i class="fa-solid fa-triangle-exclamation"></i> Une erreur est survenue lors de la recherche. Veuillez vérifier votre connexion.</div>';
                    }
                    hideLoading();
                });
            });
        }

        // Afficher l'indicateur de chargement
        function showLoading() {
            const container = document.querySelector('#personnelsContainer');
            if (container) {
                container.style.opacity = '0.5';
            }
        }

        // Masquer l'indicateur de chargement
        function hideLoading() {
            const container = document.querySelector('#personnelsContainer');
            if (container) {
                container.style.opacity = '1';
            }
        }

        // Attacher les événements aux checkboxes et préserver l'état de sélection
        function attachCheckboxListeners() {
            document.querySelectorAll('.personnel-checkbox').forEach(checkbox => {
                if (selectedCandidateIds.has(checkbox.value)) {
                    checkbox.checked = true;
                }
                checkbox.removeEventListener('change', onCheckboxChange);
                checkbox.addEventListener('change', onCheckboxChange);
            });
            updateSelection();
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            updateResultCount();
            attachCheckboxListeners();
            let initialView = 'grid';
            try {
                initialView = localStorage.getItem('cimis_impression_view_mode') || 'grid';
            } catch(e) {}
            setViewMode(initialView);
        });

        // Exporter les résultats
        function exportResults() {
            const formData = new FormData();
            formData.append('export', '1');
            
            // Ajouter tous les critères de recherche
            const searchFields = ['search_nom', 'search_matricule', 'search_grade', 'search_unite', 'search_annee', 'search_cni'];
            searchFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    formData.append(field, element.value);
                }
            });

            fetch('impression.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'candidats_cimis_' + new Date().toISOString().slice(0, 10) + '.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Erreur lors de l\'export:', error);
            });
        }
        
        // Enregistrer dans la file d'attente des reçus et ouvrir l'impression PVC avec avertissement si déjà imprimée
        function imprimerPVCEtQueue(id, url, nbReimpressions, dateDerniere) {
            if (nbReimpressions && parseInt(nbReimpressions) >= 1) {
                let dateStr = dateDerniere ? dateDerniere : 'date antérieure';
                if (dateStr.includes('-')) {
                    const parts = dateStr.split('-');
                    if (parts.length === 3) dateStr = `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                const confirmMsg = `⚠️ Attention : Cette carte a déjà été imprimée le ${dateStr}.\n\nConfirmer la réimpression / réédition ?`;
                if (!confirm(confirmMsg)) {
                    return;
                }
            }
            if (id) {
                fetch('../backend/queue_recu.php?action=add&id=' + encodeURIComponent(id))
                    .finally(() => {
                        window.location.href = url;
                    });
            } else {
                window.location.href = url;
            }
        }
        
        // Générer le document PDF multiple (Planche HD) pour les cartes sélectionnées
        function generateBatch() {
            let matricules = [];
            let ids = [];
            let reimpressionItems = [];

            if (typeof selectedCandidates !== 'undefined' && selectedCandidates.size > 0) {
                matricules = Array.from(selectedCandidates).filter(m => m && m.trim() !== '');
                ids = Array.from(selectedCandidateIds || []);
                if (typeof selectedCandidateDetails !== 'undefined') {
                    selectedCandidateDetails.forEach(item => {
                        if (item.nbReimpressions >= 1) {
                            reimpressionItems.push(`• ${item.matricule} (déjà imprimée le ${item.dateReimpression || 'date antérieure'})`);
                        }
                    });
                }
            } else {
                const selected = document.querySelectorAll('.personnel-checkbox:checked');
                selected.forEach(cb => {
                    const m = cb.getAttribute('data-matricule');
                    if (m && m.trim() !== '') matricules.push(m.trim());
                    if (cb.value) ids.push(cb.value);
                    const nb = parseInt(cb.getAttribute('data-nb-reimpressions') || '0', 10);
                    if (nb >= 1) {
                        const dt = cb.getAttribute('data-date-reimpression') || 'date antérieure';
                        reimpressionItems.push(`• ${m} (déjà imprimée le ${dt})`);
                    }
                });
            }

            if (matricules.length === 0) {
                if (typeof showNotification === 'function') {
                    showNotification('Veuillez sélectionner au moins une carte pour générer le PDF multiple', 'error');
                } else {
                    alert('Veuillez sélectionner au moins une carte pour générer le PDF multiple');
                }
                return;
            }

            let confirmMsg = `Générer le PDF pour les ${matricules.length} cartes sélectionnées ?`;
            if (reimpressionItems.length > 0) {
                confirmMsg = `⚠️ Attention : ${reimpressionItems.length} carte(s) ont DÉJÀ été imprimées :\n\n${reimpressionItems.slice(0, 5).join('\n')}${reimpressionItems.length > 5 ? '\n• ... et autres' : ''}\n\nConfirmer la génération PDF de ces ${matricules.length} cartes ?`;
            }

            if (confirm(confirmMsg)) {
                const url = '/cimcim/impression?action=generate_batch&matricules=' + encodeURIComponent(matricules.join(','));
                if (ids.length > 0) {
                    fetch('../backend/queue_recu.php?action=add&ids=' + encodeURIComponent(ids.join(',')))
                        .finally(() => {
                            window.open(url, '_blank');
                        });
                } else {
                    window.open(url, '_blank');
                }
            }
        }

        // Générer impression PVC multiple avec vérification des réimpressions
        function generateBatchPVC() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            const reimpressionItems = [];
            selected.forEach(cb => {
                const nb = parseInt(cb.getAttribute('data-nb-reimpressions') || '0');
                if (nb >= 1) {
                    const mat = cb.getAttribute('data-matricule');
                    const dt = cb.getAttribute('data-date-reimpression') || 'date antérieure';
                    reimpressionItems.push(`• ${mat} (déjà imprimée le ${dt})`);
                }
            });
            
            let confirmMsg = `Imprimer en PVC les ${selected.length} cartes sélectionnées ?`;
            if (reimpressionItems.length > 0) {
                confirmMsg = `⚠️ Attention : ${reimpressionItems.length} carte(s) ont DÉJÀ été imprimées :\n\n${reimpressionItems.slice(0, 5).join('\n')}${reimpressionItems.length > 5 ? '\n• ... et autres' : ''}\n\nConfirmer la réimpression / réédition de ces ${selected.length} cartes ?`;
            }
            
            if (confirm(confirmMsg)) {
                const matricules = Array.from(selected).map(cb => cb.getAttribute('data-matricule'));
                const ids = Array.from(selected).map(cb => cb.value);
                
                // Enregistrer tous les IDs dans la file d'attente des reçus
                fetch('../backend/queue_recu.php?action=add&ids=' + encodeURIComponent(ids.join(',')))
                    .finally(() => {
                        window.location.href = '/cimcim/impression/pvc?matricules=' + matricules.join(',') + '&mode=recto-verso';
                    });
            }
        }
    </script>

    <script>
        function confirmDelete(id) {
            if (!confirm("Êtes-vous sûr de vouloir déplacer cette carte dans la corbeille ?")) {
                return;
            }
            const formData = new FormData();
            formData.append('ids[]', id);
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) formData.append('csrf_token', csrfMeta.getAttribute('content'));

            fetch('/cimcim/candidat/delete', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Carte déplacée dans la corbeille avec succès', 'success');
                    const item = document.querySelector(`#personnel_${id}`)?.closest('.personnel-item');
                    if (item) {
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.8)';
                        setTimeout(() => item.remove(), 300);
                    }
                    const tb = document.getElementById('trash-count');
                    if (tb) {
                        let c = parseInt(tb.innerText || '0', 10);
                        tb.innerText = isNaN(c) ? 1 : c + 1;
                        tb.style.display = 'block';
                    }
                    setTimeout(updateResultCount, 350);
                } else {
                    showNotification(data.message || 'Erreur lors du déplacement dans la corbeille', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors de la suppression de la carte', 'error');
            });
        }
        function ajouterQueueRecus(ids) {
            fetch('/cimcim/impression?action=add_receipt_queue&ids=' + encodeURIComponent(ids))
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const banner = document.getElementById('recu-notification-banner');
                        const badge = document.getElementById('recu-count-badge');
                        const btnBatch = document.getElementById('btn-print-recu-batch');
                        if (banner) banner.style.display = 'flex';
                        if (badge) badge.innerText = data.count;
                        if (btnBatch && data.ids) btnBatch.href = '/cimcim/impression/recu?mode=batch&ids=' + data.ids.join(',');
                    }
                })
                .catch(err => console.error(err));
        }

        function viderQueueRecus() {
            fetch('/cimcim/impression?action=clear_receipts_queue')
                .then(res => res.json())
                .then(data => {
                    const banner = document.getElementById('recu-notification-banner');
                    if (banner) {
                        banner.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                        banner.style.opacity = '0';
                        banner.style.transform = 'translateY(-10px)';
                        setTimeout(() => { banner.style.display = 'none'; }, 350);
                    }
                })
                .catch(err => {
                    const banner = document.getElementById('recu-notification-banner');
                    if (banner) banner.style.display = 'none';
                });
        }

        function viderQueueApresImpression() {
            // Après l'ouverture de l'onglet d'impression du reçu, vider la file et masquer la bannière
            setTimeout(() => {
                viderQueueRecus();
            }, 600);
        }
    </script>
</body>
</html>

