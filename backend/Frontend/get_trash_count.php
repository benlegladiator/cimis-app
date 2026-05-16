<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
    exit;
}

require_once '../backend/config.php';

// Fonction pour compter les cartes dans la corbeille de l'utilisateur
function getTrashCount($username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE supprimer = 0 AND supprimer_par = :username");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

$username = $_SESSION['username'] ?? '';
$count = getTrashCount($username);

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>
