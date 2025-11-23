<?php
/**
 * Déconnexion Admin - Portfolio
 */
require_once '../config.php';
initSecureSession();

// Logger l'activité avant déconnexion
if (isLoggedIn()) {
    logActivity('logout', 'user', $_SESSION['user_id'], 'Déconnexion');
}

// Détruire la session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Rediriger vers la page de connexion
redirect('login.php');
