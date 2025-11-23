<?php
/**
 * Portfolio Principal - David Sangouard
 */
require_once 'config.php';
initSecureSession();

$pdo = getDB();

// Récupérer les paramètres du site
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Récupérer la section À propos
$stmt = $pdo->query("SELECT * FROM about_section LIMIT 1");
$about = $stmt->fetch();

// Récupérer les features À propos
$stmt = $pdo->query("SELECT * FROM about_features WHERE is_active = 1 ORDER BY sort_order");
$features = $stmt->fetchAll();

// Récupérer les stats
$stmt = $pdo->query("SELECT * FROM stats WHERE is_active = 1 ORDER BY sort_order");
$stats = $stmt->fetchAll();

// Récupérer les catégories de compétences avec leurs skills
$stmt = $pdo->query("SELECT * FROM skill_categories WHERE is_active = 1 ORDER BY sort_order");
$skillCategories = $stmt->fetchAll();

foreach ($skillCategories as &$category) {
    $stmtSkills = $pdo->prepare("SELECT * FROM skills WHERE category_id = ? AND is_active = 1 ORDER BY sort_order");
    $stmtSkills->execute([$category['id']]);
    $category['skills'] = $stmtSkills->fetchAll();
}

// Récupérer les projets avec leurs tags
$stmt = $pdo->query("SELECT * FROM projects WHERE is_active = 1 ORDER BY sort_order");
$projects = $stmt->fetchAll();

foreach ($projects as &$project) {
    $stmtTags = $pdo->prepare("SELECT tag_name FROM project_tags WHERE project_id = ? ORDER BY sort_order");
    $stmtTags->execute([$project['id']]);
    $project['tags'] = $stmtTags->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($settings['site_name'] ?? 'Portfolio') ?> - <?= e($settings['site_title'] ?? '') ?></title>
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

        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-3xl) var(--space-lg);
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-text {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s ease forwards;
        }

        .hero-badge {
            display: inline-block;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            padding: var(--space-xs) var(--space-md);
            border-radius: 4px;
            color: var(--accent-primary);
            font-family: var(--font-mono);
            font-size: 0.8rem;
            margin-bottom: var(--space-lg);
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            margin-bottom: var(--space-md);
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .hero-title .accent {
            color: var(--accent-primary);
            font-family: var(--font-mono);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: var(--space-2xl);
            line-height: 1.5;
        }

        .hero-buttons {
            display: flex;
            gap: var(--space-lg);
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-md) var(--space-xl);
            border: 1px solid var(--border-primary);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9rem;
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
            transform: translateY(-1px);
        }

        .section {
            padding: var(--space-3xl) var(--space-lg);
            max-width: 1200px;
            margin: 0 auto;
            min-height: 50vh;
        }

        .section-header {
            text-align: center;
            margin-bottom: var(--space-3xl);
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 600;
            margin-bottom: var(--space-md);
            color: var(--text-primary);
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--space-lg);
        }

        .tech-box {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-xl);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .tech-box:hover {
            border-color: var(--accent-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .tech-box-header {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .tech-icon {
            width: 48px;
            height: 48px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-primary);
            font-size: 1.2rem;
        }

        .tech-icon.design { color: #a5a5ff; }
        .tech-icon.backend { color: var(--accent-secondary); }
        .tech-icon.devops { color: var(--accent-warning); }

        .tech-box h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .tech-tags {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-sm);
        }

        .tech-tag {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-secondary);
            color: var(--text-secondary);
            padding: var(--space-xs) var(--space-sm);
            border-radius: 3px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            transition: all 0.2s ease;
        }

        .tech-tag:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            transform: translateY(-1px);
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--space-xl);
        }

        .project-card {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .project-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-3px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.3);
        }

        .project-header {
            padding: var(--space-xl);
            border-bottom: 1px solid var(--border-primary);
        }

        .project-meta {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .project-icon {
            width: 40px;
            height: 40px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-primary);
        }

        .project-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
        }

        .project-type {
            color: var(--accent-primary);
            font-family: var(--font-mono);
            font-size: 0.8rem;
        }

        .project-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .project-footer {
            padding: var(--space-lg) var(--space-xl);
            background: var(--bg-secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-md);
        }

        .project-tech {
            display: flex;
            gap: var(--space-sm);
        }

        .project-tech-tag {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            padding: var(--space-xs) var(--space-sm);
            border-radius: 3px;
            font-family: var(--font-mono);
            font-size: 0.7rem;
        }

        .project-links {
            display: flex;
            gap: var(--space-md);
        }

        .project-link {
            color: var(--accent-primary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .project-link:hover {
            color: var(--text-primary);
        }

        .about-grid {
            display: flex;
            flex-direction: column;
            gap: var(--space-2xl);
            align-items: center;
        }

        .about-content {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-2xl);
            width: 100%;
            max-width: 800px;
        }

        .stats-card {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-xl);
            width: 100%;
            max-width: 800px;
        }

        .about-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-lg);
        }

        .about-text {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: var(--space-xl);
        }

        .feature-list {
            list-style: none;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
            padding: var(--space-md);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-secondary);
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .feature-item:hover {
            border-color: var(--accent-primary);
            transform: translateX(4px);
        }

        .feature-icon {
            color: var(--accent-primary);
            font-size: 1.1rem;
        }

        .feature-text {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--space-lg);
        }

        .stat {
            text-align: center;
            padding: var(--space-lg);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-secondary);
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .stat:hover {
            border-color: var(--accent-primary);
            transform: translateY(-2px);
        }

        .stat-number {
            font-family: var(--font-mono);
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-primary);
            display: block;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: var(--space-sm);
        }

        .contact {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: 6px;
            padding: var(--space-3xl);
            text-align: center;
            margin: var(--space-3xl) 0;
        }

        .contact-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: var(--space-lg);
            color: var(--text-primary);
        }

        .contact-text {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: var(--space-2xl);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .contact-buttons {
            display: flex;
            gap: var(--space-lg);
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: var(--space-2xl);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: var(--space-lg);
            flex-wrap: wrap;
        }

        .social-link {
            width: 44px;
            height: 44px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .social-link:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            transform: translateY(-2px);
        }

        .footer {
            text-align: center;
            padding: var(--space-2xl);
            border-top: 1px solid var(--border-primary);
            color: var(--text-muted);
            font-family: var(--font-mono);
            font-size: 0.8rem;
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

            .hero-content {
                text-align: center;
            }

            .about-grid {
                gap: var(--space-xl);
            }

            .tech-grid,
            .projects-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-buttons,
            .contact-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }

            .project-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .contact-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @keyframes fadeInUp {
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
            <a href="#" class="logo">~/<?= e(strtolower(str_replace(' ', '-', $settings['site_name'] ?? 'portfolio'))) ?></a>
            <ul class="nav-menu" id="navMenu">
                <li><a href="#about" class="nav-link">about</a></li>
                <li><a href="#skills" class="nav-link">skills</a></li>
                <li><a href="#projects" class="nav-link">projects</a></li>
                <li><a href="veille.php" class="nav-link">veille</a></li>
                <li><a href="#contact" class="nav-link">contact</a></li>
            </ul>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-badge"><?= e($settings['hero_badge'] ?? '[Developer]') ?></div>
                <h1 class="hero-title"><?= e($settings['hero_name'] ?? 'Votre') ?> <span class="accent"><?= e($settings['hero_name_accent'] ?? 'Nom') ?></span></h1>
                <p class="hero-subtitle"><?= e($settings['hero_subtitle'] ?? '') ?></p>
                <div class="hero-buttons">
                    <a href="#projects" class="btn btn-primary">
                        <i class="fas fa-code"></i>
                        Voir projets
                    </a>
                    <a href="#contact" class="btn">
                        <i class="fas fa-envelope"></i>
                        Contact
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="section">
        <div class="section-header">
            <h2 class="section-title"><?= e($about['section_title'] ?? 'À propos') ?></h2>
            <p class="section-subtitle"><?= e($about['section_subtitle'] ?? '') ?></p>
        </div>

        <div class="about-grid">
            <div class="about-content">
                <h3 class="about-title"><?= e($about['about_title'] ?? 'Mon approche') ?></h3>
                <p class="about-text"><?= e($about['about_text'] ?? '') ?></p>
                
                <?php if (!empty($features)): ?>
                <ul class="feature-list">
                    <?php foreach ($features as $feature): ?>
                    <li class="feature-item">
                        <i class="<?= e($feature['icon']) ?> feature-icon"></i>
                        <span class="feature-text"><?= e($feature['text']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <?php if (!empty($stats)): ?>
            <div class="stats-card">
                <div class="stats-grid">
                    <?php foreach ($stats as $stat): ?>
                    <div class="stat">
                        <span class="stat-number"><?= e($stat['value']) ?></span>
                        <span class="stat-label"><?= e($stat['label']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="skills" class="section">
        <div class="section-header">
            <h2 class="section-title">Compétences techniques</h2>
            <p class="section-subtitle">Technologies et outils que j'utilise pour créer des solutions performantes</p>
        </div>

        <div class="tech-grid">
            <?php foreach ($skillCategories as $category): ?>
            <div class="tech-box">
                <div class="tech-box-header">
                    <div class="tech-icon <?= e($category['icon_color']) ?>">
                        <i class="<?= e($category['icon']) ?>"></i>
                    </div>
                    <h3><?= e($category['name']) ?></h3>
                </div>
                <div class="tech-tags">
                    <?php foreach ($category['skills'] as $skill): ?>
                    <span class="tech-tag"><?= e($skill['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="projects" class="section">
        <div class="section-header">
            <h2 class="section-title">Projets sélectionnés</h2>
            <p class="section-subtitle">Une sélection de mes réalisations récentes combinant design et développement</p>
        </div>

        <div class="projects-grid">
            <?php foreach ($projects as $project): ?>
            <div class="project-card">
                <div class="project-header">
                    <div class="project-meta">
                        <div class="project-icon">
                            <i class="<?= e($project['icon']) ?>"></i>
                        </div>
                        <div>
                            <h3 class="project-title"><?= e($project['title']) ?></h3>
                            <div class="project-type"><?= e($project['type']) ?></div>
                        </div>
                    </div>
                    <p class="project-description"><?= e($project['description']) ?></p>
                </div>
                <div class="project-footer">
                    <div class="project-tech">
                        <?php foreach ($project['tags'] as $tag): ?>
                        <span class="project-tech-tag"><?= e($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="project-links">
                        <?php if (!empty($project['demo_url'])): ?>
                        <a href="<?= e($project['demo_url']) ?>" class="project-link" target="_blank">
                            <i class="fas fa-external-link-alt"></i> demo
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($project['github_url'])): ?>
                        <a href="<?= e($project['github_url']) ?>" class="project-link" target="_blank">
                            <i class="fab fa-github"></i> code
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="contact" class="section">
        <div class="contact">
            <h2 class="contact-title"><?= e($settings['contact_title'] ?? 'Collaborons ensemble') ?></h2>
            <p class="contact-text"><?= e($settings['contact_text'] ?? '') ?></p>
            
            <div class="contact-buttons">
                <a href="mailto:<?= e($settings['contact_email'] ?? '') ?>" class="btn btn-primary">
                    <i class="fas fa-envelope"></i>
                    Envoyer email
                </a>
                <a href="tel:<?= e($settings['contact_phone'] ?? '') ?>" class="btn">
                    <i class="fas fa-phone"></i>
                    Appeler
                </a>
            </div>
            
            <div class="social-links">
                <?php if (!empty($settings['github_url'])): ?>
                <a href="<?= e($settings['github_url']) ?>" class="social-link" target="_blank">
                    <i class="fab fa-github"></i>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['linkedin_url'])): ?>
                <a href="<?= e($settings['linkedin_url']) ?>" class="social-link" target="_blank">
                    <i class="fab fa-linkedin"></i>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['dribbble_url'])): ?>
                <a href="<?= e($settings['dribbble_url']) ?>" class="social-link" target="_blank">
                    <i class="fab fa-dribbble"></i>
                </a>
                <?php endif; ?>
                <?php if (!empty($settings['behance_url'])): ?>
                <a href="<?= e($settings['behance_url']) ?>" class="social-link" target="_blank">
                    <i class="fab fa-behance"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

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

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    navMenu.classList.remove('active');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            });
        });

        // Amélioration de la détection de la section active
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

        function updateActiveNavLink() {
            const scrollPosition = window.scrollY + 100; // Offset pour la navbar
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;

            // Si on est proche du bas de la page, activer contact
            if (windowHeight + window.scrollY >= documentHeight - 50) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#contact') {
                        link.classList.add('active');
                    }
                });
                return;
            }

            // Sinon, détecter la section visible
            let currentSection = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    currentSection = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + currentSection) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', updateActiveNavLink);
        window.addEventListener('load', updateActiveNavLink);

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

        document.querySelectorAll('.tech-box, .project-card, .about-content, .stats-card').forEach(el => {
            el.classList.add('fade-in');
            observer.observe(el);
        });

        document.querySelectorAll('.tech-tag').forEach(tag => {
            tag.addEventListener('mouseenter', () => {
                tag.style.transform = 'translateY(-1px)';
            });
            
            tag.addEventListener('mouseleave', () => {
                tag.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
