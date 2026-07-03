<?php
// Fonctions d'authentification et d'autorisation

function authenticateByCredentials($email, $password) {
    $email = trim($email);
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare('SELECT id, nom, prenom, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && verifyPassword($password, $user['password'])) {
            return $user;
        }
    } catch (PDOException $e) {
        return null;
    }
    return null;
}

function loginUserFromRow($userRow) {
    // Stocker uniquement les informations nécessaires en session
    // Régénérer l'ID de session pour prévenir fixation
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => $userRow['id'],
        'nom' => $userRow['nom'],
        'prenom' => $userRow['prenom'],
        'email' => $userRow['email'],
        'role' => $userRow['role']
    ];
    // Journaux d'audit si possible (ne doit pas bloquer l'auth)
    if (file_exists(__DIR__ . '/audit.php')) {
        try { require_once __DIR__ . '/audit.php'; logAudit($userRow['id'], 'login', 'Connexion'); } catch (Throwable $e) {}
    }
}

function logoutUser() {
    $userId = $_SESSION['user']['id'] ?? null;
    unset($_SESSION['user']);
    // Ne pas détruire complètement la session si d'autres données existent,
    // mais régénérer l'id pour sécurité
    session_regenerate_id(true);
    if ($userId && file_exists(__DIR__ . '/audit.php')) {
        try { require_once __DIR__ . '/audit.php'; logAudit($userId, 'logout', 'Déconnexion'); } catch (Throwable $e) {}
    }
}

function isLoggedIn() {
    return !empty($_SESSION['user']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function requireRole($allowedRoles = []) {
    if (!isLoggedIn()) {
        header('Location: login.php?error=' . urlencode('Veuillez vous connecter.'));
        exit;
    }
    $user = currentUser();
    if (!in_array($user['role'], (array) $allowedRoles)) {
        header('Location: index.php?error=' . urlencode("Permission refusée."));
        exit;
    }
}

// Helper pour vérifier si l'utilisateur a au moins un rôle donné
function hasRole($role) {
    if (!isLoggedIn()) return false;
    return ($_SESSION['user']['role'] ?? '') === $role;
}

?>
