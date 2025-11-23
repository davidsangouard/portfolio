// ==================== FONCTIONS JAVASCRIPT COMPLÈTES ====================
// À copier-coller dans la balise <script> de admin/index.php

// ========== GESTION DES MODALES ==========

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

// Fermer la modale en cliquant en dehors
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// Fermer avec la touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// ========== FONCTIONS ÉDITION FEATURES ==========

function editFeature(id, icon, text, isActive) {
    document.getElementById('edit_feature_id').value = id;
    document.getElementById('edit_feature_icon').value = icon;
    document.getElementById('edit_feature_text').value = text;
    document.getElementById('edit_feature_active').checked = isActive == 1;
    openModal('editFeatureModal');
}

function deleteFeature(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette feature ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_feature">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== FONCTIONS ÉDITION STATS ==========

function editStat(id, value, label, isActive) {
    document.getElementById('edit_stat_id').value = id;
    document.getElementById('edit_stat_value').value = value;
    document.getElementById('edit_stat_label').value = label;
    document.getElementById('edit_stat_active').checked = isActive == 1;
    openModal('editStatModal');
}

function deleteStat(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette statistique ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_stat">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== FONCTIONS ÉDITION SKILL CATEGORIES ==========

function editSkillCategory(id, name, icon, iconColor, isActive) {
    document.getElementById('edit_skill_category_id').value = id;
    document.getElementById('edit_skill_category_name').value = name;
    document.getElementById('edit_skill_category_icon').value = icon;
    document.getElementById('edit_skill_category_color').value = iconColor || '';
    document.getElementById('edit_skill_category_active').checked = isActive == 1;
    openModal('editSkillCategoryModal');
}

function deleteSkillCategory(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Tous les tags associés seront aussi supprimés.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_skill_category">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== FONCTIONS ÉDITION SKILLS ==========

function editSkill(id, categoryId, name, isActive) {
    document.getElementById('edit_skill_id').value = id;
    document.getElementById('edit_skill_category').value = categoryId;
    document.getElementById('edit_skill_name').value = name;
    document.getElementById('edit_skill_active').checked = isActive == 1;
    openModal('editSkillModal');
}

function deleteSkill(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette compétence ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_skill">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== FONCTIONS ÉDITION PROJECTS ==========

function editProject(id, title, type, description, icon, demoUrl, githubUrl, tags, isActive) {
    document.getElementById('edit_project_id').value = id;
    document.getElementById('edit_project_title').value = title;
    document.getElementById('edit_project_type').value = type;
    document.getElementById('edit_project_description').value = description;
    document.getElementById('edit_project_icon').value = icon;
    document.getElementById('edit_project_demo').value = demoUrl || '';
    document.getElementById('edit_project_github').value = githubUrl || '';
    document.getElementById('edit_project_tags').value = tags.join(', ');
    document.getElementById('edit_project_active').checked = isActive == 1;
    openModal('editProjectModal');
}

function deleteProject(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_project">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== FONCTIONS ÉDITION VEILLE CATEGORIES ==========

function editVeilleCategory(id, name, icon, color, isActive) {
    document.getElementById('edit_veille_category_id').value = id;
    document.getElementById('edit_veille_category_name').value = name;
    document.getElementById('edit_veille_category_icon').value = icon;
    document.getElementById('edit_veille_category_color').value = color;
    document.getElementById('edit_veille_category_active').checked = isActive == 1;
    openModal('editVeilleCategoryModal');
}

function deleteVeilleCategory(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_veille_category">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== FONCTIONS ÉDITION VEILLE POSTS ==========

function editVeillePost(id, title, excerpt, content, categoryId, sourceUrl, sourceName, tags, isFeatured, isPublished) {
    document.getElementById('edit_veille_post_id').value = id;
    document.getElementById('edit_veille_post_title').value = title;
    document.getElementById('edit_veille_post_excerpt').value = excerpt;
    document.getElementById('edit_veille_post_content').value = content;
    document.getElementById('edit_veille_post_category').value = categoryId || '';
    document.getElementById('edit_veille_post_source_url').value = sourceUrl || '';
    document.getElementById('edit_veille_post_source_name').value = sourceName || '';
    document.getElementById('edit_veille_post_featured').checked = isFeatured == 1;
    document.getElementById('edit_veille_post_published').checked = isPublished == 1;
    
    // Cocher les tags appropriés
    document.querySelectorAll('.post-tag-checkbox').forEach(checkbox => {
        checkbox.checked = tags.includes(parseInt(checkbox.value));
    });
    
    openModal('editVeillePostModal');
}

function deleteVeillePost(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_veille_post">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteVeilleTag(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce tag ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_veille_tag">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== GESTION DES ONGLETS ==========

function switchTab(tabName) {
    // Masquer tous les onglets
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('tab-hidden');
    });
    
    // Désactiver tous les liens
    document.querySelectorAll('.nav-item a').forEach(link => {
        link.classList.remove('active');
    });
    
    // Afficher l'onglet sélectionné
    document.getElementById(tabName + '-tab').classList.remove('tab-hidden');
    
    // Activer le lien
    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
    
    // Mettre à jour l'URL
    history.pushState(null, '', '?tab=' + tabName);
}

// ========== SIDEBAR MOBILE ==========

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.querySelector('.mobile-overlay').classList.toggle('active');
}

// Fermer la sidebar en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.querySelector('.mobile-overlay');
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});

// ========== AUTO-HIDE MESSAGES ==========

document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }
});

// ==================== FIN DES FONCTIONS ====================
