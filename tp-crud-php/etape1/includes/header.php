<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Gestion des Utilisateurs'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar Bootstrap -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-database"></i> Gestion des Utilisateurs
            </span>
            <div>
                <?php if (($showNavbarAction ?? true) === true): ?>
                <a href="<?php echo $navbarLink ?? 'index.php'; ?>" class="btn btn-<?php echo $navbarButtonClass ?? 'secondary'; ?> me-2">
                    <i class="bi bi-<?php echo $navbarIcon ?? 'arrow-left'; ?>"></i> <?php echo $navbarText ?? 'Retour'; ?>
                </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user'])): ?>
                    <span class="text-light me-2">Connecté : <?php echo htmlspecialchars($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']); ?> (<?php echo htmlspecialchars($_SESSION['user']['role']); ?>)</span>
                    <a href="logout.php" class="btn btn-outline-light">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Conteneur principal -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-<?php echo $colClass ?? '12'; ?>">
