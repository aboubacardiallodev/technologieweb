<?php
require_once 'panier_utils.php';

requirePostWithCsrf();

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: panier.php');
    exit;
}

$id = (int)$_POST['id'];
$mode = $_POST['mode'] ?? 'all';
$redirect = getAllowedRedirect($_POST['redirect'] ?? null, 'panier.php');

$panier = readCart();

if (isset($panier[$id])) {
    if ($mode === 'one' && (int)$panier[$id] > 1) {
        $panier[$id]--;
    } else {
        unset($panier[$id]);
    }

    writeCart($panier);
}

header('Location: ' . $redirect);
exit;