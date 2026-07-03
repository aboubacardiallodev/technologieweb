<?php
require_once '../config.php';
require_once 'includes/roles.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

// La liste des utilisateurs est réservée aux comptes connectés.
requireRole(['admin', 'editor', 'author', 'guest']);

// Configuration de la page
$pageTitle = 'TP CRUD PHP/MySQL - Étape 1';
$navbarLink = 'create.php';
$navbarButtonClass = 'success';
$navbarIcon = 'plus-circle';
$navbarText = 'Ajouter un utilisateur';
$colClass = '12';

// Paramètres de pagination et recherche
$search = cleanInput($_GET['search'] ?? '');
$filterRole = cleanInput($_GET['role'] ?? '');
$dateFrom = cleanInput($_GET['date_from'] ?? '');
$dateTo = cleanInput($_GET['date_to'] ?? '');
$sortBy = cleanInput($_GET['sort_by'] ?? 'created_at');
$sortDir = strtoupper(cleanInput($_GET['sort_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$showNavbarAction = hasRole('admin') || hasRole('editor');

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
    
    // Validation du tri
    $allowedSort = ['id','nom','prenom','email','role','created_at'];
    if (!in_array($sortBy, $allowedSort)) $sortBy = 'created_at';

    // Exécuter la requête avec pagination et tri
    $query .= " ORDER BY " . $sortBy . " " . $sortDir . " LIMIT ? OFFSET ?";
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
                        <form method="GET" class="row g-2">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="search" placeholder="Rechercher par email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="role" class="form-select">
                                    <option value="">Tous les rôles</option>
                                    <?php foreach (['guest','author','editor','admin'] as $r): ?>
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
                                <a href="export.php?<?php echo http_build_query(['search'=>$search,'role'=>$filterRole,'date_from'=>$dateFrom,'date_to'=>$dateTo,'sort_by'=>$sortBy,'sort_dir'=>$sortDir]); ?>" class="btn btn-outline-success ms-2">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                                </a>
                                <a href="export_xlsx.php?<?php echo http_build_query(['search'=>$search,'role'=>$filterRole,'date_from'=>$dateFrom,'date_to'=>$dateTo,'sort_by'=>$sortBy,'sort_dir'=>$sortDir]); ?>" class="btn btn-outline-success ms-2">
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
                    <!-- Tableau Bootstrap -->
                    <div class="table-responsive">
                        <form method="POST" id="bulkForm" action="bulk_edit.php">
                            <?php echo csrfInputField(); ?>
                        <table class="table table-striped table-hover table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col" style="width: 4%;"><input type="checkbox" id="selectAll"></th>
                                    <th scope="col" style="width: 5%;"><a href="?<?php echo http_build_query(array_merge($_GET,['sort_by'=>'id','sort_dir'=> $sortBy==='id' && $sortDir==='ASC' ? 'DESC' : 'ASC'])); ?>">ID</a></th>
                                    <th scope="col" style="width: 25%;"><a href="?<?php echo http_build_query(array_merge($_GET,['sort_by'=>'nom','sort_dir'=> $sortBy==='nom' && $sortDir==='ASC' ? 'DESC' : 'ASC'])); ?>">Nom Prénom</a></th>
                                    <th scope="col" style="width: 30%;"><a href="?<?php echo http_build_query(array_merge($_GET,['sort_by'=>'email','sort_dir'=> $sortBy==='email' && $sortDir==='ASC' ? 'DESC' : 'ASC'])); ?>">Email</a></th>
                                    <th scope="col" style="width: 15%;"><a href="?<?php echo http_build_query(array_merge($_GET,['sort_by'=>'role','sort_dir'=> $sortBy==='role' && $sortDir==='ASC' ? 'DESC' : 'ASC'])); ?>">Rôle</a></th>
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
                                            <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if (isLoggedIn() && (hasRole('admin') || hasRole('editor'))): ?>
                                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (isLoggedIn() && hasRole('admin')): ?>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-url="delete.php?id=<?php echo $user['id']; ?>" title="Supprimer">
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

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Première page -->
                                <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>

                                <!-- Page précédente -->
                                <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
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
                                        <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>

                                <!-- Page suivante -->
                                <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        Suivant <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>

                                <!-- Dernière page -->
                                <li class="page-item <?php echo $page === $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="index.php?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">
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
