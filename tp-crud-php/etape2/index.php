<?php
require_once '../config.php';
require_once '../etape1/includes/roles.php';
require_once '../etape1/includes/validation.php';
require_once '../etape1/includes/auth.php';
require_once '../etape1/includes/csrf.php';

$action = cleanInput($_GET['action'] ?? 'list');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$errors = [];
$dbError = null;
$user = null;

$search = cleanInput($_GET['search'] ?? '');
$filterRole = cleanInput($_GET['role'] ?? '');
$dateFrom = cleanInput($_GET['date_from'] ?? '');
$dateTo = cleanInput($_GET['date_to'] ?? '');
$sortBy = cleanInput($_GET['sort_by'] ?? 'created_at');
$sortDir = strtoupper(cleanInput($_GET['sort_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$users = [];
$totalUsers = 0;
$totalPages = 0;

switch ($action) {
    case 'login':
        $currentUser = currentUser();
        if (!empty($currentUser)) {
            header('Location: index.php?success=' . urlencode('Vous êtes déjà connecté.'));
            exit;
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $error = 'Jeton CSRF invalide.';
            } else {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $authUser = authenticateByCredentials($email, $password);
                if ($authUser) {
                    loginUserFromRow($authUser);
                    header('Location: index.php?success=' . urlencode('Connecté avec succès.'));
                    exit;
                }
                $error = 'Email ou mot de passe invalide.';
            }
        }

        $pageTitle = 'Connexion';
        $navbarLink = 'index.php';
        $navbarText = 'Retour';
        $showNavbarAction = false;
        include '../etape1/includes/header.php';
        ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Connexion</h5>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="index.php?action=login">
                            <?php echo csrfInputField(); ?>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Se connecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include '../etape1/includes/footer.php';
        break;

    case 'logout':
        logoutUser();
        header('Location: index.php?action=login&success=' . urlencode('Déconnecté.'));
        exit;

    case 'create':
        requireRole(['admin', 'editor']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Jeton CSRF invalide.';
            }

            $validationResult = validateUserFormData($_POST);
            $errors = array_merge($errors, $validationResult['errors']);
            $userData = $validationResult['data'];

            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            validatePassword($password, $passwordConfirm, $errors);

            if (empty($errors) && !checkEmailUniqueness($userData['email'])) {
                $errors[] = 'Cet email existe déjà';
            }

            if (empty($errors)) {
                try {
                    $pdo = getConnection();
                    $hashedPassword = hashPassword($password);
                    $stmt = $pdo->prepare('INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $userData['nom'],
                        $userData['prenom'],
                        $userData['email'],
                        $hashedPassword,
                        $userData['role']
                    ]);
                    header('Location: index.php?success=' . urlencode('Utilisateur créé avec succès'));
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
            <?php echo csrfInputField(); ?>
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
        requireRole(['admin', 'editor']);

        if ($id > 0) {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role FROM users WHERE id = ?');
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
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Jeton CSRF invalide.';
            }

            $validationResult = validateUserFormData($_POST);
            $errors = array_merge($errors, $validationResult['errors']);
            $userData = $validationResult['data'];

            if (empty($errors) && !checkEmailUniqueness($userData['email'], $user['id'])) {
                $errors[] = 'Cet email est déjà utilisé';
            }

            if (empty($errors)) {
                try {
                    $pdo = getConnection();
                    $stmt = $pdo->prepare('UPDATE users SET nom = ?, prenom = ?, email = ?, role = ? WHERE id = ?');
                    $stmt->execute([
                        $userData['nom'],
                        $userData['prenom'],
                        $userData['email'],
                        $userData['role'],
                        $user['id']
                    ]);
                    header('Location: index.php?success=' . urlencode('Utilisateur modifié avec succès'));
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
                <?php echo csrfInputField(); ?>
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
        requireRole(['admin', 'editor', 'author', 'guest']);

        if ($id > 0) {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?');
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
                    <?php if (hasRole('admin') || hasRole('editor')): ?>
                        <a href="index.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                    <?php endif; ?>
                    <?php if (hasRole('admin')): ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-url="index.php?action=delete&id=<?php echo $user['id']; ?>">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
        include '../etape1/includes/footer.php';
        break;

    case 'delete':
        requireRole(['admin']);

        if ($id <= 0) {
            header('Location: index.php?error=' . urlencode('ID utilisateur invalide'));
            exit;
        }

        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if (!$user) {
                header('Location: index.php?error=' . urlencode('Utilisateur non trouvé'));
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            header('Location: index.php?success=' . urlencode('Utilisateur supprimé avec succès'));
            exit;
        } catch (PDOException $e) {
            header('Location: index.php?error=' . urlencode('Erreur lors de la suppression : ' . $e->getMessage()));
            exit;
        }

    case 'bulk_edit':
        requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: index.php?error=' . urlencode('Jeton CSRF invalide.'));
            exit;
        }

        $ids = $_POST['ids'] ?? [];
        $newRole = cleanInput($_POST['new_role'] ?? '');
        if (empty($ids) || empty($newRole)) {
            header('Location: index.php?error=' . urlencode('Sélectionnez des utilisateurs et un rôle.'));
            exit;
        }

        try {
            $pdo = getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE users SET role = ? WHERE id IN ($placeholders)";
            $params = array_merge([$newRole], $ids);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            header('Location: index.php?success=' . urlencode('Mise à jour en masse effectuée.'));
            exit;
        } catch (PDOException $e) {
            header('Location: index.php?error=' . urlencode('Erreur : ' . $e->getMessage()));
            exit;
        }

    case 'export_csv':
    case 'export_xlsx':
        requireRole(['admin', 'editor']);

        try {
            $pdo = getConnection();
            $query = 'SELECT id, nom, prenom, email, role, created_at FROM users';
            $where = [];
            $params = [];

            if (!empty($search)) {
                $where[] = 'email LIKE ?';
                $params[] = '%' . $search . '%';
            }
            if (!empty($filterRole)) {
                $where[] = 'role = ?';
                $params[] = $filterRole;
            }
            if (!empty($dateFrom)) {
                $where[] = 'created_at >= ?';
                $params[] = $dateFrom . ' 00:00:00';
            }
            if (!empty($dateTo)) {
                $where[] = 'created_at <= ?';
                $params[] = $dateTo . ' 23:59:59';
            }
            if (!empty($where)) {
                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            $allowedSort = ['id', 'nom', 'prenom', 'email', 'role', 'created_at'];
            if (!in_array($sortBy, $allowedSort, true)) {
                $sortBy = 'created_at';
            }
            $query .= " ORDER BY $sortBy $sortDir";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            if ($action === 'export_csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="users_export_etape2.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Créé le']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['id'], $r['nom'], $r['prenom'], $r['email'], $r['role'], $r['created_at']]);
                }
                fclose($out);
                exit;
            }

            if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                header('Location: index.php?action=export_csv&' . http_build_query([
                    'search' => $search,
                    'role' => $filterRole,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'sort_by' => $sortBy,
                    'sort_dir' => $sortDir
                ]));
                exit;
            }

            require_once __DIR__ . '/../../vendor/autoload.php';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray(['ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Créé le'], null, 'A1');
            $rowNum = 2;
            foreach ($rows as $r) {
                $sheet->fromArray([$r['id'], $r['nom'], $r['prenom'], $r['email'], $r['role'], $r['created_at']], null, 'A' . $rowNum);
                $rowNum++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="users_export_etape2.xlsx"');
            $writer->save('php://output');
            exit;
        } catch (PDOException $e) {
            header('Location: index.php?error=' . urlencode('Erreur export : ' . $e->getMessage()));
            exit;
        }

    case 'list':
    default:
        requireRole(['admin', 'editor', 'author', 'guest']);

        try {
            $pdo = getConnection();

            $countQuery = 'SELECT COUNT(*) as total FROM users';
            $countParams = [];

            $query = 'SELECT id, nom, prenom, email, role, created_at FROM users';
            $params = [];
            $where = [];

            if (!empty($search)) {
                $where[] = 'email LIKE ?';
                $searchTerm = '%' . $search . '%';
                $countParams[] = $searchTerm;
                $params[] = $searchTerm;
            }
            if (!empty($filterRole)) {
                $where[] = 'role = ?';
                $countParams[] = $filterRole;
                $params[] = $filterRole;
            }
            if (!empty($dateFrom)) {
                $where[] = 'created_at >= ?';
                $countParams[] = $dateFrom . ' 00:00:00';
                $params[] = $dateFrom . ' 00:00:00';
            }
            if (!empty($dateTo)) {
                $where[] = 'created_at <= ?';
                $countParams[] = $dateTo . ' 23:59:59';
                $params[] = $dateTo . ' 23:59:59';
            }
            if (!empty($where)) {
                $whereSQL = ' WHERE ' . implode(' AND ', $where);
                $countQuery .= $whereSQL;
                $query .= $whereSQL;
            }

            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($countParams);
            $totalUsers = (int) $countStmt->fetch()['total'];
            $totalPages = (int) ceil($totalUsers / $perPage);

            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
                $offset = ($page - 1) * $perPage;
            }

            $allowedSort = ['id', 'nom', 'prenom', 'email', 'role', 'created_at'];
            if (!in_array($sortBy, $allowedSort, true)) {
                $sortBy = 'created_at';
            }

            $query .= " ORDER BY $sortBy $sortDir LIMIT ? OFFSET ?";
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
        $showNavbarAction = hasRole('admin') || hasRole('editor');

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
                <form method="GET" action="index.php" class="row g-2">
                    <input type="hidden" name="action" value="list">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="search" placeholder="Rechercher par email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select">
                            <option value="">Tous les rôles</option>
                            <?php foreach (['guest', 'author', 'editor', 'admin'] as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo $filterRole === $r ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex">
                        <input type="date" name="date_from" class="form-control me-2" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="col-12 mt-2">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary ms-2">Réinitialiser</a>
                        <a href="index.php?action=export_csv&amp;<?php echo http_build_query([
                            'search' => $search,
                            'role' => $filterRole,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'sort_by' => $sortBy,
                            'sort_dir' => $sortDir
                        ]); ?>" class="btn btn-outline-success ms-2">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                        </a>
                        <a href="index.php?action=export_xlsx&amp;<?php echo http_build_query([
                            'search' => $search,
                            'role' => $filterRole,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'sort_by' => $sortBy,
                            'sort_dir' => $sortDir
                        ]); ?>" class="btn btn-outline-success ms-2">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </a>
                    </div>
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
                <form method="POST" action="index.php?action=bulk_edit" id="bulkForm">
                    <?php echo csrfInputField(); ?>
                    <table class="table table-striped table-hover table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" style="width: 4%;"><input type="checkbox" id="selectAll"></th>
                                <th scope="col" style="width: 5%;"><a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'id', 'sort_dir' => $sortBy === 'id' && $sortDir === 'ASC' ? 'DESC' : 'ASC', 'action' => 'list'])); ?>">ID</a></th>
                                <th scope="col" style="width: 25%;"><a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'nom', 'sort_dir' => $sortBy === 'nom' && $sortDir === 'ASC' ? 'DESC' : 'ASC', 'action' => 'list'])); ?>">Nom Prénom</a></th>
                                <th scope="col" style="width: 30%;"><a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'email', 'sort_dir' => $sortBy === 'email' && $sortDir === 'ASC' ? 'DESC' : 'ASC', 'action' => 'list'])); ?>">Email</a></th>
                                <th scope="col" style="width: 15%;"><a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'role', 'sort_dir' => $sortBy === 'role' && $sortDir === 'ASC' ? 'DESC' : 'ASC', 'action' => 'list'])); ?>">Rôle</a></th>
                                <th scope="col" style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?php echo $user['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo getRoleBadgeHTML($user['role']); ?></td>
                                    <td>
                                        <a href="index.php?action=view&amp;id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasRole('admin') || hasRole('editor')): ?>
                                            <a href="index.php?action=edit&amp;id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasRole('admin')): ?>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-url="index.php?action=delete&amp;id=<?php echo $user['id']; ?>" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (hasRole('admin')): ?>
                        <div class="d-flex align-items-center gap-2 mt-3">
                            <select name="new_role" class="form-select w-auto">
                                <option value="">-- Changer le rôle --</option>
                                <option value="guest">Invité</option>
                                <option value="author">Auteur</option>
                                <option value="editor">Éditeur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirmer la mise à jour en masse ?')">Appliquer</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => 1, 'action' => 'list'])); ?>">
                                <i class="bi bi-chevron-double-left"></i>
                            </a>
                        </li>

                        <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1, 'action' => 'list'])); ?>">
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
                                <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i, 'action' => 'list'])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>

                        <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1, 'action' => 'list'])); ?>">
                                Suivant <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>

                        <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages, 'action' => 'list'])); ?>">
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
                    <?php if (hasRole('admin') || hasRole('editor')): ?>
                        <a href="index.php?action=create" class="alert-link">Créer le premier utilisateur</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
        include '../etape1/includes/footer.php';
        break;
}
