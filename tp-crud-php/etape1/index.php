<?php
require_once '../config.php';
require_once 'includes/roles.php';

// Configuration de la page
$pageTitle = 'TP CRUD PHP/MySQL - Étape 1';
$navbarLink = 'create.php';
$navbarButtonClass = 'success';
$navbarIcon = 'plus-circle';
$navbarText = 'Ajouter un utilisateur';
$colClass = '12';

// Paramètres de pagination et recherche
$search = cleanInput($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$users = [];
$totalUsers = 0;
$totalPages = 0;
$dbError = null;

try {
    $pdo = getConnection();
    
    // Requête pour le compteur total
    $countQuery = "SELECT COUNT(*) as total FROM users";
    $countParams = [];
    
    // Requête pour les données
    $query = "SELECT id, nom, prenom, email, role, created_at FROM users";
    $params = [];
    
    // Ajouter le filtre de recherche si présent
    if (!empty($search)) {
        $countQuery .= " WHERE email LIKE ?";
        $query .= " WHERE email LIKE ?";
        $searchTerm = '%' . $search . '%';
        $countParams[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Obtenir le nombre total
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalUsers = $countStmt->fetch()['total'];
    $totalPages = ceil($totalUsers / $perPage);
    
    // Limiter la page à la dernière valide
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }
    
    // Exécuter la requête avec pagination
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

// Inclure l'en-tête
include 'includes/header.php';
?>

                <!-- Messages flash -->
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

                <!-- Barre de recherche et compteur -->
                <div class="row mb-4 align-items-end">
                    <div class="col-md-8">
                        <form method="GET" class="input-group">
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
                    <!-- Tableau Bootstrap -->
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
                                            <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-url="delete.php?id=<?php echo $user['id']; ?>" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Première page -->
                                <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>

                                <!-- Page précédente -->
                                <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="bi bi-chevron-left"></i> Précédent
                                    </a>
                                </li>

                                <!-- Numéros de page -->
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

                                <!-- Page suivante -->
                                <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        Suivant <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>

                                <!-- Dernière page -->
                                <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Message si aucun utilisateur -->
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            <?php echo !empty($search) ? 'Aucun utilisateur trouvé avec cette recherche.' : 'Aucun utilisateur trouvé. '; ?>
                            <a href="create.php" class="alert-link">Créer le premier utilisateur</a>
                        </div>
                    </div>
                <?php endif; ?>

<?php include 'includes/footer.php'; ?>
