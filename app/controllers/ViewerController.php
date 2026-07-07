<?php
namespace App\Controllers;

use App\Models\Template;
use App\Models\Analytics;
use App\Models\Category;

class ViewerController extends Controller {
    /**
     * Show a portfolio landing page of all publicly published templates.
     */
    public function index() {
        $templateModel = new Template();
        $categoryModel = new Category();

        $categories = $categoryModel->getAll();
        
        // Fetch only published templates for public view
        $filters = ['status' => 'published'];
        if (isset($_GET['category_id'])) {
            $filters['category_id'] = $_GET['category_id'];
        }
        if (isset($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        $templates = $templateModel->getAll($filters);

        $this->render('viewer/index', [
            'title' => 'Explore Templates',
            'categories' => $categories,
            'templates' => $templates
        ], 'plain');
    }

    /**
     * View a specific template by its slug (clean public URL).
     */
    public function view($slug) {
        $templateModel = new Template();
        $template = $templateModel->getBySlug($slug);

        if (!$template) {
            $this->abort404();
        }

        // Enforce draft privacy unless logged in as admin
        if ($template['status'] === 'draft' && !$this->isLoggedIn()) {
            $this->abort404();
        }

        // Track analytics view asynchronously/safely
        $this->logViewerTraffic($template['id']);

        $this->render('viewer/template', [
            'template' => $template,
            'title' => $template['title']
        ], 'plain');
    }

    /**
     * Telemetry tracking API for link clicks inside shared templates.
     */
    public function trackClick() {
        // Basic parameters
        $templateId = (int)($_POST['template_id'] ?? 0);
        $linkUrl = trim($_POST['link_url'] ?? '');

        if ($templateId <= 0 || empty($linkUrl)) {
            $this->json(['error' => 'Invalid parameters'], 400);
        }

        $analyticsModel = new Analytics();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $sessionId = session_id();

        $analyticsModel->logClick($templateId, $linkUrl, $ip, $sessionId);

        $this->json(['success' => true]);
    }

    /**
     * Helper to write traffic records to the database.
     */
    private function logViewerTraffic($templateId) {
        // Prevent logging admin views to keep stats clean
        if ($this->isLoggedIn()) {
            return;
        }

        try {
            $analyticsModel = new Analytics();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            $sessionId = session_id();

            $analyticsModel->logView($templateId, $ip, $ua, $referrer, $sessionId);
        } catch (\Exception $e) {
            // Log quietly to not break the page rendering if analytics fails
            error_log("Analytics logging failed: " . $e->getMessage());
        }
    }

    /**
     * Abort request and show clean 404 page.
     */
    private function abort404() {
        http_response_code(404);
        $title = "404 - Document Not Found";
        require APP_ROOT . '/public/index.php'; // loads standard 404 template
        exit;
    }

    /**
     * Submit a captured webcam photo from the viewer.
     */
    public function submitPhoto() {
        $templateId = (int)($_POST['template_id'] ?? 0);
        $photoData = $_POST['photo_data'] ?? ''; // base64 string

        if ($templateId <= 0 || empty($photoData)) {
            $this->json(['error' => 'Invalid parameters'], 400);
        }

        // Validate base64 structure: "data:image/jpeg;base64,..."
        if (strpos($photoData, 'data:image/jpeg;base64,') !== 0) {
            $this->json(['error' => 'Invalid photo format. Only JPEG is allowed.'], 400);
        }

        // Strip header and decode data
        $imgData = str_replace('data:image/jpeg;base64,', '', $photoData);
        $imgData = str_replace(' ', '+', $imgData);
        $decodedData = base64_decode($imgData);

        if (!$decodedData) {
            $this->json(['error' => 'Decoding failed.'], 400);
        }

        // Create uploads/photos directory if it doesn't exist
        $photosDir = UPLOAD_DIR . '/photos';
        if (!file_exists($photosDir)) {
            mkdir($photosDir, 0755, true);
            file_put_contents($photosDir . '/.htaccess', "removehandler .php\nSetHandler default-handler");
        }

        // Secure randomized filename
        $fileName = bin2hex(random_bytes(16)) . '.jpg';
        $targetPath = $photosDir . '/' . $fileName;
        $dbPath = 'uploads/photos/' . $fileName;

        if (file_put_contents($targetPath, $decodedData)) {
            // Write database entry
            $db = \App\Config\Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $stmt = $db->prepare("INSERT INTO visitor_photos (template_id, photo_path, visitor_ip) VALUES (?, ?, ?)");
            $stmt->execute([$templateId, $dbPath, $ip]);

            $this->json(['success' => true, 'photo_url' => BASE_URL . $dbPath]);
        } else {
            $this->json(['error' => 'Failed to save snapshot file on server.'], 500);
        }
    }
}
