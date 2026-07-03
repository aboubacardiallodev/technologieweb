<?php
require_once 'db.php';
require_once 'panier_utils.php';

$panier = readCart();

$total = 0;
$nbArticles = countCartItems($panier);
$csrfToken = getCsrfToken();

$produits = [];
if (!empty($panier)) {
    $ids = array_keys($panier);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nom, prix, stock FROM produits WHERE id IN ($placeholders)");
    $stmt->execute(array_map('intval', $ids));
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $produits[(int)$row['id']] = $row;
    }

    $cartUpdated = false;
    foreach ($panier as $id => $qty) {
        if (!isset($produits[$id])) {
            unset($panier[$id]);
            $cartUpdated = true;
            continue;
        }
        $stock = (int)$produits[$id]['stock'];
        if ($qty > $stock) {
            if ($stock > 0) {
                $panier[$id] = $stock;
            } else {
                unset($panier[$id]);
            }
            $cartUpdated = true;
        }
    }

    if ($cartUpdated) {
        writeCart($panier);
        $nbArticles = countCartItems($panier);
    }
}

$message = $_GET['m'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon panier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h1 class="mb-0"><i class="bi bi-cart4 me-2"></i>Votre panier</h1>
            <span class="badge text-bg-primary fs-6"><?= $nbArticles ?> article(s)</span>
        </div>

        <?php if ($message === 'added'): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Produit ajouté au panier.</div>
        <?php elseif ($message === 'cleared'): ?>
            <div class="alert alert-warning"><i class="bi bi-trash me-2"></i>Panier vidé.</div>
        <?php elseif ($message === 'out_of_stock'): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Stock insuffisant, quantité ajustée.</div>
        <?php elseif ($message === 'stock_changed'): ?>
            <div class="alert alert-warning"><i class="bi bi-box-seam me-2"></i>Le stock a changé, vérifiez votre panier.</div>
        <?php endif; ?>

        <a href="index.php" class="btn btn-outline-primary mb-4">
            <i class="bi bi-arrow-left me-2"></i>Continuer mes achats
        </a>

        <?php if (empty($panier)): ?>
            <div class="alert alert-secondary text-center py-5">
                <i class="bi bi-cart-x display-4"></i>
                <h3 class="mt-3">Votre panier est vide</h3>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Produit</th>
                            <th class="text-end">Prix unitaire</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end">Sous-total</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($panier as $id => $quantite):
                            $produit = $produits[$id] ?? null;
                            if ($produit === null) {
                                continue;
                            }
                            $prix = (float)$produit['prix'];
                            $stock = (int)$produit['stock'];
                            $sousTotal = $prix * $quantite;
                            $total += $sousTotal;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($produit['nom']) ?></strong><br>
                                <small class="text-muted">Stock restant: <?= $stock ?></small>
                            </td>
                            <td class="text-end"><?= number_format($prix, 2) ?> EUR</td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <form method="post" action="supprimer.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                                        <input type="hidden" name="mode" value="one">
                                        <input type="hidden" name="redirect" value="panier.php">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">-</button>
                                    </form>
                                    <span class="badge bg-primary fs-6"><?= $quantite ?></span>
                                    <form method="post" action="ajouter.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                                        <input type="hidden" name="redirect" value="panier.php">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $quantite >= $stock ? 'disabled' : '' ?>>+</button>
                                    </form>
                                </div>
                            </td>
                            <td class="text-end fw-bold"><?= number_format($sousTotal, 2) ?> EUR</td>
                            <td class="text-end">
                                <form method="post" action="supprimer.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$id ?>">
                                    <input type="hidden" name="mode" value="all">
                                    <input type="hidden" name="redirect" value="panier.php">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash me-1"></i>Retirer</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-success">
                            <th colspan="3" class="text-end">Total général</th>
                            <th class="text-end fs-5"><?= number_format($total, 2) ?> EUR</th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between gap-2 flex-wrap">
                <form method="post" action="vider.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="redirect" value="panier.php">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-2"></i>Vider le panier
                    </button>
                </form>
                <form method="post" action="confirmation.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-bag-check me-2"></i>Confirmer la commande
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- pour debug, afficher le cookie JSON du panier
        <div class="card mt-4">
            <div class="card-body">
                <h5>Cookie brut (debug)</h5>
                <code class="small"><?= htmlspecialchars($_COOKIE['panier'] ?? '(aucun cookie JSON)') ?></code>
            </div>
        </div>
        -->
    </div>
</body>
</html>