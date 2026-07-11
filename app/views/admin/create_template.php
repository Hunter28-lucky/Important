<div class="create-template-wrapper">
    <div class="card max-w-2xl mx-auto">
        <div class="card-header">
            <h3>New Document Settings</h3>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/templates/create" method="POST" id="createTemplateForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="title">Document Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Monthly Newsletter - July" required autofocus>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="slug">URL Slug Path</label>
                        <div class="slug-input-wrapper" style="display: flex; gap: 0.5rem; align-items: center;">
                            <span class="slug-domain"><?= parse_url(BASE_URL, PHP_URL_HOST) ?>/view/</span>
                            <input type="text" id="slug" name="slug" class="form-control" placeholder="monthly-newsletter-july" required style="flex-grow: 1;">
                            <button type="button" class="btn btn-secondary btn-sm" id="btnGenShortSlug" style="height: 38px; white-space: nowrap;"><i class="fa-solid fa-wand-magic-sparkles"></i> Shorten</button>
                        </div>
                        <small class="form-text text-muted">A unique URL slug. Lowercase letters, numbers, and dashes only.</small>
                    </div>
                    
                    <div class="form-group col-6">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Choose a Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Select Layout Preset</label>
                    <div class="presets-selector-grid">
                        <label class="preset-option">
                            <input type="radio" name="layout_type" value="blank" checked>
                            <div class="preset-box">
                                <div class="preset-icon"><i class="fa-regular fa-square"></i></div>
                                <div class="preset-details">
                                    <strong>Blank Template</strong>
                                    <span>Start with an empty document canvas.</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="preset-option">
                            <input type="radio" name="layout_type" value="magazine">
                            <div class="preset-box">
                                <div class="preset-icon"><i class="fa-solid fa-book-open"></i></div>
                                <div class="preset-details">
                                    <strong>Magazine Preset</strong>
                                    <span>Stylized grids, card listings, and full article sections.</span>
                                </div>
                            </div>
                        </label>

                        <label class="preset-option">
                            <input type="radio" name="layout_type" value="newsletter">
                            <div class="preset-box">
                                <div class="preset-icon"><i class="fa-regular fa-paper-plane"></i></div>
                                <div class="preset-details">
                                    <strong>Newsletter Preset</strong>
                                    <span>E-mail digest layouts, headers, and simple alerts.</span>
                                </div>
                            </div>
                        </label>

                        <label class="preset-option">
                            <input type="radio" name="layout_type" value="resume">
                            <div class="preset-box">
                                <div class="preset-icon"><i class="fa-solid fa-user-tie"></i></div>
                                <div class="preset-details">
                                    <strong>Resume / CV Preset</strong>
                                    <span>Timeline nodes, progress bars, and header cards.</span>
                                </div>
                            </div>
                        </label>

                        <label class="preset-option">
                            <input type="radio" name="layout_type" value="landing">
                            <div class="preset-box">
                                <div class="preset-icon"><i class="fa-solid fa-rocket"></i></div>
                                <div class="preset-details">
                                    <strong>Landing Page Preset</strong>
                                    <span>Hero sections, testimonials, FAQs, and calls to action.</span>
                                </div>
                            </div>
                        </label>

                        <label class="preset-option">
                            <input type="radio" name="layout_type" value="official_notice">
                            <div class="preset-box">
                                <div class="preset-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                                <div class="preset-details">
                                    <strong>Official Notice Preset</strong>
                                    <span>Official notice layout regarding XMA Dhanbad doping closure.</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-actions mt-6">
                    <a href="<?= BASE_URL ?>admin/templates" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Create & Open Editor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    // Auto-generate slug as admin typing title
    titleInput.addEventListener('input', function() {
        let titleVal = this.value;
        let slugVal = titleVal.toLowerCase()
                              .replace(/[^a-z0-9\s-]/g, '') // remove special chars
                              .replace(/\s+/g, '-')          // replace spaces with dashes
                              .replace(/-+/g, '-');          // collapse multiple dashes
        slugInput.value = slugVal.replace(/^-+|-+$/g, '');   // trim starting/ending dashes
    });

    // Enforce slug constraints on key presses
    slugInput.addEventListener('input', function() {
        this.value = this.value.toLowerCase()
                               .replace(/[^a-z0-9-]/g, '')
                               .replace(/-+/g, '-');
    });

    // Generate short random slug
    const btnGenSlug = document.getElementById('btnGenShortSlug');
    if (btnGenSlug) {
        btnGenSlug.addEventListener('click', function() {
            const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            for (let i = 0; i < 6; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            slugInput.value = result;
        });
    }
});
</script>
