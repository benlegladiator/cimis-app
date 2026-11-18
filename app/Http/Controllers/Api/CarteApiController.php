<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PDO;

class CarteApiController extends Controller
{
    /**
     * Obtenir une instance PDO connectée
     */
    protected function getPdo(): PDO
    {
        global $pdo;
        if (!$pdo) {
            require_once base_path('config/config.php');
        }
        return $pdo;
    }

    /**
     * S'assurer que le service SiadocApiService est chargé
     */
    protected function loadHelper(): void
    {
        require_once base_path('app/Services/SiadocApiService.php');
    }

    /**
     * Valide l'authentification de la requête API (Session administrateur active OU clé d'API valide)
     */
    protected function authorizeApiRequest(Request $request): ?\Illuminate\Http\JsonResponse
    {
        // 1. Session administrateur connectée
        if ($request->hasSession() && $request->session()->get('user_logged_in') === true) {
            return null;
        }
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_logged_in'])) {
            return null;
        }

        // 2. Clé d'API (Header X-API-KEY, Authorization: Bearer, ou paramètre api_key)
        $apiKey = $request->header('X-API-KEY');
        if (empty($apiKey)) {
            $authHeader = $request->header('Authorization', '');
            if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
                $apiKey = $matches[1];
            }
        }
        if (empty($apiKey)) {
            $apiKey = $request->input('api_key');
        }

        $expectedKey = env('SIADOC_API_KEY') ?: (defined('SIADOC_API_KEY') ? SIADOC_API_KEY : '');
        if (empty($expectedKey)) {
            $expectedKey = config('security.siadoc_api_key', '');
        }
        if (empty($expectedKey)) {
            $expectedKey = config('services.siadoc.api_key', '');
        }
        if (empty($expectedKey)) {
            $expectedKey = $_ENV['SIADOC_API_KEY'] ?? getenv('SIADOC_API_KEY') ?: '';
        }
        if (empty($expectedKey)) {
            $expectedKey = 'siadoc-2026-cimis-integration';
        }

        if (!empty($apiKey) && !empty($expectedKey) && hash_equals($expectedKey, trim($apiKey))) {
            return null;
        }

        return new \Illuminate\Http\JsonResponse([
            'success' => false,
            'error'   => 'Non autorisé : Clé d\'API CIMIS/SIADOC requise ou invalide.'
        ], 401, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Dossier médical d'urgence d'un militaire (table candidat_medical),
     * formaté pour l'interopérabilité SIADOC.
     */
    protected function getDossierMedical(PDO $pdo, int $candidatId): array
    {
        $vide = [
            'dossier_medical'           => (object)[],
            'traitement_en_cours'       => null,
            'contact_urgence_nom'       => null,
            'contact_urgence_lien'      => null,
            'contact_urgence_telephone' => null,
        ];

        try {
            $stmt = $pdo->prepare("SELECT * FROM candidat_medical WHERE candidat_id = :id LIMIT 1");
            $stmt->execute(['id' => $candidatId]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return $vide;
        }

        if (!$m) {
            return $vide;
        }

        $pathologies = [
            'allergies', 'maladie_chronique', 'maladie_cardiaque', 'maladie_respiratoire',
            'operation', 'probleme_vue', 'probleme_audition', 'handicap',
            'probleme_mobilite', 'probleme_psy',
        ];

        $dossier = [];
        foreach ($pathologies as $p) {
            if (!empty($m['has_' . $p])) {
                $dossier[$p] = trim((string)($m[$p . '_details'] ?? '')) ?: 'OUI';
            }
        }

        return [
            'dossier_medical'           => empty($dossier) ? (object)[] : $dossier,
            'traitement_en_cours'       => ($m['traitement_en_cours'] ?? null) ?: null,
            'contact_urgence_nom'       => ($m['contact_urgence_nom'] ?? null) ?: null,
            'contact_urgence_lien'      => ($m['contact_urgence_lien'] ?? null) ?: null,
            'contact_urgence_telephone' => ($m['contact_urgence_telephone'] ?? null) ?: null,
        ];
    }

    /**
     * GET /api/cartes
     * Liste paginée des cartes militaires avec filtres
     */
    public function index(Request $request)
    {
        if ($authError = $this->authorizeApiRequest($request)) {
            return $authError;
        }

        $this->loadHelper();
        $pdo = $this->getPdo();

        $page  = max(1, (int)$request->input('page', 1));
        $limit = max(1, min(100, (int)$request->input('limit', 20)));
        $offset = ($page - 1) * $limit;

        $where = ["(supprimer = 0 OR supprimer IS NULL)"];
        $params = [];

        $matricule = $request->input('matricule');
        if (!empty($matricule)) {
            $where[] = "(matricule = :mat1 OR matricule_militaire = :mat2)";
            $params['mat1'] = trim($matricule);
            $params['mat2'] = trim($matricule);
        }

        $search = $request->input('search');
        if (!empty($search)) {
            $where[] = "(matricule LIKE :search1 OR matricule_militaire LIKE :search2 OR nom LIKE :search3 OR prenom LIKE :search4)";
            $params['search1'] = '%' . trim($search) . '%';
            $params['search2'] = '%' . trim($search) . '%';
            $params['search3'] = '%' . trim($search) . '%';
            $params['search4'] = '%' . trim($search) . '%';
        }

        $grade = $request->input('grade');
        if (!empty($grade)) {
            $where[] = "LOWER(grade) = LOWER(:grade)";
            $params['grade'] = trim($grade);
        }

        $unite = $request->input('unite');
        if (!empty($unite)) {
            $where[] = "(LOWER(unite) LIKE LOWER(:unite))";
            $params['unite'] = '%' . trim($unite) . '%';
        }

        $whereSql = implode(' AND ', $where);

        // Nombre total
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM candidat WHERE $whereSql");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Sélection
        $stmtList = $pdo->prepare("SELECT * FROM candidat WHERE $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
        $stmtList->execute($params);
        $candidats = $stmtList->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $includeImages = $request->boolean('include_images', true);
        $cartes = [];

        foreach ($candidats as $c) {
            $formatted = [
                'id'                  => (int)$c['id'],
                'matricule'           => $c['matricule'],
                'matricule_militaire' => $c['matricule_militaire'] ?: $c['matricule'],
                'matricule_cimis'     => $c['matricule'],
                'nom'                 => $c['nom'],
                'prenom'              => $c['prenom'] ?: '',
                'grade'               => $c['grade'] ?: 'SOLDAT',
                'unite'               => $c['unite'] ?: 'ARMÉE DE TERRE',
                'date_naissance'      => $c['date_naissance'] ?: '',
                'lieu_naissance'      => $c['lieu_naissance'] ?: '',
                'sexe'                => $c['sexe'] ?: 'MASCULIN',
                'numero_cni'          => $c['numero_cni'] ?: '',
                'groupe_sanguin'      => $c['groupe_sanguin'] ?: 'O+',
                'taille'              => $c['taille'] ?: '175',
                'poids'               => $c['poids'] ?: '70',
                'date_enrolement'     => $c['date_enrolement'] ?: '',
                'date_dernier_grade'  => $c['date_dernier_grade'] ?: '',
                'statut'              => ($c['supprimer'] == 1) ? 'SUSPENDU' : 'ACTIVE',
                'statut_carte'        => ($c['supprimer'] == 1) ? 'SUSPENDU' : 'ACTIVE',
                'photo_url'           => !empty($c['photo']) ? '/cimcim/public/' . ltrim($c['photo'], '/') : null,
            ];

            if ($includeImages) {
                // Optimisation majeure : pour la liste paginée de l'annuaire, générer la miniature web légère (~25 Ko) avec cache disque
                // L'image originale haute définition (2 à 3 Mo) reste intacte sur disque pour les impressions et consultations unitaires
                $photoMiniature = siadocGenererMiniatureBase64($c['photo'] ?? $c['photo_path'] ?? null, 280, 350);
                $formatted['photo_base64']     = $photoMiniature;
                $formatted['photo_miniature']  = $photoMiniature;
                $formatted['photoVisage']      = $photoMiniature;

                // Empreintes lourdes (slaps 4-4-2) et photo originale uniquement si explicitement demandé ou recherche unitaire
                $needFullBio = $request->boolean('include_biometrie', false) || (!empty($search) && count($candidats) === 1);
                if ($needFullBio) {
                    $bio = formatCandidatBiometrieForSiadoc($c);
                    $formatted['photo_originale']       = $bio['photoVisage'] ?? null;
                    $formatted['empreinte_data']        = $bio['empreinteDoigt2'] ?? null;
                    $formatted['empreinteDoigt1']       = $bio['empreinteDoigt1'] ?? null;
                    $formatted['empreinteDoigt2']       = $bio['empreinteDoigt2'] ?? null;
                    $formatted['code_qr']               = $bio['qrCodeImage'] ?? null;
                    $formatted['qrCodeImage']           = $bio['qrCodeImage'] ?? null;
                    $formatted['empreinte_carte']       = $bio['empreinte_carte'] ?? null;
                    $formatted['empreinte_pouces']      = $bio['empreinte_pouces'] ?? null;
                    $formatted['empreinte_slap_gauche'] = $bio['empreinte_slap_gauche'] ?? null;
                    $formatted['empreinte_slap_droite'] = $bio['empreinte_slap_droite'] ?? null;
                    $formatted = array_merge($formatted, $this->getDossierMedical($pdo, (int)$c['id']));
                }
            }

            $cartes[] = $formatted;
        }

        return new \Illuminate\Http\JsonResponse([
            'success' => true,
            'data'    => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($total / $limit),
                'cartes'      => $cartes,
                'militaires'  => $cartes
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /api/cartes/{matricule}
     * Récupérer le détail complet et la biométrie d'un militaire spécifique
     */
    public function show(Request $request, ?string $matricule = null)
    {
        if ($authError = $this->authorizeApiRequest($request)) {
            return $authError;
        }

        $this->loadHelper();
        $pdo = $this->getPdo();

        $targetMatricule = $matricule ?: $request->input('matricule');
        if (empty($targetMatricule)) {
            return new \Illuminate\Http\JsonResponse([
                'success' => false,
                'error'   => 'Le matricule est obligatoire'
            ], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM candidat WHERE (matricule = :m1 OR matricule_militaire = :m2) AND (supprimer = 0 OR supprimer IS NULL) LIMIT 1");
        $stmt->execute(['m1' => trim($targetMatricule), 'm2' => trim($targetMatricule)]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$c) {
            return new \Illuminate\Http\JsonResponse([
                'success' => false,
                'error'   => "Aucun militaire trouvé pour le matricule : $targetMatricule"
            ], 404);
        }

        $bio = formatCandidatBiometrieForSiadoc($c);

        $data = [
            'id'                  => (int)$c['id'],
            'matricule'           => $c['matricule_militaire'] ?: $c['matricule'],
            'matricule_militaire' => $c['matricule_militaire'] ?: $c['matricule'],
            'matricule_cimis'     => $c['matricule'],
            'numeroCIM'           => $c['matricule'],
            'nom'                 => $c['nom'],
            'prenom'              => $c['prenom'] ?: '',
            'grade'               => $c['grade'] ?: 'SOLDAT',
            'unite'               => $c['unite'] ?: 'ARMÉE DE TERRE',
            'date_naissance'      => $c['date_naissance'] ?: '',
            'lieu_naissance'      => $c['lieu_naissance'] ?: '',
            'sexe'                => $c['sexe'] ?: 'MASCULIN',
            'numero_cni'          => $c['numero_cni'] ?: '',
            'groupe_sanguin'      => $c['groupe_sanguin'] ?: 'O+',
            'taille'              => $c['taille'] ?: '175',
            'poids'               => $c['poids'] ?: '70',
            'date_enrolement'     => $c['date_enrolement'] ?: '',
            'date_dernier_grade'  => $c['date_dernier_grade'] ?: '',
            'statut'              => ($c['supprimer'] == 1) ? 'SUSPENDU' : 'ACTIVE',
            'statut_carte'        => ($c['supprimer'] == 1) ? 'SUSPENDU' : 'ACTIVE',
            'photo_url'           => !empty($c['photo']) ? '/cimcim/public/' . ltrim($c['photo'], '/') : null,
            // Biométrie Base64 brute originale HD (pour impression et archive militaire)
            'photo_base64'        => $bio['photoVisage'] ?? null,
            'photoVisage'         => $bio['photoVisage'] ?? null,
            'photo_originale'     => $bio['photoVisage'] ?? null,
            // Miniature web légère pour affichage d'aperçu rapide
            'photo_miniature'     => siadocGenererMiniatureBase64($c['photo'] ?? $c['photo_path'] ?? null, 280, 350),
            'empreinte_data'      => $bio['empreinteDoigt2'] ?? null,
            'empreinteDoigt1'     => $bio['empreinteDoigt1'] ?? null,
            'empreinteDoigt2'     => $bio['empreinteDoigt2'] ?? null,
            'code_qr'             => $bio['qrCodeImage'] ?? null,
            'qrCodeImage'         => $bio['qrCodeImage'] ?? null,

            // Biométrie complète protocole 4-4-2 (chaque capture identifiée explicitement)
            'empreinte_carte'       => $bio['empreinte_carte'] ?? null,       // Pouce droit gravé sur la carte
            'empreinte_pouces'      => $bio['empreinte_pouces'] ?? null,      // 2 pouces
            'empreinte_slap_gauche' => $bio['empreinte_slap_gauche'] ?? null, // 4 doigts main gauche
            'empreinte_slap_droite' => $bio['empreinte_slap_droite'] ?? null, // 4 doigts main droite
        ];

        // Dossier médical d'urgence & contact (table candidat_medical)
        $data = array_merge($data, $this->getDossierMedical($pdo, (int)$c['id']));

        return new \Illuminate\Http\JsonResponse([
            'success' => true,
            'data'    => $data
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /api/cartes/statistiques
     * Statistiques globales de production des cartes
     */
    public function statistiques(Request $request)
    {
        if ($authError = $this->authorizeApiRequest($request)) {
            return $authError;
        }

        $pdo = $this->getPdo();

        $stmt = $pdo->query("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN supprimer = 0 OR supprimer IS NULL THEN 1 ELSE 0 END) AS actives,
                SUM(CASE WHEN supprimer = 1 THEN 1 ELSE 0 END) AS suspendus,
                SUM(CASE WHEN date_derniere_reimpression IS NOT NULL THEN 1 ELSE 0 END) AS reimpressions
            FROM candidat
        ");
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $total = (int)($res['total'] ?? 0);
        $actives = (int)($res['actives'] ?? 0);
        $suspendus = (int)($res['suspendus'] ?? 0);
        $reimpressions = (int)($res['reimpressions'] ?? 0);

        return new \Illuminate\Http\JsonResponse([
            'success' => true,
            'data'    => [
                'total'         => $total,
                'total_cartes'  => $total,
                'actives'       => $actives,
                'suspendus'     => $suspendus,
                'reimpressions' => $reimpressions,
                'validees'      => $actives
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
