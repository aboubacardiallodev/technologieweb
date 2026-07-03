<?php
require_once __DIR__ . '/../../config.php';

function logAudit($userId, $action, $details = null) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        // Ne pas bloquer l'utilisateur en cas d'erreur de log
        error_log('Audit log error: ' . $e->getMessage());
    }
}

?>
