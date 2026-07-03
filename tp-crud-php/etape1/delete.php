<?php
require_once '../config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error = null;
$success = false;

if ($id <= 0) {
    $error = 'ID utilisateur invalide';
} else {
    // Vérifier que l'utilisateur existe
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Utilisateur non trouvé';
        } else {
            // Supprimer l'utilisateur
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
        }
    } catch (PDOException $e) {
        $error = 'Erreur lors de la suppression : ' . $e->getMessage();
    }
}

// Redirection automatique
if ($success) {
    header('Location: index.php?success=Utilisateur supprimé avec succès');
    exit;
} elseif ($error) {
    header('Location: index.php?error=' . urlencode($error));
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>
