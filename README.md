# TemplateLink Builder

TemplateLink Builder is a production-ready, SaaS-like PHP MVC web application. It allows administrators to create responsive documents (e.g. Magazines, Newsletters, Landing Pages, Resumes), edit them visually inside a drag-and-drop property inspector canvas, generate custom slug URL links (e.g., `/view/alexander-cv`), and share them with recipients who view them in a clean, read-only mode with active visitor and click telemetry.

---

## 📂 Project Architecture & Directory Structure

```
/
├── app/                        # Main MVC Core Application
│   ├── config/                 # Configurations & Mappings
│   │   ├── config.php          # Database credentials, dynamic BASE_URL, session start
│   │   ├── database.php        # PDO connection wrapper (with automatic DB/table generation)
│   │   └── routes.php          # Request routing maps
│   ├── controllers/            # Controller layers
│   │   ├── Controller.php      # Base Controller (CSRF protection, layouts, flash alerts)
│   │   ├── AuthController.php  # Admin login / logout security
│   │   ├── AdminController.php # Dashboard summary, settings panel, analytics charts
│   │   ├── TemplateController.php # Template CRUD, preset block injections, editor routing
│   │   ├── MediaController.php # Upload restrictions, file renames, media list
│   │   └── ViewerController.php # Public viewer pages, beacon click tracking API
│   ├── models/                 # Model/Database Layers (PDO transactions)
│   │   ├── Model.php           # Base PDO Query helper methods
│   │   ├── Admin.php           # Admin authentication checks
│   │   ├── Template.php        # Template listings, CRUD, slug checking
│   │   ├── Category.php        # Organises template presets
│   │   ├── Media.php           # Registries for uploaded files
│   │   ├── Analytics.php       # Logs visitor views & telemetry link clicks
│   │   └── Settings.php        # Key-Value site configuration overrides
│   └── views/                  # View Template Layouts (Outfit fonts & dark SaaS themes)
│       ├── admin/              # Dashboard, settings panel, analytics, template lists
│       │   ├── dashboard.php   # Aggregated analytics metrics, recent views, Chart.js trends
│       │   ├── templates.php   # Filterable template lists, slug sharing, deletion forms
│       │   ├── create_template.php # Setup form with auto-slug generation JS
│       │   ├── editor.php      # Split visual workspace, properties inspector, media modal
│       │   ├── media.php       # Drag & drop file uploads, copy URLs grid
│       │   ├── settings.php    # Global CSS overrides & categories list
│       │   └── analytics.php   # In-depth traffic analysis, CTR leaderboard, raw logs
│       ├── auth/
│       │   └── login.php       # Glassmorphism login page
│       ├── viewer/
│       │   ├── index.php       # Published documents gallery landing page
│       │   └── template.php    # Read-only document renderer (no editor controls output)
│       └── layout/
│           ├── admin_head.php  # Common dashboard navigation sidebar
│           └── admin_foot.php  # Common sidebar closure scripts
├── database/                   # Database resources
│   ├── schema.sql              # Clean MySQL table setups
│   └── seed.php                # Populates admin account, categories, and 5 responsive templates
├── public/                     # Public Web Assets (Serve root)
│   ├── assets/
│   │   ├── css/
│   │   │   ├── admin.css       # Layout styles for dashboard, inputs, and workspaces
│   │   │   └── viewer.css      # Core styles for blocks (Hero, Accordion, Timeline, Pricing)
│   │   └── js/
│   │       ├── editor.js       # Visual editor state management & inline editing engine
│   │       └── viewer.js       # Client interaction handlers & click tracker beacons
│   ├── uploads/                # Secured directory for media files (.htaccess php block)
│   ├── .htaccess               # Apache URL rewriting to index.php
│   └── index.php               # Front controller & PSR-4 autoload registry
├── verify.php                  # CLI verification suite (runs recursive syntax audits & schema check)
└── README.md                   # This documentation guide
```

---

## 🚀 Installation & Local Startup

### 1. Prerequisites
- **PHP 8.0+**
- **MySQL 5.7+ / MariaDB**

### 2. Startup Database Service
Start your local MySQL service. (If using Homebrew on macOS):
```bash
brew services start mysql
```

### 3. Clone and Check Configurations
The database configurations are located in [app/config/config.php](file:///Users/krishyogi/Desktop/imge%20capture%20vir/app/config/config.php):
```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'templatelink_builder');
```
*Note: The application will automatically attempt to create the database `templatelink_builder` and load the schema tables on first connection if they do not exist.*

### 4. Run Verification and Database Seeding
Run the verification suite from the terminal to recursively audit PHP syntax, verify connection variables, and automatically populate the tables with pre-built responsive templates:
```bash
php verify.php
```

### 5. Launch the Local Development Server
To launch the site on your local machine, run the PHP built-in server directed at the `public/` directory front controller:
```bash
php -S localhost:8000 public/index.php
```
Open your browser and navigate to **`http://localhost:8000`**.

---

## 🔑 Administrator Credentials

- **URL**: `http://localhost:8000/admin/login`
- **Username**: `admin`
- **Password**: `password123`

---

## 🎨 Visual Page Builder Guide

The Visual Page Builder includes a three-column interface:
1. **Toolbox (Left)**: Click any block type (e.g. Hero, Accordion, Pricing, Gallery, YouTube, Map) to insert a fresh block at the bottom of your document canvas.
2. **Interactive Canvas (Center)**: 
   - Viewport toggle icons at the top allow previewing layouts on **Desktop**, **Tablet**, and **Mobile** sizes.
   - Text fields feature inline `contenteditable="true"` capabilities. Double-click headings, body copy, or bullet features to change texts instantly in real-time.
   - On-hover controllers let you re-order blocks (move up/down), duplicate them, or delete them.
3. **Inspector Panel (Right)**:
   - Selecting a canvas block loads its contextual properties.
   - Adjust typography sizes/colors, padding vertical values, solid colors or linear gradient CSS codes, custom hyperlinks, and slider borders.
   - Includes a **Media Library Picker** that lists all files uploaded in your database to insert URLs with a single click.

---

## 🔒 Security Architectures

1. **Prepared Statements**: All database operations use PDO parameterized values to prevent SQL injection vulnerabilities.
2. **CSRF Tokens**: All POST actions (including login, template metadata edits, uploads, and deletions) validate a cryptographically secure token stored in the user's session.
3. **MIME Upload Safeguards**: The file uploader evaluates MIME type signatures using `finfo_file` to restrict uploads to safe items (images, PDFs, videos). Uploaded files are renamed using a 32-character randomized hash to prevent remote code execution.
4. **PHP Execution Block**: The `/public/uploads/` directory contains a dedicated `.htaccess` that strips handlers for `.php` and scripts, protecting the server.
5. **XSS Protection**: Base controllers escape output parameters (`htmlspecialchars`). Custom HTML blocks are stored raw but separate from templated text parameters.

---

## 📊 Analytics Telemetry Tracking

1. **Page Views**: When a shared URL is opened (e.g. `/view/landing-page`), a view record is logged in the `analytics_views` table with the visitor IP address, user-agent, and referrer page. Admin page views are filtered out to keep stats clean.
2. **Link Clicks**: The visitor template script hooks link clicks globally. Clicking any hyperlink sends a telemetry beacon using the browser's native `navigator.sendBeacon` API to `/api/track-click` to record clicks without delaying navigation.
3. **Dashboard Telemetry**: The Admin panel displays a line chart mapping views over the last 30 days (utilizing Chart.js CDN), total views, total clicks, CTR (Click-Through-Rate) percentages, and a table ranking the most popular pages.
