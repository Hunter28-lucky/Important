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
