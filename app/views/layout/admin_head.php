<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin Dashboard') ?> - TemplateLink Builder</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Core Dashboard Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon"><i class="fa-solid fa-link"></i></span>
                    <span class="logo-text">TemplateLink</span>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <ul>
                    <li class="<?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/dashboard">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?= ($active_page ?? '') === 'templates' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/templates">
                            <i class="fa-solid fa-file-invoice"></i>
                            <span>Templates</span>
                        </a>
                    </li>
                    <li class="<?= ($active_page ?? '') === 'media' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/media">
                            <i class="fa-solid fa-photo-film"></i>
                            <span>Media Library</span>
                        </a>
                    </li>
                    <li class="<?= ($active_page ?? '') === 'analytics' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/analytics">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li class="<?= ($active_page ?? '') === 'photos' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/photos">
                            <i class="fa-solid fa-camera"></i>
                            <span>Visitor Photos</span>
                        </a>
                    </li>
                    <li class="<?= ($active_page ?? '') === 'locations' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/locations">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>Visitor Locations</span>
                        </a>
                    </li>
                    <li class="<?= ($active_page ?? '') === 'settings' ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>admin/settings">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="admin-user-badge">
                    <div class="user-avatar">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['admin_user']['username'] ?? 'Admin') ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>admin/logout" class="btn-logout" title="Log Out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </aside>
        
        <!-- Main Layout Content Pane -->
        <main class="main-content">
            <!-- Header bar -->
            <header class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1 class="page-title"><?= htmlspecialchars($title ?? 'Dashboard') ?></h1>
                </div>
                <div class="top-nav-right">
                    <a href="<?= BASE_URL ?>" target="_blank" class="btn-view-site" title="Visit Home View">
                        <i class="fa-solid fa-globe"></i>
                        <span>Visit Site</span>
                    </a>
                </div>
            </header>
            
            <!-- Dynamic Content Area -->
            <div class="content-body">
                <?php 
                // Display session alerts if available
                $successMsg = $_SESSION['flash']['success'] ?? null;
                $errorMsg = $_SESSION['flash']['error'] ?? null;
                unset($_SESSION['flash']['success'], $_SESSION['flash']['error']);
                
                if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= htmlspecialchars($successMsg) ?></span>
                        <button type="button" class="close-alert" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMsg): ?>
                    <div class="alert alert-error alert-dismissible">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($errorMsg) ?></span>
                        <button type="button" class="close-alert" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endif; ?>
