<?php
require_once 'db.php';
require_once 'panier_utils.php';

requirePostWithCsrf();

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_POST['id'];
$redirect = getAllowedRedirect($_POST['redirect'] ?? null, 'panier.php');

$stmt = $pdo->prepare('SELECT id, nom, prix, stock FROM produits WHERE id = :id');
$stmt->execute(['id' => $id]);
$produit = $stmt->fetch();

if (!$produit) {
    redirectWithMessage('index.php', 'invalid_product');
}

$panier = readCart();
$quantiteActuelle = $panier[$id] ?? 0;
$stock = (int)$produit['stock'];

if ($stock <= $quantiteActuelle) {
    redirectWithMessage($redirect, 'out_of_stock');
}

$panier[$id] = $quantiteActuelle + 1;
writeCart($panier);
redirectWithMessage($redirect, 'added');