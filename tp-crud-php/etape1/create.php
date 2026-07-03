<?php
require_once '../config.php';
require_once 'includes/roles.php';
require_once 'includes/validation.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

// Seuls les admin et editor peuvent créer des utilisateurs
requireRole(['admin','editor']);

$errors = [];

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Jeton CSRF invalide.';
    }
    // Validation des données de base
    $validationResult = validateUserFormData($_POST);
    $errors = $validationResult['errors'];
    $userData = $validationResult['data'];
    
    // Validation du mot de passe
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (!validatePassword($password, $passwordConfirm, $errors)) {
        // Les erreurs sont déjà ajoutées par validatePassword
    }

    // Vérifier l'unicité de l'email
    if (empty($errors) && !checkEmailUniqueness($userData['email'])) {
        $errors[] = 'Cet email existe déjà';
    }

    // Si pas d'erreurs, créer l'utilisateur
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
                    // Audit log
                    if (function_exists('currentUser')) {
                        if (file_exists(__DIR__ . '/includes/audit.php')) {
                            require_once __DIR__ . '/includes/audit.php';
                            $actor = currentUser()['id'] ?? null;
                            try { logAudit($actor, 'create_user', 'ID:' . $pdo->lastInsertId()); } catch (Throwable $e) {}
                        }
                    }
            
            // Redirection avec message de succès
            header('Location: index.php?success=Utilisateur créé avec succès');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la création : ' . $e->getMessage();
        }
    }
}

// Configuration de la page
$pageTitle = 'Créer un utilisateur';
$navbarLink = 'index.php';
$navbarButtonClass = 'secondary';
$navbarIcon = 'arrow-left';
$navbarText = 'Retour';
$colClass = 'md-6';

// Inclure l'en-tête
include 'includes/header.php';
?>

                <h2 class="mb-4">Créer un nouvel utilisateur</h2>

                <!-- Afficher les erreurs -->
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

                <!-- Formulaire de création -->
                <form method="POST" class="needs-validation">
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

<?php include 'includes/footer.php'; ?>
