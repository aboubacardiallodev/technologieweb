<?php
require_once '../config.php';
require_once '../etape1/includes/auth.php';

logoutUser();
header('Location: login.php?success=' . urlencode('Déconnecté.'));
exit;
