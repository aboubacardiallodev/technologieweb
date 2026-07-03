<?php
require_once '../config.php';
require_once 'includes/roles.php';
require_once 'includes/validation.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

// Admin et editor peuvent modifier
requireRole(['admin','editor']);

$user = null;
$errors = [];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Récupérer l'utilisateur existant
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

// Traiter le formulaire de modification
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Jeton CSRF invalide.';
            }
    // Validation des données
    $validationResult = validateUserFormData($_POST);
    $errors = $validationResult['errors'];
    $userData = $validationResult['data'];

    // Vérifier l'unicité de l'email (sauf pour l'utilisateur courant)
    if (empty($errors) && !checkEmailUniqueness($userData['email'], $user['id'])) {
        $errors[] = 'Cet email est déjà utilisé';
    }

    // Si pas d'erreurs, mettre à jour l'utilisateur
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
                    // Audit log
                    if (function_exists('currentUser')) {
                        if (file_exists(__DIR__ . '/includes/audit.php')) {
                            require_once __DIR__ . '/includes/audit.php';
                            $actor = currentUser()['id'] ?? null;
                            try { logAudit($actor, 'edit_user', 'ID:' . $user['id']); } catch (Throwable $e) {}
                        }
                    }
            
            // Redirection avec message de succès
            header('Location: index.php?success=Utilisateur modifié avec succès');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
        }
    }
}

// Configuration de la page
$pageTitle = 'Modifier l\'utilisateur';
$navbarLink = 'index.php';
$navbarButtonClass = 'secondary';
$navbarIcon = 'arrow-left';
$navbarText = 'Retour';
$colClass = 'md-6';

// Inclure l'en-tête
include 'includes/header.php';
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

                    <form method="POST" class="needs-validation">
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

<?php include 'includes/footer.php'; ?>
