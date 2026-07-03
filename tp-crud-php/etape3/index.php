<?php
require_once '../config.php';
require_once '../etape1/includes/roles.php';
require_once '../etape1/includes/auth.php';
require_once '../etape1/includes/csrf.php';

requireRole(['admin', 'editor', 'author', 'guest']);

$currentUser = currentUser();
$currentRole = $currentUser['role'] ?? 'guest';
$canCreate = in_array($currentRole, ['admin', 'editor'], true);
$canEdit = in_array($currentRole, ['admin', 'editor'], true);
$canDelete = $currentRole === 'admin';
$canBulkEdit = $currentRole === 'admin';
$canExport = in_array($currentRole, ['admin', 'editor'], true);
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
    <title>TP CRUD PHP/MySQL - Étape 3 (AJAX)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .clickable-sort { cursor: pointer; user-select: none; }
        .table-actions .btn { margin-right: 0.25rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-lightning-charge"></i> CRUD AJAX - Étape 3
            </span>
            <div>
                <span class="text-light me-2">
                    Connecté : <?php echo htmlspecialchars(($currentUser['prenom'] ?? '') . ' ' . ($currentUser['nom'] ?? '')); ?>
                    (<?php echo htmlspecialchars($currentRole); ?>)
                </span>
                <a href="logout.php" class="btn btn-outline-light">Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container py-3">
        <div id="alertContainer"></div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">Utilisateurs</h2>
                    <div class="d-flex align-items-center gap-2">
                        <span id="loadingIndicator" class="text-muted" style="display:none;">
                            <i class="bi bi-arrow-repeat"></i> Chargement...
                        </span>
                        <?php if ($canCreate): ?>
                            <button type="button" class="btn btn-success" id="createUserBtn">
                                <i class="bi bi-person-add"></i> Ajouter
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <form id="filtersForm" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="searchInput" placeholder="Rechercher par email...">
                    </div>
                    <div class="col-md-3">
                        <select id="roleFilter" class="form-select">
                            <option value="">Tous les rôles</option>
                            <option value="guest">Guest</option>
                            <option value="author">Author</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex">
                        <input type="date" id="dateFrom" class="form-control me-2">
                        <input type="date" id="dateTo" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-secondary flex-fill">Filtrer</button>
                        <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </form>

                <?php if ($canExport): ?>
                    <div class="mb-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" id="exportCsvBtn">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-outline-success" id="exportXlsxBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <?php if ($canBulkEdit): ?>
                                    <th style="width:4%;"><input type="checkbox" id="selectAll"></th>
                                <?php endif; ?>
                                <th class="clickable-sort" data-sort="id">ID <span data-indicator="id"></span></th>
                                <th class="clickable-sort" data-sort="nom">Nom Prénom <span data-indicator="nom"></span></th>
                                <th class="clickable-sort" data-sort="email">Email <span data-indicator="email"></span></th>
                                <th class="clickable-sort" data-sort="role">Rôle <span data-indicator="role"></span></th>
                                <th class="clickable-sort" data-sort="created_at">Créé le <span data-indicator="created_at"></span></th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Chargement des utilisateurs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if ($canBulkEdit): ?>
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <select id="bulkRole" class="form-select w-auto">
                            <option value="">-- Changer le rôle --</option>
                            <option value="guest">Invité</option>
                            <option value="author">Auteur</option>
                            <option value="editor">Éditeur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                        <button type="button" class="btn btn-primary" id="bulkApplyBtn">Appliquer</button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="paginationInfo"></div>
                    <div class="btn-group" id="paginationControls"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="userForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">Créer un utilisateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="row" id="passwordFields">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role">
                                <?php displayRoleOptions('guest'); ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success" id="submitUserBtn">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Détails utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body" id="userDetailsContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p>Voulez-vous vraiment supprimer cet utilisateur ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const APP = {
            role: <?php echo json_encode($currentRole); ?>,
            canCreate: <?php echo $canCreate ? 'true' : 'false'; ?>,
            canEdit: <?php echo $canEdit ? 'true' : 'false'; ?>,
            canDelete: <?php echo $canDelete ? 'true' : 'false'; ?>,
            canBulkEdit: <?php echo $canBulkEdit ? 'true' : 'false'; ?>,
            canExport: <?php echo $canExport ? 'true' : 'false'; ?>,
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };

        const state = {
            users: [],
            currentUserId: null,
            deleteUserId: null,
            page: 1,
            perPage: 10,
            totalPages: 1,
            totalUsers: 0,
            filters: {
                search: '',
                role: '',
                date_from: '',
                date_to: ''
            },
            sort: {
                by: 'created_at',
                dir: 'DESC'
            }
        };

        const alertContainer = document.getElementById('alertContainer');
        const userForm = document.getElementById('userForm');
        const usersTableBody = document.getElementById('usersTableBody');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const userModal = new bootstrap.Modal(document.getElementById('userModal'));
        const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const userModalLabel = document.getElementById('userModalLabel');
        const submitUserBtn = document.getElementById('submitUserBtn');
        const userDetailsContent = document.getElementById('userDetailsContent');
        const paginationInfo = document.getElementById('paginationInfo');
        const paginationControls = document.getElementById('paginationControls');

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showAlert(type, message) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${escapeHtml(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }

        function setLoading(isLoading) {
            loadingIndicator.style.display = isLoading ? 'inline-block' : 'none';
        }

        function roleBadge(role) {
            const map = { admin: 'danger', author: 'info', editor: 'warning', guest: 'secondary' };
            return `<span class="badge bg-${map[role] || 'secondary'}">${escapeHtml(role)}</span>`;
        }

        function buildQueryParams(extra = {}) {
            const params = new URLSearchParams({
                action: 'list',
                page: state.page,
                per_page: state.perPage,
                search: state.filters.search,
                role: state.filters.role,
                date_from: state.filters.date_from,
                date_to: state.filters.date_to,
                sort_by: state.sort.by,
                sort_dir: state.sort.dir
            });

            Object.entries(extra).forEach(([k, v]) => {
                params.set(k, v);
            });
            return params;
        }

        function renderSortIndicators() {
            document.querySelectorAll('[data-indicator]').forEach((el) => {
                const key = el.getAttribute('data-indicator');
                if (key === state.sort.by) {
                    el.textContent = state.sort.dir === 'ASC' ? '▲' : '▼';
                } else {
                    el.textContent = '';
                }
            });
        }

        function renderPagination() {
            paginationInfo.textContent = `Page ${state.page} / ${state.totalPages} - ${state.totalUsers} utilisateur(s)`;
            paginationControls.innerHTML = '';

            const makeBtn = (label, targetPage, disabled = false) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `btn btn-outline-secondary ${disabled ? 'disabled' : ''}`;
                btn.textContent = label;
                btn.disabled = disabled;
                btn.addEventListener('click', () => {
                    state.page = targetPage;
                    loadUsers();
                });
                return btn;
            };

            paginationControls.appendChild(makeBtn('<<', 1, state.page <= 1));
            paginationControls.appendChild(makeBtn('<', Math.max(1, state.page - 1), state.page <= 1));
            paginationControls.appendChild(makeBtn('>', Math.min(state.totalPages, state.page + 1), state.page >= state.totalPages));
            paginationControls.appendChild(makeBtn('>>', state.totalPages, state.page >= state.totalPages));
        }

        function renderUsers() {
            const colspan = APP.canBulkEdit ? 7 : 6;
            if (!state.users.length) {
                usersTableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td></tr>`;
                return;
            }

            usersTableBody.innerHTML = state.users.map((user) => {
                const editBtn = APP.canEdit ? `
                    <button class="btn btn-sm btn-warning" data-action="edit" data-id="${user.id}" title="Modifier">
                        <i class="bi bi-pencil"></i>
                    </button>
                ` : '';
                const deleteBtn = APP.canDelete ? `
                    <button class="btn btn-sm btn-danger" data-action="delete" data-id="${user.id}" title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>
                ` : '';
                const checkbox = APP.canBulkEdit ? `<td><input type="checkbox" name="ids[]" value="${user.id}"></td>` : '';

                return `
                    <tr>
                        ${checkbox}
                        <td>${user.id}</td>
                        <td>${escapeHtml(user.prenom)} ${escapeHtml(user.nom)}</td>
                        <td>${escapeHtml(user.email)}</td>
                        <td>${roleBadge(user.role)}</td>
                        <td>${escapeHtml(new Date(user.created_at).toLocaleString('fr-FR'))}</td>
                        <td class="text-end table-actions">
                            <button class="btn btn-sm btn-info" data-action="view" data-id="${user.id}" title="Voir">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${editBtn}
                            ${deleteBtn}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function loadUsers() {
            setLoading(true);
            try {
                const params = buildQueryParams();
                const response = await fetch(`api.php?${params.toString()}`);
                const result = await response.json();
                if (response.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Impossible de charger les utilisateurs');
                }

                state.users = result.data || [];
                state.totalUsers = result.pagination?.total || 0;
                state.totalPages = result.pagination?.total_pages || 1;
                state.page = result.pagination?.page || 1;

                renderUsers();
                renderSortIndicators();
                renderPagination();
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                setLoading(false);
            }
        }

        function resetForm() {
            userForm.reset();
            document.getElementById('role').value = 'guest';
            document.getElementById('password').required = true;
            document.getElementById('password_confirm').required = true;
            document.getElementById('passwordFields').style.display = '';
        }

        function openCreateModal() {
            state.currentUserId = null;
            userModalLabel.textContent = 'Créer un utilisateur';
            submitUserBtn.textContent = 'Créer';
            resetForm();
            userModal.show();
        }

        function openEditModal(userId) {
            const user = state.users.find((item) => item.id === Number(userId));
            if (!user) {
                showAlert('danger', 'Utilisateur introuvable');
                return;
            }
            state.currentUserId = user.id;
            userModalLabel.textContent = 'Modifier un utilisateur';
            submitUserBtn.textContent = 'Modifier';
            document.getElementById('prenom').value = user.prenom;
            document.getElementById('nom').value = user.nom;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('password').required = false;
            document.getElementById('password_confirm').required = false;
            document.getElementById('passwordFields').style.display = 'none';
            userModal.show();
        }

        async function openViewModal(userId) {
            try {
                const response = await fetch(`api.php?action=view&id=${userId}`);
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Impossible d\'afficher l\'utilisateur');
                }
                const user = result.data;
                userDetailsContent.innerHTML = `
                    <div class="mb-2"><strong>ID :</strong> ${user.id}</div>
                    <div class="mb-2"><strong>Prénom :</strong> ${escapeHtml(user.prenom)}</div>
                    <div class="mb-2"><strong>Nom :</strong> ${escapeHtml(user.nom)}</div>
                    <div class="mb-2"><strong>Email :</strong> ${escapeHtml(user.email)}</div>
                    <div class="mb-2"><strong>Rôle :</strong> ${roleBadge(user.role)}</div>
                    <div class="mb-2"><strong>Créé le :</strong> ${escapeHtml(new Date(user.created_at).toLocaleString('fr-FR'))}</div>
                    <div class="mb-2"><strong>Mis à jour le :</strong> ${escapeHtml(new Date(user.updated_at).toLocaleString('fr-FR'))}</div>
                `;
                viewModal.show();
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        function validateClientSide() {
            const required = ['prenom', 'nom', 'email'];
            for (const id of required) {
                const field = document.getElementById(id);
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    return false;
                }
                field.classList.remove('is-invalid');
            }

            if (!state.currentUserId) {
                if (!document.getElementById('password').value.trim() || !document.getElementById('password_confirm').value.trim()) {
                    showAlert('danger', 'Le mot de passe est requis pour la création.');
                    return false;
                }
            }
            return true;
        }

        async function handleSubmit(event) {
            event.preventDefault();
            if (!validateClientSide()) {
                return;
            }

            const payload = {
                prenom: document.getElementById('prenom').value.trim(),
                nom: document.getElementById('nom').value.trim(),
                email: document.getElementById('email').value.trim(),
                role: document.getElementById('role').value
            };
            if (!state.currentUserId) {
                payload.password = document.getElementById('password').value;
                payload.password_confirm = document.getElementById('password_confirm').value;
            }

            try {
                const method = state.currentUserId ? 'PUT' : 'POST';
                const endpoint = state.currentUserId ? `api.php?action=edit&id=${state.currentUserId}` : 'api.php?action=create';
                const response = await fetch(endpoint, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': APP.csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Échec de l\'opération');
                }

                showAlert('success', result.message);
                userModal.hide();
                await loadUsers();
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        function confirmDelete(userId) {
            state.deleteUserId = Number(userId);
            deleteModal.show();
        }

        async function deleteUser() {
            if (!state.deleteUserId) {
                return;
            }
            try {
                const response = await fetch(`api.php?action=delete&id=${state.deleteUserId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-Token': APP.csrfToken }
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'La suppression a échoué');
                }
                showAlert('success', result.message);
                deleteModal.hide();
                await loadUsers();
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        async function applyBulkEdit() {
            const role = document.getElementById('bulkRole')?.value || '';
            const ids = Array.from(document.querySelectorAll('input[name="ids[]"]:checked')).map((cb) => Number(cb.value));
            if (!role || ids.length === 0) {
                showAlert('danger', 'Sélectionnez au moins un utilisateur et un rôle.');
                return;
            }
            if (!confirm('Confirmer la mise à jour en masse ?')) {
                return;
            }

            try {
                const response = await fetch('api.php?action=bulk_edit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': APP.csrfToken
                    },
                    body: JSON.stringify({ ids, new_role: role })
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Échec de la mise à jour en masse');
                }
                showAlert('success', result.message);
                await loadUsers();
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        function updateFiltersFromUI() {
            state.filters.search = document.getElementById('searchInput').value.trim();
            state.filters.role = document.getElementById('roleFilter').value;
            state.filters.date_from = document.getElementById('dateFrom').value;
            state.filters.date_to = document.getElementById('dateTo').value;
            state.page = 1;
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('roleFilter').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            updateFiltersFromUI();
            loadUsers();
        }

        function exportUsers(type) {
            const params = buildQueryParams({ action: type === 'xlsx' ? 'export_xlsx' : 'export_csv', page: 1 });
            window.location.href = `api.php?${params.toString()}`;
        }

        document.getElementById('filtersForm').addEventListener('submit', (event) => {
            event.preventDefault();
            updateFiltersFromUI();
            loadUsers();
        });
        document.getElementById('resetFiltersBtn').addEventListener('click', resetFilters);

        if (APP.canCreate) {
            document.getElementById('createUserBtn').addEventListener('click', openCreateModal);
        }

        document.querySelectorAll('.clickable-sort').forEach((th) => {
            th.addEventListener('click', () => {
                const nextSortBy = th.getAttribute('data-sort');
                if (state.sort.by === nextSortBy) {
                    state.sort.dir = state.sort.dir === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    state.sort.by = nextSortBy;
                    state.sort.dir = 'ASC';
                }
                loadUsers();
            });
        });

        if (APP.canBulkEdit) {
            document.getElementById('selectAll').addEventListener('change', (event) => {
                const checked = event.target.checked;
                document.querySelectorAll('input[name="ids[]"]').forEach((cb) => { cb.checked = checked; });
            });
            document.getElementById('bulkApplyBtn').addEventListener('click', applyBulkEdit);
        }

        if (APP.canExport) {
            document.getElementById('exportCsvBtn').addEventListener('click', () => exportUsers('csv'));
            document.getElementById('exportXlsxBtn').addEventListener('click', () => exportUsers('xlsx'));
        }

        userForm.addEventListener('submit', handleSubmit);
        usersTableBody.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-action]');
            if (!button) {
                return;
            }
            const action = button.dataset.action;
            const id = button.dataset.id;
            if (action === 'view') {
                openViewModal(id);
            } else if (action === 'edit') {
                openEditModal(id);
            } else if (action === 'delete') {
                confirmDelete(id);
            }
        });
        document.getElementById('confirmDeleteBtn').addEventListener('click', deleteUser);

        document.addEventListener('DOMContentLoaded', loadUsers);
    </script>
</body>
</html>
