<?php
require_once '../config.php';
require_once '../etape1/includes/roles.php';
require_once '../etape1/includes/validation.php';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$errors = [];
$dbError = null;
$user = null;
$search = cleanInput($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$users = [];
$totalUsers = 0;
$totalPages = 0;

switch ($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validationResult = validateUserFormData($_POST);
            $errors = $validationResult['errors'];
            $userData = $validationResult['data'];

            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (!validatePassword($password, $passwordConfirm, $errors)) {
                // Les erreurs sont déjà ajoutées par la fonction
            }

            if (empty($errors) && !checkEmailUniqueness($userData['email'])) {
                $errors[] = 'Cet email existe déjà';
            }

            if (empty($errors)) {
                try {
                    $pdo = getConnection();
                    $hashedPassword = hashPassword($password);
                    $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $userData['nom'],
                        $userData['prenom'],
                        $userData['email'],
                        $hashedPassword,
                        $userData['role']
                    ]);

                    header('Location: index.php?success=Utilisateur créé avec succès');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Erreur lors de la création : ' . $e->getMessage();
                }
            }
        }

        $pageTitle = 'Créer un utilisateur';
        $navbarLink = 'index.php';
        $navbarButtonClass = 'secondary';
        $navbarIcon = 'arrow-left';
        $navbarText = 'Retour';
        $colClass = 'md-6';

        include '../etape1/includes/header.php';
        ?>
        <h2 class="mb-4">Créer un nouvel utilisateur</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                <strong>Erreur(s) :</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?action=create" class="needs-validation">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                <small class="text-muted">L'email doit être unique</small>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
                <small class="text-muted">Minimum 6 caractères</small>
            </div>

            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                <select class="form-select" id="role" name="role" required>
                    <?php displayRoleOptions($_POST['role'] ?? 'guest'); ?>
                </select>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Annuler
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Créer
                </button>
            </div>
        </form>
        <?php
        include '../etape1/includes/footer.php';
        break;

    case 'edit':
        if ($id > 0) {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare("SELECT id, nom, prenom, email, role FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch();

                if (!$user) {
                    $errors[] = 'Utilisateur non trouvé';
                }
            } catch (PDOException $e) {
                $errors[] = 'Erreur lors de la récupération : ' . $e->getMessage();
            }
        } else {
            $errors[] = 'ID utilisateur invalide';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
            $validationResult = validateUserFormData($_POST);
            $errors = $validationResult['errors'];
            $userData = $validationResult['data'];

            if (empty($errors) && !checkEmailUniqueness($userData['email'], $user['id'])) {
                $errors[] = 'Cet email est déjà utilisé';
            }

            if (empty($errors)) {
                try {
                    $pdo = getConnection();
                    $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([
                        $userData['nom'],
                        $userData['prenom'],
                        $userData['email'],
                        $userData['role'],
                        $user['id']
                    ]);

                    header('Location: index.php?success=Utilisateur modifié avec succès');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
                }
            }
        }

        $pageTitle = 'Modifier l\'utilisateur';
        $navbarLink = 'index.php';
        $navbarButtonClass = 'secondary';
        $navbarIcon = 'arrow-left';
        $navbarText = 'Retour';
        $colClass = 'md-6';

        include '../etape1/includes/header.php';
        ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                <strong>Erreur(s) :</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($user && empty($errors)): ?>
            <h2 class="mb-4">Modifier l'utilisateur</h2>

            <form method="POST" action="index.php?action=edit&id=<?php echo $user['id']; ?>" class="needs-validation">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <?php displayRoleOptions($user['role']); ?>
                    </select>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle"></i> Modifier
                    </button>
                </div>
            </form>
        <?php elseif ($user === null && !empty($errors)): ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        <?php endif; ?>
        <?php
        include '../etape1/includes/footer.php';
        break;

    case 'view':
        if ($id > 0) {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare("SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                $errors[] = 'Erreur lors de la récupération : ' . $e->getMessage();
            }
        } else {
            $errors[] = 'ID utilisateur invalide';
        }

        $pageTitle = 'Détails de l\'utilisateur';
        $navbarLink = 'index.php';
        $navbarButtonClass = 'secondary';
        $navbarIcon = 'arrow-left';
        $navbarText = 'Retour';
        $colClass = 'md-6';

        include '../etape1/includes/header.php';
        ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($errors[0]); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        <?php elseif ($user): ?>
            <h2 class="mb-4">Détails de l'utilisateur</h2>

            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">ID</label>
                        <p class="h5"><?php echo htmlspecialchars($user['id']); ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Prénom</label>
                                <p class="h5"><?php echo htmlspecialchars($user['prenom']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Nom</label>
                                <p class="h5"><?php echo htmlspecialchars($user['nom']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <p class="h5"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Rôle</label>
                        <p><?php echo getRoleBadgeHTML($user['role']); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Créé le</label>
                        <p><?php echo date('d/m/Y H:i:s', strtotime($user['created_at'])); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Mis à jour le</label>
                        <p><?php echo date('d/m/Y H:i:s', strtotime($user['updated_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-between">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <div>
                    <a href="index.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-url="index.php?action=delete&id=<?php echo $user['id']; ?>">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        <?php endif; ?>
        <?php
        include '../etape1/includes/footer.php';
        break;

    case 'delete':
        if ($id <= 0) {
            header('Location: index.php?error=' . urlencode('ID utilisateur invalide'));
            exit;
        }

        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if (!$user) {
                header('Location: index.php?error=' . urlencode('Utilisateur non trouvé'));
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: index.php?success=' . urlencode('Utilisateur supprimé avec succès'));
            exit;
        } catch (PDOException $e) {
            header('Location: index.php?error=' . urlencode('Erreur lors de la suppression : ' . $e->getMessage()));
            exit;
        }
        break;

    case 'list':
    default:
        try {
            $pdo = getConnection();
            $countQuery = "SELECT COUNT(*) as total FROM users";
            $countParams = [];
            $query = "SELECT id, nom, prenom, email, role, created_at FROM users";
            $params = [];

            if (!empty($search)) {
                $countQuery .= " WHERE email LIKE ?";
                $query .= " WHERE email LIKE ?";
                $searchTerm = '%' . $search . '%';
                $countParams[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($countParams);
            $totalUsers = $countStmt->fetch()['total'];
            $totalPages = ceil($totalUsers / $perPage);

            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
                $offset = ($page - 1) * $perPage;
            }

            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
        } catch (PDOException $e) {
            $dbError = $e->getMessage();
        }

        $pageTitle = 'TP CRUD PHP/MySQL - Étape 2';
        $navbarLink = 'index.php?action=create';
        $navbarButtonClass = 'success';
        $navbarIcon = 'plus-circle';
        $navbarText = 'Ajouter un utilisateur';
        $colClass = '12';

        include '../etape1/includes/header.php';
        ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($dbError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <strong>Erreur Base de Données:</strong> <?php echo htmlspecialchars($dbError); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4 align-items-end">
            <div class="col-md-8">
                <form method="GET" action="index.php" class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Rechercher par email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <div class="badge bg-primary p-3">
                    <i class="bi bi-people"></i> <?php echo $totalUsers; ?> utilisateur<?php echo $totalUsers > 1 ? 's' : ''; ?>
                </div>
            </div>
        </div>

        <?php if ($totalUsers > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" style="width: 5%;">ID</th>
                            <th scope="col" style="width: 25%;">Nom Prénom</th>
                            <th scope="col" style="width: 30%;">Email</th>
                            <th scope="col" style="width: 15%;">Rôle</th>
                            <th scope="col" style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo getRoleBadgeHTML($user['role']); ?></td>
                                <td>
                                    <a href="index.php?action=view&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="index.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-url="index.php?action=delete&id=<?php echo $user['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="bi bi-chevron-double-left"></i>
                            </a>
                        </li>

                        <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="bi bi-chevron-left"></i> Précédent
                            </a>
                        </li>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>

                        <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                Suivant <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>

                        <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="bi bi-chevron-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <div>
                    <?php echo !empty($search) ? 'Aucun utilisateur trouvé avec cette recherche.' : 'Aucun utilisateur trouvé. '; ?>
                    <a href="index.php?action=create" class="alert-link">Créer le premier utilisateur</a>
                </div>
            </div>
        <?php endif; ?>
        <?php
        include '../etape1/includes/footer.php';
        break;
}
