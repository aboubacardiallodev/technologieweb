<?php
require_once 'db.php';
require_once 'panier_utils.php';

requirePostWithCsrf();

$panier = readCart();
if (empty($panier)) {
    header('Location: panier.php');
    exit;
}

$ids = array_keys($panier);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, nom, prix, stock FROM produits WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute(array_map('intval', $ids));
    $rows = $stmt->fetchAll();

    $produits = [];
    foreach ($rows as $row) {
        $produits[(int)$row['id']] = $row;
    }

    $lignes = [];
    $total = 0.0;

    foreach ($panier as $id => $qte) {
        if (!isset($produits[$id])) {
            $pdo->rollBack();
            redirectWithMessage('panier.php', 'stock_changed');
        }

        $stock = (int)$produits[$id]['stock'];
        if ($stock < $qte) {
            $pdo->rollBack();
            redirectWithMessage('panier.php', 'stock_changed');
        }

        $prix = (float)$produits[$id]['prix'];
        $sousTotal = $prix * (int)$qte;
        $total += $sousTotal;

        $lignes[] = [
            'id' => (int)$id,
            'nom' => $produits[$id]['nom'],
            'prix' => $prix,
            'qte' => (int)$qte,
            'sous_total' => $sousTotal,
        ];

        $update = $pdo->prepare('UPDATE produits SET stock = stock - :qte WHERE id = :id');
        $update->execute([
            'qte' => (int)$qte,
            'id' => (int)$id,
        ]);
    }

    $pdo->commit();
    clearCart();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo 'Erreur lors de la confirmation de la commande.';
    exit;
}

$commandeNumero = 'CMD-' . date('Ymd-His') . '-' . random_int(1000, 9999);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmation de commande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="alert alert-success">
            <h1 class="h3 mb-2"><i class="bi bi-bag-check me-2"></i>Commande confirmée</h1>
            <p class="mb-0">Merci ! Votre commande a bien été enregistrée.</p>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <p class="mb-1"><strong>Numéro:</strong> <?= htmlspecialchars($commandeNumero) ?></p>
                <p class="mb-1"><strong>Date:</strong> <?= date('d/m/Y H:i:s') ?></p>
                <p class="mb-0"><strong>Total:</strong> <?= number_format($total, 2) ?> EUR</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Produit</th>
                        <th class="text-end">Prix unitaire</th>
                        <th class="text-center">Quantité</th>
                        <th class="text-end">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?= htmlspecialchars($ligne['nom']) ?></td>
                        <td class="text-end"><?= number_format($ligne['prix'], 2) ?> EUR</td>
                        <td class="text-center"><?= $ligne['qte'] ?></td>
                        <td class="text-end fw-bold"><?= number_format($ligne['sous_total'], 2) ?> EUR</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="index.php?m=order_ok" class="btn btn-primary"><i class="bi bi-shop me-2"></i>Retour au magasin</a>
            <a href="panier.php" class="btn btn-outline-secondary"><i class="bi bi-cart4 me-2"></i>Voir le panier</a>
        </div>
    </div>
</body>
</html>
