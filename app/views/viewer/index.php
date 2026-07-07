<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Explore Templates') ?> - TemplateLink Builder</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #09090b;
            --surface: #18181b;
            --primary: #6366f1;
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #f4f4f5;
            --text-muted: #a1a1aa;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            margin: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 3rem;
        }
        .logo {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
        }
        .logo i { color: var(--primary); }
        .btn-admin {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }
        .hero {
            text-align: center;
            margin-bottom: 4rem;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin: 0 0 1rem 0;
            background: linear-gradient(135deg, #a5b4fc 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            color: var(--text-muted);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        .doc-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s, border-color 0.3s;
        }
        .doc-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .doc-thumbnail {
            height: 180px;
            background: rgba(255,255,255,0.02);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }
        .doc-thumbnail i { font-size: 3rem; opacity: 0.3; }
        .doc-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .doc-category {
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        .doc-desc {
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .empty-showcase {
            text-align: center;
            padding: 5rem 0;
            color: var(--text-muted);
        }
        .empty-showcase i { font-size: 4rem; opacity: 0.2; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <a href="<?= BASE_URL ?>" class="logo">
                <i class="fa-solid fa-link"></i>
                <span>TemplateLink</span>
            </a>
            <a href="<?= BASE_URL ?>admin" class="btn-admin">Admin Portal</a>
        </header>

        <section class="hero">
            <h1>Interactive Shared Documents</h1>
            <p>Explore beautiful responsive templates published using TemplateLink Builder.</p>
        </section>

        <?php if (empty($templates)): ?>
            <div class="empty-showcase">
                <i class="fa-solid fa-folder-open"></i>
                <h3>No documents published yet</h3>
                <p>Log in as administrator to design and publish your first template.</p>
            </div>
        <?php else: ?>
            <div class="catalog-grid">
                <?php foreach ($templates as $tmpl): 
                    $catName = $tmpl['category_name'] ?? 'Uncategorized';
                ?>
                    <a href="<?= BASE_URL ?>view/<?= htmlspecialchars($tmpl['slug']) ?>" class="doc-card" target="_blank">
                        <div class="doc-thumbnail">
                            <?php if (!empty($tmpl['thumbnail_url'])): ?>
                                <img src="<?= htmlspecialchars($tmpl['thumbnail_url']) ?>" alt="<?= htmlspecialchars($tmpl['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-pager"></i>
                            <?php endif; ?>
                        </div>
                        <div class="doc-content">
                            <span class="doc-category"><?= htmlspecialchars($catName) ?></span>
                            <h3 class="doc-title"><?= htmlspecialchars($tmpl['title']) ?></h3>
                            <p class="doc-desc"><?= htmlspecialchars($tmpl['description'] ?? 'Click to explore document details.') ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
