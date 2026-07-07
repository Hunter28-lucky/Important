<div class="settings-grid">
    <!-- General Settings Form -->
    <div class="card col-8">
        <div class="card-header">
            <h3>App Configuration</h3>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/settings" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="site_name">Site Brand Name</label>
                    <input type="text" id="site_name" name="site_name" class="form-control" 
                           value="<?= htmlspecialchars($settings['site_name'] ?? 'TemplateLink Builder') ?>" required>
                    <small class="form-text text-muted">The name used in emails, titles, and site branding.</small>
                </div>

                <div class="form-group">
                    <label for="admin_email">Admin Contact Email</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" 
                           value="<?= htmlspecialchars($settings['admin_email'] ?? 'admin@example.com') ?>" required>
                    <small class="form-text text-muted">Used for alerts, reports, and system notifications.</small>
                </div>

                <div class="form-group">
                    <label for="custom_css">Global Custom CSS</label>
                    <textarea id="custom_css" name="custom_css" rows="10" class="form-control code-editor" 
                              placeholder="/* Custom CSS loaded on all viewer pages */"><?= htmlspecialchars($settings['custom_css'] ?? '') ?></textarea>
                    <small class="form-text text-muted">Inject custom overrides or animations globally across viewer templates.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Configurations
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Manager Sidebar -->
    <div class="card col-4">
        <div class="card-header">
            <h3>Template Categories</h3>
        </div>
        <div class="card-body">
            <!-- Add Category Form -->
            <form action="<?= BASE_URL ?>admin/settings" method="POST" class="add-cat-form mb-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="new_category_name">Create Category</label>
                    <div class="input-group">
                        <input type="text" id="new_category_name" name="new_category_name" class="form-control" 
                               placeholder="e.g. Magazine" required>
                        <button type="submit" class="btn btn-secondary">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </form>

            <hr class="divider">

            <!-- Category List -->
            <div class="category-list-wrapper">
                <label>Existing Categories</label>
                <?php if (empty($categories)): ?>
                    <p class="text-muted text-sm">No categories defined yet.</p>
                <?php else: ?>
                    <ul class="category-list">
                        <?php foreach ($categories as $cat): ?>
                            <li class="category-item">
                                <span class="cat-name"><?= htmlspecialchars($cat['name']) ?></span>
                                <code class="cat-slug"><?= htmlspecialchars($cat['slug']) ?></code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
