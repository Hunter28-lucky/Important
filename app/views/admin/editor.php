<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Editor: <?= htmlspecialchars($template['title']) ?> - TemplateLink Builder</title>
    
    <!-- Google Fonts & Icon packs -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Core styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/viewer.css">
    
    <!-- Dedicated Editor Workspace Overrides -->
    <style>
        body { background-color: #0c0a0f; overflow: hidden; height: 100vh; }
        
        .editor-workspace {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        /* Top Navigation Bar */
        .editor-topbar {
            height: 64px;
            background-color: #121016;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            flex-shrink: 0;
            z-index: 10;
        }
        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .btn-back { color: var(--text-muted); font-size: 1.2rem; text-decoration: none; padding: 0.5rem; border-radius: 8px; transition: background 0.2s; }
        .btn-back:hover { color: white; background: rgba(255,255,255,0.05); }
        
        .topbar-settings-inline { display: flex; align-items: center; gap: 0.75rem; }
        .topbar-input { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; color: white; font-family: inherit; font-size: 0.9rem; padding: 0.4rem 0.8rem; outline: none; }
        .topbar-input:focus { border-color: var(--primary); }
        .topbar-slug-prefix { color: var(--text-muted); font-size: 0.8rem; margin-right: -0.4rem; font-family: monospace; }
        
        .topbar-center { display: flex; align-items: center; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 3px; }
        .viewport-btn { background: transparent; border: none; color: var(--text-muted); width: 36px; height: 32px; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
        .viewport-btn:hover { color: white; }
        .viewport-btn.active { background: var(--primary); color: white; }
        
        .topbar-right { display: flex; align-items: center; gap: 0.75rem; }
        
        /* Three Column Body Split */
        .editor-body-split {
            display: flex;
            flex-grow: 1;
            overflow: hidden;
            height: calc(100vh - 64px);
        }
        
        /* Left: Toolbox */
        .editor-toolbox-sidebar {
            width: 280px;
            background-color: #121016;
            border-right: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow-y: auto;
        }
        .toolbox-header { padding: 1.2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .toolbox-header h4 { font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
        .toolbox-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; padding: 1.5rem; }
        .toolbox-item {
            background-color: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 1rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .toolbox-item:hover { background-color: rgba(99,102,241,0.08); border-color: rgba(99,102,241,0.25); color: #cbd5e1; transform: translateY(-1px); }
        .toolbox-item i { font-size: 1.25rem; color: #818cf8; }
        .toolbox-item span { font-size: 0.75rem; font-weight: 500; }
        
        /* Center: Canvas workspace */
        .editor-canvas-pane {
            flex-grow: 1;
            background-color: #070509;
            overflow-y: auto;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 3rem 1.5rem;
        }
        .canvas-container {
            width: 100%;
            max-width: 100%;
            transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            min-height: 600px;
            border-radius: 16px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            overflow: hidden;
        }
        .canvas-container.tablet-view { max-width: 768px; }
        .canvas-container.mobile-view { max-width: 375px; }
        
        #editorCanvas {
            min-height: 600px;
            background-color: #ffffff;
            color: #1f2937;
        }
        
        /* Hover and selections inside canvas */
        .editor-block-wrapper {
            position: relative;
            border: 2px solid transparent;
            transition: border 0.15s;
        }
        .editor-block-wrapper:hover {
            border-color: rgba(99, 102, 241, 0.4);
        }
        .editor-block-wrapper.selected {
            border-color: var(--primary);
        }
        .block-toolbar {
            position: absolute;
            top: -28px; right: 8px;
            background-color: var(--primary);
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 6px 6px 0 0;
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 5;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
            box-shadow: var(--shadow-sm);
        }
        .editor-block-wrapper:hover .block-toolbar, .editor-block-wrapper.selected .block-toolbar {
            opacity: 1;
            pointer-events: auto;
        }
        .block-type-badge { font-weight: 700; font-family: monospace; }
        .toolbar-actions { display: flex; align-items: center; gap: 4px; border-left: 1px solid rgba(255,255,255,0.25); padding-left: 8px; }
        .btn-tool { background: transparent; border: none; color: white; cursor: pointer; padding: 2px 4px; font-size: 0.75rem; border-radius: 4px; transition: background 0.15s; }
        .btn-tool:hover { background: rgba(255,255,255,0.2); }
        .btn-tool:disabled { opacity: 0.3; cursor: not-allowed; }
        
        /* Highlight contenteditable */
        [contenteditable="true"]:hover {
            outline: 1px dashed rgba(99, 102, 241, 0.6);
            outline-offset: 4px;
        }
        [contenteditable="true"]:focus {
            outline: 2px solid var(--primary);
            outline-offset: 4px;
            background: rgba(99, 102, 241, 0.05);
        }
        
        .canvas-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; height: 500px; text-align: center; color: #94a3b8; }
        .canvas-empty-state i { font-size: 4rem; opacity: 0.15; }
        .canvas-empty-state h3 { font-size: 1.5rem; color: #64748b; }
        
        /* Right: Inspector */
        .editor-inspector-sidebar {
            width: 320px;
            background-color: #121016;
            border-left: 1px solid rgba(255,255,255,0.06);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 10;
        }
        .inspector-header { padding: 1.2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.03); display: flex; justify-content: space-between; align-items: center; }
        .inspector-header h3 { font-size: 0.95rem; font-weight: 600; color: white; }
        .block-id-text { font-family: monospace; font-size: 0.7rem; color: var(--text-muted); }
        .btn-close-inspector { background: none; border: none; color: var(--text-muted); font-size: 1.1rem; cursor: pointer; }
        .btn-close-inspector:hover { color: white; }
        
        .inspector-scrollable { flex-grow: 1; overflow-y: auto; padding: 1.5rem; }
        .inspector-section { margin-bottom: 2rem; }
        .inspector-section h4 { font-size: 0.8rem; text-transform: uppercase; color: var(--primary); letter-spacing: 0.5px; margin-bottom: 1rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 0.3rem; }
        
        .form-group-sm { margin-bottom: 1rem; }
        .form-group-sm label { font-size: 0.8rem; margin-bottom: 0.3rem; color: var(--text-muted); }
        .prop-input, .prop-select { width: 100%; background: #08060a; border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; color: white; font-family: inherit; font-size: 0.85rem; padding: 0.5rem; outline: none; }
        .prop-input:focus, .prop-select:focus { border-color: var(--primary); }
        .prop-range { width: 100%; height: 5px; background: rgba(255,255,255,0.1); border-radius: 5px; outline: none; -webkit-appearance: none; }
        .prop-range::-webkit-slider-thumb { -webkit-appearance: none; width: 15px; height: 15px; border-radius: 50%; background: var(--primary); cursor: pointer; }
        .inline-checkbox { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
        .inline-checkbox input { cursor: pointer; width: 15px; height: 15px; }
        .input-with-color { display: flex; gap: 0.5rem; align-items: center; }
        .prop-color-input { width: 36px; height: 34px; padding: 0; border: 1px solid rgba(255,255,255,0.08); background: transparent; border-radius: 6px; cursor: pointer; }
        .color-text { flex-grow: 1; }
        
        /* Nested layout forms */
        .nested-items-inspector { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1rem; }
        .nested-item-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius: 10px; padding: 0.8rem; }
        .nested-header { display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.8rem; text-transform: uppercase; }
        .btn-remove-nested { background: none; border: none; color: var(--danger); font-size: 1.1rem; cursor: pointer; line-height: 1; }
        .prop-nested-input { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); border-radius: 6px; color: white; font-family: inherit; font-size: 0.8rem; padding: 0.4rem; outline: none; margin-bottom: 0.5rem; }
        .prop-nested-checkbox { margin-right: 0.5rem; }
        
        .w-full { width: 100%; }
        
        /* Toast notification */
        .editor-toast {
            position: fixed; bottom: 2rem; right: 2rem;
            background: #18181b; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;
            padding: 0.75rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
            transform: translateY(100px); opacity: 0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 200; font-size: 0.9rem; font-weight: 500;
        }
        .editor-toast.visible { transform: translateY(0); opacity: 1; }
        .editor-toast.success i { color: var(--success); }
        .editor-toast.error i { color: var(--danger); }
        
        /* Dialog Modal styling overrides */
        dialog::backdrop { background-color: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); }
        dialog {
            background-color: #121016; border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; padding: 1.5rem; max-width: 600px; width: 90%;
            color: white; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            margin: auto; outline: none;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { font-size: 1.2rem; font-weight: 600; }
        .close-modal { background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; line-height: 1; }
        .close-modal:hover { color: white; }
        
        .picker-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; max-height: 380px; overflow-y: auto; padding: 4px; }
        .picker-media-item { border: 1px solid rgba(255,255,255,0.04); border-radius: 10px; overflow: hidden; height: 100px; cursor: pointer; transition: border-color 0.2s; position: relative; background: rgba(0,0,0,0.2); }
        .picker-media-item:hover { border-color: var(--primary); }
        .picker-media-item img { width: 100%; height: 100%; object-fit: cover; }
        .picker-icon-placeholder { display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; font-size: 2rem; color: var(--text-muted); opacity: 0.5; }
        .picker-media-name { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); font-size: 0.65rem; padding: 2px 4px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; }
        
        .inspector-empty { text-align: center; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; justify-content: center; height: 400px; padding: 20px; gap: 1rem; }
        .inspector-empty i { font-size: 2.5rem; opacity: 0.15; }
        .inspector-empty p { font-size: 0.8rem; line-height: 1.4; opacity: 0.7; }
        
        /* Mobile responsiveness media queries */
        @media (max-width: 992px) {
            body { overflow: auto !important; height: auto !important; }
            .editor-workspace { height: auto !important; }
            .editor-body-split {
                flex-direction: column;
                height: auto !important;
                overflow: visible !important;
            }
            .editor-toolbox-sidebar {
                width: 100% !important;
                height: auto !important;
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.06);
                overflow-y: visible !important;
            }
            .toolbox-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                padding: 1rem !important;
                gap: 0.5rem !important;
            }
            .toolbox-item {
                padding: 0.6rem 0.3rem !important;
            }
            .editor-canvas-pane {
                padding: 1.5rem 0.5rem !important;
                overflow: visible !important;
                width: 100% !important;
                height: auto !important;
            }
            .canvas-container {
                border-radius: 8px;
                box-shadow: none;
                min-height: auto !important;
                max-width: 100% !important;
            }
            #editorCanvas {
                min-height: 400px !important;
            }
            .editor-inspector-sidebar {
                width: 100% !important;
                height: auto !important;
                border-left: none;
                border-top: 1px solid rgba(255,255,255,0.06);
            }
            .editor-topbar {
                flex-direction: column;
                height: auto !important;
                padding: 1rem !important;
                gap: 0.75rem !important;
            }
            .topbar-left, .topbar-center, .topbar-right {
                width: 100% !important;
                justify-content: center !important;
            }
            .topbar-settings-inline {
                width: 100%;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
            }
        }
        @media (max-width: 576px) {
            .toolbox-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
    </style>
</head>
<body>
    <div class="editor-workspace">
        <!-- 1. Top Navbar Controls -->
        <header class="editor-topbar">
            <div class="topbar-left">
                <a href="<?= BASE_URL ?>admin/templates" class="btn-back" title="Back to Templates"><i class="fa-solid fa-arrow-left"></i></a>
                <div class="topbar-settings-inline">
                    <input type="text" id="tmplTitle" class="topbar-input" style="font-weight: 600; width: 220px;" value="<?= htmlspecialchars($template['title']) ?>" placeholder="Document Title">
                    <div style="display: flex; align-items: center;">
                        <span class="topbar-slug-prefix">/view/</span>
                        <input type="text" id="tmplSlug" class="topbar-input" style="width: 140px; font-family: monospace; font-size: 0.8rem;" value="<?= htmlspecialchars($template['slug']) ?>" placeholder="slug-path">
                    </div>
                    
                    <select id="tmplCategory" class="topbar-input" style="width: 120px; cursor: pointer;">
                        <option value="">No Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $template['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="topbar-center">
                <button type="button" class="viewport-btn active" data-view="desktop" title="Desktop View"><i class="fa-solid fa-desktop"></i></button>
                <button type="button" class="viewport-btn" data-view="tablet" title="Tablet View"><i class="fa-solid fa-tablet-screen-button"></i></button>
                <button type="button" class="viewport-btn" data-view="mobile" title="Mobile View"><i class="fa-solid fa-mobile-screen-button"></i></button>
            </div>
            
            <div class="topbar-right">
                <button type="button" class="btn btn-sm btn-secondary" id="btnSeoSettings" title="SEO & Link Preview" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.8rem; border-radius: 8px;">
                    <i class="fa-solid fa-share-nodes"></i> SEO
                </button>
                <select id="tmplStatus" class="topbar-input" style="width: 100px; cursor: pointer; border-color: rgba(99,102,241,0.15); background-color: rgba(99,102,241,0.05); color: #cbd5e1;">
                    <option value="draft" <?= $template['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $template['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
                <button type="button" class="btn btn-primary" id="btnSaveTemplate">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Save Layout
                </button>
            </div>
        </header>
        
        <!-- 2. Main Visual Workspace -->
        <div class="editor-body-split">
            <!-- Left Panel: Toolbox -->
            <aside class="editor-toolbox-sidebar">
                <div class="toolbox-header">
                    <h4>Add Visual Block</h4>
                </div>
                <div class="toolbox-grid">
                    <div class="toolbox-item" data-type="hero">
                        <i class="fa-solid fa-pager"></i>
                        <span>Hero Block</span>
                    </div>
                    <div class="toolbox-item" data-type="text">
                        <i class="fa-solid fa-font"></i>
                        <span>Text Article</span>
                    </div>
                    <div class="toolbox-item" data-type="card_grid">
                        <i class="fa-solid fa-grip"></i>
                        <span>Card Grid</span>
                    </div>
                    <div class="toolbox-item" data-type="accordion">
                        <i class="fa-solid fa-list-ul"></i>
                        <span>Accordion</span>
                    </div>
                    <div class="toolbox-item" data-type="pricing">
                        <i class="fa-solid fa-tags"></i>
                        <span>Pricing Grid</span>
                    </div>
                    <div class="toolbox-item" data-type="testimonial">
                        <i class="fa-solid fa-quote-left"></i>
                        <span>Testimonial</span>
                    </div>
                    <div class="toolbox-item" data-type="image">
                        <i class="fa-solid fa-image"></i>
                        <span>Single Image</span>
                    </div>
                    <div class="toolbox-item" data-type="gallery">
                        <i class="fa-solid fa-images"></i>
                        <span>Grid Gallery</span>
                    </div>
                    <div class="toolbox-item" data-type="carousel">
                        <i class="fa-solid fa-sliders"></i>
                        <span>Carousel</span>
                    </div>
                    <div class="toolbox-item" data-type="youtube">
                        <i class="fa-brands fa-youtube"></i>
                        <span>YouTube Video</span>
                    </div>
                    <div class="toolbox-item" data-type="pdf">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>PDF Viewer</span>
                    </div>
                    <div class="toolbox-item" data-type="map">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span>Google Map</span>
                    </div>
                    <div class="toolbox-item" data-type="progress">
                        <i class="fa-solid fa-percent"></i>
                        <span>Progress Bar</span>
                    </div>
                    <div class="toolbox-item" data-type="timeline">
                        <i class="fa-solid fa-timeline"></i>
                        <span>Timeline</span>
                    </div>
                    <div class="toolbox-item" data-type="html">
                        <i class="fa-solid fa-code"></i>
                        <span>Custom HTML</span>
                    </div>
                    <div class="toolbox-item" data-type="webcam">
                        <i class="fa-solid fa-camera"></i>
                        <span>Webcam Capture</span>
                    </div>
                    <div class="toolbox-item" data-type="sound">
                        <i class="fa-solid fa-music"></i>
                        <span>Sound Block</span>
                    </div>
                    <div class="toolbox-item" data-type="button">
                        <i class="fa-solid fa-square-caret-right"></i>
                        <span>Action Button</span>
                    </div>
                </div>
            </aside>
            
            <!-- Center Panel: Live responsive canvas preview -->
            <section class="editor-canvas-pane" id="canvasPane">
                <div class="canvas-container desktop-view" id="previewCanvasContainer">
                    <div id="editorCanvas">
                        <!-- Javascript will compile and inject live DOM preview blocks here -->
                    </div>
                </div>
            </section>
            
            <!-- Right Panel: Inspector -->
            <aside class="editor-inspector-sidebar">
                <div class="inspector-header">
                    <h3>Property Inspector</h3>
                    <button class="btn-close-inspector" id="closeInspectorBtn" title="Deselect Block">&times;</button>
                </div>
                <div class="inspector-scrollable" id="inspectorPanel">
                    <!-- Javascript will inject properties settings fields depending on selected block type -->
                </div>
            </aside>
        </div>
    </div>

    <!-- Media Library Picker Dialog (Native modal element) -->
    <dialog id="mediaPickerModal" closedby="any" aria-labelledby="modalTitle">
        <div class="modal-header">
            <h3 id="modalTitle">Select from Media Library</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (empty($assets)): ?>
                <div class="empty-state" style="padding: 1.5rem 0;">
                    <i class="fa-solid fa-folder-open"></i>
                    <p style="font-size:0.85rem;">No files uploaded yet. Navigate to the Media Library from your dashboard to upload files first.</p>
                </div>
            <?php else: ?>
                <div class="picker-media-grid">
                    <?php foreach ($assets as $asset): 
                        $isImg = strpos($asset['file_type'], 'image/') === 0;
                        $fullPathUrl = BASE_URL . $asset['file_path'];
                    ?>
                        <div class="picker-media-item" data-url="<?= $fullPathUrl ?>" title="Select: <?= htmlspecialchars($asset['file_name']) ?>">
                            <?php if ($isImg): ?>
                                <img src="<?= $fullPathUrl ?>" alt="media" loading="lazy">
                            <?php else: 
                                $isAudio = strpos($asset['file_type'], 'audio/') === 0;
                                $iconClass = 'fa-file';
                                if ($asset['file_type'] === 'application/pdf') {
                                    $iconClass = 'fa-file-pdf';
                                } elseif ($isAudio) {
                                    $iconClass = 'fa-file-audio';
                                }
                            ?>
                                <div class="picker-icon-placeholder">
                                    <i class="fa-solid <?= $iconClass ?>"></i>
                                </div>
                            <?php endif; ?>
                            <div class="picker-media-name"><?= htmlspecialchars($asset['file_name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </dialog>

    <!-- SEO / Link Share Preview Dialog -->
    <dialog id="seoSettingsModal" closedby="any" aria-labelledby="seoModalTitle">
        <div class="modal-header">
            <h3 id="seoModalTitle"><i class="fa-solid fa-share-nodes" style="color: var(--primary); margin-right: 0.4rem;"></i> Link Share Preview (SEO)</h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 1rem;">
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; margin-bottom: 0.5rem;">Configure how this link appears when shared on social media (WhatsApp, Facebook, LinkedIn, Twitter). These Open Graph tags control the title, description, and image shown in link previews.</p>
            
            <div class="form-group-sm">
                <label style="font-weight: 600; color: #cbd5e1;">OG Title (Link Preview Title)</label>
                <input type="text" class="prop-input" id="seo_og_title" placeholder="e.g. Important Notice from XMA India">
            </div>
            <div class="form-group-sm">
                <label style="font-weight: 600; color: #cbd5e1;">OG Description (Link Preview Description)</label>
                <textarea class="prop-input" id="seo_og_description" rows="3" placeholder="e.g. Read this important notice about..."></textarea>
            </div>
            <div class="form-group-sm">
                <label style="font-weight: 600; color: #cbd5e1;">OG Image URL (Link Preview Image / Logo)</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" class="prop-input" id="seo_og_image" placeholder="https://example.com/image.jpg" style="flex: 1;">
                    <button type="button" class="btn btn-sm btn-secondary" id="btnPickOgImage" style="white-space: nowrap;"><i class="fa-solid fa-photo-film"></i> Pick</button>
                </div>
                <small style="color: #888;">The image/logo that appears in the link preview card. Recommended size: 1200×630px.</small>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.05); margin: 0.5rem 0;">
            
            <div class="form-group-sm">
                <label style="font-weight: 600; color: #cbd5e1;">Meta Title (Browser Tab / Search Engine)</label>
                <input type="text" class="prop-input" id="seo_meta_title" placeholder="Page title for search engines">
            </div>
            <div class="form-group-sm">
                <label style="font-weight: 600; color: #cbd5e1;">Meta Description (Search Engine Snippet)</label>
                <textarea class="prop-input" id="seo_meta_description" rows="2" placeholder="Brief description for search results"></textarea>
            </div>
            
            <button type="button" class="btn btn-primary w-full" id="btnSaveSeoSettings" style="margin-top: 0.5rem;">
                <i class="fa-solid fa-check"></i> Apply SEO Settings
            </button>
        </div>
    </dialog>

    <!-- Initialise Editor App JS -->
    <script src="<?= BASE_URL ?>assets/js/editor.js?v=<?= filemtime(APP_ROOT . '/public/assets/js/editor.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialise builder object
            Editor.init({
                templateId: <?= $template['id'] ?>,
                initialBlocks: <?= $template['content'] ? $template['content'] : '[]'  ?>,
                csrfToken: '<?= $_SESSION['csrf_token'] ?>',
                baseUrl: '<?= BASE_URL ?>',
                seoData: {
                    meta_title: <?= json_encode($template['meta_title'] ?? '') ?>,
                    meta_description: <?= json_encode($template['meta_description'] ?? '') ?>,
                    og_title: <?= json_encode($template['og_title'] ?? '') ?>,
                    og_description: <?= json_encode($template['og_description'] ?? '') ?>,
                    og_image: <?= json_encode($template['og_image'] ?? '') ?>
                }
            });
        });
    </script>
</body>
</html>
