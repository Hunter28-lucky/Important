<div class="templates-header-actions mb-4">
    <!-- Filter Toolbar Form -->
    <form action="<?= BASE_URL ?>admin/templates" method="GET" class="filter-form">
        <div class="filter-group">
            <div class="input-with-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search templates..." value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            
            <select name="category_id">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="status">
                <option value="">All Statuses</option>
                <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
            
            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-filter"></i> Apply Filters
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['status'])): ?>
                <a href="<?= BASE_URL ?>admin/templates" class="btn btn-link text-muted">Clear</a>
            <?php endif; ?>
        </div>
    </form>
    
    <a href="<?= BASE_URL ?>admin/templates/create" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Create Template
    </a>
</div>

<!-- Grid listing -->
<?php if (empty($templates)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="fa-solid fa-file-invoice"></i>
                <h3>No templates found</h3>
                <p class="text-muted">Start by creating a new template or adjustments in search parameters.</p>
                <a href="<?= BASE_URL ?>admin/templates/create" class="btn btn-primary">Create Template Now</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="templates-grid">
        <?php foreach ($templates as $tmpl): 
            $viewUrl = BASE_URL . 'view/' . $tmpl['slug'];
            $catName = $tmpl['category_name'] ?? 'Uncategorized';
        ?>
            <div class="template-card">
                <div class="template-thumbnail">
                    <?php if (!empty($tmpl['thumbnail_url'])): ?>
                        <img src="<?= htmlspecialchars($tmpl['thumbnail_url']) ?>" alt="<?= htmlspecialchars($tmpl['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <!-- Standard styled icon card -->
                        <div class="empty-thumbnail cat-<?= strtolower(str_replace(' ', '-', $catName)) ?>">
                            <i class="fa-solid fa-pager"></i>
                            <span><?= htmlspecialchars($catName) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="template-status-badge">
                        <span class="badge <?= $tmpl['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                            <?= ucfirst($tmpl['status']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="template-info">
                    <span class="template-category"><?= htmlspecialchars($catName) ?></span>
                    <h3 class="template-title" title="<?= htmlspecialchars($tmpl['title']) ?>">
                        <?= htmlspecialchars($tmpl['title']) ?>
                    </h3>
                    <p class="template-desc"><?= htmlspecialchars($tmpl['description'] ?? 'No description added.') ?></p>
                    
                    <div class="share-url-container">
                        <input type="text" readonly value="<?= $viewUrl ?>" class="share-input">
                        <button type="button" class="btn-copy-url" data-url="<?= $viewUrl ?>" title="Copy Link">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="template-card-footer">
                    <div class="template-dates">
                        <span>Created: <?= date('M d, Y', strtotime($tmpl['created_at'])) ?></span>
                    </div>
                    <div class="template-card-actions">
                        <a href="<?= BASE_URL ?>admin/templates/edit?id=<?= $tmpl['id'] ?>" class="btn-icon btn-edit" title="Edit Template">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="<?= $viewUrl ?>" target="_blank" class="btn-icon btn-view" title="Open Public Link">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                        <form action="<?= BASE_URL ?>admin/templates/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this template? All analytics views and clicks will be lost permanently.');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="template_id" value="<?= $tmpl['id'] ?>">
                            <button type="submit" class="btn-icon btn-delete" title="Delete Template">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Share URL copying
    const copyButtons = document.querySelectorAll('.btn-copy-url');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(() => {
                const originalIcon = this.innerHTML;
                this.innerHTML = '<i class="fa-solid fa-check" style="color: #10b981;"></i>';
                setTimeout(() => {
                    this.innerHTML = originalIcon;
                }, 2000);
            });
        });
    });
});
</script>
