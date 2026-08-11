# PROCÉDURE D'ÉCHANGE API CIMIS - SIADOC
## Intégration des Systèmes d'Information Militaires

---

## INFORMATIONS D'HÉBERGEMENT

### CIMIS (InfinityFree)
- **Domaine** : cimis.ct.ws
- **IP** : 185.27.134.208
- **Répertoire** : cimis.ct.ws/htdocs
- **Compte** : if0_39882531
- **MySQL Host** : sql113.infinityfree.com
- **MySQL Port** : 3306
- **MySQL Username** : if0_39882531
- **MySQL Password** : cmTJtR2Z2yq8MO
- **Database** : if0_39882531_XXX

### SIADOC (GT.TC)
- **Domaine** : siadoc.gt.tc
- **IP** : 185.27.134.208
- **Répertoire** : siadoc.gt.tc/htdocs
- **Compte** : if0_39882531
- **Database** : if0_39882531_siadoc

---

## ARCHITECTURE DE L'ÉCHANGE

### Schéma de Communication
```
CIMIS (cimis.ct.ws)           SIADOC (siadoc.gt.tc)
       │                               │
       ├───> API REST HTTPS ────────┤
       │                               │
       └───> Webhook Events ───────┤
       │                               │
       └───> Sync Database ────────┘
```

### Protocole de Sécurité
- **HTTPS/TLS 1.3** obligatoire
- **API Keys** bidirectionnelles
- **OAuth 2.0** pour l'authentification
- **Rate limiting** par IP
- **Logging complet** des échanges
- **Encryption** des données sensibles

---

## ENDPOINTS API CIMIS

### 1. Authentification
```http
POST https://cimis.ct.ws/api/auth
Content-Type: application/json
Authorization: Bearer SIADOC_API_KEY

{
    "client_id": "siadoc_system",
    "client_secret": "SIADOC_SECRET_KEY",
    "grant_type": "client_credentials"
}
```

**Réponse :**
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "def50200f3b8b8d5f5e5a5a5c5a5d5e5f5g5h5i5j5k5l5m5n5o5p5q5r5s5t5u5v5w5x5y5z"
}
```

### 2. Envoi des Données Militaires
```http
POST https://cimis.ct.ws/api/militaires/sync
Content-Type: application/json
Authorization: Bearer ACCESS_TOKEN

{
    "militaires": [
        {
            "matricule": "CM20260001",
            "nom": "DOE",
            "prenom": "JOHN",
            "sexe": "MASCULIN",
            "date_naissance": "1990-01-15",
            "lieu_naissance": "YAOUNDE",
            "grade": "CAPITAINE",
            "unite": "BATAILLON X",
            "date_entree_service": "2015-06-01",
            "specialite": "INFANTERIE",
            "contact": {
                "telephone": "+237 6XX XXX XXX",
                "email": "john.doe@mindef.cm"
            },
            "adresse": {
                "quartier": "BASTOS",
                "ville": "DOUALA",
                "region": "LITTORAL"
            }
        }
    ],
    "sync_timestamp": "2026-04-11T05:48:00Z",
    "source_system": "SIADOC",
    "batch_id": "BATCH_20260411_001"
}
```

**Réponse :**
```json
{
    "status": "success",
    "message": "Données synchronisées avec succès",
    "processed": 1,
    "failed": 0,
    "errors": [],
    "sync_id": "SYNC_20260411_001",
    "timestamp": "2026-04-11T05:48:30Z"
}
```

### 3. Vérification du Statut de Synchronisation
```http
GET https://cimis.ct.ws/api/sync/status/SYNC_20260411_001
Authorization: Bearer ACCESS_TOKEN
```

**Réponse :**
```json
{
    "sync_id": "SYNC_20260411_001",
    "status": "completed",
    "progress": 100,
    "processed": 1,
    "total": 1,
    "started_at": "2026-04-11T05:48:00Z",
    "completed_at": "2026-04-11T05:48:30Z",
    "details": {
        "militaires_created": 0,
        "militaires_updated": 1,
        "militaires_failed": 0
    }
}
```

### 4. Récupération des Cartes Générées
```http
GET https://cimis.ct.ws/api/cartes/generatees?date_debut=2026-04-01&date_fin=2026-04-11
Authorization: Bearer ACCESS_TOKEN
```

**Réponse :**
```json
{
    "cartes": [
        {
            "id_carte": "CIMIS_20260411_001",
            "matricule": "CM20260001",
            "date_generation": "2026-04-11T05:45:00Z",
            "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
            "statut": "GENERE",
            "url_carte": "https://cimis.ct.ws/Carte/carte_CM20260001.pdf"
        }
    ],
    "total": 1,
    "page": 1,
    "per_page": 50
}
```

---

## ENDPOINTS API SIADOC

### 1. Réception des Données CIMIS
```http
POST https://siadoc.gt.tc/api/cimis/recevoir
Content-Type: application/json
Authorization: Bearer SIADOC_API_KEY

{
    "source": "CIMIS",
    "type": "CARTE_GENEREE",
    "timestamp": "2026-04-11T05:45:00Z",
    "data": {
        "matricule": "CM20260001",
        "id_carte": "CIMIS_20260411_001",
        "date_generation": "2026-04-11T05:45:00Z",
        "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
        "statut": "GENERE"
    }
}
```

### 2. Confirmation de Réception
```http
POST https://siadoc.gt.tc/api/cimis/confirmation
Content-Type: application/json
Authorization: Bearer SIADOC_API_KEY

{
    "reception_id": "RECEP_20260411_001",
    "carte_id": "CIMIS_20260411_001",
    "matricule": "CM20260001",
    "status": "RECU",
    "timestamp": "2026-04-11T05:50:00Z",
    "actions": [
        "ENREGISTRE_DANS_SIADOC",
        "NOTIFICATION_SUPERIEUR",
        "ARCHIVAGE_SECURE"
    ]
}
```

---

## CONFIGURATION TECHNIQUE

### 1. Clés API
```php
// Configuration CIMIS
define('CIMIS_API_KEY', 'CIMIS_SIADOC_2026_KEY');
define('CIMIS_API_SECRET', 'CIMIS_SIADOC_SECRET_2026');
define('SIADOC_API_KEY', 'SIADOC_CIMIS_2026_KEY');
define('SIADOC_API_SECRET', 'SIADOC_CIMIS_SECRET_2026');
define('SIADOC_WEBHOOK_SECRET', 'SIADOC_WEBHOOK_2026');

// URLs des APIs
define('CIMIS_API_URL', 'https://cimis.ct.ws/api/');
define('SIADOC_API_URL', 'https://siadoc.gt.tc/api/');
```

### 2. Configuration CORS
```php
// Dans les deux systèmes
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
```

### 3. Validation des Requêtes
```php
function validateAPIRequest($request, $required_fields) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($request[$field]) || empty($request[$field])) {
            $errors[] = "Champ obligatoire manquant: $field";
        }
    }
    
    return $errors;
}
```

---

## SCRIPT D'IMPLÉMENTATION

### 1. Script CIMIS pour envoyer à SIADOC
```php
<?php
// api_cimis_to_siadoc.php
require_once 'backend/config.php';

class CIMISToSIADOC {
    private $siadoc_api_url;
    private $api_key;
    private $access_token;
    
    public function __construct() {
        $this->siadoc_api_url = 'https://siadoc.gt.tc/api/';
        $this->api_key = 'SIADOC_CIMIS_2026_KEY';
        $this->authenticate();
    }
    
    private function authenticate() {
        $response = $this->makeRequest('auth', [
            'client_id' => 'cimis_system',
            'client_secret' => 'CIMIS_SIADOC_SECRET_2026',
            'grant_type' => 'client_credentials'
        ]);
        
        $this->access_token = $response['access_token'];
    }
    
    public function sendCarteGeneree($carte_data) {
        $payload = [
            'source' => 'CIMIS',
            'type' => 'CARTE_GENEREE',
            'timestamp' => date('c'),
            'data' => $carte_data
        ];
        
        return $this->makeRequest('cimis/recevoir', $payload);
    }
    
    private function makeRequest($endpoint, $data = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->siadoc_api_url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->access_token
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'data' => json_decode($response, true),
            'http_code' => $http_code
        ];
    }
}

// Utilisation
$api = new CIMISToSIADOC();

// Envoyer une nouvelle carte générée
$carte = [
    'matricule' => 'CM20260001',
    'id_carte' => 'CIMIS_20260411_001',
    'date_generation' => date('c'),
    'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...',
    'statut' => 'GENERE'
];

$result = $api->sendCarteGeneree($carte);
echo json_encode($result);
?>
```

### 2. Script SIADOC pour recevoir de CIMIS
```php
<?php
// api_siadoc_recevoir.php
require_once 'backend/config.php';

class SIADOCReceiver {
    private $cimis_api_key = 'CIMIS_SIADOC_2026_KEY';
    
    public function __construct() {
        $this->validateRequest();
    }
    
    private function validateRequest() {
        $headers = getallheaders();
        
        // Vérifier l'authorization
        if (!isset($headers['Authorization'])) {
            $this->sendError('Authorization manquante', 401);
        }
        
        $auth_header = $headers['Authorization'];
        if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $this->sendError('Format d\'authorization invalide', 401);
        }
        
        $token = $matches[1];
        if ($token !== $this->cimis_api_key) {
            $this->sendError('Token invalide', 401);
        }
    }
    
    public function receiveFromCIMIS() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Valider les données
        $required_fields = ['source', 'type', 'timestamp', 'data'];
        $errors = $this->validateData($input, $required_fields);
        
        if (!empty($errors)) {
            $this->sendError('Données invalides: ' . implode(', ', $errors), 400);
        }
        
        // Traiter selon le type
        switch ($input['type']) {
            case 'CARTE_GENEREE':
                return $this->handleCarteGeneree($input['data']);
            case 'MILITAIRE_MIS_A_JOUR':
                return $this->handleMilitaireUpdate($input['data']);
            default:
                $this->sendError('Type de message non supporté', 400);
        }
    }
    
    private function handleCarteGeneree($data) {
        try {
            // Insérer dans la base SIADOC
            $stmt = $pdo->prepare("
                INSERT INTO cartes_cimis 
                (matricule, id_carte, date_generation, qr_code, statut, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['matricule'],
                $data['id_carte'],
                $data['date_generation'],
                $data['qr_code'],
                $data['statut']
            ]);
            
            // Envoyer confirmation à CIMIS
            $this->sendConfirmation($data);
            
            $this->sendResponse([
                'status' => 'success',
                'message' => 'Carte enregistrée avec succès',
                'carte_id' => $data['id_carte']
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Erreur lors de l\'enregistrement: ' . $e->getMessage(), 500);
        }
    }
    
    private function sendConfirmation($carte_data) {
        $payload = [
            'reception_id' => 'RECEP_' . date('YmdHis'),
            'carte_id' => $carte_data['id_carte'],
            'matricule' => $carte_data['matricule'],
            'status' => 'RECU',
            'timestamp' => date('c'),
            'actions' => ['ENREGISTRE_DANS_SIADOC', 'NOTIFICATION_SUPERIEUR']
        ];
        
        // Envoyer à l'API CIMIS
        $this->makeCIMISRequest('cimis/confirmation', $payload);
    }
    
    private function makeCIMISRequest($endpoint, $data) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://cimis.ct.ws/api/' . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->cimis_api_key
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
    
    private function validateData($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = $field;
            }
        }
        
        return $errors;
    }
    
    private function sendResponse($data, $http_code = 200) {
        header('Content-Type: application/json');
        http_response_code($http_code);
        echo json_encode($data);
        exit();
    }
    
    private function sendError($message, $http_code = 400) {
        $this->sendResponse([
            'status' => 'error',
            'message' => $message
        ], $http_code);
    }
}

// Router
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

if ($request_method === 'POST' && strpos($request_uri, '/api/cimis/recevoir') !== false) {
    $receiver = new SIADOCReceiver();
    $receiver->receiveFromCIMIS();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint non trouvé']);
}
?>
```

---

## PROCÉDURE DE DÉPLOIEMENT

### 1. Configuration des Bases de Données
```sql
-- Dans la base CIMIS
CREATE TABLE IF NOT EXISTS api_sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_id VARCHAR(50) UNIQUE,
    target_system VARCHAR(20),
    status ENUM('pending', 'processing', 'completed', 'failed'),
    data_sent TEXT,
    response_received TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL
);

-- Dans la base SIADOC
CREATE TABLE IF NOT EXISTS cartes_cimis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) UNIQUE,
    id_carte VARCHAR(50) UNIQUE,
    date_generation DATETIME,
    qr_code TEXT,
    statut VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2. Configuration des Webhooks
```php
// Dans CIMIS - pour recevoir les confirmations de SIADOC
function handleSIADOCWebhook() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Vérifier la signature
    $signature = $_SERVER['HTTP_X_SIADOC_SIGNATURE'];
    $expected_signature = hash_hmac('sha256', json_encode($input), 'SIADOC_WEBHOOK_2026');
    
    if ($signature !== $expected_signature) {
        http_response_code(401);
        exit();
    }
    
    // Traiter le webhook
    logWebhook($input);
    
    http_response_code(200);
    echo json_encode(['status' => 'received']);
}
```

### 3. Monitoring et Logging
```php
function logAPICall($system, $endpoint, $data, $response, $http_code) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'system' => $system,
        'endpoint' => $endpoint,
        'http_code' => $http_code,
        'data_size' => strlen(json_encode($data)),
        'response_size' => strlen(json_encode($response))
    ];
    
    file_put_contents('api_logs.log', json_encode($log_entry) . "\n", FILE_APPEND);
}
```

---

## TESTS ET VALIDATION

### 1. Script de Test
```php
<?php
// test_api_integration.php

echo "=== TEST D'INTÉGRATION API CIMIS-SIADOC ===\n\n";

// Test 1: Authentification
echo "Test 1: Authentification CIMIS → SIADOC\n";
$auth_data = [
    'client_id' => 'cimis_system',
    'client_secret' => 'CIMIS_SIADOC_SECRET_2026',
    'grant_type' => 'client_credentials'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://siadoc.gt.tc/api/auth',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($auth_data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n\n";

// Test 2: Envoi de données
echo "Test 2: Envoi carte générée\n";
$carte_data = [
    'matricule' => 'CM20260001',
    'id_carte' => 'TEST_' . date('YmdHis'),
    'date_generation' => date('c'),
    'qr_code' => 'TEST_QR_CODE',
    'statut' => 'GENERE'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://siadoc.gt.tc/api/cimis/recevoir',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'source' => 'CIMIS',
        'type' => 'CARTE_GENEREE',
        'timestamp' => date('c'),
        'data' => $carte_data
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer SIADOC_CIMIS_2026_KEY'
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n\n";

echo "=== FIN DES TESTS ===\n";
?>
```

---

## SÉCURITÉ ET MONITORING

### 1. Mesures de Sécurité
- **HTTPS obligatoire** sur tous les endpoints
- **API Keys** rotatives tous les 90 jours
- **Rate limiting** : 100 requêtes/minute/IP
- **IP whitelist** pour les endpoints critiques
- **Logging complet** de tous les échanges
- **Encryption** des données sensibles
- **Digital signatures** pour les webhooks

### 2. Monitoring
- **Dashboard de monitoring** des APIs
- **Alertes automatiques** en cas d'échec
- **Métriques de performance** (temps de réponse, taux d'erreur)
- **Health checks** toutes les 5 minutes
- **Backup automatique** des logs

### 3. Gestion des Erreurs
- **Retry automatique** avec backoff exponentiel
- **Queue de messages** pour les échecs
- **Notification admin** après 3 échecs consécutifs
- **Fallback mechanism** si API indisponible

---

## CALENDRIER DE DÉPLOIEMENT

### Phase 1: Préparation (Semaine 1)
- [ ] Configuration des bases de données
- [ ] Création des clés API
- [ ] Mise en place des endpoints
- [ ] Tests unitaires

### Phase 2: Intégration (Semaine 2)
- [ ] Déploiement des scripts API
- [ ] Configuration des webhooks
- [ ] Tests d'intégration
- [ ] Validation de la sécurité

### Phase 3: Production (Semaine 3)
- [ ] Déploiement en production
- [ ] Monitoring actif
- [ ] Formation des équipes
- [ ] Documentation finale

---

## CONTACTS SUPPORT

### Équipe Technique CIMIS
- **Email** : support@cimis.ct.ws
- **Téléphone** : +237 XXX XXX XXX
- **API Documentation** : https://cimis.ct.ws/api/docs

### Équipe Technique SIADOC
- **Email** : support@siadoc.gt.tc
- **Téléphone** : +237 XXX XXX XXX
- **API Documentation** : https://siadoc.gt.tc/api/docs

---

*Document de Procédure API CIMIS-SIADOC*
*Version 1.0*
*Dernière mise à jour : 11 Avril 2026*
*Classification : CONFIDENTIEL DÉFENSE*
