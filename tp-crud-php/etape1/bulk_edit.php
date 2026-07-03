<?php
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

// Seul admin peut modifier en masse
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: index.php?error=' . urlencode('Jeton CSRF invalide.'));
        exit;
    }
    $ids = $_POST['ids'] ?? [];
    $newRole = cleanInput($_POST['new_role'] ?? '');
    if (empty($ids) || empty($newRole)) {
        header('Location: index.php?error=' . urlencode('Sélectionnez des utilisateurs et un rôle.'));
        exit;
    }
    try {
        $pdo = getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE users SET role = ? WHERE id IN ($placeholders)";
        $params = array_merge([$newRole], $ids);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        // Audit log
        if (function_exists('currentUser')) {
            if (file_exists(__DIR__ . '/includes/audit.php')) {
                require_once __DIR__ . '/includes/audit.php';
                $actor = $_SESSION['user']['id'] ?? null;
                try { logAudit($actor, 'bulk_edit', 'IDs:' . implode(',', $ids) . ' => role:' . $newRole); } catch (Throwable $e) {}
            }
        }
        header('Location: index.php?success=' . urlencode('Mise à jour en masse effectuée.'));
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode('Erreur : ' . $e->getMessage()));
        exit;
    }
}

header('Location: index.php');
exit;
