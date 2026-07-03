<?php
require_once 'panier_utils.php';

requirePostWithCsrf();

$redirect = getAllowedRedirect($_POST['redirect'] ?? null, 'index.php');

if (isset($_POST['demo_expire'])) {
    $panier = readCart();
    if (!empty($panier)) {
        writeCart($panier, 30);
        redirectWithMessage('index.php', 'expire30');
    }
    header('Location: index.php');
    exit;
}

clearCart();
redirectWithMessage($redirect, 'cleared');