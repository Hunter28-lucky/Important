<?php
// Database Seeder for TemplateLink Builder
// Run via CLI: php database/seed.php

// Load configuration to get DB details
require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/database.php';

use App\Config\Database;

echo "--- Starting TemplateLink Database Seeding ---\n";

try {
    $db = Database::getConnection();
    
    // 1. Clear existing table structures safely
    echo "Clearing existing data...\n";
    $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $db->exec("PRAGMA foreign_keys = OFF;");
        $tables = ['analytics_clicks', 'analytics_views', 'visitor_photos', 'visitor_locations', 'settings', 'templates', 'categories', 'admins'];
        foreach ($tables as $table) {
            $db->exec("DELETE FROM `{$table}`;");
            $db->exec("DELETE FROM sqlite_sequence WHERE name='{$table}';");
        }
        $db->exec("PRAGMA foreign_keys = ON;");
    } else {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $db->exec("TRUNCATE TABLE analytics_clicks;");
        $db->exec("TRUNCATE TABLE analytics_views;");
        $db->exec("TRUNCATE TABLE visitor_photos;");
        $db->exec("TRUNCATE TABLE visitor_locations;");
        $db->exec("TRUNCATE TABLE settings;");
        $db->exec("TRUNCATE TABLE templates;");
        $db->exec("TRUNCATE TABLE categories;");
        $db->exec("TRUNCATE TABLE admins;");
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    // 2. Seed Admin account
    echo "Seeding Admin account...\n";
    $username = 'admin';
    $email = 'admin@templatelink.com';
    $password = 'password123'; // Default secure local development password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $passwordHash]);
    echo "  > Admin User: admin / password123\n";

    // 3. Seed Default Categories
    echo "Seeding Categories...\n";
    $categories = [
        ['Medical', 'medical'],
        ['Business', 'business'],
        ['Technology', 'technology'],
        ['Finance', 'finance'],
        ['Personal', 'personal'],
        ['Magazine', 'magazine'],
        ['Corporate', 'corporate'],
        ['Marketing', 'marketing'],
        ['Legal', 'legal'],
        ['Research', 'research']
    ];
    $catStmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
    $catIds = [];
    foreach ($categories as $cat) {
        $catStmt->execute([$cat[0], $cat[1]]);
        $catIds[$cat[1]] = $db->lastInsertId();
    }
    echo "  > Seeded " . count($categories) . " categories.\n";

    // 4. Seed Default Settings
    echo "Seeding Settings...\n";
    $settings = [
        'site_name' => 'TemplateLink Builder',
        'admin_email' => 'admin@templatelink.com',
        'custom_css' => "/* Custom overrides */\n.tmpl-hero-title {\n    text-shadow: 0 4px 12px rgba(0,0,0,0.15);\n}\n"
    ];
    $setStmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $setStmt->execute([$k, $v]);
    }

    // 5. Seed Templates with visual block payload content
    echo "Seeding Sample Templates...\n";
    
    // Preset 1: Landing Page
    $landingBlocks = [
        [
            'id' => 'land-h1',
            'type' => 'hero',
            'content' => [
                'title' => 'SaaS Product Landing Page',
                'subtitle' => 'The complete customer conversion engine out of the box.',
                'btn_text' => 'Start Trial Free',
                'btn_url' => 'https://example.com/trial',
                'bg_type' => 'gradient',
                'bg_value' => 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                'text_color' => '#ffffff',
                'padding' => '90px 20px',
                'text_align' => 'center'
            ]
        ],
        [
            'id' => 'land-g1',
            'type' => 'card_grid',
            'content' => [
                'columns' => '3',
                'card_icon_color' => '#4f46e5',
                'cards' => [
                    ['icon' => 'fa-solid fa-bolt', 'title' => 'Instant Deployment', 'text' => 'Share custom links within seconds to recipients.', 'link_text' => 'Learn Speed', 'link_url' => 'https://example.com/speed' ],
                    ['icon' => 'fa-solid fa-chart-line', 'title' => 'Analytics Tracker', 'text' => 'Monitor views, visitors, and click-through rates live.', 'link_text' => 'Explore Telemetry', 'link_url' => 'https://example.com/stats' ],
                    ['icon' => 'fa-solid fa-shield-halved', 'title' => 'Enterprise Security', 'text' => 'Password hashing, SQL safeguards, and XSS blocks.', 'link_text' => 'Security Specs', 'link_url' => 'https://example.com/security' ]
                ],
                'bg_type' => 'solid',
                'bg_color' => '#f9fafb',
                'padding' => '60px 20px'
            ]
        ],
        [
            'id' => 'land-p1',
            'type' => 'pricing',
            'content' => [
                'cards' => [
                    ['tier' => 'Starter Plan', 'price' => 'Free', 'features' => '1 shareable URL, Basic designs, 24h caching', 'btn_text' => 'Activate Free', 'btn_url' => '#', 'popular' => false],
                    ['tier' => 'Premium SaaS', 'price' => '$29', 'features' => 'Unlimited custom slugs, Media Library access, Real-time click tracking, Dedicated SEO controls', 'btn_text' => 'Activate Premium', 'btn_url' => '#', 'popular' => true]
                ],
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '60px 20px'
            ]
        ],
        [
            'id' => 'land-a1',
            'type' => 'accordion',
            'content' => [
                'items' => [
                    ['title' => 'Do viewers need to register accounts?', 'content' => 'No. Viewing templates is completely public and requires no login or authentication.'],
                    ['title' => 'Can I customize the viewer slug?', 'content' => 'Yes. Administrators can change the path to custom slugs (e.g. site.com/view/custom-slug).']
                ],
                'bg_type' => 'solid',
                'bg_color' => '#f3f4f6',
                'padding' => '60px 20px'
            ]
        ]
    ];

    // Preset 2: Newsletter
    $newsletterBlocks = [
        [
            'id' => 'news-h1',
            'type' => 'hero',
            'content' => [
                'title' => 'Weekly Tech Digest',
                'subtitle' => 'Your curated insights into modular design systems & PHP trends.',
                'btn_text' => '',
                'bg_type' => 'solid',
                'bg_color' => '#0f172a',
                'text_color' => '#f8fafc',
                'padding' => '60px 20px',
                'text_align' => 'center'
            ]
        ],
        [
            'id' => 'news-t1',
            'type' => 'text',
            'content' => [
                'html' => '<h2>1. Modular Design Systems are Scaling</h2><p>In 2026, styling paradigms emphasize light-dismiss native dialog overlay parameters and container queries. Building accessible UI panels requires native elements to minimize weight. Our visual builder supports custom HTML, grids, and galleries out of the box.</p><h2>2. Secure Sessions in SaaS</h2><p>Securing PHP platforms remains a priority. Implement prepared parameters inside database layers, check file mime header signatures on storage, and enforce strict CSRF protection checks globally.</p>',
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'text_color' => '#334155',
                'padding' => '40px 20px'
            ]
        ],
        [
            'id' => 'news-tm1',
            'type' => 'testimonial',
            'content' => [
                'quote' => 'This newsletter is the highlight of my week. The design is beautiful and the links load instantly.',
                'author' => 'Mark Spencer',
                'company' => 'Lead Developer, techCorp',
                'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d',
                'bg_type' => 'solid',
                'bg_color' => '#f8fafc',
                'padding' => '50px 20px'
            ]
        ]
    ];

    // Preset 3: Professional Resume
    $resumeBlocks = [
        [
            'id' => 'res-h1',
            'type' => 'hero',
            'content' => [
                'title' => 'Alexander Knight',
                'subtitle' => 'Lead Full Stack PHP Architect & UX Engineer',
                'btn_text' => 'Download CV',
                'btn_url' => '#',
                'bg_type' => 'solid',
                'bg_color' => '#1e293b',
                'text_color' => '#f8fafc',
                'padding' => '70px 20px',
                'text_align' => 'center'
            ]
        ],
        [
            'id' => 'res-t1',
            'type' => 'text',
            'content' => [
                'html' => '<h3>Professional Summary</h3><p>Results-driven architect with 8+ years designing scalable SaaS web portals. Expert in PHP MVC, database schema design, vanilla CSS, and secure transaction workflows.</p>',
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '30px 20px'
            ]
        ],
        [
            'id' => 'res-timeline',
            'type' => 'timeline',
            'content' => [
                'items' => [
                    ['date' => '2024 - Present', 'title' => 'Senior Architect at SaaSify Inc.', 'desc' => 'Leading development of custom visual document sharing pipelines, saving 30% on page rendering times.'],
                    ['date' => '2021 - 2024', 'title' => 'Full Stack Developer at techLabs', 'desc' => 'Structured secure PHP controllers, implemented prepared SQL statements, and engineered visual card layout inspectors.']
                ],
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '40px 20px'
            ]
        ],
        [
            'id' => 'res-progress',
            'type' => 'progress',
            'content' => [
                'items' => [
                    ['label' => 'PHP MVC & SQL Architecture', 'percent' => 95],
                    ['label' => 'Vanilla CSS Styling & Responsive Web Design', 'percent' => 90],
                    ['label' => 'Security Audit & SQL/XSS Prevention', 'percent' => 85]
                ],
                'bg_type' => 'solid',
                'bg_color' => '#f8fafc',
                'padding' => '40px 20px'
            ]
        ]
    ];

    // Preset 4: Magazine Page
    $magazineBlocks = [
        [
            'id' => 'mag-h1',
            'type' => 'hero',
            'content' => [
                'title' => 'Metropolitan Design',
                'subtitle' => 'Issue 42 &bull; Exploring the physical materials and digital canvases of tomorrow.',
                'btn_text' => 'Read Full Issue',
                'btn_url' => '#',
                'bg_type' => 'gradient',
                'bg_value' => 'linear-gradient(135deg, #1e3a8a 0%, #701a75 100%)',
                'text_color' => '#ffffff',
                'padding' => '100px 20px',
                'text_align' => 'center'
            ]
        ],
        [
            'id' => 'mag-g1',
            'type' => 'gallery',
            'content' => [
                'images' => [
                    'https://images.unsplash.com/photo-1451187580459-43490279c0fa',
                    'https://images.unsplash.com/photo-1518770660439-4636190af475',
                    'https://images.unsplash.com/photo-1498050108023-c5249f4df085'
                ],
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '40px 20px'
            ]
        ],
        [
            'id' => 'mag-t1',
            'type' => 'text',
            'content' => [
                'html' => '<h3>Visual Minimalism in Print</h3><p>Minimalism is not the absence of design, but the perfect density of communication elements. Modern publications require typography hierarchies that feel organic, bold serif titles, and layouts that flow seamlessly across screen sizes.</p>',
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '40px 20px'
            ]
        ]
    ];

    // Preset 5: Corporate Report
    $corporateBlocks = [
        [
            'id' => 'corp-h1',
            'type' => 'hero',
            'content' => [
                'title' => 'Q3 Performance Report',
                'subtitle' => 'Acme Corp Corporate telemetry highlights, user conversion indexes, and financial forecasts.',
                'btn_text' => 'Download Full Report',
                'btn_url' => 'https://example.com/reports/Q3.pdf',
                'bg_type' => 'solid',
                'bg_color' => '#0f172a',
                'text_color' => '#f8fafc',
                'padding' => '80px 20px',
                'text_align' => 'left'
            ]
        ],
        [
            'id' => 'corp-g1',
            'type' => 'card_grid',
            'content' => [
                'columns' => '2',
                'card_icon_color' => '#10b981',
                'cards' => [
                    ['icon' => 'fa-solid fa-chart-line-up', 'title' => 'Revenue Growth', 'text' => 'Q3 posted a 24% revenue surge compared to the preceding quarter.', 'link_text' => 'Audit Metrics', 'link_url' => '#' ],
                    ['icon' => 'fa-solid fa-users', 'title' => 'Customer Retention', 'text' => 'User retention indexes jumped to 92.4% following custom dashboard releases.', 'link_text' => 'Audit Metrics', 'link_url' => '#' ]
                ],
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '60px 20px'
            ]
        ],
        [
            'id' => 'corp-pdf',
            'type' => 'pdf',
            'content' => [
                'url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'bg_type' => 'solid',
                'bg_color' => '#f8fafc',
                'padding' => '40px 20px'
            ]
        ]
    ];

    // Preset 6: Instagram Stickers Download Page
    $instagramBlocks = [
        [
            'id' => 'insta-h1',
            'type' => 'hero',
            'content' => [
                'title' => '<i class="fa-brands fa-instagram" style="font-size: 3.5rem; display: block; margin-bottom: 1rem; color: #ffffff;"></i> Instagram Stickers Store',
                'subtitle' => 'Download the ultimate aesthetic stickers pack to boost your stories and engagements! Free to use for personal creators.',
                'btn_text' => 'Unlock Pack Now',
                'btn_url' => '#download-section',
                'bg_type' => 'gradient',
                'bg_value' => 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)',
                'text_color' => '#ffffff',
                'padding' => '80px 20px',
                'text_align' => 'center'
            ]
        ],
        [
            'id' => 'insta-loc',
            'type' => 'location',
            'content' => [
                'title' => 'Unlock Region-Specific Sticker Packs',
                'btn_text' => 'Authorize Location Access',
                'hide_box' => false,
                'bg_type' => 'solid',
                'bg_color' => '#f8fafc',
                'text_color' => '#1e293b',
                'padding' => '40px 20px'
            ]
        ],
        [
            'id' => 'insta-grid',
            'type' => 'card_grid',
            'content' => [
                'columns' => '3',
                'card_icon_color' => '#dc2743',
                'cards' => [
                    ['icon' => 'fa-solid fa-camera-retro', 'title' => 'Vlog Aesthetics', 'text' => 'Handcrafted polaroid frames, retro timestamps, and film dust overlays.', 'link_text' => '', 'link_url' => '' ],
                    ['icon' => 'fa-solid fa-wand-magic-sparkles', 'title' => 'Neon Badges', 'text' => 'High contrast neon icons, interactive tags, and custom doodle text bubbles.', 'link_text' => '', 'link_url' => '' ],
                    ['icon' => 'fa-solid fa-heart', 'title' => 'Cute Doodles', 'text' => 'Soft pastel hearts, starry sparkles, and hand-drawn emoji overlays.', 'link_text' => '', 'link_url' => '' ]
                ],
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '50px 20px'
            ]
        ],
        [
            'id' => 'insta-gallery',
            'type' => 'gallery',
            'content' => [
                'images' => [
                    'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1620641788421-7a1c342ea42e?auto=format&fit=crop&w=400&q=80'
                ],
                'bg_type' => 'solid',
                'bg_color' => '#ffffff',
                'padding' => '40px 20px'
            ]
        ],
        [
            'id' => 'insta-btn',
            'type' => 'button',
            'content' => [
                'btn_text' => 'Download All Stickers (ZIP)',
                'btn_url' => BASE_URL . 'uploads/instagram-stickers-pack.zip',
                'is_download' => true,
                'download_name' => 'instagram-stickers-pack.zip',
                'open_new_tab' => true,
                'alignment' => 'center',
                'btn_size' => 'lg',
                'bg_type' => 'solid',
                'bg_color' => '#bc1888',
                'text_color' => '#ffffff',
                'padding' => '30px 20px'
            ]
        ]
    ];

    $templatesToSeed = [
        [
            'title' => 'SaaS Product Landing Page',
            'description' => 'A professionally designed landing page featuring pricing tiers, testimonials, and FAQ toggles.',
            'category_id' => $catIds['marketing'],
            'tags' => 'marketing, product, pricing',
            'slug' => 'landing-page',
            'content' => json_encode($landingBlocks),
            'status' => 'published'
        ],
        [
            'title' => 'Weekly Technology Digest',
            'description' => 'Tech digest and newsletter format. Ideal for articles, blog posts, and text digests.',
            'category_id' => $catIds['magazine'],
            'tags' => 'tech, newsletter, reading',
            'slug' => 'tech-digest',
            'content' => json_encode($newsletterBlocks),
            'status' => 'published'
        ],
        [
            'title' => 'Alexander Knight - Curriculum Vitae',
            'description' => 'Interactive curriculum vitae showing timelines, experience logs, and progress skills trackers.',
            'category_id' => $catIds['personal'],
            'tags' => 'resume, cv, professional',
            'slug' => 'alexander-cv',
            'content' => json_encode($resumeBlocks),
            'status' => 'published'
        ],
        [
            'title' => 'Metropolitan Design Magazine',
            'description' => 'Slick magazine format featuring image galleries and block paragraphs.',
            'category_id' => $catIds['magazine'],
            'tags' => 'magazine, art, design',
            'slug' => 'metro-design',
            'content' => json_encode($magazineBlocks),
            'status' => 'published'
        ],
        [
            'title' => 'Acme Corp - Q3 Performance Report',
            'description' => 'Corporate performance report including highlights cards and PDF widgets.',
            'category_id' => $catIds['corporate'],
            'tags' => 'corporate, Q3, metrics',
            'slug' => 'acme-q3-report',
            'content' => json_encode($corporateBlocks),
            'status' => 'published'
        ],
        [
            'title' => 'Instagram Stickers Download',
            'description' => 'Slick Instagram story stickers promotion page with location capture and ZIP downloads.',
            'category_id' => $catIds['personal'],
            'tags' => 'instagram, stickers, download, media',
            'slug' => 'instagram-stickers',
            'content' => json_encode($instagramBlocks),
            'status' => 'published'
        ]
    ];

    $tmplStmt = $db->prepare("INSERT INTO templates (
        title, description, category_id, tags, slug, content, status, 
        meta_title, meta_description
    ) VALUES (
        :title, :description, :category_id, :tags, :slug, :content, :status, 
        :meta_title, :meta_description
    )");

    foreach ($templatesToSeed as $tmpl) {
        $tmpl['meta_title'] = $tmpl['title'] . ' | Shareable Link';
        $tmpl['meta_description'] = $tmpl['description'];
        $tmplStmt->execute($tmpl);
    }
    
    echo "  > Seeded " . count($templatesToSeed) . " sample templates successfully.\n";
    echo "--- Database Seeding Completed Successfully! ---\n";

} catch (\PDOException $e) {
    die("Seeding Error: " . $e->getMessage() . "\n");
}
