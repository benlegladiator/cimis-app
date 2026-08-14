<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../backend/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Accepter uniquement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

// Récupérer les IDs à supprimer (unique ou multiple)
$raw_ids = $_POST['ids'] ?? $_POST['id'] ?? null;
$ids = [];

if (is_string($raw_ids)) {
    $decoded = json_decode($raw_ids, true);
    if (is_array($decoded)) {
        $ids = array_map('intval', $decoded);
    } else {
        $ids = array_filter(array_map('intval', explode(',', $raw_ids)));
    }
} elseif (is_array($raw_ids)) {
    $ids = array_map('intval', $raw_ids);
} elseif (is_numeric($raw_ids)) {
    $ids = [intval($raw_ids)];
}

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Aucun ID de candidat fourni']);
    exit;
}

try {
    global $pdo;
    $username = $_SESSION['username'] ?? 'SUPER_ADMIN';
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Soft delete (supprimer = 0)
    $sql = "UPDATE candidat SET supprimer = 0, supprimer_par = ?, date_suppression = NOW() WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$username], $ids);
    $result = $stmt->execute($params);
    $affected = $stmt->rowCount();

    error_log("Soft delete réussi pour $affected candidat(s) par $username");

    echo json_encode([
        'success' => true,
        'message' => $affected > 1 ? "$affected cartes déplacées dans la corbeille" : "Carte déplacée dans la corbeille",
        'count' => $affected
    ]);
} catch (Exception $e) {
    error_log("Erreur suppression candidat: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
