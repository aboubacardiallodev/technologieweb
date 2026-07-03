<?php
require_once '../config.php';
require_once '../etape1/includes/validation.php';
require_once '../etape1/includes/auth.php';
require_once '../etape1/includes/csrf.php';

function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) {
        return [];
    }
    $decoded = json_decode($rawInput, true);
    return is_array($decoded) ? $decoded : [];
}

function currentRoleValue() {
    return $_SESSION['user']['role'] ?? '';
}

function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    return in_array(currentRoleValue(), (array) $roles, true);
}

function requireApiRole($roles) {
    if (!isLoggedIn()) {
        sendJson(['success' => false, 'message' => 'Authentification requise'], 401);
    }
    if (!hasAnyRole($roles)) {
        sendJson(['success' => false, 'message' => 'Permission refusée'], 403);
    }
}

function requestCsrfToken() {
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!empty($header)) {
        return $header;
    }

    $body = getJsonInput();
    return (string) ($body['csrf_token'] ?? '');
}

function ensureMutatingCsrf() {
    $token = requestCsrfToken();
    if (!verifyCsrfToken($token)) {
        sendJson(['success' => false, 'message' => 'Jeton CSRF invalide'], 403);
    }
}

$action = cleanInput($_GET['action'] ?? 'list');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $pdo = getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'view') {
            requireApiRole(['admin', 'editor', 'author', 'guest']);

            if ($id <= 0) {
                sendJson(['success' => false, 'message' => 'ID utilisateur invalide'], 400);
            }

            $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if (!$user) {
                sendJson(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
            }

            sendJson(['success' => true, 'data' => $user]);
        }

        if ($action === 'export_csv' || $action === 'export_xlsx') {
            requireApiRole(['admin', 'editor']);

            $search = cleanInput($_GET['search'] ?? '');
            $filterRole = cleanInput($_GET['role'] ?? '');
            $dateFrom = cleanInput($_GET['date_from'] ?? '');
            $dateTo = cleanInput($_GET['date_to'] ?? '');
            $sortBy = cleanInput($_GET['sort_by'] ?? 'created_at');
            $sortDir = strtoupper(cleanInput($_GET['sort_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

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
                header('Content-Disposition: attachment; filename="users_export_etape3.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Créé le']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['id'], $r['nom'], $r['prenom'], $r['email'], $r['role'], $r['created_at']]);
                }
                fclose($out);
                exit;
            }

            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
            if (!file_exists($autoloadPath)) {
                header('Location: api.php?action=export_csv&' . http_build_query([
                    'search' => $search,
                    'role' => $filterRole,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'sort_by' => $sortBy,
                    'sort_dir' => $sortDir
                ]));
                exit;
            }

            require_once $autoloadPath;
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
            header('Content-Disposition: attachment; filename="users_export_etape3.xlsx"');
            $writer->save('php://output');
            exit;
        }

        requireApiRole(['admin', 'editor', 'author', 'guest']);

        $search = cleanInput($_GET['search'] ?? '');
        $filterRole = cleanInput($_GET['role'] ?? '');
        $dateFrom = cleanInput($_GET['date_from'] ?? '');
        $dateTo = cleanInput($_GET['date_to'] ?? '');
        $sortBy = cleanInput($_GET['sort_by'] ?? 'created_at');
        $sortDir = strtoupper(cleanInput($_GET['sort_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $countQuery = 'SELECT COUNT(*) as total FROM users';
        $query = 'SELECT id, nom, prenom, email, role, created_at, updated_at FROM users';
        $countParams = [];
        $params = [];
        $where = [];

        if (!empty($search)) {
            $where[] = 'email LIKE ?';
            $term = '%' . $search . '%';
            $countParams[] = $term;
            $params[] = $term;
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
            $whereSql = ' WHERE ' . implode(' AND ', $where);
            $countQuery .= $whereSql;
            $query .= $whereSql;
        }

        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetch()['total'];
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
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

        sendJson([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ensureMutatingCsrf();

        if ($action === 'bulk_edit') {
            requireApiRole(['admin']);
            $data = getJsonInput();
            $ids = $data['ids'] ?? [];
            $newRole = cleanInput($data['new_role'] ?? '');

            if (empty($ids) || empty($newRole)) {
                sendJson(['success' => false, 'message' => 'Sélectionnez des utilisateurs et un rôle'], 422);
            }
            $allowedRoles = ['guest', 'author', 'editor', 'admin'];
            if (!in_array($newRole, $allowedRoles, true)) {
                sendJson(['success' => false, 'message' => 'Rôle invalide'], 422);
            }

            $normalizedIds = array_values(array_filter(array_map('intval', $ids), function ($value) {
                return $value > 0;
            }));
            if (empty($normalizedIds)) {
                sendJson(['success' => false, 'message' => 'Aucun utilisateur valide sélectionné'], 422);
            }

            $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
            $sql = "UPDATE users SET role = ? WHERE id IN ($placeholders)";
            $params = array_merge([$newRole], $normalizedIds);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            sendJson(['success' => true, 'message' => 'Mise à jour en masse effectuée']);
        }

        if ($action !== 'create') {
            sendJson(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        requireApiRole(['admin', 'editor']);

        $data = getJsonInput();
        $validationResult = validateUserFormData($data);
        $errors = $validationResult['errors'];
        $userData = $validationResult['data'];

        $password = trim((string) ($data['password'] ?? ''));
        $passwordConfirm = trim((string) ($data['password_confirm'] ?? ''));
        validatePassword($password, $passwordConfirm, $errors);

        if (empty($errors) && !checkEmailUniqueness($userData['email'])) {
            $errors[] = 'Cet email existe déjà';
        }

        if (!empty($errors)) {
            sendJson(['success' => false, 'message' => 'Validation invalide', 'errors' => $errors], 422);
        }

        $hashedPassword = hashPassword($password);
        $stmt = $pdo->prepare('INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $userData['nom'],
            $userData['prenom'],
            $userData['email'],
            $hashedPassword,
            $userData['role']
        ]);

        $newId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?');
        $stmt->execute([$newId]);
        $createdUser = $stmt->fetch();

        sendJson(['success' => true, 'message' => 'Utilisateur créé avec succès', 'data' => $createdUser], 201);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        ensureMutatingCsrf();
        requireApiRole(['admin', 'editor']);

        if ($action !== 'edit' || $id <= 0) {
            sendJson(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        $data = getJsonInput();
        $validationResult = validateUserFormData($data);
        $errors = $validationResult['errors'];
        $userData = $validationResult['data'];

        if (empty($errors) && !checkEmailUniqueness($userData['email'], $id)) {
            $errors[] = 'Cet email est déjà utilisé';
        }

        if (!empty($errors)) {
            sendJson(['success' => false, 'message' => 'Validation invalide', 'errors' => $errors], 422);
        }

        $stmt = $pdo->prepare('UPDATE users SET nom = ?, prenom = ?, email = ?, role = ? WHERE id = ?');
        $stmt->execute([
            $userData['nom'],
            $userData['prenom'],
            $userData['email'],
            $userData['role'],
            $id
        ]);

        $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, created_at, updated_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $updatedUser = $stmt->fetch();

        sendJson(['success' => true, 'message' => 'Utilisateur modifié avec succès', 'data' => $updatedUser]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        ensureMutatingCsrf();
        requireApiRole(['admin']);

        if ($action !== 'delete' || $id <= 0) {
            sendJson(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            sendJson(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);

        sendJson(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
    }

    sendJson(['success' => false, 'message' => 'Méthode non supportée'], 405);
} catch (PDOException $e) {
    sendJson(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()], 500);
}
