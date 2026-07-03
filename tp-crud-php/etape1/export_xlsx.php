<?php
require_once '../config.php';
require_once 'includes/roles.php';
require_once 'includes/auth.php';

// Export réservé aux rôles avec droits de gestion
requireRole(['admin', 'editor']);

// Try to load PhpSpreadsheet via Composer autoload
if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    // Fallback to CSV if library not installed
    header('Location: export.php?' . $_SERVER['QUERY_STRING']);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$search = cleanInput($_GET['search'] ?? '');
$filterRole = cleanInput($_GET['role'] ?? '');
$dateFrom = cleanInput($_GET['date_from'] ?? '');
$dateTo = cleanInput($_GET['date_to'] ?? '');
$sortBy = cleanInput($_GET['sort_by'] ?? 'created_at');
$sortDir = strtoupper(cleanInput($_GET['sort_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

try {
    $pdo = getConnection();
    $query = "SELECT id, nom, prenom, email, role, created_at FROM users";
    $where = [];
    $params = [];
    if (!empty($search)) { $where[] = 'email LIKE ?'; $params[] = '%'.$search.'%'; }
    if (!empty($filterRole)) { $where[] = 'role = ?'; $params[] = $filterRole; }
    if (!empty($dateFrom)) { $where[] = 'created_at >= ?'; $params[] = $dateFrom . ' 00:00:00'; }
    if (!empty($dateTo)) { $where[] = 'created_at <= ?'; $params[] = $dateTo . ' 23:59:59'; }
    if (!empty($where)) $query .= ' WHERE ' . implode(' AND ', $where);
    $allowedSort = ['id','nom','prenom','email','role','created_at'];
    if (!in_array($sortBy, $allowedSort)) $sortBy = 'created_at';
    $query .= " ORDER BY $sortBy $sortDir";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['ID','Nom','Prénom','Email','Rôle','Créé le'], null, 'A1');
    $rowNum = 2;
    foreach ($rows as $r) {
        $sheet->fromArray([$r['id'], $r['nom'], $r['prenom'], $r['email'], $r['role'], $r['created_at']], null, 'A' . $rowNum);
        $rowNum++;
    }

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="users_export.xlsx"');
    $writer->save('php://output');
    exit;
} catch (PDOException $e) {
    header('Location: index.php?error=' . urlencode('Erreur export : ' . $e->getMessage()));
    exit;
}
