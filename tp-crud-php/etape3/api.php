<?php
require_once '../config.php';
require_once '../etape1/includes/validation.php';

function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) {
        return [];
    }

    $decoded = json_decode($rawInput, true);
    return is_array($decoded) ? $decoded : [];
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $pdo = getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'view') {
            if ($id <= 0) {
                sendJson(['success' => false, 'message' => 'ID utilisateur invalide'], 400);
            }

            $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if (!$user) {
                sendJson(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
            }

            sendJson(['success' => true, 'data' => $user]);
        }

        $stmt = $pdo->query('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users ORDER BY created_at DESC');
        $users = $stmt->fetchAll();
        sendJson(['success' => true, 'data' => $users]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = getJsonInput();
        $validationResult = validateUserFormData($data);
        $errors = $validationResult['errors'];
        $userData = $validationResult['data'];

        $password = trim((string) ($data['password'] ?? ''));
        $passwordConfirm = trim((string) ($data['password_confirm'] ?? ''));

        if (!validatePassword($password, $passwordConfirm, $errors)) {
            // Les erreurs sont déjà ajoutées par la fonction
        }

        if (empty($errors) && !checkEmailUniqueness($userData['email'])) {
            $errors[] = 'Cet email existe déjà';
        }

        if (!empty($errors)) {
            sendJson(['success' => false, 'message' => 'Validation invalide', 'errors' => $errors], 422);
        }

        $hashedPassword = hashPassword($password);
        $stmt = $pdo->prepare('INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $userData['nom'],
            $userData['prenom'],
            $userData['email'],
            $hashedPassword,
            $userData['role']
        ]);

        $newId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?');
        $stmt->execute([$newId]);
        $createdUser = $stmt->fetch();

        sendJson(['success' => true, 'message' => 'Utilisateur créé avec succès', 'data' => $createdUser], 201);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if ($action !== 'edit' || $id <= 0) {
            sendJson(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        $data = getJsonInput();
        $validationResult = validateUserFormData($data);
        $errors = $validationResult['errors'];
        $userData = $validationResult['data'];

        if (empty($errors) && !checkEmailUniqueness($userData['email'], $id)) {
            $errors[] = 'Cet email est déjà utilisé';
        }

        if (!empty($errors)) {
            sendJson(['success' => false, 'message' => 'Validation invalide', 'errors' => $errors], 422);
        }

        $stmt = $pdo->prepare('UPDATE users SET nom = ?, prenom = ?, email = ?, role = ? WHERE id = ?');
        $stmt->execute([
            $userData['nom'],
            $userData['prenom'],
            $userData['email'],
            $userData['role'],
            $id
        ]);

        $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $updatedUser = $stmt->fetch();

        sendJson(['success' => true, 'message' => 'Utilisateur modifié avec succès', 'data' => $updatedUser]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if ($action !== 'delete' || $id <= 0) {
            sendJson(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);

        sendJson(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
    }

    sendJson(['success' => false, 'message' => 'Méthode non supportée'], 405);
} catch (PDOException $e) {
    sendJson(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()], 500);
}

