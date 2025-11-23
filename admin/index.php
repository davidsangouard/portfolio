<?php
/**
 * Dashboard Admin Responsive - Portfolio
 */
require_once '../config.php';
initSecureSession();

if (!isLoggedIn()) redirect('login.php');

$pdo = getDB();
$csrfToken = generateCSRFToken();

// Messages flash
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$tab = $_GET['tab'] ?? 'dashboard';

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Session expirée';
        $_SESSION['flash_type'] = 'error';
        redirect('index.php?tab=' . $tab);
    }

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'update_settings':
                foreach ($_POST['settings'] as $key => $value) {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([sanitize($value), sanitize($key)]);
                }
                $_SESSION['flash_message'] = 'Paramètres mis à jour';
                break;

            case 'update_about':
                $stmt = $pdo->prepare("UPDATE about_section SET section_title = ?, section_subtitle = ?, about_title = ?, about_text = ? WHERE id = 1");
                $stmt->execute([sanitize($_POST['section_title']), sanitize($_POST['section_subtitle']), sanitize($_POST['about_title']), sanitize($_POST['about_text'])]);
                $_SESSION['flash_message'] = 'Section À propos mise à jour';
                break;

            case 'add_feature':
                $stmt = $pdo->prepare("INSERT INTO about_features (icon, text, sort_order) VALUES (?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM about_features af))");
                $stmt->execute([sanitize($_POST['icon']), sanitize($_POST['text'])]);
                $_SESSION['flash_message'] = 'Feature ajoutée';
                break;

            case 'update_feature':
                $stmt = $pdo->prepare("UPDATE about_features SET icon = ?, text = ?, is_active = ? WHERE id = ?");
                $stmt->execute([sanitize($_POST['icon']), sanitize($_POST['text']), isset($_POST['is_active']) ? 1 : 0, (int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Feature mise à jour';
                break;

            case 'delete_feature':
                $pdo->prepare("DELETE FROM about_features WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Feature supprimée';
                break;

            case 'add_stat':
                $stmt = $pdo->prepare("INSERT INTO stats (value, label, sort_order) VALUES (?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM stats s))");
                $stmt->execute([sanitize($_POST['value']), sanitize($_POST['label'])]);
                $_SESSION['flash_message'] = 'Statistique ajoutée';
                break;

            case 'update_stat':
                $stmt = $pdo->prepare("UPDATE stats SET value = ?, label = ?, is_active = ? WHERE id = ?");
                $stmt->execute([sanitize($_POST['value']), sanitize($_POST['label']), isset($_POST['is_active']) ? 1 : 0, (int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Statistique mise à jour';
                break;

            case 'delete_stat':
                $pdo->prepare("DELETE FROM stats WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Statistique supprimée';
                break;

            case 'add_skill_category':
                $stmt = $pdo->prepare("INSERT INTO skill_categories (name, icon, icon_color, sort_order) VALUES (?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM skill_categories sc))");
                $stmt->execute([sanitize($_POST['name']), sanitize($_POST['icon']), sanitize($_POST['icon_color'])]);
                $_SESSION['flash_message'] = 'Catégorie ajoutée';
                break;

            case 'update_skill_category':
                $stmt = $pdo->prepare("UPDATE skill_categories SET name = ?, icon = ?, icon_color = ?, is_active = ? WHERE id = ?");
                $stmt->execute([sanitize($_POST['name']), sanitize($_POST['icon']), sanitize($_POST['icon_color']), isset($_POST['is_active']) ? 1 : 0, (int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Catégorie mise à jour';
                break;

            case 'delete_skill_category':
                $pdo->prepare("DELETE FROM skill_categories WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Catégorie supprimée';
                break;

            case 'add_skill':
                $stmt = $pdo->prepare("INSERT INTO skills (category_id, name, sort_order) VALUES (?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM skills s WHERE s.category_id = ?))");
                $stmt->execute([(int)$_POST['category_id'], sanitize($_POST['name']), (int)$_POST['category_id']]);
                $_SESSION['flash_message'] = 'Compétence ajoutée';
                break;

            case 'update_skill':
                $stmt = $pdo->prepare("UPDATE skills SET category_id = ?, name = ?, is_active = ? WHERE id = ?");
                $stmt->execute([(int)$_POST['category_id'], sanitize($_POST['name']), isset($_POST['is_active']) ? 1 : 0, (int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Compétence mise à jour';
                break;

            case 'delete_skill':
                $pdo->prepare("DELETE FROM skills WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Compétence supprimée';
                break;

            case 'add_project':
                $stmt = $pdo->prepare("INSERT INTO projects (title, type, description, icon, demo_url, github_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM projects p))");
                $stmt->execute([sanitize($_POST['title']), sanitize($_POST['type']), sanitize($_POST['description']), sanitize($_POST['icon']), sanitize($_POST['demo_url']), sanitize($_POST['github_url'])]);
                $projectId = $pdo->lastInsertId();
                if (!empty($_POST['tags'])) {
                    $tags = explode(',', $_POST['tags']);
                    $stmtTag = $pdo->prepare("INSERT INTO project_tags (project_id, tag_name, sort_order) VALUES (?, ?, ?)");
                    foreach ($tags as $i => $tag) $stmtTag->execute([$projectId, trim(sanitize($tag)), $i]);
                }
                $_SESSION['flash_message'] = 'Projet ajouté';
                break;

            case 'update_project':
                $projectId = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE projects SET title = ?, type = ?, description = ?, icon = ?, demo_url = ?, github_url = ?, is_active = ? WHERE id = ?");
                $stmt->execute([sanitize($_POST['title']), sanitize($_POST['type']), sanitize($_POST['description']), sanitize($_POST['icon']), sanitize($_POST['demo_url']), sanitize($_POST['github_url']), isset($_POST['is_active']) ? 1 : 0, $projectId]);
                $pdo->prepare("DELETE FROM project_tags WHERE project_id = ?")->execute([$projectId]);
                if (!empty($_POST['tags'])) {
                    $tags = explode(',', $_POST['tags']);
                    $stmtTag = $pdo->prepare("INSERT INTO project_tags (project_id, tag_name, sort_order) VALUES (?, ?, ?)");
                    foreach ($tags as $i => $tag) $stmtTag->execute([$projectId, trim(sanitize($tag)), $i]);
                }
                $_SESSION['flash_message'] = 'Projet mis à jour';
                break;

            case 'delete_project':
                $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Projet supprimé';
                break;

            case 'add_veille_category':
                $slug = generateSlug($_POST['name']);
                $stmt = $pdo->prepare("INSERT INTO veille_categories (name, slug, color, icon, sort_order) VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM veille_categories vc))");
                $stmt->execute([sanitize($_POST['name']), $slug, sanitize($_POST['color']), sanitize($_POST['icon'])]);
                $_SESSION['flash_message'] = 'Catégorie veille ajoutée';
                break;

            case 'update_veille_category':
                $stmt = $pdo->prepare("UPDATE veille_categories SET name = ?, color = ?, icon = ?, is_active = ? WHERE id = ?");
                $stmt->execute([sanitize($_POST['name']), sanitize($_POST['color']), sanitize($_POST['icon']), isset($_POST['is_active']) ? 1 : 0, (int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Catégorie veille mise à jour';
                break;

            case 'delete_veille_category':
                $pdo->prepare("DELETE FROM veille_categories WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Catégorie veille supprimée';
                break;

            case 'add_veille_post':
                $slug = generateSlug($_POST['title']);
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM veille_posts WHERE slug = ?");
                $stmtCheck->execute([$slug]);
                if ($stmtCheck->fetchColumn() > 0) $slug .= '-' . time();
                $stmt = $pdo->prepare("INSERT INTO veille_posts (title, slug, excerpt, content, category_id, source_url, source_name, author_id, is_featured, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([sanitize($_POST['title']), $slug, sanitize($_POST['excerpt']), sanitizeHtml($_POST['content']), (int)$_POST['category_id'] ?: null, sanitize($_POST['source_url']), sanitize($_POST['source_name']), $_SESSION['user_id'], isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_published']) ? 1 : 0, isset($_POST['is_published']) ? date('Y-m-d H:i:s') : null]);
                $postId = $pdo->lastInsertId();
                if (!empty($_POST['post_tags'])) {
                    $stmtTag = $pdo->prepare("INSERT INTO veille_post_tags (post_id, tag_id) VALUES (?, ?)");
                    foreach ($_POST['post_tags'] as $tagId) $stmtTag->execute([$postId, (int)$tagId]);
                }
                $_SESSION['flash_message'] = 'Article ajouté';
                break;

            case 'update_veille_post':
                $postId = (int)$_POST['id'];
                $wasPublished = $pdo->query("SELECT is_published, published_at FROM veille_posts WHERE id = $postId")->fetch();
                $publishedAt = $wasPublished['published_at'];
                if (isset($_POST['is_published']) && !$wasPublished['is_published']) $publishedAt = date('Y-m-d H:i:s');
                elseif (!isset($_POST['is_published'])) $publishedAt = null;
                $stmt = $pdo->prepare("UPDATE veille_posts SET title = ?, excerpt = ?, content = ?, category_id = ?, source_url = ?, source_name = ?, is_featured = ?, is_published = ?, published_at = ? WHERE id = ?");
                $stmt->execute([sanitize($_POST['title']), sanitize($_POST['excerpt']), sanitizeHtml($_POST['content']), (int)$_POST['category_id'] ?: null, sanitize($_POST['source_url']), sanitize($_POST['source_name']), isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_published']) ? 1 : 0, $publishedAt, $postId]);
                $pdo->prepare("DELETE FROM veille_post_tags WHERE post_id = ?")->execute([$postId]);
                if (!empty($_POST['post_tags'])) {
                    $stmtTag = $pdo->prepare("INSERT INTO veille_post_tags (post_id, tag_id) VALUES (?, ?)");
                    foreach ($_POST['post_tags'] as $tagId) $stmtTag->execute([$postId, (int)$tagId]);
                }
                $_SESSION['flash_message'] = 'Article mis à jour';
                break;

            case 'delete_veille_post':
                $pdo->prepare("DELETE FROM veille_posts WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Article supprimé';
                break;

            case 'add_veille_tag':
                $slug = generateSlug($_POST['name']);
                $pdo->prepare("INSERT INTO veille_tags (name, slug) VALUES (?, ?)")->execute([sanitize($_POST['name']), $slug]);
                $_SESSION['flash_message'] = 'Tag ajouté';
                break;

            case 'delete_veille_tag':
                $pdo->prepare("DELETE FROM veille_tags WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['flash_message'] = 'Tag supprimé';
                break;

            case 'change_password':
                if (!password_verify($_POST['current_password'], $pdo->query("SELECT password FROM users WHERE id = {$_SESSION['user_id']}")->fetchColumn())) {
                    $_SESSION['flash_message'] = 'Mot de passe actuel incorrect';
                    $_SESSION['flash_type'] = 'error';
                } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                    $_SESSION['flash_message'] = 'Les mots de passe ne correspondent pas';
                    $_SESSION['flash_type'] = 'error';
                } elseif (strlen($_POST['new_password']) < 8) {
                    $_SESSION['flash_message'] = 'Le mot de passe doit contenir au moins 8 caractères';
                    $_SESSION['flash_type'] = 'error';
                } else {
                    $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $_SESSION['user_id']]);
                    $_SESSION['flash_message'] = 'Mot de passe modifié';
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Erreur : ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }

    redirect('index.php?tab=' . $tab);
}

// Récupérer les données
$settings = [];
$stmt = $pdo->query("SELECT * FROM site_settings");
while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row;

$about = $pdo->query("SELECT * FROM about_section LIMIT 1")->fetch();
$features = $pdo->query("SELECT * FROM about_features ORDER BY sort_order")->fetchAll();
$stats = $pdo->query("SELECT * FROM stats ORDER BY sort_order")->fetchAll();
$skillCategories = $pdo->query("SELECT * FROM skill_categories ORDER BY sort_order")->fetchAll();
$skills = $pdo->query("SELECT s.*, sc.name as category_name FROM skills s LEFT JOIN skill_categories sc ON s.category_id = sc.id ORDER BY s.category_id, s.sort_order")->fetchAll();
$projects = $pdo->query("SELECT * FROM projects ORDER BY sort_order")->fetchAll();
foreach ($projects as &$p) {
    $stmtT = $pdo->prepare("SELECT tag_name FROM project_tags WHERE project_id = ? ORDER BY sort_order");
    $stmtT->execute([$p['id']]);
    $p['tags'] = $stmtT->fetchAll(PDO::FETCH_COLUMN);
}
$veilleCategories = $pdo->query("SELECT * FROM veille_categories ORDER BY sort_order")->fetchAll();
$veillePosts = $pdo->query("SELECT vp.*, vc.name as category_name FROM veille_posts vp LEFT JOIN veille_categories vc ON vp.category_id = vc.id ORDER BY vp.created_at DESC")->fetchAll();
$veilleTags = $pdo->query("SELECT * FROM veille_tags ORDER BY name")->fetchAll();

$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE is_active = 1")->fetchColumn();
$totalPosts = $pdo->query("SELECT COUNT(*) FROM veille_posts WHERE is_published = 1")->fetchColumn();
$totalViews = $pdo->query("SELECT SUM(views) FROM veille_posts")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Portfolio</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .mobile-toggle {
            display: none;
            position: fixed;
            top: var(--space-md);
            left: var(--space-md);
            z-index: 1001;
            width: 44px;
            height: 44px;
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            color: var(--text-primary);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
        }

        .mobile-overlay.active { display: block; }

        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-primary);
            padding: var(--space-lg);
            position: fixed;
            width: 260px;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding-bottom: var(--space-lg);
            border-bottom: 1px solid var(--border-primary);
            margin-bottom: var(--space-lg);
        }

        .sidebar-logo {
            font-family: var(--font-mono);
            font-size: 1.1rem;
            color: var(--accent-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .sidebar-user {
            margin-top: var(--space-md);
            padding: var(--space-md);
            background: var(--bg-tertiary);
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .nav-section { margin-bottom: var(--space-lg); }

        .nav-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.05em;
            margin-bottom: var(--space-sm);
            padding: 0 var(--space-sm);
        }

        .nav-list { list-style: none; }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-sm) var(--space-md);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .nav-item a:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .nav-item a.active {
            background: rgba(88, 166, 255, 0.1);
            color: var(--accent-primary);
            border-left: 2px solid var(--accent-primary);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
            width: 100%;
            padding: var(--space-md);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-top: var(--space-lg);
        }

        .logout-btn:hover {
            border-color: var(--accent-danger);
            color: var(--accent-danger);
        }

        .main-content {
            margin-left: 260px;
            padding: var(--space-xl);
            min-height: 100vh;
            max-width: none; /* Utilise toute la largeur disponible */
            width: 100%;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-xl);
            padding-bottom: var(--space-lg);
            border-bottom: 1px solid var(--border-primary);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .alert {
            padding: var(--space-md) var(--space-lg);
            border-radius: 4px;
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .alert-success {
            background: rgba(35, 134, 54, 0.1);
            border: 1px solid var(--accent-secondary);
            color: var(--accent-secondary);
        }

        .alert-error {
            background: rgba(248, 81, 73, 0.1);
            border: 1px solid var(--accent-danger);
            color: var(--accent-danger);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-2xl);
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-xl);
        }

        .stat-value {
            font-family: var(--font-mono);
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-primary);
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            margin-bottom: var(--space-xl);
        }

        .card-header {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .card-body { padding: var(--space-lg); }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-lg);
            width: 100%;
        }

        .form-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .form-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        /* Sur écrans plus petits */
        @media (max-width: 1200px) {
            .form-grid.cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .form-group { margin-bottom: var(--space-lg); }

        .form-label {
            display: block;
            margin-bottom: var(--space-sm);
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: var(--space-md);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .form-textarea { 
            min-height: 180px; 
            resize: vertical; 
            font-family: var(--font-mono);
            line-height: 1.6;
        }

        .form-textarea.large { min-height: 300px; }
        .form-textarea.xl { min-height: 400px; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-sm) var(--space-lg);
            border: 1px solid var(--border-primary);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: var(--bg-primary);
        }

        .btn-danger {
            border-color: var(--accent-danger);
            color: var(--accent-danger);
        }

        .btn-sm { padding: var(--space-xs) var(--space-sm); font-size: 0.8rem; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: var(--space-md);
            text-align: left;
            border-bottom: 1px solid var(--border-primary);
        }

        .badge {
            display: inline-block;
            padding: var(--space-xs) var(--space-sm);
            border-radius: 3px;
            font-size: 0.75rem;
            font-family: var(--font-mono);
        }

        .badge-success { background: rgba(35, 134, 54, 0.2); color: var(--accent-secondary); }
        .badge-danger { background: rgba(248, 81, 73, 0.2); color: var(--accent-danger); }

        .actions { display: flex; gap: var(--space-sm); }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
        }

        .modal.active { display: flex; }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            width: 100%;
            max-width: 800px; /* Augmenté de 600px à 800px */
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content.large {
            max-width: 1000px;
        }

        .modal-content.xl {
            max-width: 1200px;
        }

        .modal-header {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            justify-content: space-between;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body { padding: var(--space-lg); }
        .modal-footer { 
            padding: var(--space-lg); 
            border-top: 1px solid var(--border-primary);
            display: flex;
            justify-content: flex-end;
            gap: var(--space-md);
        }

        .tab-hidden { display: none; }

        /* ==================== RESPONSIVE AMÉLIORÉ ==================== */
        
        /* Tablettes et petits écrans */
        @media (max-width: 1200px) {
            .admin-layout {
                grid-template-columns: 220px 1fr;
            }

            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-md);
            }

            .data-table {
                font-size: 0.9rem;
            }

            .data-table th,
            .data-table td {
                padding: var(--space-sm) var(--space-md);
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .mobile-toggle {
                display: flex;
            }

            .sidebar {
                position: fixed;
                left: -280px;
                width: 280px;
                transition: left 0.3s ease;
                z-index: 2000;
                height: 100vh;
                overflow-y: auto;
            }

            .sidebar.active {
                left: 0;
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.5);
            }

            .admin-layout {
                grid-template-columns: 1fr;
            }

            .main-content {
                margin-left: 0;
                padding: var(--space-lg) var(--space-md);
                padding-top: 70px;
            }

            .page-header h1 {
                font-size: 1.3rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: var(--space-md);
            }

            .card {
                margin-bottom: var(--space-lg);
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-md);
            }

            .card-header .btn {
                width: 100%;
            }

            .card-body {
                padding: var(--space-md);
            }

            /* Tables responsive */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 calc(-1 * var(--space-md));
                padding: 0 var(--space-md);
            }

            .data-table {
                font-size: 0.8rem;
                min-width: 600px;
            }

            .data-table th,
            .data-table td {
                padding: var(--space-sm);
                white-space: nowrap;
            }

            /* Actions buttons */
            .actions {
                display: flex;
                flex-direction: row;
                gap: var(--space-xs);
                flex-wrap: wrap;
            }

            .actions .btn-sm {
                font-size: 0.75rem;
                padding: var(--space-xs) var(--space-sm);
            }

            /* Forms */
            .form-group {
                margin-bottom: var(--space-md);
            }

            .form-input,
            .form-select,
            .form-textarea {
                font-size: 16px; /* Évite le zoom sur iOS */
            }

            /* Buttons */
            .btn {
                width: 100%;
                justify-content: center;
                font-size: 0.9rem;
            }

            .btn-sm {
                width: auto;
                font-size: 0.8rem;
            }

            /* Modales */
            .modal-content {
                max-width: 95vw;
                margin: var(--space-md);
            }

            .modal-header {
                padding: var(--space-md);
            }

            .modal-body {
                padding: var(--space-md);
            }

            .modal-footer {
                padding: var(--space-md);
                flex-direction: column;
            }

            .modal-footer .btn {
                width: 100%;
            }

            /* Badges et tags */
            .badge {
                font-size: 0.7rem;
                padding: 2px 6px;
            }

            /* Activity log */
            .activity-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .activity-icon {
                margin-bottom: var(--space-sm);
            }

            /* Alerts */
            .alert {
                font-size: 0.85rem;
                padding: var(--space-sm) var(--space-md);
            }

            /* Sidebar footer */
            .sidebar-footer {
                position: sticky;
                bottom: 0;
                left: var(--space-lg);
                right: var(--space-lg);
                background: var(--bg-secondary);
                padding-top: var(--space-md);
            }
        }

        /* Très petits écrans */
        @media (max-width: 480px) {
            .main-content {
                padding: var(--space-md) var(--space-sm);
                padding-top: 65px;
            }

            .page-header {
                margin-bottom: var(--space-lg);
            }

            .page-header h1 {
                font-size: 1.2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: var(--space-md);
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .card-header h3 {
                font-size: 1rem;
            }

            .form-label {
                font-size: 0.8rem;
            }

            .data-table {
                font-size: 0.75rem;
            }

            .project-tech,
            .post-tags {
                flex-wrap: wrap;
            }

            .modal-content {
                max-width: 100vw;
                margin: 0;
                border-radius: 0;
                max-height: 100vh;
            }

            /* Sidebar plus compacte sur très petits écrans */
            .sidebar {
                width: 260px;
                left: -260px;
            }

            .nav-menu {
                gap: var(--space-xs);
            }

            .nav-item a {
                padding: var(--space-xs) var(--space-sm);
                font-size: 0.85rem;
            }
        }

        /* Landscape mobile */
        @media (max-width: 768px) and (orientation: landscape) {
            .main-content {
                padding-top: 60px;
            }

            .modal-content {
                max-height: 85vh;
            }
        }

        /* Print styles */
        @media print {
            .sidebar,
            .mobile-toggle,
            .mobile-overlay,
            .btn,
            .alert {
                display: none !important;
            }

            .main-content {
                margin-left: 0;
                padding: 0;
            }

            .card {
                page-break-inside: avoid;
            }
        }

        /* ====== AMÉLIORATIONS LAYOUT HORIZONTAL ====== */
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.1);
        }

        .form-full-width {
            grid-column: 1 / -1 !important;
        }

        /* Conteneurs de cards plus larges */
        .card {
            width: 100%;
            max-width: none;
        }

        .card-body {
            width: 100%;
        }

        /* Tables responsive avec scroll horizontal si nécessaire */
        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            min-width: 800px;
        }

        /* Amélioration du responsive vertical et horizontal */
        @media (min-width: 1400px) {
            .main-content {
                padding: var(--space-2xl);
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .form-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1600px) {
            .main-content {
                padding: var(--space-2xl) var(--space-3xl);
            }
        }

        /* Ajustements pour très petits écrans verticaux */
        @media (max-height: 600px) {
            .modal-content {
                max-height: 85vh;
            }

            .sidebar {
                font-size: 0.9rem;
            }

            .nav-item a {
                padding: var(--space-xs) var(--space-md);
            }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="mobile-overlay" onclick="toggleSidebar()"></div>

    <div class="admin-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-logo" target="_blank">
                    <i class="fas fa-terminal"></i> ~/admin
                </a>
                <div class="sidebar-user">
                    <div style="color: var(--text-primary); font-weight: 500;"><?= e($_SESSION['username']) ?></div>
                    <div style="color: var(--text-muted); font-size: 0.75rem;">[<?= e($_SESSION['user_role']) ?>]</div>
                </div>
            </div>

            <nav>
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <ul class="nav-list">
                        <li class="nav-item"><a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="?tab=settings" class="<?= $tab === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Paramètres</a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Portfolio</div>
                    <ul class="nav-list">
                        <li class="nav-item"><a href="?tab=about" class="<?= $tab === 'about' ? 'active' : '' ?>"><i class="fas fa-user"></i> À propos</a></li>
                        <li class="nav-item"><a href="?tab=skills" class="<?= $tab === 'skills' ? 'active' : '' ?>"><i class="fas fa-code"></i> Compétences</a></li>
                        <li class="nav-item"><a href="?tab=projects" class="<?= $tab === 'projects' ? 'active' : '' ?>"><i class="fas fa-folder"></i> Projets</a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Veille</div>
                    <ul class="nav-list">
                        <li class="nav-item"><a href="?tab=veille" class="<?= $tab === 'veille' ? 'active' : '' ?>"><i class="fas fa-newspaper"></i> Articles</a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Compte</div>
                    <ul class="nav-list">
                        <li class="nav-item"><a href="?tab=account" class="<?= $tab === 'account' ? 'active' : '' ?>"><i class="fas fa-key"></i> Sécurité</a></li>
                    </ul>
                </div>
            </nav>

            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </aside>

        <main class="main-content">
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= e($message) ?>
            </div>
            <?php endif; ?>

            <!-- DASHBOARD -->
            <div class="<?= $tab === 'dashboard' ? '' : 'tab-hidden' ?>">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-chart-pie"></i> Dashboard</h1>
                    <a href="../index.php" target="_blank" class="btn"><i class="fas fa-external-link-alt"></i> Voir le site</a>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div style="font-size: 2rem; color: var(--accent-primary); margin-bottom: 0.5rem;"><i class="fas fa-folder"></i></div>
                        <div class="stat-value"><?= $totalProjects ?></div>
                        <div style="color: var(--text-muted); margin-top: 0.5rem;">Projets actifs</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 2rem; color: var(--accent-secondary); margin-bottom: 0.5rem;"><i class="fas fa-newspaper"></i></div>
                        <div class="stat-value"><?= $totalPosts ?></div>
                        <div style="color: var(--text-muted); margin-top: 0.5rem;">Articles publiés</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 2rem; color: var(--accent-warning); margin-bottom: 0.5rem;"><i class="fas fa-eye"></i></div>
                        <div class="stat-value"><?= number_format($totalViews) ?></div>
                        <div style="color: var(--text-muted); margin-top: 0.5rem;">Vues totales</div>
                    </div>
                </div>
            </div>

            <!-- PARAMÈTRES -->
            <div class="<?= $tab === 'settings' ? '' : 'tab-hidden' ?>">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-cog"></i> Paramètres</h1>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_settings">

                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Informations générales</h3></div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Nom du site</label>
                                    <input type="text" name="settings[site_name]" class="form-input" value="<?= e($settings['site_name']['setting_value'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Titre / Profession</label>
                                    <input type="text" name="settings[site_title]" class="form-input" value="<?= e($settings['site_title']['setting_value'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Contact & Réseaux</h3></div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="settings[contact_email]" class="form-input" value="<?= e($settings['contact_email']['setting_value'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Téléphone</label>
                                    <input type="text" name="settings[contact_phone]" class="form-input" value="<?= e($settings['contact_phone']['setting_value'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GitHub URL</label>
                                    <input type="url" name="settings[github_url]" class="form-input" value="<?= e($settings['github_url']['setting_value'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">LinkedIn URL</label>
                                    <input type="url" name="settings[linkedin_url]" class="form-input" value="<?= e($settings['linkedin_url']['setting_value'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>

            <!-- À PROPOS -->
            <div class="<?= $tab === 'about' ? '' : 'tab-hidden' ?>">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-user"></i> À propos</h1>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_about">

                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Texte de présentation</label>
                                <textarea name="about_text" class="form-textarea"><?= e($about['about_text'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                        </div>
                    </div>
                </form>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Points forts</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addFeatureModal')"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead><tr><th>Icône</th><th>Texte</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($features as $f): ?>
                                <tr>
                                    <td><i class="<?= e($f['icon']) ?>"></i></td>
                                    <td><?= e($f['text']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn btn-sm" onclick='editFeature(<?= json_encode($f) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_feature">
                                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Statistiques</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addStatModal')"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead><tr><th>Valeur</th><th>Label</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($stats as $s): ?>
                                <tr>
                                    <td><strong style="color: var(--accent-primary);"><?= e($s['value']) ?></strong></td>
                                    <td><?= e($s['label']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn btn-sm" onclick='editStat(<?= json_encode($s) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_stat">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- COMPÉTENCES -->
            <div class="<?= $tab === 'skills' ? '' : 'tab-hidden' ?>">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-code"></i> Compétences</h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Catégories</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addSkillCategoryModal')"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead><tr><th>Nom</th><th>Icône</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($skillCategories as $sc): ?>
                                <tr>
                                    <td><?= e($sc['name']) ?></td>
                                    <td><i class="<?= e($sc['icon']) ?>"></i></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn btn-sm" onclick='editSkillCategory(<?= json_encode($sc) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_skill_category">
                                                <input type="hidden" name="id" value="<?= $sc['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Compétences</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addSkillModal')"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead><tr><th>Nom</th><th>Catégorie</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($skills as $sk): ?>
                                <tr>
                                    <td><code style="color: var(--accent-primary);"><?= e($sk['name']) ?></code></td>
                                    <td><?= e($sk['category_name']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn btn-sm" onclick='editSkill(<?= json_encode($sk) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_skill">
                                                <input type="hidden" name="id" value="<?= $sk['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PROJETS -->
            <div class="<?= $tab === 'projects' ? '' : 'tab-hidden' ?>">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-folder"></i> Projets</h1>
                    <button type="button" class="btn btn-primary" onclick="openModal('addProjectModal')"><i class="fas fa-plus"></i> Nouveau</button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="data-table">
                            <thead><tr><th>Titre</th><th>Type</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($projects as $p): ?>
                                <tr>
                                    <td><strong><?= e($p['title']) ?></strong></td>
                                    <td><code><?= e($p['type']) ?></code></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn btn-sm" onclick='editProject(<?= json_encode($p) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_project">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VEILLE -->
            <div class="<?= $tab === 'veille' ? '' : 'tab-hidden' ?>">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-newspaper"></i> Articles de veille</h1>
                    <button type="button" class="btn btn-primary" onclick="openModal('addVeillePostModal')"><i class="fas fa-plus"></i> Nouvel article</button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Articles</h3>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead><tr><th>Titre</th><th>Catégorie</th><th>Vues</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($veillePosts as $vp): ?>
                                <tr>
                                    <td><?= e(truncate($vp['title'], 50)) ?></td>
                                    <td><?= e($vp['category_name'] ?? '-') ?></td>
                                    <td><?= number_format($vp['views']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="../veille.php?article=<?= e($vp['slug']) ?>" target="_blank" class="btn btn-sm"><i class="fas fa-eye"></i></a>
                                            <button type="button" class="btn btn-sm" onclick='editVeillePost(<?= json_encode($vp) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_veille_post">
                                                <input type="hidden" name="id" value="<?= $vp['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Catégories</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addVeilleCategoryModal')"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead><tr><th>Nom</th><th>Icône</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($veilleCategories as $vc): ?>
                                <tr>
                                    <td><?= e($vc['name']) ?></td>
                                    <td><i class="<?= e($vc['icon']) ?>" style="color: <?= e($vc['color']) ?>"></i></td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn btn-sm" onclick='editVeilleCategory(<?= json_encode($vc) ?>)'><i class="fas fa-edit"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete_veille_category">
                                                <input type="hidden" name="id" value="<?= $vc['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tags</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addVeilleTagModal')"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body">
                        <?php foreach ($veilleTags as $vt): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="delete_veille_tag">
                            <input type="hidden" name="id" value="<?= $vt['id'] ?>">
                            <span class="badge" style="cursor:pointer; margin: 2px;" onclick="if(confirm('Supprimer?')) this.parentElement.submit();">
                                #<?= e($vt['name']) ?> <i class="fas fa-times" style="margin-left: 5px;"></i>
                            </span>
                        </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- COMPTE -->
            <div class="<?= $tab === 'account' ? '' : 'tab-hidden' ?>">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-key"></i> Sécurité</h1>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Changer le mot de passe</h3></div>
                    <div class="card-body">
                        <form method="POST" style="max-width: 400px;">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <label class="form-label">Mot de passe actuel</label>
                                <input type="password" name="current_password" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-input" required minlength="8">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirmer</label>
                                <input type="password" name="confirm_password" class="form-input" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Mettre à jour</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== MODALES ==================== -->

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.querySelector('.mobile-overlay').classList.toggle('active');
        }

        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function editFeature(d) {
            document.getElementById('editFeatureId').value = d.id;
            document.getElementById('editFeatureIcon').value = d.icon;
            document.getElementById('editFeatureText').value = d.text;
            document.getElementById('editFeatureActive').checked = d.is_active == 1;
            openModal('editFeatureModal');
        }

        function editStat(d) {
            document.getElementById('editStatId').value = d.id;
            document.getElementById('editStatValue').value = d.value;
            document.getElementById('editStatLabel').value = d.label;
            document.getElementById('editStatActive').checked = d.is_active == 1;
            openModal('editStatModal');
        }

        function editSkillCategory(d) {
            document.getElementById('editSkillCategoryId').value = d.id;
            document.getElementById('editSkillCategoryName').value = d.name;
            document.getElementById('editSkillCategoryIcon').value = d.icon;
            document.getElementById('editSkillCategoryColor').value = d.icon_color || '';
            document.getElementById('editSkillCategoryActive').checked = d.is_active == 1;
            openModal('editSkillCategoryModal');
        }

        function editSkill(d) {
            document.getElementById('editSkillId').value = d.id;
            document.getElementById('editSkillName').value = d.name;
            document.getElementById('editSkillCategory').value = d.category_id;
            document.getElementById('editSkillActive').checked = d.is_active == 1;
            openModal('editSkillModal');
        }

        function editProject(d) {
            document.getElementById('editProjectId').value = d.id;
            document.getElementById('editProjectTitle').value = d.title;
            document.getElementById('editProjectType').value = d.type;
            document.getElementById('editProjectDescription').value = d.description;
            document.getElementById('editProjectIcon').value = d.icon;
            document.getElementById('editProjectDemo').value = d.demo_url || '';
            document.getElementById('editProjectGithub').value = d.github_url || '';
            document.getElementById('editProjectTags').value = d.tags ? d.tags.join(', ') : '';
            document.getElementById('editProjectActive').checked = d.is_active == 1;
            openModal('editProjectModal');
        }

        function editVeilleCategory(d) {
            document.getElementById('editVeilleCategoryId').value = d.id;
            document.getElementById('editVeilleCategoryName').value = d.name;
            document.getElementById('editVeilleCategoryIcon').value = d.icon;
            document.getElementById('editVeilleCategoryColor').value = d.color;
            document.getElementById('editVeilleCategoryActive').checked = d.is_active == 1;
            openModal('editVeilleCategoryModal');
        }

        function editVeillePost(d) {
            document.getElementById('editVeillePostId').value = d.id;
            document.getElementById('editVeillePostTitle').value = d.title;
            document.getElementById('editVeillePostCategory').value = d.category_id || '';
            document.getElementById('editVeillePostSourceName').value = d.source_name || '';
            document.getElementById('editVeillePostSourceUrl').value = d.source_url || '';
            document.getElementById('editVeillePostExcerpt').value = d.excerpt || '';
            document.getElementById('editVeillePostContent').value = d.content;
            document.getElementById('editVeillePostPublished').checked = d.is_published == 1;
            document.getElementById('editVeillePostFeatured').checked = d.is_featured == 1;
            openModal('editVeillePostModal');
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('active');
            });
        });

        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
