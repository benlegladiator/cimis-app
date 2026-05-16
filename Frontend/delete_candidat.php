<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../backend/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID du candidat non fourni']);
    exit;
}

$id = $_GET['id'];

try {
    // Récupérer les informations du candidat avant suppression
    $stmt = $pdo->prepare("SELECT matricule, nom, prenom, photo FROM candidat WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$candidat) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Candidat non trouvé']);
        exit;
    }
    
    // Supprimer la photo si elle existe
    if (!empty($candidat['photo']) && file_exists($candidat['photo'])) {
        if (unlink($candidat['photo'])) {
            error_log("Photo supprimée: " . $candidat['photo']);
        } else {
            error_log("Erreur lors de la suppression de la photo: " . $candidat['photo']);
        }
    }
    
    // Suppression soft : mettre à jour les attributs au lieu de supprimer
    $stmt = $pdo->prepare("UPDATE candidat SET supprimer = 0, supprimer_par = :username, date_suppression = NOW() WHERE id = :id");
    $result = $stmt->execute(['id' => $id, 'username' => $_SESSION['username']]);
    
    if ($result) {
        // Journaliser la suppression soft
        error_log("Candidat déplacé dans corbeille: ID=$id, Matricule=" . $candidat['matricule'] . ", Nom=" . $candidat['nom'] . " " . $candidat['prenom'] . ", Par=" . $_SESSION['username']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Carte déplacée dans la corbeille / Card moved to trash',
            'candidat' => $candidat
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement dans la corbeille / Error moving to trash']);
    }
    
} catch (Exception $e) {
    error_log("Erreur suppression candidat: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
