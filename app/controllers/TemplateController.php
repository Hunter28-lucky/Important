<?php
namespace App\Controllers;

use App\Models\Template;
use App\Models\Category;
use App\Models\Media;

class TemplateController extends Controller {
    public function __construct() {
        $this->requireAuth();
    }

    /**
     * Display templates dashboard list (with search, category, status filters).
     */
    public function index() {
        $categoryModel = new Category();
        $templateModel = new Template();

        $categories = $categoryModel->getAll();
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        $templates = $templateModel->getAll($filters);

        $this->render('admin/templates', [
            'title' => 'Manage Templates',
            'active_page' => 'templates',
            'categories' => $categories,
            'templates' => $templates,
            'filters' => $filters
        ]);
    }

    /**
     * Show the template creation setup form (Title, Slug, Layout choice).
     */
    public function create() {
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        $this->render('admin/create_template', [
            'title' => 'Create Template',
            'active_page' => 'templates',
            'categories' => $categories
        ]);
    }

    /**
     * Process creation submission. Seeds database with pre-built layouts if requested.
     */
    public function store() {
        $this->validateCsrf();

        $title = trim($_POST['title'] ?? '');
        $categoryId = $_POST['category_id'] ?? '';
        $slug = trim($_POST['slug'] ?? '');
        $layoutType = $_POST['layout_type'] ?? 'blank';
        $status = $_POST['status'] ?? 'draft';

        if (empty($title) || empty($slug)) {
            $this->setFlash('error', 'Title and Slug fields are required.');
            $this->redirect('admin/templates/create');
        }

        // Clean slug
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug));
        $slug = trim($slug, '-');

        $templateModel = new Template();

        if (!$templateModel->isSlugUnique($slug)) {
            $this->setFlash('error', 'Slug already in use. Please select a unique path.');
            $this->redirect('admin/templates/create');
        }

        // Setup JSON block structures based on layout preset selection
        $blocks = $this->getPresetBlocks($layoutType, $title);
        $contentJSON = json_encode($blocks);

        $data = [
            'title' => $title,
            'description' => 'A custom ' . $layoutType . ' template.',
            'thumbnail_url' => null,
            'category_id' => $categoryId,
            'tags' => $layoutType,
            'slug' => $slug,
            'content' => $contentJSON,
            'status' => $status,
            'meta_title' => $title . ' | Shareable Link',
            'meta_description' => 'Interactive document built with TemplateLink Builder.',
            'og_image' => null,
            'og_title' => $title,
            'og_description' => 'Interactive document.',
            'schema_markup' => null
        ];

        $newId = $templateModel->create($data);

        if ($newId) {
            $this->setFlash('success', 'Template created successfully. Welcome to the Visual Editor!');
            $this->redirect('admin/templates/edit?id=' . $newId);
        } else {
            $this->setFlash('error', 'Failed to create template record.');
            $this->redirect('admin/templates/create');
        }
    }

    /**
     * Render the Visual Editor interface.
     * Uses 'plain' layout to allow full-bleed screen workspace.
     */
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid Template ID.');
            $this->redirect('admin/templates');
        }

        $templateModel = new Template();
        $template = $templateModel->getById($id);

        if (!$template) {
            $this->setFlash('error', 'Template not found.');
            $this->redirect('admin/templates');
        }

        $categoryModel = new Category();
        $mediaModel = new Media();

        $categories = $categoryModel->getAll();
        $assets = $mediaModel->getAll();

        $this->render('admin/editor', [
            'title' => 'Editing: ' . $template['title'],
            'template' => $template,
            'categories' => $categories,
            'assets' => $assets
        ], 'plain');
    }

    /**
     * Process updates (accepts normal form posts for meta settings, or JSON AJAX posts from the visual builder).
     */
    public function update() {
        $this->validateCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['error' => 'Invalid Template ID.'], 400);
        }

        $templateModel = new Template();
        $template = $templateModel->getById($id);

        if (!$template) {
            $this->json(['error' => 'Template not found.'], 404);
        }

        // Detect if AJAX post from visual builder
        $isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

        if ($isAjax) {
            // Visual editor payload saving
            $blocksData = $_POST['content'] ?? '[]';
            
            // Allow auto title and status savings
            $title = $_POST['title'] ?? $template['title'];
            $status = $_POST['status'] ?? $template['status'];
            $slug = $_POST['slug'] ?? $template['slug'];

            // Check slug uniqueness if changed
            if ($slug !== $template['slug'] && !$templateModel->isSlugUnique($slug, $id)) {
                $this->json(['error' => 'The slug is already in use by another template.'], 422);
            }

            // Update template content
            $updateData = $template;
            $updateData['title'] = $title;
            $updateData['status'] = $status;
            $updateData['slug'] = $slug;
            $updateData['content'] = $blocksData;

            // Optional Thumbnail capture from visual editor if supplied
            if (!empty($_POST['thumbnail_url'])) {
                $updateData['thumbnail_url'] = $_POST['thumbnail_url'];
            }

            // Accept SEO / Open Graph fields from visual editor
            if (isset($_POST['meta_title'])) {
                $updateData['meta_title'] = $_POST['meta_title'];
            }
            if (isset($_POST['meta_description'])) {
                $updateData['meta_description'] = $_POST['meta_description'];
            }
            if (isset($_POST['og_title'])) {
                $updateData['og_title'] = $_POST['og_title'];
            }
            if (isset($_POST['og_description'])) {
                $updateData['og_description'] = $_POST['og_description'];
            }
            if (isset($_POST['og_image'])) {
                $updateData['og_image'] = $_POST['og_image'];
            }

            $templateModel->update($id, $updateData);
            $this->json(['success' => true, 'message' => 'Template visual layout saved successfully.']);
        } else {
            // Traditional form save for SEO and Metadata configuration settings
            $title = trim($_POST['title'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            
            if (empty($title) || empty($slug)) {
                $this->setFlash('error', 'Title and Slug fields cannot be empty.');
                $this->redirect('admin/templates/edit?id=' . $id);
            }

            // Clean slug
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug));
            $slug = trim($slug, '-');

            if ($slug !== $template['slug'] && !$templateModel->isSlugUnique($slug, $id)) {
                $this->setFlash('error', 'The slug path is already in use.');
                $this->redirect('admin/templates/edit?id=' . $id);
            }

            $updateData = [
                'title' => $title,
                'description' => $_POST['description'] ?? null,
                'thumbnail_url' => $_POST['thumbnail_url'] ?? null,
                'category_id' => $_POST['category_id'] ?? null,
                'tags' => $_POST['tags'] ?? null,
                'slug' => $slug,
                'content' => $template['content'], // keep existing canvas
                'status' => $_POST['status'] ?? 'draft',
                'meta_title' => $_POST['meta_title'] ?? null,
                'meta_description' => $_POST['meta_description'] ?? null,
                'og_image' => $_POST['og_image'] ?? null,
                'og_title' => $_POST['og_title'] ?? null,
                'og_description' => $_POST['og_description'] ?? null,
                'schema_markup' => $_POST['schema_markup'] ?? null
            ];

            $templateModel->update($id, $updateData);
            $this->setFlash('success', 'Metadata configuration saved successfully.');
            $this->redirect('admin/templates/edit?id=' . $id);
        }
    }

    /**
     * Delete a template record.
     */
    public function delete() {
        $this->validateCsrf();

        $id = (int)($_POST['template_id'] ?? 0);
        if ($id <= 0) {
            $this->setFlash('error', 'Invalid Template ID.');
            $this->redirect('admin/templates');
        }

        $templateModel = new Template();
        $templateModel->delete($id);

        $this->setFlash('success', 'Template record deleted successfully.');
        $this->redirect('admin/templates');
    }

    /**
     * Hard-code JSON block structures for quick layout presets.
     */
    private function getPresetBlocks($layoutType, $title) {
        // We will seed presets. For blank, return an empty array.
        if ($layoutType === 'blank') {
            return [];
        }

        if ($layoutType === 'official_notice') {
            return [
                [
                    'id' => 'html-notice-1',
                    'type' => 'html',
                    'content' => [
                        'code' => '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">\n<style>\n.official-notice-wrapper {\n  font-family: Poppins, sans-serif;\n  min-height: 100vh;\n  display: flex;\n  justify-content: center;\n  align-items: center;\n  padding: 16px;\n  color: #fff;\n  background: radial-gradient(circle at top, #4d0000 0%, transparent 35%), linear-gradient(135deg, #050505, #101010);\n  width: 100%;\n}\n.official-notice-wrapper * { box-sizing: border-box; }\n.official-notice-card {\n  width: min(100%, 760px);\n  background: #111;\n  border: 1px solid rgba(255,255,255,.08);\n  border-radius: 24px;\n  padding: clamp(20px, 4vw, 42px);\n  text-align: center;\n  overflow: hidden;\n  box-shadow: 0 20px 60px rgba(0,0,0,.55);\n}\n.official-notice-logo {\n  width: clamp(88px, 26vw, 155px);\n  height: clamp(88px, 26vw, 155px);\n  border-radius: 50%;\n  border: 5px solid #fff;\n  object-fit: cover;\n  display: block;\n  margin: 0 auto;\n  box-shadow: 0 0 40px rgba(255,0,0,.35);\n  animation: notice-float 4s ease-in-out infinite;\n}\n@keyframes notice-float {\n  0%, 100% { transform: translateY(0); }\n  50% { transform: translateY(-6px); }\n}\n.official-notice-badge {\n  display: inline-flex;\n  align-items: center;\n  justify-content: center;\n  gap: 8px;\n  margin-top: 22px;\n  padding: 10px 24px;\n  background: #ff2d2d;\n  border-radius: 999px;\n  font-weight: 700;\n  font-size: clamp(12px, 3vw, 16px);\n  letter-spacing: .6px;\n  animation: notice-glow 3s infinite;\n}\n@keyframes notice-glow {\n  50% { box-shadow: 0 0 22px rgba(255,0,0,.5); }\n}\n.official-notice-card h1 {\n  margin-top: 28px;\n  font-size: clamp(30px, 9vw, 64px);\n  line-height: 1.08;\n  font-weight: 800;\n}\n.official-notice-red { color: #ff3232; display: block; }\n.official-notice-white { color: #fff; display: block; }\n.official-notice-subtitle {\n  margin: 22px auto 0;\n  max-width: 95%;\n  font-size: clamp(15px, 4vw, 21px);\n  line-height: 1.75;\n  color: #d0d0d0;\n}\n.official-notice-content {\n  margin-top: 28px;\n  background: #1d1d1d;\n  border-left: 5px solid #ff3232;\n  border-radius: 16px;\n  padding: 20px;\n  text-align: left;\n  font-size: clamp(14px, 3.7vw, 17px);\n  line-height: 1.8;\n  color: #ddd;\n}\n.official-notice-content strong {\n  display: block;\n  font-size: clamp(18px, 4.5vw, 22px);\n  margin-bottom: 10px;\n}\n.official-notice-btn {\n  display: inline-block;\n  margin-top: 30px;\n  padding: 15px 34px;\n  background: #ff3232;\n  color: #fff;\n  text-decoration: none;\n  font-weight: 700;\n  font-size: clamp(15px, 4vw, 18px);\n  border-radius: 999px;\n  transition: .25s;\n}\n.official-notice-btn:hover {\n  transform: translateY(-3px);\n  background: #ff5555;\n  box-shadow: 0 12px 28px rgba(255,0,0,.35);\n}\n.official-notice-footer {\n  margin-top: 26px;\n  font-size: 13px;\n  color: #888;\n}\n@media (max-width: 480px) {\n  .official-notice-wrapper { padding: 10px; }\n  .official-notice-card { padding: 18px 15px; border-radius: 18px; }\n  .official-notice-logo { width: 90px; height: 90px; animation: none; }\n  .official-notice-badge { padding: 8px 16px; animation: none; }\n  .official-notice-card h1 { font-size: clamp(28px, 10vw, 40px); line-height: 1.12; }\n  .official-notice-subtitle { font-size: 15px; line-height: 1.65; }\n  .official-notice-content { padding: 16px; font-size: 14px; }\n  .official-notice-btn {\n    display: block;\n    width: 100%;\n    max-width: 300px;\n    margin: 24px auto 0;\n    padding: 14px;\n  }\n}\n</style>\n<div class=\"official-notice-wrapper\">\n  <div class=\"official-notice-card\">\n    <img class=\"official-notice-logo\" src=\"https://pbs.twimg.com/profile_images/1551281701615124480/tMCAdnlQ_400x400.jpg\" alt=\"XMA Logo\">\n    <div class=\"official-notice-badge\">🚨 OFFICIAL NOTICE</div>\n    <h1>\n      <span class=\"official-notice-red\">XMA DHANBAD</span>\n      <span class=\"official-notice-white\">Closed Due to Doping</span>\n    </h1>\n    <div class=\"official-notice-subtitle\">\n      An official announcement has been issued regarding the temporary closure of XMA Dhanbad following allegations related to banned substances.\n    </div>\n    <div class=\"official-notice-content\">\n      <strong>Notice</strong>\n      XMA Dhanbad has been temporarily closed following reports concerning the alleged use of banned substances. Members and visitors are advised to await further official updates regarding future operations.\n    </div>\n    <a class="official-notice-btn" href=\"https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR100M-E9wnbmB5a07hAGMFBeKv89n9wapNYRJ-u4inHMXst2h2H7J7DcrC&s=10\" target="_blank\">\n      📰 Read Full News\n    </a>\n    <div class=\"official-notice-footer\">© 2026 Xtreme Martial Arts India</div>\n  </div>\n</div>',
                        'bg_type' => 'solid',
                        'bg_color' => 'transparent',
                        'padding' => '0px'
                    ]
                ]
            ];
        }

        // Return a basic initial structure for the specific layout.
        // In database/seed.php we will write richer templates, but this is a nice fall-back.
        return [
            [
                'id' => 'hero-1',
                'type' => 'hero',
                'content' => [
                    'title' => $title,
                    'subtitle' => 'Interactive visual document powered by TemplateLink.',
                    'btn_text' => 'Get Started',
                    'btn_url' => '#',
                    'bg_type' => 'gradient',
                    'bg_value' => 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                    'text_color' => '#ffffff',
                    'padding' => '80px 20px',
                    'text_align' => 'center'
                ]
            ],
            [
                'id' => 'text-1',
                'type' => 'text',
                'content' => [
                    'html' => '<h2>Introduction</h2><p>Double-click to edit this content. Use the inspector panels on the right side to change formatting details, colors, spacing, and hyperlinks dynamically.</p>',
                    'padding' => '40px 20px',
                    'bg_color' => '#ffffff',
                    'text_color' => '#1f2937'
                ]
            ]
        ];
    }
}
