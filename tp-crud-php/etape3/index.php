<?php
require_once '../config.php';
require_once '../etape1/includes/roles.php';
require_once '../etape1/includes/validation.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP CRUD PHP/MySQL - Étape 3 (AJAX)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .table-actions .btn { margin-right: 0.25rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-lightning-charge"></i> CRUD AJAX
            </span>
            <button type="button" class="btn btn-success" id="createUserBtn">
                <i class="bi bi-person-add"></i> Ajouter
            </button>
        </div>
    </nav>

    <div class="container py-3">
        <div id="alertContainer"></div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">Utilisateurs</h2>
                    <div id="loadingIndicator" class="text-muted">
                        <span class="me-2"><i class="bi bi-arrow-repeat"></i></span>
                        Chargement...
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Chargement des utilisateurs...</td>
                            </tr>
                        </tbody>
                    </table>
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
        const state = { users: [], currentUserId: null, deleteUserId: null };
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

        function showAlert(type, message) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="bi ${type === 'success' ? 'bi-check-circle' : type === 'danger' ? 'bi-exclamation-circle' : 'bi-info-circle'}"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }

        function setLoading(isLoading) {
            loadingIndicator.style.display = isLoading ? 'block' : 'none';
        }

        function resetForm() {
            userForm.reset();
            document.getElementById('role').value = 'guest';
        }

        function renderUsers() {
            if (!state.users.length) {
                usersTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td></tr>';
                return;
            }

            usersTableBody.innerHTML = state.users.map((user) => `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.prenom} ${user.nom}</td>
                    <td>${user.email}</td>
                    <td><span class="badge bg-${user.role === 'admin' ? 'danger' : user.role === 'author' ? 'info' : user.role === 'editor' ? 'warning' : 'secondary'}">${user.role}</span></td>
                    <td class="text-end table-actions">
                        <button class="btn btn-sm btn-info" data-action="view" data-id="${user.id}" title="Voir">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" data-action="edit" data-id="${user.id}" title="Modifier">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" data-action="delete" data-id="${user.id}" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        async function loadUsers() {
            setLoading(true);
            try {
                const response = await fetch('api.php?action=list');
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Impossible de charger les utilisateurs');
                }
                state.users = result.data || [];
                renderUsers();
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                setLoading(false);
            }
        }

        async function openEditModal(userId) {
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
            userModal.show();
        }

        function openCreateModal() {
            state.currentUserId = null;
            userModalLabel.textContent = 'Créer un utilisateur';
            submitUserBtn.textContent = 'Créer';
            resetForm();
            userModal.show();
        }

        async function openViewModal(userId) {
            try {
                const response = await fetch(`api.php?action=view&id=${userId}`);
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Impossible d’afficher l’utilisateur');
                }

                const user = result.data;
                userDetailsContent.innerHTML = `
                    <div class="mb-3">
                        <strong>ID :</strong> ${user.id}
                    </div>
                    <div class="mb-3">
                        <strong>Prénom :</strong> ${user.prenom}
                    </div>
                    <div class="mb-3">
                        <strong>Nom :</strong> ${user.nom}
                    </div>
                    <div class="mb-3">
                        <strong>Email :</strong> ${user.email}
                    </div>
                    <div class="mb-3">
                        <strong>Rôle :</strong> <span class="badge bg-${user.role === 'admin' ? 'danger' : user.role === 'author' ? 'info' : user.role === 'editor' ? 'warning' : 'secondary'}">${user.role}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Créé le :</strong> ${new Date(user.created_at).toLocaleString('fr-FR')}
                    </div>
                `;
                viewModal.show();
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        function validateClientSide() {
            const fields = ['prenom', 'nom', 'email'];
            for (const fieldName of fields) {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    return false;
                }
                field.classList.remove('is-invalid');
            }

            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('email').classList.add('is-invalid');
                return false;
            }
            document.getElementById('email').classList.remove('is-invalid');

            return true;
        }

        async function handleSubmit(event) {
            event.preventDefault();
            if (!validateClientSide()) {
                showAlert('danger', 'Veuillez corriger les champs du formulaire.');
                return;
            }

            const payload = {
                prenom: document.getElementById('prenom').value.trim(),
                nom: document.getElementById('nom').value.trim(),
                email: document.getElementById('email').value.trim(),
                role: document.getElementById('role').value
            };

            try {
                const method = state.currentUserId ? 'PUT' : 'POST';
                const endpoint = state.currentUserId
                    ? `api.php?action=edit&id=${state.currentUserId}`
                    : 'api.php?action=create';

                const response = await fetch(endpoint, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Échec de l’opération');
                }

                showAlert('success', result.message);
                userModal.hide();
                await loadUsers();
            } catch (error) {
                showAlert('danger', error.message);
            }
        }

        async function confirmDelete(userId) {
            state.deleteUserId = userId;
            deleteModal.show();
        }

        async function deleteUser() {
            if (!state.deleteUserId) {
                return;
            }

            try {
                const response = await fetch(`api.php?action=delete&id=${state.deleteUserId}`, { method: 'DELETE' });
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

        document.getElementById('createUserBtn').addEventListener('click', openCreateModal);
        userForm.addEventListener('submit', handleSubmit);
        usersTableBody.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-action]');
            if (!button) {
                return;
            }

            const { action, id } = button.dataset;
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
