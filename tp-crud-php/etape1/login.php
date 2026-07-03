<?php
require_once '../config.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

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
        $user = authenticateByCredentials($email, $password);
        if ($user) {
            loginUserFromRow($user);
            header('Location: index.php?success=' . urlencode('Connecté avec succès.'));
            exit;
        } else {
            $error = 'Email ou mot de passe invalide.';
        }
    }
}

$pageTitle = 'Connexion';
$navbarLink = 'index.php';
$navbarText = 'Retour';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Connexion</h5>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
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

<?php include 'includes/footer.php'; ?>
