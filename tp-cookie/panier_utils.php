<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const PANIER_COOKIE = 'panier';
const PANIER_TTL = 2592000;

function getAllowedRedirect(?string $redirect, string $default = 'index.php'): string
{
    $allowed = ['index.php', 'panier.php'];
    if ($redirect !== null && in_array($redirect, $allowed, true)) {
        return $redirect;
    }
    return $default;
}

function redirectWithMessage(string $target, string $message): never
{
    $sep = str_contains($target, '?') ? '&' : '?';
    header('Location: ' . $target . $sep . 'm=' . urlencode($message));
    exit;
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function requirePostWithCsrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo 'Méthode non autorisée';
        exit;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(getCsrfToken(), $token)) {
        http_response_code(403);
        echo 'Requête invalide (CSRF).';
        exit;
    }
}

function readCart(): array
{
    if (!isset($_COOKIE[PANIER_COOKIE]) || !is_string($_COOKIE[PANIER_COOKIE])) {
        return [];
    }

    $raw = $_COOKIE[PANIER_COOKIE];
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return normalizeCart($decoded);
    }

    $legacy = @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($legacy)) {
        return [];
    }

    $migrated = [];
    foreach ($legacy as $id => $item) {
        $intId = (int)$id;
        if ($intId <= 0 || !is_array($item)) {
            continue;
        }
        $qte = isset($item['quantite']) ? (int)$item['quantite'] : 0;
        if ($qte > 0) {
            $migrated[$intId] = $qte;
        }
    }

    if (!empty($migrated)) {
        writeCart($migrated);
    }

    return $migrated;
}

function normalizeCart(array $cart): array
{
    $clean = [];
    foreach ($cart as $id => $qte) {
        $intId = (int)$id;
        $intQte = (int)$qte;
        if ($intId > 0 && $intQte > 0) {
            $clean[$intId] = $intQte;
        }
    }
    return $clean;
}

function writeCart(array $cart, int $ttl = PANIER_TTL): void
{
    $normalized = normalizeCart($cart);
    if (empty($normalized)) {
        clearCart();
        return;
    }

    $json = json_encode($normalized, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return;
    }

    setcookie(PANIER_COOKIE, $json, time() + $ttl, '/', '', false, true);
}

function clearCart(): void
{
    setcookie(PANIER_COOKIE, '', time() - 3600, '/', '', false, true);
    unset($_COOKIE[PANIER_COOKIE]);
}

function countCartItems(array $cart): int
{
    return array_sum(array_map('intval', $cart));
}
