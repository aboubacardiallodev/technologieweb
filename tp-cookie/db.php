<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=tp_cookie;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Backward compatibility: add stock column if schema was created before inventory support.
    $pdo->exec('ALTER TABLE produits ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 0');
} catch(Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>