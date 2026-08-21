<?php
// API SIADOC - Interface selon contrat d'interface officiel
require_once 'backend/config.php';

// Configuration de la réponse en JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration SIADOC
define('SIADOC_API_URL', 'https://siadoc.gt.tc/api/');
define('SIADOC_API_KEY', 'a1b2c3d4-e5f6-7890'); // Clé fournie par SIADOC

// Fonction pour envoyer une réponse d'erreur
function sendErrorResponse($message, $http_code = 400) {
    http_response_code($http_code);
    echo json_encode([
        'error' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

// Fonction pour envoyer une réponse de succès
function sendSuccessResponse($data, $message = null) {
    $response = $data;
    if ($message) {
        $response['message'] = $message;
    }
    echo json_encode($response);
}

// Fonction pour appeler l'API SIADOC
function callSIADOCAPI($endpoint, $params = []) {
    $url = SIADOC_API_URL . $endpoint;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
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
    $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $year . $sequence;
}

// Fonction pour générer un QR code
function generateQRCode($matricule, $matricule_cimis) {
    $qr_data = "https://cimis.cm/verify/" . $matricule;
    $qr_filename = 'img/qrcodes/' . $matricule . '_qr.png';
    
    // Créer le répertoire si nécessaire
    if (!file_exists('img/qrcodes')) {
        mkdir('img/qrcodes', 0777, true);
    }
    
    // Simulation de génération de QR code
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

// Fonction pour encoder une image en base64 brut
function encodeImageToBase64Raw($image_path) {
    if (file_exists($image_path)) {
        $image_data = file_get_contents($image_path);
        return base64_encode($image_data);
    }
    return null;
}

// Router API
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path_info = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path_info, '/'));

// Endpoint principal: Interface web pour tester l'API SIADOC
if ($request_method === 'GET' && (empty($path_parts[1]) || $path_parts[1] === 'api_siadoc.php')) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>API SIADOC - Interface CIMIS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
            .container { padding-top: 2rem; }
            .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
            .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
            .result-box { background: #f8f9fa; border-radius: 10px; padding: 1rem; margin-top: 1rem; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title text-center mb-4">
                                <i class="fas fa-exchange-alt"></i> API SIADOC - Interface CIMIS
                            </h2>
                            
                            <!-- Appel d'un Militaire -->
                            <div class="mb-4">
                                <h4><i class="fas fa-user"></i> 1. Appeler un Militaire (SIADOC → CIMIS)</h4>
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <input type="text" id="matriculeInput" class="form-control" placeholder="Matricule (ex: MAT-2023-12345)">
                                    </div>
                                    <div class="col-md-4">
                                        <button onclick="getMilitaire()" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Appeler
                                        </button>
                                    </div>
                                </div>
                                <div id="militaireResult" class="result-box" style="display: none;"></div>
                            </div>
                            
                            <!-- Appel des Militaires par Période -->
                            <div class="mb-4">
                                <h4><i class="fas fa-calendar-alt"></i> 2. Appeler les Militaires par Période</h4>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label>Date Début:</label>
                                        <input type="date" id="dateDebutInput" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Date Fin:</label>
                                        <input type="date" id="dateFinInput" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label>&nbsp;</label>
                                        <button onclick="getMilitairesPeriode()" class="btn btn-info w-100">
                                            <i class="fas fa-calendar-check"></i> Appeler Période
                                        </button>
                                    </div>
                                </div>
                                <div id="periodeResult" class="result-box" style="display: none;"></div>
                            </div>
                            
                            <!-- Appel de Tous les Militaires -->
                            <div class="mb-4">
                                <h4><i class="fas fa-users"></i> 3. Appeler Tous les Militaires</h4>
                                <button onclick="getTousMilitaires()" class="btn btn-warning">
                                    <i class="fas fa-users"></i> Appeler Tous les Militaires
                                </button>
                                <div id="tousMilitairesResult" class="result-box" style="display: none;"></div>
                            </div>
                            
                            <!-- Actions sur les Résultats -->
                            <div class="mb-4">
                                <h4><i class="fas fa-cogs"></i> 4. Actions sur les Résultats</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <button onclick="genererMatriculesCIMIS()" class="btn btn-success w-100">
                                            <i class="fas fa-id-card"></i> Générer Matricules CIMIS
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button onclick="genererQRCodes()" class="btn btn-primary w-100">
                                            <i class="fas fa-qrcode"></i> Générer QR Codes
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button onclick="enregistrerEnBase()" class="btn btn-secondary w-100">
                                            <i class="fas fa-database"></i> Enregistrer en Base
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button onclick="envoyerBiometrie()" class="btn btn-dark w-100">
                                            <i class="fas fa-fingerprint"></i> Envoyer Biométrie
                                        </button>
                                    </div>
                                </div>
                                <div id="actionsResult" class="result-box" style="display: none;"></div>
                            </div>
                            
                            <!-- Test Envoi Biométrie -->
                            <div class="mb-4">
                                <h4><i class="fas fa-upload"></i> 2. Envoyer les Biométries (CIMIS → SIADOC)</h4>
                                <div class="row">
                                    <div class="col-md-8">
                                        <input type="text" id="matriculeBioInput" class="form-control" placeholder="Matricule pour envoi biométrie">
                                    </div>
                                    <div class="col-md-4">
                                        <button onclick="sendBiometrie()" class="btn btn-success w-100">
                                            <i class="fas fa-fingerprint"></i> Envoyer
                                        </button>
                                    </div>
                                </div>
                                <div id="biometrieResult" class="result-box" style="display: none;"></div>
                            </div>
                            
                            <!-- Statistiques -->
                            <div class="mb-4">
                                <h4><i class="fas fa-chart-bar"></i> 3. Statistiques CIMIS</h4>
                                <button onclick="getStats()" class="btn btn-info">
                                    <i class="fas fa-chart-line"></i> Voir les stats
                                </button>
                                <div id="statsResult" class="result-box" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // Variables globales pour stocker les données
            let militairesData = [];
            let selectedMilitaires = [];

            function getMilitaire() {
                const matricule = document.getElementById('matriculeInput').value;
                if (!matricule) {
                    alert('Veuillez entrer un matricule');
                    return;
                }
                
                fetch(`api_siadoc.php?action=get_militaire&matricule=${encodeURIComponent(matricule)}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('militaireResult');
                        if (data.error) {
                            resultDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        } else {
                            militairesData = [data];
                            selectedMilitaires = [data.matricule];
                            resultDiv.innerHTML = `
                                <h5>✅ Militaire trouvé</h5>
                                <div class="row">
                                    <div class="col-md-6"><strong>Nom:</strong> ${data.nom || ''}</div>
                                    <div class="col-md-6"><strong>Prénom:</strong> ${data.prenom || ''}</div>
                                    <div class="col-md-6"><strong>Matricule:</strong> ${data.matricule || ''}</div>
                                    <div class="col-md-6"><strong>Grade:</strong> ${data.grade || ''}</div>
                                    <div class="col-md-6"><strong>Corps:</strong> ${data.corps || ''}</div>
                                    <div class="col-md-6"><strong>Date naissance:</strong> ${data.dateNaissance || ''}</div>
                                </div>
                                <div class="alert alert-info mt-2">
                                    <input type="checkbox" id="selectMilitaire_${data.matricule}" checked>
                                    <label for="selectMilitaire_${data.matricule}">Sélectionner pour actions</label>
                                </div>
                            `;
                        }
                        resultDiv.style.display = 'block';
                    })
                    .catch(error => {
                        document.getElementById('militaireResult').innerHTML = 
                            `<div class="alert alert-danger">Erreur: ${error.message}</div>`;
                        document.getElementById('militaireResult').style.display = 'block';
                    });
            }
            
            function getMilitairesPeriode() {
                const dateDebut = document.getElementById('dateDebutInput').value;
                const dateFin = document.getElementById('dateFinInput').value;
                
                if (!dateDebut || !dateFin) {
                    alert('Veuillez entrer les dates de début et de fin');
                    return;
                }
                
                fetch(`api_siadoc.php?action=get_militaires_periode&date_debut=${dateDebut}&date_fin=${dateFin}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('periodeResult');
                        if (data.error) {
                            resultDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        } else {
                            militairesData = data.militaires || [];
                            afficherListeMilitaires(resultDiv, data.militaires || [], 'période');
                        }
                        resultDiv.style.display = 'block';
                    })
                    .catch(error => {
                        document.getElementById('periodeResult').innerHTML = 
                            `<div class="alert alert-danger">Erreur: ${error.message}</div>`;
                        document.getElementById('periodeResult').style.display = 'block';
                    });
            }
            
            function getTousMilitaires() {
                if (confirm('Ceci peut prendre du temps. Voulez-vous continuer?')) {
                    fetch(`api_siadoc.php?action=get_tous_militaires`)
                        .then(response => response.json())
                        .then(data => {
                            const resultDiv = document.getElementById('tousMilitairesResult');
                            if (data.error) {
                                resultDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            } else {
                                militairesData = data.militaires || [];
                                afficherListeMilitaires(resultDiv, data.militaires || [], 'tous');
                            }
                            resultDiv.style.display = 'block';
                        })
                        .catch(error => {
                            document.getElementById('tousMilitairesResult').innerHTML = 
                                `<div class="alert alert-danger">Erreur: ${error.message}</div>`;
                            document.getElementById('tousMilitairesResult').style.display = 'block';
                        });
                }
            }
            
            function afficherListeMilitaires(resultDiv, militaires, type) {
                let html = `<h5>✅ ${militaires.length} militaire(s) trouvé(s)</h5>`;
                html += '<div class="table-responsive"><table class="table table-striped">';
                html += '<thead><tr><th><input type="checkbox" id="selectAll" onchange="toggleAllMilitaires()"></th><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Grade</th><th>Corps</th></tr></thead>';
                html += '<tbody>';
                
                militaires.forEach(militaire => {
                    html += `<tr>
                        <td><input type="checkbox" class="militaire-checkbox" value="${militaire.matricule}" onchange="updateSelectedMilitaires()"></td>
                        <td>${militaire.matricule}</td>
                        <td>${militaire.nom}</td>
                        <td>${militaire.prenom}</td>
                        <td>${militaire.grade}</td>
                        <td>${militaire.corps}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                html += `
                    <div class="mt-3">
                        <small class="text-info">
                            <strong>${selectedMilitaires.length}</strong> militaire(s) sélectionné(s)
                        </small>
                    </div>
                `;
                
                resultDiv.innerHTML = html;
            }
            
            function toggleAllMilitaires() {
                const selectAll = document.getElementById('selectAll');
                const checkboxes = document.querySelectorAll('.militaire-checkbox');
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                
                updateSelectedMilitaires();
            }
            
            function updateSelectedMilitaires() {
                const checkboxes = document.querySelectorAll('.militaire-checkbox:checked');
                selectedMilitaires = Array.from(checkboxes).map(cb => cb.value);
                
                // Mettre à jour le compteur
                const counters = document.querySelectorAll('.text-info strong');
                counters.forEach(counter => {
                    counter.textContent = selectedMilitaires.length;
                });
            }
            
            function genererMatriculesCIMIS() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire');
                    return;
                }
                
                if (confirm(`Générer les matricules CIMIS pour ${selectedMilitaires.length} militaire(s)?`)) {
                    fetch(`api_siadoc.php?action=generer_matricules_cimis`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'génération des matricules CIMIS');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'génération des matricules CIMIS');
                    });
                }
            }
            
            function genererQRCodes() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire');
                    return;
                }
                
                if (confirm(`Générer les QR codes pour ${selectedMilitaires.length} militaire(s)?`)) {
                    fetch(`api_siadoc.php?action=generer_qr_codes`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'génération des QR codes');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'génération des QR codes');
                    });
                }
            }
            
            function enregistrerEnBase() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire');
                    return;
                }
                
                if (confirm(`Enregistrer en base ${selectedMilitaires.length} militaire(s)?\n\n⚠️  IMPORTANT: Les militaires seront enregistrés AVEC matricules CIMIS générés!`)) {
                    fetch(`api_siadoc.php?action=enregistrer_en_base`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'enregistrement en base');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'enregistrement en base');
                    });
                }
            }
            
            function envoyerBiometrie() {
                if (selectedMilitaires.length === 0) {
                    alert('Veuillez sélectionner au moins un militaire');
                    return;
                }
                
                if (confirm(`Envoyer les données biométriques à SIADOC pour ${selectedMilitaires.length} militaire(s)?`)) {
                    fetch(`api_siadoc.php?action=envoyer_biometrie_massive`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({matricules: selectedMilitaires})
                    })
                    .then(response => response.json())
                    .then(data => {
                        afficherResultatActions(data, 'envoi biométrie à SIADOC');
                    })
                    .catch(error => {
                        afficherResultatActions({error: error.message}, 'envoi biométrie à SIADOC');
                    });
                }
            }
            
            function afficherResultatActions(data, action) {
                const resultDiv = document.getElementById('actionsResult');
                if (data.error) {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Erreur lors de ${action}: ${data.error}</div>`;
                } else {
                    let html = `<div class="alert alert-success">✅ ${action} réussie!</div>`;
                    
                    if (data.resultats) {
                        html += '<div class="mt-3"><h6>Résultats détaillés:</h6>';
                        html += '<div class="table-responsive"><table class="table table-sm">';
                        html += '<thead><tr><th>Matricule</th><th>Matricule CIMIS</th><th>Statut</th><th>QR Code</th></tr></thead>';
                        html += '<tbody>';
                        
                        data.resultats.forEach(resultat => {
                            html += `<tr>
                                <td>${resultat.matricule}</td>
                                <td>${resultat.matricule_cimis || '-'}</td>
                                <td><span class="badge bg-${resultat.statut === 'success' ? 'success' : 'warning'}">${resultat.statut}</span></td>
                                <td>${resultat.qr_code ? '✅' : '-'}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div></div>';
                    }
                    
                    resultDiv.innerHTML = html;
                }
                resultDiv.style.display = 'block';
            }
            
            function getStats() {
                fetch(`api_siadoc.php?action=stats`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('statsResult');
                        if (data.error) {
                            resultDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        } else {
                            resultDiv.innerHTML = `
                                <h6>📊 Statistiques CIMIS</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="text-primary">${data.total_militaires || 0}</h5>
                                                <small>Militaires total</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="text-success">${data.cartes_generees || 0}</h5>
                                                <small>Cartes générées</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="text-info">${data.envois_siadoc || 0}</h5>
                                                <small>Envois SIADOC</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        resultDiv.style.display = 'block';
                    })
                    .catch(error => {
                        document.getElementById('statsResult').innerHTML = 
                            `<div class="alert alert-danger">Erreur: ${error.message}</div>`;
                        document.getElementById('statsResult').style.display = 'block';
                    });
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}

// API endpoints
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get_militaires_periode':
            // Appeler l'API SIADOC pour récupérer les militaires par période
            if (!isset($_GET['date_debut']) || !isset($_GET['date_fin'])) {
                sendErrorResponse('Date de début et de fin obligatoires');
            }
            
            try {
                // Pour l'instant, simuler la réponse (à adapter avec vrai appel SIADOC)
                $militaires = [
                    [
                        'matricule' => 'MAT-2023-001',
                        'nom' => 'ESSOMBA',
                        'prenom' => 'Jean-Pierre',
                        'dateNaissance' => '1985-03-15',
                        'corps' => 'AA',
                        'grade' => 'Sergent',
                        'dateGrade' => '2020-07-01',
                        'sexe' => 'M'
                    ],
                    [
                        'matricule' => 'MAT-2023-002',
                        'nom' => 'DUPONT',
                        'prenom' => 'Marie',
                        'dateNaissance' => '1990-05-20',
                        'corps' => 'GG',
                        'grade' => 'Caporal',
                        'dateGrade' => '2018-01-01',
                        'sexe' => 'F'
                    ]
                ];
                
                sendSuccessResponse([
                    'militaires' => $militaires,
                    'periode' => $_GET['date_debut'] . ' au ' . $_GET['date_fin'],
                    'total' => count($militaires)
                ]);
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'appel API SIADOC: ' . $e->getMessage());
            }
            break;
            
        case 'get_tous_militaires':
            // Appeler l'API SIADOC pour récupérer tous les militaires
            try {
                // Pour l'instant, simuler la réponse (à adapter avec vrai appel SIADOC)
                $militaires = [
                    [
                        'matricule' => 'MAT-2023-001',
                        'nom' => 'ESSOMBA',
                        'prenom' => 'Jean-Pierre',
                        'dateNaissance' => '1985-03-15',
                        'corps' => 'AA',
                        'grade' => 'Sergent',
                        'dateGrade' => '2020-07-01',
                        'sexe' => 'M'
                    ],
                    [
                        'matricule' => 'MAT-2023-002',
                        'nom' => 'DUPONT',
                        'prenom' => 'Marie',
                        'dateNaissance' => '1990-05-20',
                        'corps' => 'GG',
                        'grade' => 'Caporal',
                        'dateGrade' => '2018-01-01',
                        'sexe' => 'F'
                    ],
                    [
                        'matricule' => 'MAT-2023-003',
                        'nom' => 'MARTIN',
                        'prenom' => 'Pierre',
                        'dateNaissance' => '1988-11-10',
                        'corps' => 'TT',
                        'grade' => 'Adjudant',
                        'dateGrade' => '2015-03-15',
                        'sexe' => 'M'
                    ]
                ];
                
                sendSuccessResponse([
                    'militaires' => $militaires,
                    'total' => count($militaires)
                ]);
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'appel API SIADOC: ' . $e->getMessage());
            }
            break;
            
        case 'generer_matricules_cimis':
            // Générer les matricules CIMIS pour les militaires sélectionnés
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
            }
            
            try {
                $resultats = [];
                
                foreach ($input['matricules'] as $matricule) {
                    $matricule_cimis = generateCIMISMatricule();
                    
                    // Vérifier si existe déjà
                    $stmt = $pdo->prepare("SELECT id FROM candidat WHERE matricule_militaire = ?");
                    $stmt->execute([$matricule]);
                    $existing = $stmt->fetch();
                    
                    if (!$existing) {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => $matricule_cimis,
                            'statut' => 'generated'
                        ];
                    } else {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => 'EXISTS',
                            'statut' => 'exists'
                        ];
                    }
                }
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules']),
                    'generated' => count(array_filter($resultats, fn($r) => $r['statut'] === 'generated'))
                ], 'Matricules CIMIS générés');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la génération: ' . $e->getMessage());
            }
            break;
            
        case 'generer_qr_codes':
            // Générer les QR codes pour les militaires sélectionnés
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
            }
            
            try {
                $resultats = [];
                
                foreach ($input['matricules'] as $matricule) {
                    // Récupérer le matricule CIMIS
                    $stmt = $pdo->prepare("SELECT matricule FROM candidat WHERE matricule_militaire = ?");
                    $stmt->execute([$matricule]);
                    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($candidat) {
                        $qr_data = generateQRCode($matricule, $candidat['matricule']);
                        
                        // Mettre à jour le QR code
                        $stmt = $pdo->prepare("UPDATE candidat SET code_qr = ? WHERE matricule_militaire = ?");
                        $stmt->execute([$qr_data['image_path'], $matricule]);
                        
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => $candidat['matricule'],
                            'qr_code' => $qr_data['image_path'],
                            'statut' => 'success'
                        ];
                    } else {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => 'NOT_FOUND',
                            'qr_code' => null,
                            'statut' => 'not_found'
                        ];
                    }
                }
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules']),
                    'success' => count(array_filter($resultats, fn($r) => $r['statut'] === 'success'))
                ], 'QR codes générés');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la génération QR: ' . $e->getMessage());
            }
            break;
            
        case 'enregistrer_en_base':
            // Enregistrer les militaires en base avec matricules CIMIS
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
            }
            
            try {
                $resultats = [];
                
                foreach ($input['matricules'] as $matricule) {
                    // D'abord récupérer les données depuis SIADOC
                    $siadoc_result = callSIADOCAPI('export/militaire/info', [
                        'matricule' => $matricule
                    ]);
                    
                    if ($siadoc_result['http_code'] === 200) {
                        $militaire = $siadoc_result['data'];
                        
                        // Vérifier si existe déjà
                        $stmt = $pdo->prepare("SELECT id FROM candidat WHERE matricule_militaire = ?");
                        $stmt->execute([$matricule]);
                        $existing = $stmt->fetch();
                        
                        if (!$existing) {
                            // Générer matricule CIMIS et QR code
                            $matricule_cimis = generateCIMISMatricule();
                            $qr_data = generateQRCode($matricule, $matricule_cimis);
                            
                            // Insérer dans CIMIS
                            $stmt = $pdo->prepare("
                                INSERT INTO candidat (
                                    matricule, matricule_militaire, nom, prenom, 
                                    date_naissance, sexe, grade, unite, 
                                    code_qr, source_system, date_enrolement, type_personnel
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'MILITAIRE')
                            ");
                            
                            $stmt->execute([
                                $matricule_cimis,
                                $militaire['matricule'],
                                strtoupper($militaire['nom']),
                                ucfirst(strtolower($militaire['prenom'])),
                                $militaire['dateNaissance'],
                                strtoupper($militaire['sexe']) === 'M' ? 'MASCULIN' : 'FEMININ',
                                strtoupper($militaire['grade']),
                                $militaire['corps'],
                                $qr_data['image_path'],
                                'SIADOC'
                            ]);
                            
                            $resultats[] = [
                                'matricule' => $matricule,
                                'matricule_cimis' => $matricule_cimis,
                                'statut' => 'registered'
                            ];
                        } else {
                            $resultats[] = [
                                'matricule' => $matricule,
                                'matricule_cimis' => 'EXISTS',
                                'statut' => 'already_exists'
                            ];
                        }
                    } else {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => 'NOT_FOUND_SIADOC',
                            'statut' => 'not_found'
                        ];
                    }
                }
                
                // Logger l'enregistrement
                $stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, last_sync) 
                    VALUES ('SIADOC_ENREGISTREMENT', NOW())
                ");
                $stmt->execute();
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules']),
                    'registered' => count(array_filter($resultats, fn($r) => $r['statut'] === 'registered'))
                ], 'Enregistrement en base terminé');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'enregistrement: ' . $e->getMessage());
            }
            break;
            
        case 'envoyer_biometrie_massive':
            // Envoyer les biométries à SIADOC pour plusieurs militaires
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['matricules']) || empty($input['matricules'])) {
                sendErrorResponse('Liste de matricules obligatoire');
            }
            
            try {
                $resultats = [];
                
                foreach ($input['matricules'] as $matricule) {
                    // Récupérer les données CIMIS
                    $stmt = $pdo->prepare("
                        SELECT * FROM candidat 
                        WHERE matricule_militaire = ? AND statut_carte = 'ACTIVE'
                    ");
                    $stmt->execute([$matricule]);
                    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($candidat) {
                        // Préparer les données biométriques
                        $photo_base64 = null;
                        if ($candidat['photo'] && file_exists($candidat['photo'])) {
                            $photo_base64 = encodeImageToBase64Raw($candidat['photo']);
                        }
                        
                        $qr_base64 = null;
                        if ($candidat['code_qr'] && file_exists($candidat['code_qr'])) {
                            $qr_base64 = encodeImageToBase64Raw($candidat['code_qr']);
                        }
                        
                        $empreinte_base64 = $candidat['empreinte_data'];
                        
                        $payload = [
                            'matricule' => $candidat['matricule_militaire'],
                            'numeroCIM' => $candidat['matricule'],
                            'photoVisage' => $photo_base64,
                            'photoVisageType' => $photo_base64 ? 'image/jpeg' : null,
                            'empreinteDoigt1' => $empreinte_base64,
                            'empreinteDoigt1Type' => $empreinte_base64 ? 'image/png' : null,
                            'empreinteDoigt2' => null,
                            'empreinteDoigt2Type' => null,
                            'qrCodeImage' => $qr_base64,
                            'qrCodeContenu' => 'https://cimis.cm/verify/' . $candidat['matricule_militaire']
                        ];
                        
                        // Envoyer à SIADOC
                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => SIADOC_API_URL . 'import/cimis/biometrie',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => json_encode($payload),
                            CURLOPT_HTTPHEADER => [
                                'X-API-KEY: ' . SIADOC_API_KEY,
                                'Content-Type: application/json'
                            ],
                            CURLOPT_SSL_VERIFYPEER => true,
                            CURLOPT_TIMEOUT => 30
                        ]);
                        
                        $response = curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => $candidat['matricule'],
                            'http_code' => $http_code,
                            'response' => trim($response),
                            'statut' => $http_code === 200 ? 'sent' : 'error'
                        ];
                    } else {
                        $resultats[] = [
                            'matricule' => $matricule,
                            'matricule_cimis' => 'NOT_FOUND',
                            'http_code' => 404,
                            'response' => 'Carte CIMIS non trouvée',
                            'statut' => 'not_found'
                        ];
                    }
                }
                
                // Logger l'envoi
                $stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, last_sync) 
                    VALUES ('SIADOC_ENVOI_BIOMETRIE_MASSIVE', NOW())
                ");
                $stmt->execute();
                
                sendSuccessResponse([
                    'resultats' => $resultats,
                    'total' => count($input['matricules']),
                    'sent' => count(array_filter($resultats, fn($r) => $r['statut'] === 'sent'))
                ], 'Envoi biométrie massif terminé');
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'envoi: ' . $e->getMessage());
            }
            break;
            // Appeler l'API SIADOC pour récupérer un militaire
            if (!isset($_GET['matricule'])) {
                sendErrorResponse('Matricule obligatoire');
            }
            
            try {
                $result = callSIADOCAPI('export/militaire/info', [
                    'matricule' => $_GET['matricule']
                ]);
                
                if ($result['http_code'] === 200) {
                    sendSuccessResponse($result['data']);
                } else {
                    sendErrorResponse('Militaire non trouvé dans SIADOC', $result['http_code']);
                }
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'appel API SIADOC: ' . $e->getMessage());
            }
            break;
            
        case 'create_cimis_card':
            // Créer une carte CIMIS à partir des données SIADOC
            if (!isset($_GET['matricule'])) {
                sendErrorResponse('Matricule obligatoire');
            }
            
            try {
                // D'abord récupérer les données depuis SIADOC
                $siadoc_result = callSIADOCAPI('export/militaire/info', [
                    'matricule' => $_GET['matricule']
                ]);
                
                if ($siadoc_result['http_code'] !== 200) {
                    sendErrorResponse('Militaire non trouvé dans SIADOC');
                }
                
                $militaire = $siadoc_result['data'];
                
                // Vérifier si existe déjà dans CIMIS
                $stmt = $pdo->prepare("SELECT id FROM candidat WHERE matricule_militaire = ?");
                $stmt->execute([$militaire['matricule']]);
                $existing = $stmt->fetch();
                
                if (!$existing) {
                    // Générer matricule CIMIS et QR code
                    $matricule_cimis = generateCIMISMatricule();
                    $qr_data = generateQRCode($militaire['matricule'], $matricule_cimis);
                    
                    // Insérer dans CIMIS
                    $stmt = $pdo->prepare("
                        INSERT INTO candidat (
                            matricule, matricule_militaire, nom, prenom, 
                            date_naissance, sexe, grade, unite, 
                            code_qr, source_system, date_enrolement, type_personnel
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'MILITAIRE')
                    ");
                    
                    $stmt->execute([
                        $matricule_cimis,
                        $militaire['matricule'],
                        strtoupper($militaire['nom']),
                        ucfirst(strtolower($militaire['prenom'])),
                        $militaire['dateNaissance'],
                        strtoupper($militaire['sexe']) === 'M' ? 'MASCULIN' : 'FEMININ',
                        strtoupper($militaire['grade']),
                        $militaire['corps'],
                        $qr_data['image_path'],
                        'SIADOC'
                    ]);
                    
                    sendSuccessResponse([
                        'matricule_cimis' => $matricule_cimis,
                        'matricule_militaire' => $militaire['matricule'],
                        'qr_code_url' => $qr_data['image_path'],
                        'statut' => 'created'
                    ], 'Carte CIMIS créée avec succès');
                    
                } else {
                    sendErrorResponse('Carte CIMIS existe déjà pour ce militaire');
                }
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la création: ' . $e->getMessage());
            }
            break;
            
        case 'send_biometrie':
            // Envoyer les données biométriques à SIADOC
            if (!isset($_GET['matricule'])) {
                sendErrorResponse('Matricule obligatoire');
            }
            
            try {
                // Récupérer les données CIMIS
                $stmt = $pdo->prepare("
                    SELECT * FROM candidat 
                    WHERE matricule_militaire = ? AND statut_carte = 'ACTIVE'
                ");
                $stmt->execute([$_GET['matricule']]);
                $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$candidat) {
                    sendErrorResponse('Carte CIMIS non trouvée pour ce militaire');
                }
                
                // Préparer les données biométriques
                $photo_base64 = null;
                if ($candidat['photo'] && file_exists($candidat['photo'])) {
                    $photo_base64 = encodeImageToBase64Raw($candidat['photo']);
                }
                
                $qr_base64 = null;
                if ($candidat['code_qr'] && file_exists($candidat['code_qr'])) {
                    $qr_base64 = encodeImageToBase64Raw($candidat['code_qr']);
                }
                
                $empreinte_base64 = $candidat['empreinte_data'];
                
                $payload = [
                    'matricule' => $candidat['matricule_militaire'],
                    'numeroCIM' => $candidat['matricule'],
                    'photoVisage' => $photo_base64,
                    'photoVisageType' => $photo_base64 ? 'image/jpeg' : null,
                    'empreinteDoigt1' => $empreinte_base64,
                    'empreinteDoigt1Type' => $empreinte_base64 ? 'image/png' : null,
                    'empreinteDoigt2' => null,
                    'empreinteDoigt2Type' => null,
                    'qrCodeImage' => $qr_base64,
                    'qrCodeContenu' => 'https://cimis.cm/verify/' . $candidat['matricule_militaire']
                ];
                
                // Envoyer à SIADOC
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => SIADOC_API_URL . 'import/cimis/biometrie',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => [
                        'X-API-KEY: ' . SIADOC_API_KEY,
                        'Content-Type: application/json'
                    ],
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_TIMEOUT => 30
                ]);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                // Logger l'envoi
                $stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, last_sync) 
                    VALUES ('SIADOC_ENVOI_BIOMETRIE', NOW())
                ");
                $stmt->execute();
                
                sendSuccessResponse([
                    'message' => trim($response),
                    'http_code' => $http_code,
                    'matricule' => $candidat['matricule_militaire'],
                    'timestamp' => date('c')
                ]);
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de l\'envoi: ' . $e->getMessage());
            }
            break;
            
        case 'stats':
            // Statistiques CIMIS
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_militaires,
                        COUNT(CASE WHEN statut_carte = 'ACTIVE' THEN 1 END) as cartes_generees,
                        COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc
                    FROM candidat 
                    WHERE type_personnel = 'MILITAIRE'
                ");
                $stmt->execute();
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as envois_siadoc 
                    FROM api_sync_log 
                    WHERE system LIKE 'SIADOC%'
                ");
                $stmt->execute();
                $envois = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendSuccessResponse([
                    'total_militaires' => (int)$stats['total_militaires'],
                    'cartes_generees' => (int)$stats['cartes_generees'],
                    'venus_de_siadoc' => (int)$stats['venus_de_siadoc'],
                    'envois_siadoc' => (int)$envois['envois_siadoc']
                ]);
                
            } catch (Exception $e) {
                sendErrorResponse('Erreur lors de la récupération des stats: ' . $e->getMessage());
            }
            break;
            
        default:
            sendErrorResponse('Action non reconnue');
    }
} else {
    sendErrorResponse('Action non spécifiée');
}
?>
