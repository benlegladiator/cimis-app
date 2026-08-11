<?php
session_start();

// Vérifier si l'utilisateur est connecté et est SUPER_ADMIN
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['role'] !== 'SUPER_ADMIN') {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
    exit;
}

require_once '../backend/config.php';

// Fonction pour compter toutes les cartes dans la corbeille (vue admin)
function getAdminTrashCount() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE supprimer = 0");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

$count = getAdminTrashCount();

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>
