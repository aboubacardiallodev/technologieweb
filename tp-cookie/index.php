<?php
require_once 'db.php';
require_once 'panier_utils.php';

$stmt = $pdo->query("SELECT * FROM produits ORDER BY nom");
$produits = $stmt->fetchAll();

$panier = readCart();
$nbArticles = countCartItems($panier);
$csrfToken = getCsrfToken();

$message = $_GET['m'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Magasin - TP Cookies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4"><i class="bi bi-shop me-2"></i> Magasin en ligne</h1>

        <?php if ($message === 'added'): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Produit ajouté au panier.</div>
        <?php elseif ($message === 'cleared'): ?>
            <div class="alert alert-warning"><i class="bi bi-trash me-2"></i>Le panier a été vidé.</div>
        <?php elseif ($message === 'out_of_stock'): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Stock insuffisant pour ce produit.</div>
        <?php elseif ($message === 'order_ok'): ?>
            <div class="alert alert-success"><i class="bi bi-bag-check me-2"></i>Commande validée avec succès.</div>
        <?php elseif ($message === 'expire30'): ?>
            <div class="alert alert-warning"><i class="bi bi-clock me-2"></i>Démo: le panier expirera dans 30 secondes.</div>
        <?php endif; ?>

        <div class="alert alert-info">
            <i class="bi bi-cookie me-2"></i>
            Ce site utilise des <strong>cookies</strong> pour conserver votre panier même si vous fermez le navigateur !
        </div>

        <div class="text-end mb-3">
            <a href="panier.php" class="btn btn-primary btn-lg">
                <i class="bi bi-cart4 me-2"></i> Voir mon panier (<?= $nbArticles ?>)
            </a>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($produits as $p): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($p['nom']) ?></h5>
                        <p class="card-text mt-auto">
                            <strong class="text-success fs-4"><?= number_format($p['prix'], 2) ?> €</strong>
                        </p>
                        <p class="mb-0 mt-2">
                            <?php if ((int)$p['stock'] > 0): ?>
                                <span class="badge text-bg-success">En stock: <?= (int)$p['stock'] ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">Rupture de stock</span>
                            <?php endif; ?>
                        </p>
                        <form method="post" action="ajouter.php" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="redirect" value="index.php">
                            <button type="submit" class="btn btn-success w-100" <?= (int)$p['stock'] <= 0 ? 'disabled' : '' ?>>
                                <i class="bi bi-cart-plus me-2"></i> Ajouter au panier
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr class="my-5">
        <p class="text-center">
            <form method="post" action="vider.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="redirect" value="index.php">
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Vider le panier</button>
            </form>
            <form method="post" action="vider.php" class="d-inline ms-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="demo_expire" value="1">
                <button type="submit" class="btn btn-outline-warning">
                    <i class="bi bi-clock"></i> Démo expiration (30 s)
                </button>
            </form>
        </p>
    </div>
</body>
</html>