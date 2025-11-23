<?php
/**
 * Configuration et fonctions de sécurité
 * Portfolio - David Sangouard
 */

// =====================================================
// CONFIGURATION BASE DE DONNÉES
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'portfolio_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURATION SÉCURITÉ
// =====================================================
define('CSRF_TOKEN_EXPIRY', 3600); // 1 heure
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
define('SESSION_TIMEOUT', 1800); // 30 minutes

// =====================================================
// CONNEXION PDO SÉCURISÉE
// =====================================================
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Erreur de connexion à la base de données.");
        }
    }
    
    return $pdo;
}

// =====================================================
// FONCTIONS DE SÉCURITÉ
// =====================================================

/**
 * Initialise une session sécurisée
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
    
    // Régénérer l'ID de session périodiquement
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > SESSION_TIMEOUT) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Génère un token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Échappe les données pour l'affichage HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoie les entrées utilisateur
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(strip_tags($input));
}

/**
 * Valide une URL
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Valide un email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Génère un slug à partir d'un texte
 */
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirige vers une URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Retourne une réponse JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log une activité admin
 */
function logActivity($action, $entityType = null, $entityId = null, $details = null) {
    if (!isLoggedIn()) return;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $entityType,
        $entityId,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
    ]);
}

/**
 * Vérifie le rate limiting pour les tentatives de connexion
 */
function checkLoginAttempts($username) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT login_attempts, locked_until FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
        return false;
    }
    
    return true;
}

/**
 * Incrémente les tentatives de connexion échouées
 */
function incrementLoginAttempts($username) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        UPDATE users SET 
            login_attempts = login_attempts + 1,
            locked_until = IF(login_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), locked_until)
        WHERE username = ?
    ");
    $stmt->execute([MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME, $username]);
}

/**
 * Réinitialise les tentatives de connexion
 */
function resetLoginAttempts($username) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE username = ?");
    $stmt->execute([$username]);
}

/**
 * Récupère un paramètre du site
 */
function getSetting($key, $default = '') {
    static $settings = null;
    
    if ($settings === null) {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings[$key] ?? $default;
}

/**
 * Protège contre les injections XSS dans le contenu riche
 */
function sanitizeHtml($html) {
    $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><blockquote><code><pre>';
    $html = strip_tags($html, $allowed_tags);
    
    // Nettoyer les attributs dangereux
    $html = preg_replace('/(<[^>]+)\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '$1', $html);
    $html = preg_replace('/(<[^>]+)\s+style\s*=\s*["\'][^"\']*["\']/i', '$1', $html);
    
    return $html;
}

/**
 * Formatte une date en français
 */
function formatDateFr($date) {
    $timestamp = strtotime($date);
    $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return date('d', $timestamp) . ' ' . $mois[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}

/**
 * Tronque un texte
 */
function truncate($text, $length = 150) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}
