<?php
/**
 * API d'insertion des militaires - Backend CIMIS
 * Version anti-bot compatible
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Headers JSON et anti-bot
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Gérer le paramètre anti-bot
if (isset($_GET['i']) && $_GET['i'] == '1') {
    // Marquer comme requête valide
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
}

<?php
/**
 * API d'insertion des militaires - Backend CIMIS (simplifié)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') exit(0);
if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Read POST data
$data = $_POST;

$nom = trim($data['nom'] ?? '');
$prenom = trim($data['prenom'] ?? '');
$date_naissance = trim($data['date_naissance'] ?? '');
$lieu_naissance = trim($data['lieu_naissance'] ?? '');
$sexe = trim($data['sexe'] ?? '');
$telephone = trim($data['telephone_candidat'] ?? '');
$ville_residence = trim($data['ville_residence'] ?? '');
$numero_cni = trim($data['numero_cni'] ?? '');
$date_delivrance_cni = trim($data['date_delivrance_cni'] ?? '');
$unite = trim($data['unite'] ?? '');

// Required for card: name, prenom, date_naissance, sexe, unite, photo
$required = ['nom','prenom','date_naissance','sexe','unite'];
$missing = [];
foreach($required as $f) if (empty($$f)) $missing[] = $f;
if (!empty($missing)) {
    echo json_encode(['success'=>false,'message'=>'Champs obligatoires manquants: '.implode(',',$missing),'missing'=>$missing]);
    exit;
}

// Validate date
$d = DateTime::createFromFormat('Y-m-d',$date_naissance);
if (!$d) { echo json_encode(['success'=>false,'message'=>'Format date invalide']); exit; }

// Handle photo upload
$photo_filename = null;
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['photo'];
    if ($f['size'] > 2*1024*1024) { echo json_encode(['success'=>false,'message'=>'Photo trop grande (max 2MB)']); exit; }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (strpos($mime,'image/') !== 0) { echo json_encode(['success'=>false,'message'=>'Fichier photo invalide']); exit; }
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $photo_dir = __DIR__ . '/../img/photos';
    if (!is_dir($photo_dir)) mkdir($photo_dir, 0755, true);
    $photo_filename = 'photo_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);
    $dest = $photo_dir . '/' . $photo_filename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        echo json_encode(['success'=>false,'message'=>'Impossible d\'enregistrer la photo']); exit;
    }
}

try {
    $conn = db_connect();
    if (!$conn) throw new Exception('Connexion BDD impossible');

    // Duplicate check (nom+prenom+date_naissance) or numero_cni if provided
    $checkSql = 'SELECT id FROM candidat WHERE nom = ? AND prenom = ? AND date_naissance = ? LIMIT 1';
    $stmt = $conn->prepare($checkSql);
    $stmt->execute([$nom,$prenom,$date_naissance]);
    if ($stmt->fetch()) {
        echo json_encode(['success'=>false,'message'=>'Candidat déjà présent']); exit;
    }

    // Build insert - only essential columns for the card
    $cols = ['nom','prenom','date_naissance','lieu_naissance','sexe','telephone_candidat','ville_residence','numero_cni','date_delivrance_cni','unite','photo'];
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $sql = 'INSERT INTO candidat (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
    $params = [$nom,$prenom,$date_naissance,$lieu_naissance,$sexe,$telephone,$ville_residence,$numero_cni,$date_delivrance_cni,$unite,$photo_filename];

    $ins = $conn->prepare($sql);
    if ($ins->execute($params)) {
        $id = $conn->lastInsertId();
        echo json_encode(['success'=>true,'message'=>'Enregistré','data'=>['id'=>$id,'nom'=>$nom,'prenom'=>$prenom,'unite'=>$unite]]);
        exit;
    } else {
        throw new Exception('Insertion échouée');
    }

} catch(Exception $e){
    error_log('insert_militaire error: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erreur serveur']);
    exit;
}

?>
try {
