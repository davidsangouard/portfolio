<?php
/**
 * Page de connexion Admin - Portfolio
 * Sécurité : Protection CSRF, Rate limiting, Validation
 */
require_once '../config.php';
initSecureSession();

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            // Vérifier le rate limiting
            if (!checkLoginAttempts($username)) {
                $error = 'Trop de tentatives. Compte temporairement verrouillé.';
            } else {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Connexion réussie
                    resetLoginAttempts($username);
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['created'] = time();
                    
                    logActivity('login', 'user', $user['id'], 'Connexion réussie');
                    
                    redirect('index.php');
                } else {
                    // Échec de connexion
                    incrementLoginAttempts($username);
                    $error = 'Identifiants incorrects.';
                    
                    // Log l'échec
                    if ($user) {
                        logActivity('login_failed', 'user', $user['id'], 'Tentative de connexion échouée');
                    }
                }
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0d1117;
            --bg-secondary: #161b22;
            --bg-tertiary: #21262d;
            --bg-card: #1c2128;
            --text-primary: #f0f6fc;
            --text-secondary: #8b949e;
            --text-muted: #6e7681;
            --accent-primary: #58a6ff;
            --accent-secondary: #238636;
            --accent-warning: #d29922;
            --accent-danger: #f85149;
            --border-primary: #30363d;
            --border-secondary: #21262d;
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            --font-mono: 'JetBrains Mono', monospace;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: var(--space-lg);
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-2xl);
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--space-2xl);
        }

        .login-icon {
            width: 60px;
            height: 60px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-lg);
            color: var(--accent-primary);
            font-size: 1.5rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: var(--space-sm);
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: var(--space-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--space-sm);
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: var(--space-md);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: var(--space-md);
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .input-icon-wrapper .form-input {
            padding-left: 2.8rem;
        }

        .password-toggle {
            position: absolute;
            right: var(--space-md);
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
            width: 100%;
            padding: var(--space-md) var(--space-xl);
            border: 1px solid var(--border-primary);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn:hover {
            border-color: var(--accent-primary);
            background: rgba(88, 166, 255, 0.1);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: var(--bg-primary);
        }

        .btn-primary:hover {
            background: #4493f8;
            border-color: #4493f8;
        }

        .alert {
            padding: var(--space-md);
            border-radius: 4px;
            margin-bottom: var(--space-lg);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .alert-error {
            background: rgba(248, 81, 73, 0.1);
            border: 1px solid var(--accent-danger);
            color: var(--accent-danger);
        }

        .alert-success {
            background: rgba(35, 134, 54, 0.1);
            border: 1px solid var(--accent-secondary);
            color: var(--accent-secondary);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: var(--space-xl);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--accent-primary);
        }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
            margin-top: var(--space-xl);
            padding-top: var(--space-lg);
            border-top: 1px solid var(--border-primary);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .security-badge i {
            color: var(--accent-secondary);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="login-title">Administration</h1>
                <p class="login-subtitle">Connectez-vous pour accéder au panneau</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= e($success) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label" for="username">Identifiant</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-input" 
                               placeholder="Nom d'utilisateur ou email"
                               required
                               autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="Votre mot de passe"
                               required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>

            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour au portfolio
            </a>

            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                Connexion sécurisée
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus sur le premier champ
        document.getElementById('username').focus();
    </script>
</body>
</html>
