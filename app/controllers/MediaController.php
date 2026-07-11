<?php
namespace App\Controllers;

use App\Models\Media;

class MediaController extends Controller {
    public function __construct() {
        $this->requireAuth();
    }

    /**
     * Show all media assets.
     */
    public function index() {
        $mediaModel = new Media();
        $assets = $mediaModel->getAll();

        $this->render('admin/media', [
            'title' => 'Media Library',
            'active_page' => 'media',
            'assets' => $assets
        ]);
    }

    /**
     * Upload an asset (handles both regular forms and editor AJAX calls).
     */
    public function upload() {
        $this->validateCsrf();

        if (empty($_FILES['media_file'])) {
            $this->handleUploadResponse(['error' => 'No file uploaded.'], 400);
        }

        $file = $_FILES['media_file'];
        
        // 1. Validate Upload Error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->handleUploadResponse(['error' => 'File upload error code: ' . $file['error']], 400);
        }

        // 2. Validate Size (Max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            $this->handleUploadResponse(['error' => 'File size exceeds maximum limit of 10MB.'], 400);
        }

        // 3. Validate MIME type
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'audio/aac' => 'aac',
            'audio/x-m4a' => 'm4a',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/vnd.android.package-archive' => 'apk'
        ];

        // Fetch actual mime-type safely
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $originalExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'mp4', 'mp3', 'wav', 'ogg', 'm4a', 'aac', 'zip', 'apk'];

        if ($mimeType === 'application/octet-stream' && in_array($originalExtension, ['zip', 'apk', 'mp4'])) {
            $extension = $originalExtension;
        } elseif (array_key_exists($mimeType, $allowedTypes)) {
            $extension = $allowedTypes[$mimeType];
        } elseif (in_array($originalExtension, $allowedExtensions)) {
            $extension = $originalExtension;
        } else {
            $this->handleUploadResponse(['error' => 'Invalid file format. Allowed types: JPG, PNG, GIF, WEBP, SVG, PDF, MP4, MP3, WAV, OGG, M4A, AAC, ZIP, APK.'], 400);
        }

        // 4. Secure naming & directory checks
        $extension = $allowedTypes[$mimeType];
        $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
        
        if (!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        // 5. Write file & Database registry
        $targetPath = UPLOAD_DIR . '/' . $safeName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Write block for PHP script execution in uploads using a custom .htaccess if not already done
            $htaccessFile = UPLOAD_DIR . '/.htaccess';
            if (!file_exists($htaccessFile)) {
                file_put_contents($htaccessFile, "removehandler .php\nSetHandler default-handler");
            }

            $mediaModel = new Media();
            $dbPath = 'uploads/' . $safeName;
            $mediaId = $mediaModel->create($file['name'], $dbPath, $mimeType, $file['size']);
            
            $url = BASE_URL . $dbPath;

            $this->handleUploadResponse([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'id' => $mediaId,
                'name' => htmlspecialchars($file['name']),
                'url' => $url,
                'path' => $dbPath
            ]);
        } else {
            $this->handleUploadResponse(['error' => 'Failed to write uploaded file to destination.'], 500);
        }
    }

    /**
     * Delete an asset.
     */
    public function delete() {
        $this->validateCsrf();
        
        $id = (int)($_POST['media_id'] ?? 0);
        if ($id <= 0) {
            $this->handleUploadResponse(['error' => 'Invalid asset ID.'], 400);
        }

        $mediaModel = new Media();
        $media = $mediaModel->getById($id);

        if (!$media) {
            $this->handleUploadResponse(['error' => 'Asset not found.'], 404);
        }

        // Unlink physical file
        $physicalPath = APP_ROOT . '/public/' . $media['file_path'];
        if (file_exists($physicalPath)) {
            unlink($physicalPath);
        }

        // Delete DB record
        $mediaModel->delete($id);

        $this->handleUploadResponse([
            'success' => true,
            'message' => 'Asset deleted successfully.'
        ]);
    }

    /**
     * Helper to output correct response format (JSON or Redirect) depending on request.
     */
    private function handleUploadResponse($data, $statusCode = 200) {
        $isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
        
        if ($isAjax) {
            $this->json($data, $statusCode);
        } else {
            if ($statusCode === 200 && !empty($data['success'])) {
                $this->setFlash('success', $data['message']);
            } else {
                $this->setFlash('error', $data['error'] ?? 'An error occurred.');
            }
            $this->redirect('admin/media');
        }
    }
}
