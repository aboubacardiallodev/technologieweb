<?php
require_once '../config.php';
require_once 'includes/roles.php';

$user = null;
$error = null;

// Récupérer l'ID de l'utilisateur
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    $error = 'ID utilisateur invalide';
} else {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Utilisateur non trouvé';
        }
    } catch (PDOException $e) {
        $error = 'Erreur lors de la récupération : ' . $e->getMessage();
    }
}

// Configuration de la page
$pageTitle = 'Détails de l\'utilisateur';
$navbarLink = 'index.php';
$navbarButtonClass = 'secondary';
$navbarIcon = 'arrow-left';
$navbarText = 'Retour';
$colClass = 'md-6';

// Inclure l'en-tête
include 'includes/header.php';
?>

                <?php if ($error): ?>
                    <!-- Message d'erreur -->
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                <?php elseif ($user): ?>
                    <!-- Détails de l'utilisateur -->
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

                    <!-- Actions -->
                    <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <div>
                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Modifier
                            </a>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-url="delete.php?id=<?php echo $user['id']; ?>">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

<?php include 'includes/footer.php'; ?>
