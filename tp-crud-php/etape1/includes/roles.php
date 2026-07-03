<?php
/**
 * Configuration des rôles avec leurs couleurs Bootstrap
 */
function getRoleConfig() {
    return [
        'admin' => 'danger',
        'author' => 'info',
        'editor' => 'warning',
        'guest' => 'secondary'
    ];
}

/**
 * Affiche un badge pour un rôle
 */
function displayRoleBadge($role) {
    $rolesConfig = getRoleConfig();
    $badgeClass = $rolesConfig[$role] ?? 'secondary';
    echo '<span class="badge bg-' . $badgeClass . '">' . ucfirst($role) . '</span>';
}

/**
 * Retourne le HTML d'un badge pour un rôle
 */
function getRoleBadgeHTML($role) {
    $rolesConfig = getRoleConfig();
    $badgeClass = $rolesConfig[$role] ?? 'secondary';
    return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($role) . '</span>';
}

/**
 * Génère les options du select pour les rôles
 */
function displayRoleOptions($selectedRole = 'guest') {
    $roles = [
        'guest' => 'Invité (guest)',
        'author' => 'Auteur (author)',
        'editor' => 'Éditeur (editor)',
        'admin' => 'Administrateur (admin)'
    ];
    
    foreach ($roles as $value => $label) {
        $selected = $selectedRole === $value ? 'selected' : '';
        echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
    }
}
?>
