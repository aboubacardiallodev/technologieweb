<?php
/**
 * Fonctions de validation réutilisables
 */

/**
 * Valide et retourne les données du formulaire utilisateur
 */
function validateUserFormData($postData = null) {
    if ($postData === null) {
        $postData = $_POST;
    }

    $errors = [];
    
    // Récupération et nettoyage des données
    $nom = cleanInput($postData['nom'] ?? '');
    $prenom = cleanInput($postData['prenom'] ?? '');
    $email = cleanInput($postData['email'] ?? '');
    $role = cleanInput($postData['role'] ?? 'guest');

    // Validation Nom
    if (empty($nom)) {
        $errors[] = 'Le nom est requis';
    } elseif (strlen($nom) > 100) {
        $errors[] = 'Le nom ne peut pas dépasser 100 caractères';
    }

    // Validation Prénom
    if (empty($prenom)) {
        $errors[] = 'Le prénom est requis';
    } elseif (strlen($prenom) > 100) {
        $errors[] = 'Le prénom ne peut pas dépasser 100 caractères';
    }

    // Validation Email
    if (empty($email)) {
        $errors[] = 'L\'email est requis';
    } elseif (!validateEmail($email)) {
        $errors[] = 'L\'email n\'est pas valide';
    }

    // Validation Rôle
    if (!in_array($role, ['guest', 'admin', 'author', 'editor'])) {
        $errors[] = 'Le rôle sélectionné n\'est pas valide';
    }

    return [
        'errors' => $errors,
        'data' => [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'role' => $role
        ]
    ];
}

/**
 * Valide un mot de passe
 */
function validatePassword($password, $passwordConfirm, &$errors) {
    if (empty($password)) {
        $errors[] = 'Le mot de passe est requis';
        return false;
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
        return false;
    }
    
    if ($password !== $passwordConfirm) {
        $errors[] = 'Les mots de passe ne correspondent pas';
        return false;
    }

    return true;
}

/**
 * Vérifie l'unicité d'un email (sauf pour un utilisateur donné)
 */
function checkEmailUniqueness($email, $currentUserId = null) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // Si c'est le même utilisateur, c'est OK
            if ($currentUserId && (int)$existingUser['id'] === (int)$currentUserId) {
                return true;
            }
            return false;
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
