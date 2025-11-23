<?php
/**
 * Page Veille Informatique - David Sangouard
 */
require_once 'config.php';
initSecureSession();

$pdo = getDB();

// Récupérer les paramètres du site
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Récupérer les catégories
$stmt = $pdo->query("SELECT * FROM veille_categories WHERE is_active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

// Filtre par catégorie
$categoryFilter = isset($_GET['category']) ? sanitize($_GET['category']) : null;

// Recherche
$search = isset($_GET['search']) ? sanitize($_GET['search']) : null;

// Construire la requête
$sql = "SELECT vp.*, vc.name as category_name, vc.color as category_color, vc.icon as category_icon,
        u.username as author_name
        FROM veille_posts vp
        LEFT JOIN veille_categories vc ON vp.category_id = vc.id
        LEFT JOIN users u ON vp.author_id = u.id
        WHERE vp.is_published = 1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND vc.slug = ?";
    $params[] = $categoryFilter;
}

if ($search) {
    $sql .= " AND (vp.title LIKE ? OR vp.excerpt LIKE ? OR vp.content LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY vp.is_featured DESC, vp.published_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Récupérer les tags pour chaque post
foreach ($posts as &$post) {
    $stmtTags = $pdo->prepare("
        SELECT vt.name, vt.slug 
        FROM veille_tags vt 
        JOIN veille_post_tags vpt ON vt.id = vpt.tag_id 
        WHERE vpt.post_id = ?
    ");
    $stmtTags->execute([$post['id']]);
    $post['tags'] = $stmtTags->fetchAll();
}

// Page de détail d'un article
$viewPost = null;
if (isset($_GET['article'])) {
    $slug = sanitize($_GET['article']);
    $stmt = $pdo->prepare("
        SELECT vp.*, vc.name as category_name, vc.color as category_color, vc.icon as category_icon,
        u.username as author_name
        FROM veille_posts vp
        LEFT JOIN veille_categories vc ON vp.category_id = vc.id
        LEFT JOIN users u ON vp.author_id = u.id
        WHERE vp.slug = ? AND vp.is_published = 1
    ");
    $stmt->execute([$slug]);
    $viewPost = $stmt->fetch();
    
    if ($viewPost) {
        // Incrémenter les vues
        $pdo->prepare("UPDATE veille_posts SET views = views + 1 WHERE id = ?")->execute([$viewPost['id']]);
        
        // Récupérer les tags
        $stmtTags = $pdo->prepare("
            SELECT vt.name, vt.slug 
            FROM veille_tags vt 
            JOIN veille_post_tags vpt ON vt.id = vpt.tag_id 
            WHERE vpt.post_id = ?
        ");
        $stmtTags->execute([$viewPost['id']]);
        $viewPost['tags'] = $stmtTags->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veille Informatique - <?= e($settings['site_name'] ?? 'Portfolio') ?></title>
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
            --accent-purple: #a5a5ff;
            --border-primary: #30363d;
            --border-secondary: #21262d;
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            --space-3xl: 4rem;
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
            overflow-x: hidden;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(13, 17, 23, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-primary);
            z-index: 1000;
            padding: 0 var(--space-lg);
            transition: all 0.2s ease;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }

        .logo {
            font-family: var(--font-mono);
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent-primary);
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: var(--space-xl);
            list-style: none;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 400;
            font-size: 0.9rem;
            transition: color 0.2s ease;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--accent-primary);
            transition: width 0.2s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--text-primary);
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
        }

        .page-header {
            padding: calc(64px + var(--space-3xl)) var(--space-lg) var(--space-2xl);
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            text-align: center;
        }

        .page-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: var(--space-md);
            color: var(--text-primary);
        }

        .page-title .accent {
            color: var(--accent-primary);
            font-family: var(--font-mono);
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto var(--space-2xl);
        }

        .search-bar {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: var(--space-md) var(--space-xl);
            padding-left: 3rem;
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.1);
        }

        .search-icon {
            position: absolute;
            left: var(--space-lg);
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-2xl) var(--space-lg);
        }

        .content-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: var(--space-2xl);
        }

        .sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .sidebar-section {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-lg);
            margin-bottom: var(--space-lg);
        }

        .sidebar-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-md);
            font-family: var(--font-mono);
        }

        .category-list {
            list-style: none;
        }

        .category-item {
            margin-bottom: var(--space-sm);
        }

        .category-link {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-sm) var(--space-md);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .category-link:hover,
        .category-link.active {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .category-link i {
            font-size: 0.8rem;
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-xl);
        }

        .post-card {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .post-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-3px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.3);
        }

        .post-card.featured {
            border-color: var(--accent-warning);
        }

        .post-card.featured::before {
            content: 'À la une';
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            background: var(--accent-warning);
            color: var(--bg-primary);
            padding: var(--space-xs) var(--space-sm);
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            font-family: var(--font-mono);
        }

        .post-header {
            padding: var(--space-xl);
            position: relative;
        }

        .post-category {
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
            padding: var(--space-xs) var(--space-sm);
            border-radius: 3px;
            font-size: 0.75rem;
            font-family: var(--font-mono);
            margin-bottom: var(--space-md);
            background: var(--bg-tertiary);
        }

        .post-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-md);
            line-height: 1.3;
        }

        .post-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .post-title a:hover {
            color: var(--accent-primary);
        }

        .post-excerpt {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: var(--space-lg);
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .post-meta-item {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }

        .post-footer {
            padding: var(--space-md) var(--space-xl);
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-primary);
        }

        .post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-sm);
        }

        .post-tag {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            padding: var(--space-xs) var(--space-sm);
            border-radius: 3px;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .post-tag:hover {
            color: var(--accent-primary);
            background: rgba(88, 166, 255, 0.1);
        }

        /* Article détail */
        .article-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .article-back {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            color: var(--accent-primary);
            text-decoration: none;
            margin-bottom: var(--space-xl);
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .article-back:hover {
            color: var(--text-primary);
        }

        .article-header {
            margin-bottom: var(--space-2xl);
        }

        .article-title {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-lg);
            line-height: 1.2;
        }

        .article-info {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-lg);
            color: var(--text-secondary);
            font-size: 0.9rem;
            padding-bottom: var(--space-xl);
            border-bottom: 1px solid var(--border-primary);
        }

        .article-info-item {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .article-content {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-2xl);
        }

        .article-content h2,
        .article-content h3,
        .article-content h4 {
            color: var(--text-primary);
            margin: var(--space-xl) 0 var(--space-md);
        }

        .article-content h2 { font-size: 1.5rem; }
        .article-content h3 { font-size: 1.3rem; }
        .article-content h4 { font-size: 1.1rem; }

        .article-content p {
            color: var(--text-secondary);
            margin-bottom: var(--space-lg);
            line-height: 1.8;
        }

        .article-content a {
            color: var(--accent-primary);
        }

        .article-content code {
            background: var(--bg-tertiary);
            padding: var(--space-xs) var(--space-sm);
            border-radius: 3px;
            font-family: var(--font-mono);
            font-size: 0.9em;
        }

        .article-content pre {
            background: var(--bg-tertiary);
            padding: var(--space-lg);
            border-radius: 6px;
            overflow-x: auto;
            margin: var(--space-lg) 0;
        }

        .article-content pre code {
            background: none;
            padding: 0;
        }

        .article-content ul,
        .article-content ol {
            margin: var(--space-lg) 0;
            padding-left: var(--space-xl);
            color: var(--text-secondary);
        }

        .article-content li {
            margin-bottom: var(--space-sm);
        }

        .article-content blockquote {
            border-left: 3px solid var(--accent-primary);
            padding-left: var(--space-lg);
            margin: var(--space-lg) 0;
            color: var(--text-secondary);
            font-style: italic;
        }

        .article-source {
            margin-top: var(--space-2xl);
            padding-top: var(--space-xl);
            border-top: 1px solid var(--border-primary);
        }

        .article-source a {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            color: var(--accent-primary);
            text-decoration: none;
        }

        .article-tags {
            margin-top: var(--space-xl);
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-sm);
        }

        .no-results {
            text-align: center;
            padding: var(--space-3xl);
            color: var(--text-secondary);
        }

        .no-results i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: var(--space-lg);
        }

        .footer {
            text-align: center;
            padding: var(--space-2xl);
            border-top: 1px solid var(--border-primary);
            color: var(--text-muted);
            font-family: var(--font-mono);
            font-size: 0.8rem;
        }

        @media (max-width: 968px) {
            .content-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                display: flex;
                gap: var(--space-lg);
                overflow-x: auto;
                padding-bottom: var(--space-md);
            }

            .sidebar-section {
                flex-shrink: 0;
                min-width: 200px;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--bg-secondary);
                border: 1px solid var(--border-primary);
                border-radius: 0 0 6px 6px;
                flex-direction: column;
                padding: var(--space-lg);
            }

            .nav-menu.active {
                display: flex;
            }

            .posts-grid {
                grid-template-columns: 1fr;
            }
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

        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo">~/<?= e(strtolower(str_replace(' ', '-', $settings['site_name'] ?? 'portfolio'))) ?></a>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php#about" class="nav-link">about</a></li>
                <li><a href="index.php#skills" class="nav-link">skills</a></li>
                <li><a href="index.php#projects" class="nav-link">projects</a></li>
                <li><a href="veille.php" class="nav-link active">veille</a></li>
                <li><a href="index.php#contact" class="nav-link">contact</a></li>
            </ul>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <?php if ($viewPost): ?>
    <!-- Vue détail article -->
    <header class="page-header">
        <h1 class="page-title">Veille <span class="accent">Informatique</span></h1>
    </header>

    <main class="main-content">
        <div class="article-container">
            <a href="veille.php" class="article-back">
                <i class="fas fa-arrow-left"></i> Retour aux articles
            </a>

            <article>
                <header class="article-header">
                    <?php if ($viewPost['category_name']): ?>
                    <span class="post-category" style="color: <?= e($viewPost['category_color']) ?>">
                        <i class="<?= e($viewPost['category_icon']) ?>"></i>
                        <?= e($viewPost['category_name']) ?>
                    </span>
                    <?php endif; ?>

                    <h1 class="article-title"><?= e($viewPost['title']) ?></h1>

                    <div class="article-info">
                        <span class="article-info-item">
                            <i class="fas fa-calendar"></i>
                            <?= formatDateFr($viewPost['published_at']) ?>
                        </span>
                        <span class="article-info-item">
                            <i class="fas fa-eye"></i>
                            <?= number_format($viewPost['views']) ?> vues
                        </span>
                        <?php if ($viewPost['author_name']): ?>
                        <span class="article-info-item">
                            <i class="fas fa-user"></i>
                            <?= e($viewPost['author_name']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="article-content">
                    <?= $viewPost['content'] ?>

                    <?php if ($viewPost['source_url']): ?>
                    <div class="article-source">
                        <strong>Source :</strong>
                        <a href="<?= e($viewPost['source_url']) ?>" target="_blank" rel="noopener">
                            <?= e($viewPost['source_name'] ?? $viewPost['source_url']) ?>
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($viewPost['tags'])): ?>
                    <div class="article-tags">
                        <?php foreach ($viewPost['tags'] as $tag): ?>
                        <a href="veille.php?tag=<?= e($tag['slug']) ?>" class="post-tag">#<?= e($tag['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    </main>

    <?php else: ?>
    <!-- Vue liste articles -->
    <header class="page-header">
        <h1 class="page-title">Veille <span class="accent">Informatique</span></h1>
        <p class="page-subtitle">
            Restez informé des dernières tendances, technologies et actualités du monde de l'informatique.
        </p>

        <form class="search-bar" method="get" action="veille.php">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="search" class="search-input" placeholder="Rechercher un article..." value="<?= e($search ?? '') ?>">
        </form>
    </header>

    <main class="main-content">
        <div class="content-layout">
            <aside class="sidebar">
                <div class="sidebar-section">
                    <h3 class="sidebar-title">[catégories]</h3>
                    <ul class="category-list">
                        <li class="category-item">
                            <a href="veille.php" class="category-link <?= !$categoryFilter ? 'active' : '' ?>">
                                <i class="fas fa-th-large"></i>
                                Toutes
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                        <li class="category-item">
                            <a href="veille.php?category=<?= e($cat['slug']) ?>" 
                               class="category-link <?= $categoryFilter === $cat['slug'] ? 'active' : '' ?>"
                               style="<?= $categoryFilter === $cat['slug'] ? 'color:' . e($cat['color']) : '' ?>">
                                <i class="<?= e($cat['icon']) ?>"></i>
                                <?= e($cat['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <div class="posts-container">
                <?php if (empty($posts)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>Aucun article trouvé.</p>
                </div>
                <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                    <article class="post-card <?= $post['is_featured'] ? 'featured' : '' ?>">
                        <div class="post-header">
                            <?php if ($post['category_name']): ?>
                            <span class="post-category" style="color: <?= e($post['category_color']) ?>">
                                <i class="<?= e($post['category_icon']) ?>"></i>
                                <?= e($post['category_name']) ?>
                            </span>
                            <?php endif; ?>

                            <h2 class="post-title">
                                <a href="veille.php?article=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a>
                            </h2>

                            <p class="post-excerpt"><?= e(truncate($post['excerpt'] ?? strip_tags($post['content']), 150)) ?></p>

                            <div class="post-meta">
                                <span class="post-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <?= formatDateFr($post['published_at']) ?>
                                </span>
                                <span class="post-meta-item">
                                    <i class="fas fa-eye"></i>
                                    <?= number_format($post['views']) ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($post['tags'])): ?>
                        <div class="post-footer">
                            <div class="post-tags">
                                <?php foreach ($post['tags'] as $tag): ?>
                                <span class="post-tag">#<?= e($tag['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php endif; ?>

    <footer class="footer">
        <p><?= e($settings['footer_text'] ?? '© 2025 Portfolio') ?></p>
    </footer>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navMenu = document.getElementById('navMenu');

        mobileMenuBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Animation au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.post-card, .article-content').forEach(el => {
            el.classList.add('fade-in');
            observer.observe(el);
        });
    </script>
</body>
</html>
