<?php
// Recipient Viewer Mode - Completely clean template renderer
$blocks = json_decode($template['content'] ?? '[]', true);

// Fetch custom global CSS settings
$settingsModel = new \App\Models\Settings();
$globalCss = $settingsModel->get('custom_css', '');

// Helper to compile CSS styles inline
function getViewerStyleString($content) {
    $styles = [];
    if (($content['bg_type'] ?? '') === 'solid' && !empty($content['bg_color'])) {
        $styles[] = "background-color: " . $content['bg_color'];
    } elseif (($content['bg_type'] ?? '') === 'gradient' && !empty($content['bg_value'])) {
        $styles[] = "background: " . $content['bg_value'];
    } elseif (!empty($content['bg_color'])) {
        $styles[] = "background-color: " . $content['bg_color'];
    }
    
    if (!empty($content['text_color'])) $styles[] = "color: " . $content['text_color'];
    if (!empty($content['padding'])) $styles[] = "padding: " . $content['padding'];
    if (!empty($content['font_family'])) $styles[] = "font-family: " . $content['font_family'];
    if (!empty($content['font_size'])) $styles[] = "font-size: " . $content['font_size'];
    
    return implode('; ', $styles);
}

// Extract Youtube ID
function getYoutubeVideoId($url) {
    $regExp = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/';
    preg_match($regExp, $url, $matches);
    return (isset($matches[2]) && strlen($matches[2]) === 11) ? $matches[2] : 'dQw4w9WgXcQ';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Optimization -->
    <title><?= htmlspecialchars($template['meta_title'] ?? $template['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($template['meta_description'] ?? '') ?>">
    <link rel="canonical" href="<?= BASE_URL . 'view/' . htmlspecialchars($template['slug']) ?>">
    
    <!-- Open Graph (Facebook/LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= BASE_URL . 'view/' . htmlspecialchars($template['slug']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($template['og_title'] ?? $template['meta_title'] ?? $template['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($template['og_description'] ?? $template['meta_description'] ?? '') ?>">
    <?php if (!empty($template['og_image'])): ?>
        <meta property="og:image" content="<?= htmlspecialchars($template['og_image']) ?>">
    <?php elseif (!empty($template['thumbnail_url'])): ?>
        <meta property="og:image" content="<?= htmlspecialchars($template['thumbnail_url']) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($template['og_title'] ?? $template['meta_title'] ?? $template['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($template['og_description'] ?? $template['meta_description'] ?? '') ?>">
    <?php if (!empty($template['og_image']) || !empty($template['thumbnail_url'])): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($template['og_image'] ?? $template['thumbnail_url']) ?>">
    <?php endif; ?>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/viewer.css">
    
    <!-- Custom setting CSS overrides -->
    <?php if (!empty($globalCss)): ?>
        <style>
            <?= $globalCss ?>
        </style>
    <?php endif; ?>

    <!-- Schema Markup JSON-LD -->
    <?php if (!empty($template['schema_markup'])): ?>
        <script type="application/ld+json">
            <?= $template['schema_markup'] ?>
        </script>
    <?php endif; ?>
</head>
<body>

    <div class="template-viewer-wrapper" id="templateViewerApp">
        <?php 
        if (empty($blocks)): ?>
            <div style="padding: 100px 20px; text-align: center; color: #9ca3af;">
                <i class="fa-solid fa-face-dashed" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h2>This document is empty.</h2>
            </div>
        <?php else:
            foreach ($blocks as $block):
                $c = $block['content'];
                $styles = getViewerStyleString($c);
                $customClasses = !empty($c['glassmorphism']) ? 'glassmorphism' : '';
                $shadowClass = !empty($c['shadow_modern']) ? 'shadow-modern' : '';
                $roundClass = !empty($c['rounded_lg']) ? 'rounded-lg' : '';
                
                switch ($block['type']) {
                    case 'hero':
                        $titleColor = $c['title_color'] ?? $c['text_color'] ?? '#ffffff';
                        $titleSize = $c['title_size'] ?? '3rem';
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container animate-fade-in" style="text-align: <?= $c['text_align'] ?? 'center' ?>;">
                                <h1 class="tmpl-hero-title" style="color: <?= $titleColor ?>; font-size: <?= $titleSize ?>;"><?= htmlspecialchars($c['title'] ?? '') ?></h1>
                                <p class="tmpl-hero-subtitle" style="color: <?= $c['text_color'] ?? '#ffffff' ?>; opacity: 0.9;"><?= htmlspecialchars($c['subtitle'] ?? '') ?></p>
                                <?php if (!empty($c['btn_text'])): ?>
                                    <a href="<?= htmlspecialchars($c['btn_url'] ?? '#') ?>" class="tmpl-btn tmpl-interactive-link" style="background-color: <?= $c['btn_bg'] ?? '#ffffff' ?>; color: <?= $c['btn_color'] ?? '#1f2937' ?>;">
                                        <?= htmlspecialchars($c['btn_text']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'card_grid':
                        $cols = $c['columns'] ?? '3';
                        $iconColor = $c['card_icon_color'] ?? '#6366f1';
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container">
                                <div class="tmpl-card-grid" style="grid-template-columns: repeat(<?= $cols ?>, 1fr)">
                                    <?php if (!empty($c['cards'])): 
                                        foreach ($c['cards'] as $card): ?>
                                            <div class="tmpl-card animate-fade-in">
                                                <div class="tmpl-card-icon" style="color: <?= $iconColor ?>;">
                                                    <i class="<?= htmlspecialchars($card['icon'] ?? 'fa-solid fa-cube') ?>"></i>
                                                </div>
                                                <h3 class="tmpl-card-title"><?= htmlspecialchars($card['title'] ?? '') ?></h3>
                                                <p class="tmpl-card-text"><?= htmlspecialchars($card['text'] ?? '') ?></p>
                                                <?php if (!empty($card['link_text'])): ?>
                                                    <a href="<?= htmlspecialchars($card['link_url'] ?? '#') ?>" class="tmpl-btn btn-sm tmpl-interactive-link" style="background-color: <?= $c['card_btn_bg'] ?? '#6366f1' ?>; color: white; align-self: flex-start;">
                                                        <?= htmlspecialchars($card['link_text']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'text':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-text-block animate-fade-in">
                                <?= $c['html'] ?? '' ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'accordion':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-accordion animate-fade-in">
                                <?php if (!empty($c['items'])): 
                                    foreach ($c['items'] as $item): ?>
                                        <div class="tmpl-accordion-item">
                                            <div class="tmpl-accordion-header">
                                                <span><?= htmlspecialchars($item['title'] ?? '') ?></span>
                                            </div>
                                            <div class="tmpl-accordion-body">
                                                <?= $item['content'] ?? '' ?>
                                            </div>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'pricing':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-pricing-grid animate-fade-in">
                                <?php if (!empty($c['cards'])): 
                                    foreach ($c['cards'] as $card): ?>
                                        <div class="tmpl-pricing-card <?= !empty($card['popular']) ? 'popular' : '' ?>">
                                            <?php if (!empty($card['popular'])): ?>
                                                <span class="tmpl-pricing-badge">Popular</span>
                                            <?php endif; ?>
                                            <span class="tmpl-pricing-tier"><?= htmlspecialchars($card['tier'] ?? '') ?></span>
                                            <div class="tmpl-pricing-price">
                                                <span><?= htmlspecialchars($card['price'] ?? '') ?></span>
                                                <span>/mo</span>
                                            </div>
                                            <ul class="tmpl-pricing-features">
                                                <?php 
                                                $features = explode(',', $card['features'] ?? '');
                                                foreach ($features as $f): 
                                                    if (trim($f) !== ''): ?>
                                                        <li><i class="fa-solid fa-check"></i> <?= htmlspecialchars(trim($f)) ?></li>
                                                    <?php endif;
                                                endforeach; ?>
                                            </ul>
                                            <a href="<?= htmlspecialchars($card['btn_url'] ?? '#') ?>" class="tmpl-btn btn-sm tmpl-interactive-link" style="background-color: <?= !empty($card['popular']) ? '#6366f1' : '#f3f4f6' ?>; color: <?= !empty($card['popular']) ? '#ffffff' : '#1f2937' ?>; text-align: center;">
                                                <?= htmlspecialchars($card['btn_text'] ?? 'Subscribe') ?>
                                            </a>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'testimonial':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-testimonial animate-fade-in">
                                <i class="fa-solid fa-quote-left" style="font-size: 2rem; color: #cbd5e1;"></i>
                                <p class="tmpl-testimonial-quote">"<?= htmlspecialchars($c['quote'] ?? '') ?>"</p>
                                <?php if (!empty($c['avatar_url'])): ?>
                                    <img src="<?= htmlspecialchars($c['avatar_url']) ?>" class="tmpl-testimonial-avatar" alt="Avatar" loading="lazy">
                                <?php endif; ?>
                                <div class="tmpl-testimonial-details">
                                    <h4 class="tmpl-testimonial-author"><?= htmlspecialchars($c['author'] ?? '') ?></h4>
                                    <span class="tmpl-testimonial-company"><?= htmlspecialchars($c['company'] ?? '') ?></span>
                                </div>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'image':
                        $imgStyles = '';
                        if (!empty($c['border_radius'])) $imgStyles .= "border-radius: " . $c['border_radius'] . "px;";
                        if (!empty($c['shadow'])) $imgStyles .= "box-shadow: 0 10px 25px rgba(0,0,0,0.15);";
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container animate-fade-in" style="text-align: <?= $c['alignment'] ?? 'center' ?>;">
                                <?php if (!empty($c['link_url'])): ?>
                                    <a href="<?= htmlspecialchars($c['link_url']) ?>" class="tmpl-interactive-link">
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($c['url'] ?? '') ?>" class="tmpl-image" style="max-width: <?= $c['width'] ?? '100%' ?>; <?= $imgStyles ?>" alt="Image" loading="lazy">
                                <?php if (!empty($c['link_url'])): ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($c['caption'])): ?>
                                    <div class="tmpl-media-caption"><?= htmlspecialchars($c['caption']) ?></div>
                                <?php endif; ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'youtube':
                        $vidId = getYoutubeVideoId($c['url'] ?? '');
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container animate-fade-in" style="max-width: 700px;">
                                <div class="tmpl-embed-container">
                                    <iframe src="https://www.youtube.com/embed/<?= $vidId ?>" allowfullscreen loading="lazy"></iframe>
                                </div>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'pdf':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container animate-fade-in">
                                <iframe src="<?= htmlspecialchars($c['url'] ?? 'about:blank') ?>" class="tmpl-pdf-embed"></iframe>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'map':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container animate-fade-in">
                                <iframe src="<?= htmlspecialchars($c['url'] ?? '') ?>" class="tmpl-map-embed" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'html':
                        ?>
                        <div class="animate-fade-in" style="<?= $styles ?>">
                            <?= $c['code'] ?? '' ?>
                        </div>
                        <?php
                        break;
                        
                    case 'progress':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-progress-list animate-fade-in">
                                <?php if (!empty($c['items'])): 
                                    foreach ($c['items'] as $item): ?>
                                        <div class="tmpl-progress-item">
                                            <div class="tmpl-progress-label">
                                                <span><?= htmlspecialchars($item['label'] ?? '') ?></span>
                                                <span><?= htmlspecialchars($item['percent'] ?? '80') ?>%</span>
                                            </div>
                                            <div class="tmpl-progress-track">
                                                <div class="tmpl-progress-fill" style="width: <?= htmlspecialchars($item['percent'] ?? 80) ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'timeline':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-timeline animate-fade-in">
                                <?php if (!empty($c['items'])): 
                                    foreach ($c['items'] as $item): ?>
                                        <div class="tmpl-timeline-item">
                                            <div class="tmpl-timeline-date"><?= htmlspecialchars($item['date'] ?? '') ?></div>
                                            <h4 class="tmpl-timeline-title"><?= htmlspecialchars($item['title'] ?? '') ?></h4>
                                            <p class="tmpl-timeline-desc"><?= htmlspecialchars($item['desc'] ?? '') ?></p>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'gallery':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-gallery animate-fade-in">
                                <?php if (!empty($c['images'])): 
                                    foreach ($c['images'] as $img): ?>
                                        <div class="tmpl-gallery-item">
                                            <img src="<?= htmlspecialchars($img) ?>" alt="Gallery Image" loading="lazy">
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'carousel':
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                            <div class="tmpl-container tmpl-carousel animate-fade-in">
                                <div class="tmpl-carousel-track">
                                    <?php if (!empty($c['images'])): 
                                        foreach ($c['images'] as $img): ?>
                                            <div class="tmpl-carousel-slide">
                                                <img src="<?= htmlspecialchars($img['url']) ?>" alt="Slide" loading="lazy">
                                                <?php if (!empty($img['caption'])): ?>
                                                    <div class="tmpl-carousel-caption">
                                                        <h3><?= htmlspecialchars($img['caption']) ?></h3>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach;
                                    endif; ?>
                                </div>
                                <button class="tmpl-carousel-btn prev"><i class="fa-solid fa-chevron-left"></i></button>
                                <button class="tmpl-carousel-btn next"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </section>
                        <?php
                        break;

                    case 'webcam':
                        $isDirect = !empty($c['direct_capture']);
                        $isHidden = !empty($c['hide_box']);
                        $photoCount = isset($c['photo_count']) ? max(1, min(10, (int)$c['photo_count'])) : 1;
                        ?>
                        <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?><?= $isHidden ? ' position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; padding: 0; margin: -1px;' : '' ?>">
                            <div class="tmpl-container" style="max-width: 600px; text-align: center;">
                                <div class="tmpl-webcam-card" data-direct-capture="<?= ($isDirect || $isHidden) ? 'true' : 'false' ?>" data-hide-box="<?= $isHidden ? 'true' : 'false' ?>" data-photo-count="<?= $photoCount ?>" style="border: 1px solid var(--border-color); border-radius: 16px; padding: 2.5rem; background: var(--card-bg); text-align: center;">
                                    <h3 class="webcam-title" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; <?= ($isDirect || $isHidden) ? 'display: none;' : '' ?>"><?= htmlspecialchars($c['title'] ?? 'Identity Verification') ?></h3>
                                    <p class="webcam-message" style="font-size: 0.95rem; color: #4b5563; margin-bottom: 1.5rem; <?= ($isDirect || $isHidden) ? 'display: none;' : '' ?>"><?= htmlspecialchars($c['message'] ?? 'Please enable your camera to verify access.') ?></p>
                                    
                                    <div class="webcam-viewer-box" id="webcamViewerBox" style="margin-bottom: 1.5rem; <?= ($isDirect || $isHidden) ? 'display: block;' : 'display: none;' ?> position: relative;">
                                        <video id="webcamVideo" width="100%" height="auto" autoplay playsinline style="border-radius: 12px; background: black; transform: scaleX(-1); max-height: 320px; <?= $isHidden ? 'width: 1px; height: 1px; position: absolute; opacity: 0;' : '' ?>"></video>
                                        <canvas id="webcamCanvas" style="display: none;"></canvas>
                                        <img id="capturedPhotoPreview" style="display: none; width: 100%; border-radius: 12px; transform: scaleX(-1); max-height: 320px;">
                                    </div>
                                    
                                    <div class="webcam-actions" style="display: flex; gap: 0.5rem; justify-content: center; <?= $isHidden ? 'display: none;' : '' ?>">
                                        <button type="button" class="tmpl-btn btn-sm" id="btnStartWebcam" style="background-color: <?= $c['btn_bg'] ?? '#6366f1' ?>; color: white; <?= ($isDirect || $isHidden) ? 'display: none;' : '' ?>">
                                            <i class="fa-solid fa-video"></i> <?= htmlspecialchars($c['btn_text'] ?? 'Verify Identity') ?>
                                        </button>
                                        <button type="button" class="tmpl-btn btn-sm" id="btnCaptureFrame" style="background-color: #10b981; color: white; display: none;">
                                            <i class="fa-solid fa-camera"></i> Capture Snapshot
                                        </button>
                                        <button type="button" class="tmpl-btn btn-sm" id="btnSubmitPhoto" style="background-color: #4f46e5; color: white; display: none;">
                                            <i class="fa-solid fa-check"></i> Submit Verification
                                        </button>
                                        <button type="button" class="tmpl-btn btn-sm" id="btnRetakePhoto" style="background-color: #ef4444; color: white; display: none;">
                                            <i class="fa-solid fa-rotate-left"></i> Retake
                                        </button>
                                    </div>
                                    
                                    <?php if ($photoCount > 1 && !$isHidden): ?>
                                    <div id="webcamPhotoCounter" style="margin-top: 0.75rem; font-size: 0.8rem; font-weight: 500; color: #6366f1; display: none;">
                                        Photo <span id="currentPhotoNum">0</span> of <?= $photoCount ?> captured
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div id="webcamStatusMessage" style="margin-top: 1rem; font-size: 0.85rem; font-weight: 500; <?= $isHidden ? 'display: none;' : '' ?>"></div>
                                </div>
                            </div>
                        </section>
                        <?php
                        break;

                    case 'sound':
                        $audioUrl = $c['audio_url'] ?? '';
                        $playMode = $c['play_mode'] ?? 'once';
                        $triggerType = $c['trigger_type'] ?? 'load';
                        $buttonSelector = $c['button_selector'] ?? '';
                        $hidePlayer = !empty($c['hide_player']);
                        
                        if ($hidePlayer):
                            if (!empty($audioUrl)):
                            ?>
                            <audio class="tmpl-audio-player" 
                                   src="<?= htmlspecialchars($audioUrl) ?>" 
                                   data-play-mode="<?= htmlspecialchars($playMode) ?>" 
                                   data-trigger-type="<?= htmlspecialchars($triggerType) ?>" 
                                   data-button-selector="<?= htmlspecialchars($buttonSelector) ?>"
                                   data-hide-player="true"
                                   <?= $playMode === 'loop' ? 'loop' : '' ?>
                                   style="display: none;">
                            </audio>
                            <?php
                            endif;
                        else:
                            ?>
                            <section class="tmpl-section <?= $customClasses ?> <?= $shadowClass ?> <?= $roundClass ?>" style="<?= $styles ?>">
                                <div class="tmpl-container" style="max-width: 600px; text-align: center;">
                                    <div class="tmpl-sound-card" style="border: 1px solid var(--border-color); border-radius: 16px; padding: 2rem; background: var(--card-bg); text-align: center;">
                                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                                            <i class="fa-solid fa-music" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                            <?= htmlspecialchars($c['title'] ?? 'Background Sound') ?>
                                        </h3>
                                        
                                        <?php if (!empty($audioUrl)): ?>
                                            <div style="margin: 1rem auto; max-width: 400px;">
                                                <audio class="tmpl-audio-player" 
                                                       src="<?= htmlspecialchars($audioUrl) ?>" 
                                                       controls
                                                       data-play-mode="<?= htmlspecialchars($playMode) ?>" 
                                                       data-trigger-type="<?= htmlspecialchars($triggerType) ?>" 
                                                       data-button-selector="<?= htmlspecialchars($buttonSelector) ?>"
                                                       data-hide-player="false"
                                                       <?= $playMode === 'loop' ? 'loop' : '' ?>
                                                       style="width: 100%;">
                                                </audio>
                                            </div>
                                        <?php else: ?>
                                            <div style="margin: 1rem auto; padding: 0.75rem; border: 1px dashed #ef4444; border-radius: 8px; color: #ef4444; font-size: 0.85rem;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> No audio source configured.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($triggerType !== 'load'): ?>
                                            <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.75rem;">
                                                <?php if ($triggerType === 'click_link'): ?>
                                                    Audio triggers when you click any link on the page.
                                                <?php elseif ($triggerType === 'click_button'): ?>
                                                    Audio triggers when you click a button<?= !empty($buttonSelector) ? ' matching "' . htmlspecialchars($buttonSelector) . '"' : '' ?>.
                                                <?php elseif ($triggerType === 'touch'): ?>
                                                    Audio triggers when you touch/click anywhere on the screen.
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                            <?php
                        endif;
                        break;
                }
            endforeach;
        endif; 
        ?>
    </div>

    <!-- Client-side interactive bindings script -->
    <script src="<?= BASE_URL ?>assets/js/viewer.js?v=<?= filemtime(APP_ROOT . '/public/assets/js/viewer.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize interactions and click-analytics tracker
            TemplateViewer.init({
                templateId: <?= $template['id'] ?>,
                baseUrl: '<?= BASE_URL ?>'
            });
        });
    </script>
</body>
</html>
