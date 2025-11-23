-- =====================================================
-- BASE DE DONNÉES PORTFOLIO - DAVID SANGOUARD
-- =====================================================
-- Importez ce fichier dans phpMyAdmin
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS `portfolio_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `portfolio_db`;

-- =====================================================
-- TABLE: users (Administrateurs)
-- =====================================================
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('admin','editor') DEFAULT 'editor',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` timestamp NULL DEFAULT NULL,
    `login_attempts` int(11) DEFAULT 0,
    `locked_until` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mot de passe par défaut: Admin@123 (changez-le après la première connexion!)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@portfolio.com', '$2a$12$.Q31ygO1o.TXJ2STZDYFme.KDamJSYUfGNcpxUDKAotamJNx.Pe6W', 'admin');

-- =====================================================
-- TABLE: site_settings (Paramètres généraux)
-- =====================================================
CREATE TABLE `site_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text,
    `setting_type` enum('text','textarea','number','email','url','icon') DEFAULT 'text',
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'David Sangouard', 'text'),
('site_title', 'UI/UX Designer & Full-Stack Developer', 'text'),
('hero_badge', '[UI/UX Designer & Full-Stack Developer]', 'text'),
('hero_name', 'David', 'text'),
('hero_name_accent', 'Sangouard', 'text'),
('hero_subtitle', 'Je conçois et développe des expériences digitales avec une approche technique rigoureuse et une attention particulière aux détails d''interface.', 'textarea'),
('contact_email', 'contact@davidsangouard.com', 'email'),
('contact_phone', '+33123456789', 'text'),
('contact_title', 'Collaborons ensemble', 'text'),
('contact_text', 'Vous avez un projet en tête ? Discutons de la façon dont nous pouvons créer quelque chose d''exceptionnel ensemble.', 'textarea'),
('footer_text', '© 2025 David Sangouard • Built with passion for clean code', 'text'),
('github_url', 'https://github.com/davidsangouard', 'url'),
('linkedin_url', 'https://www.linkedin.com/in/david-sangouard-6193162a1/', 'url'),
('dribbble_url', 'https://dribbble.com/davidsangouard', 'url'),
('behance_url', 'https://behance.net/davidsangouard', 'url');

-- =====================================================
-- TABLE: about_section (Section À propos)
-- =====================================================
CREATE TABLE `about_section` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_title` varchar(100) DEFAULT 'À propos',
    `section_subtitle` text,
    `about_title` varchar(100) DEFAULT 'Mon approche',
    `about_text` text,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `about_section` (`section_title`, `section_subtitle`, `about_title`, `about_text`) VALUES
('À propos', 'Développeur passionné par l''intersection entre design et technologie', 'Mon approche', 'Je combine expertise technique et sensibilité design pour créer des solutions digitales qui répondent aux besoins réels des utilisateurs. Chaque ligne de code et chaque pixel sont pensés pour offrir la meilleure expérience possible.');

-- =====================================================
-- TABLE: about_features (Caractéristiques À propos)
-- =====================================================
CREATE TABLE `about_features` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `icon` varchar(50) NOT NULL,
    `text` varchar(255) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `about_features` (`icon`, `text`, `sort_order`) VALUES
('fas fa-palette', 'Design centré utilisateur avec recherche UX', 1),
('fas fa-code', 'Code propre et architectures scalables', 2),
('fas fa-rocket', 'Performance et optimisation continue', 3);

-- =====================================================
-- TABLE: stats (Statistiques)
-- =====================================================
CREATE TABLE `stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `value` varchar(20) NOT NULL,
    `label` varchar(50) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stats` (`value`, `label`, `sort_order`) VALUES
('3+', 'Années', 1),
('25+', 'Projets', 2),
('15+', 'Technologies', 3),
('100%', 'Passion', 4);

-- =====================================================
-- TABLE: skill_categories (Catégories de compétences)
-- =====================================================
CREATE TABLE `skill_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `icon` varchar(50) NOT NULL,
    `icon_color` varchar(20) DEFAULT 'accent-primary',
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `skill_categories` (`name`, `icon`, `icon_color`, `sort_order`) VALUES
('UI/UX Design', 'fas fa-paint-brush', 'design', 1),
('Frontend', 'fas fa-laptop-code', '', 2),
('Backend', 'fas fa-server', 'backend', 3),
('DevOps & Tools', 'fas fa-cloud', 'devops', 4);

-- =====================================================
-- TABLE: skills (Compétences/Tags)
-- =====================================================
CREATE TABLE `skills` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `name` varchar(50) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `skills_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `skill_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `skills` (`category_id`, `name`, `sort_order`) VALUES
-- UI/UX Design (category_id = 1)
(1, 'figma', 1),
(1, 'adobe-xd', 2),
(1, 'sketch', 3),
(1, 'photoshop', 4),
(1, 'design-systems', 5),
(1, 'prototyping', 6),
(1, 'user-research', 7),
(1, 'wireframing', 8),
-- Frontend (category_id = 2)
(2, 'react', 1),
(2, 'vue.js', 2),
(2, 'typescript', 3),
(2, 'next.js', 4),
(2, 'tailwind-css', 5),
(2, 'sass', 6),
(2, 'framer-motion', 7),
(2, 'webpack', 8),
-- Backend (category_id = 3)
(3, 'node.js', 1),
(3, 'python', 2),
(3, 'express', 3),
(3, 'fastapi', 4),
(3, 'postgresql', 5),
(3, 'mongodb', 6),
(3, 'redis', 7),
(3, 'graphql', 8),
-- DevOps & Tools (category_id = 4)
(4, 'docker', 1),
(4, 'aws', 2),
(4, 'vercel', 3),
(4, 'github-actions', 4),
(4, 'nginx', 5),
(4, 'linux', 6),
(4, 'jest', 7),
(4, 'cypress', 8);

-- =====================================================
-- TABLE: projects (Projets)
-- =====================================================
CREATE TABLE `projects` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(150) NOT NULL,
    `type` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `icon` varchar(50) DEFAULT 'fas fa-folder',
    `demo_url` varchar(255) DEFAULT NULL,
    `github_url` varchar(255) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `projects` (`title`, `type`, `description`, `icon`, `demo_url`, `github_url`, `sort_order`) VALUES
('E-commerce Platform', '[full-stack • ui/ux]', 'Plateforme e-commerce complète avec design system personnalisé, gestion avancée des produits et système de paiement sécurisé.', 'fas fa-shopping-cart', '#', '#', 1),
('Analytics Dashboard', '[saas • data-viz]', 'Dashboard analytics en temps réel avec visualisations interactives et système d''alertes automatisées.', 'fas fa-chart-line', '#', '#', 2),
('Mobile Banking App', '[mobile • fintech]', 'Application bancaire mobile avec authentification biométrique et interface moderne optimisée.', 'fas fa-mobile-alt', '#', '#', 3),
('Design System', '[ui/ux • components]', 'Système de design complet avec composants réutilisables et documentation interactive.', 'fas fa-palette', '#', '#', 4);

-- =====================================================
-- TABLE: project_tags (Tags des projets)
-- =====================================================
CREATE TABLE `project_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `project_id` int(11) NOT NULL,
    `tag_name` varchar(50) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `project_id` (`project_id`),
    CONSTRAINT `project_tags_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `project_tags` (`project_id`, `tag_name`, `sort_order`) VALUES
(1, 'react', 1),
(1, 'node.js', 2),
(1, 'stripe', 3),
(2, 'vue.js', 1),
(2, 'd3.js', 2),
(2, 'python', 3),
(3, 'react-native', 1),
(3, 'typescript', 2),
(3, 'aws', 3),
(4, 'storybook', 1),
(4, 'figma', 2),
(4, 'tokens', 3);

-- =====================================================
-- TABLE: veille_categories (Catégories de veille)
-- =====================================================
CREATE TABLE `veille_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    `color` varchar(20) DEFAULT 'accent-primary',
    `icon` varchar(50) DEFAULT 'fas fa-tag',
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `veille_categories` (`name`, `slug`, `color`, `icon`, `sort_order`) VALUES
('Intelligence Artificielle', 'ia', '#a5a5ff', 'fas fa-brain', 1),
('Cybersécurité', 'cybersecurite', '#f85149', 'fas fa-shield-alt', 2),
('Développement Web', 'dev-web', '#58a6ff', 'fas fa-code', 3),
('Cloud Computing', 'cloud', '#d29922', 'fas fa-cloud', 4),
('IoT & Hardware', 'iot', '#238636', 'fas fa-microchip', 5),
('Blockchain', 'blockchain', '#ff7b72', 'fas fa-link', 6);

-- =====================================================
-- TABLE: veille_posts (Articles de veille)
-- =====================================================
CREATE TABLE `veille_posts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `excerpt` text,
    `content` longtext NOT NULL,
    `category_id` int(11) DEFAULT NULL,
    `image_url` varchar(255) DEFAULT NULL,
    `source_url` varchar(255) DEFAULT NULL,
    `source_name` varchar(100) DEFAULT NULL,
    `author_id` int(11) DEFAULT NULL,
    `views` int(11) DEFAULT 0,
    `is_featured` tinyint(1) DEFAULT 0,
    `is_published` tinyint(1) DEFAULT 1,
    `published_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `category_id` (`category_id`),
    KEY `author_id` (`author_id`),
    CONSTRAINT `veille_posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `veille_categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `veille_posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `veille_posts` (`title`, `slug`, `excerpt`, `content`, `category_id`, `source_url`, `source_name`, `author_id`, `is_featured`, `is_published`, `published_at`) VALUES
('GPT-5 : Les nouvelles avancées de l''IA générative', 'gpt-5-avancees-ia-generative', 'Découvrez les dernières innovations en matière d''intelligence artificielle avec l''arrivée de GPT-5 et ses capacités révolutionnaires.', '<p>L''intelligence artificielle continue de progresser à un rythme effréné. OpenAI a récemment dévoilé les premières informations concernant GPT-5, la prochaine génération de son modèle de langage.</p>\n<h3>Les principales améliorations</h3>\n<p>GPT-5 promet des avancées significatives en matière de raisonnement logique, de compréhension contextuelle et de génération de contenu multimodal. Les premiers tests montrent une réduction notable des hallucinations et une meilleure capacité à suivre des instructions complexes.</p>\n<h3>Impact sur le développement</h3>\n<p>Pour les développeurs, ces avancées signifient de nouvelles possibilités en termes d''automatisation et d''assistance au codage. Les APIs seront plus performantes et offriront une meilleure intégration dans les workflows existants.</p>', 1, 'https://openai.com', 'OpenAI Blog', 1, 1, 1, NOW()),
('Nouvelle faille critique dans les processeurs Intel', 'faille-critique-processeurs-intel', 'Une vulnérabilité majeure découverte dans les processeurs Intel nécessite une mise à jour urgente des systèmes.', '<p>Des chercheurs en sécurité ont découvert une nouvelle vulnérabilité critique affectant plusieurs générations de processeurs Intel.</p>\n<h3>Nature de la faille</h3>\n<p>Cette faille, nommée "Downfall", permet à des attaquants d''accéder à des données sensibles stockées dans les registres vectoriels du processeur. Elle affecte les processeurs de la 6ème à la 11ème génération.</p>\n<h3>Recommandations</h3>\n<p>Intel a publié des correctifs microcode. Il est fortement recommandé de mettre à jour vos systèmes dès que possible. Les administrateurs système doivent également vérifier les mises à jour BIOS disponibles auprès des fabricants.</p>', 2, 'https://intel.com/security', 'Intel Security', 1, 1, 1, NOW()),
('React 19 : Les nouvelles fonctionnalités à connaître', 'react-19-nouvelles-fonctionnalites', 'React 19 apporte son lot de nouveautés avec les Server Components améliorés et de nouvelles optimisations de performance.', '<p>La nouvelle version majeure de React est enfin disponible, apportant des améliorations significatives pour les développeurs front-end.</p>\n<h3>Server Components</h3>\n<p>Les Server Components sont maintenant pleinement intégrés et stables. Ils permettent de réduire considérablement la taille du bundle JavaScript envoyé au client tout en maintenant une excellente expérience développeur.</p>\n<h3>Actions et Forms</h3>\n<p>Les nouvelles Server Actions simplifient la gestion des formulaires et des mutations de données. Plus besoin de créer des endpoints API séparés pour les opérations simples.</p>\n<h3>Performances</h3>\n<p>Le nouveau compilateur React optimise automatiquement les re-renders, éliminant le besoin de mémorisation manuelle dans la plupart des cas.</p>', 3, 'https://react.dev/blog', 'React Blog', 1, 0, 1, NOW()),
('AWS annonce de nouveaux services serverless', 'aws-nouveaux-services-serverless', 'Amazon Web Services étend son offre serverless avec de nouveaux services destinés aux applications modernes.', '<p>AWS continue d''innover dans le domaine du serverless computing avec l''annonce de plusieurs nouveaux services lors de re:Invent.</p>\n<h3>Lambda SnapStart pour Python</h3>\n<p>Après Java, Lambda SnapStart est maintenant disponible pour Python, réduisant drastiquement les temps de démarrage à froid des fonctions.</p>\n<h3>EventBridge Pipes amélioré</h3>\n<p>Les nouvelles fonctionnalités d''EventBridge Pipes permettent des transformations de données plus complexes et une meilleure intégration avec les services tiers.</p>', 4, 'https://aws.amazon.com/blogs', 'AWS Blog', 1, 0, 1, NOW()),
('Tendances IoT 2025 : L''edge computing en première ligne', 'tendances-iot-2025-edge-computing', 'L''edge computing devient central dans les architectures IoT modernes, offrant des temps de réponse réduits et une meilleure confidentialité des données.', '<p>Le marché de l''Internet des Objets évolue rapidement, avec l''edge computing qui s''impose comme une composante essentielle des architectures modernes.</p>\n<h3>Pourquoi l''edge ?</h3>\n<p>Le traitement des données en périphérie permet de réduire la latence, d''économiser de la bande passante et de mieux protéger les données sensibles. Pour les applications critiques comme la santé ou l''industrie, ces avantages sont décisifs.</p>\n<h3>Technologies clés</h3>\n<p>Les microcontrôleurs nouvelle génération, comme les ESP32-S3 ou les RP2040, offrent suffisamment de puissance pour exécuter des modèles d''IA légers directement sur l''appareil.</p>', 5, 'https://iot-analytics.com', 'IoT Analytics', 1, 0, 1, NOW());

-- =====================================================
-- TABLE: veille_tags (Tags des articles de veille)
-- =====================================================
CREATE TABLE `veille_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `slug` varchar(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `veille_tags` (`name`, `slug`) VALUES
('IA', 'ia'),
('Machine Learning', 'machine-learning'),
('Sécurité', 'securite'),
('JavaScript', 'javascript'),
('React', 'react'),
('Cloud', 'cloud'),
('AWS', 'aws'),
('IoT', 'iot'),
('Hardware', 'hardware'),
('Blockchain', 'blockchain'),
('API', 'api'),
('Performance', 'performance');

-- =====================================================
-- TABLE: veille_post_tags (Relation posts-tags)
-- =====================================================
CREATE TABLE `veille_post_tags` (
    `post_id` int(11) NOT NULL,
    `tag_id` int(11) NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`),
    KEY `tag_id` (`tag_id`),
    CONSTRAINT `veille_post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `veille_posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `veille_post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `veille_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `veille_post_tags` (`post_id`, `tag_id`) VALUES
(1, 1), (1, 2),
(2, 3),
(3, 4), (3, 5), (3, 12),
(4, 6), (4, 7),
(5, 8), (5, 9);

-- =====================================================
-- TABLE: activity_log (Journal d'activité admin)
-- =====================================================
CREATE TABLE `activity_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `entity_type` varchar(50) DEFAULT NULL,
    `entity_id` int(11) DEFAULT NULL,
    `details` text,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: csrf_tokens (Tokens CSRF pour sécurité)
-- =====================================================
CREATE TABLE `csrf_tokens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `token` varchar(64) NOT NULL,
    `session_id` varchar(128) NOT NULL,
    `expires_at` timestamp NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `token` (`token`),
    KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INDEX SUPPLÉMENTAIRES POUR PERFORMANCES
-- =====================================================
CREATE INDEX idx_veille_posts_published ON veille_posts(is_published, published_at);
CREATE INDEX idx_veille_posts_featured ON veille_posts(is_featured, is_published);
CREATE INDEX idx_projects_active ON projects(is_active, sort_order);
CREATE INDEX idx_skills_active ON skills(is_active, category_id);

COMMIT;
