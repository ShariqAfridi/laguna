<?php
check_admin_auth();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (substr($scriptDir, -6) === '/logic') {
    $scriptDir = substr($scriptDir, 0, -6);
}
$base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?php echo rtrim($base, '/') . '/'; ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>LVB Admin | Dashboard</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon (matching header LVB) -->
    <link rel="icon" href="https://lagunavibe.com/img/logo3.jpg" type="image/jpeg">
    
    <style>
        /* ---------- RESET & GLOBAL ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            transition: background 0.2s;
        }

        /* ===== UNIFIED ADMIN DESIGN SYSTEM GLOBAL CLASSES ===== */
        .admin-wrapper {
            width: 100%;
            max-width: 100%;
            padding: 24px 40px;
            margin: 0;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .admin-title {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            font-weight: 700;
            color: #1f2c35;
            margin: 0;
        }
        .admin-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }
        .admin-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }
        .admin-table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }
        .admin-table th {
            padding: 14px 16px;
            border-bottom: 2px solid #e5e7eb;
            background: #f9fafb;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }
        .admin-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
            vertical-align: middle;
        }
        .admin-label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #374151;
            margin-bottom: 6px;
        }
        .admin-input, .admin-select, .admin-textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            outline: none;
            background: #ffffff;
        }
        .admin-input:focus, .admin-select:focus, .admin-textarea:focus {
            border-color: #1f2c35;
            box-shadow: 0 0 0 3px rgba(31,44,53,0.1);
        }
        .admin-btn-primary {
            background: #1f2c35;
            color: #ffffff;
            padding: 11px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .admin-btn-primary:hover {
            background: #111827;
            color: #ffffff;
        }
        .admin-btn-secondary {
            background: #e5e7eb;
            color: #374151;
            padding: 11px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }
        .admin-btn-edit {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin-right: 6px;
        }
        .admin-btn-delete {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }
        .admin-badge-active {
            background: #dcfce7;
            color: #15803d;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .admin-badge-inactive {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .admin-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .admin-no-thumb {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 10px;
            font-style: italic;
            font-weight: 500;
            line-height: 1.2;
            text-align: center;
            user-select: none;
        }
        .admin-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-pagination-pages {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .admin-page-link {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
        }
        .admin-page-link:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        .admin-page-link.active {
            background: #1f2c35;
            color: #ffffff;
            border-color: #1f2c35;
        }
        .admin-page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
            background: #f9fafb;
        }
        .status-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            color: #374151;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .status-pill:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        .status-pill.active {
            background: #1f2c35;
            color: #ffffff;
            border-color: #1f2c35;
        }
        .status-pill .count {
            background: #f3f4f6;
            color: #6b7280;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-pill.active .count {
            background: rgba(255,255,255,0.2);
            color: #ffffff;
        }

        /* ----- LVB HEADER STYLES (exactly matching provided header, but integrated) ----- */
        .header-desktop {
            position: sticky;
            top: 0;
            z-index: 1100;  /* above sidebar overlay but below sidebar? Sidebar z-index higher, but fine */
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 65px;
            padding: 0 10%;
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            background-image: linear-gradient(
                to bottom,
                rgba(255,255,255,0.65),
                rgba(255,255,255,0.35)
            );
            border-bottom: 1px solid rgba(200, 210, 220, 0.25);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .logo {
            font-family: 'Cinzel', serif;
            font-size: 22px;
            letter-spacing: 3px;
            font-weight: 500;
            color: #1f2c35;
            text-decoration: none;
            flex: 1;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            gap: 48px;
            flex: 2;
            justify-content: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #5b6f7e;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            letter-spacing: 2.16px;
            text-transform: uppercase;
            white-space: nowrap;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #000;
        }

        .cart-wrapper {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            cursor: pointer;
        }

        .icon-svg {
            width: 24px;
            height: 24px;
            stroke: #24303a;
            stroke-width: 1.5;
            fill: none;
        }

        /* tablet responsive (keep desktop header) */
        @media (max-width: 1200px) {
            .nav-links { gap: 20px; }
            .logo { font-size: 18px; }
        }

        @media (min-width: 1440px) {
            .header-desktop { padding: 0 120px; }
        }

        /* Hide LVB desktop header on mobile (as original design) BUT we keep sidebar toggle independent */
        @media (max-width: 1024px) {
            .header-desktop {
                display: none;
            }
            /* Adjust body padding to not conflict */
            body {
                padding-top: 0;
            }
        }

        /* ---------- SIDEBAR VARIABLES & CORE (enhanced icons) ---------- */
        :root {
            --sb-width: 260px;
            --sb-bg: #0a2e3f;
            --sb-accent: #5bc0ff;
            --sb-text: rgba(255, 255, 255, 0.85);
        }

        /* --- SIDEBAR CORE --- */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: var(--sb-width);
            background: var(--sb-bg);
            color: #fff;
            z-index: 2000;
            overflow-y: auto;
            scrollbar-width: thin;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .logo {
            font-family: 'Cinzel', serif;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 2px;
            background: none;
            color: white;
            padding: 0;
            margin: 0;
            flex: none;
        }

        .mobile-nav-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            padding: 6px;
        }

        .mobile-nav-close:hover {
            background: rgba(255,255,255,0.1);
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            color: var(--sb-text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .sidebar-nav ul li a:hover,
        .sidebar-nav ul li a.active {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border-left-color: var(--sb-accent);
        }

        /* Logout special style */
        .sidebar-nav ul li.logout-item {
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 16px;
        }
        .sidebar-nav ul li.logout-item a {
            color: #ffb5a7;
        }
        .sidebar-nav ul li.logout-item a:hover {
            background: rgba(220, 53, 69, 0.2);
            border-left-color: #ff6b6b;
            color: #fff;
        }

        /* Icon styling - consistent sizing */
        .nav-icon {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 1.7;
            fill: none;
            flex-shrink: 0;
        }

        /* for fill icons we keep consistency */
        .nav-icon-fill {
            fill: currentColor;
            stroke: none;
        }

        /* DESKTOP: Permanent Sidebar */
        @media (min-width: 769px) {
            body {
                padding-left: var(--sb-width);
                padding-top: 0;
            }
            .mobile-nav-toggle,
            .mobile-nav-close {
                display: none !important;
            }
            .sidebar {
                transform: translateX(0) !important;
            }
            /* Adjust header to align with sidebar on desktop */
            .header-desktop {
                width: calc(100% - var(--sb-width));
                left: var(--sb-width);
                position: sticky;
                margin-left: auto;
            }
        }

        /* MOBILE: Drawer Logic */
        @media (max-width: 768px) {
            body {
                padding-left: 0;
                padding-top: 70px;
            }
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .sidebar.is-open {
                transform: translateX(0);
                box-shadow: 10px 0 30px rgba(0,0,0,0.3);
            }
            .mobile-nav-toggle {
                display: flex;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: var(--sb-bg);
                color: white;
                border: none;
                padding: 10px;
                border-radius: 8px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                align-items: center;
                justify-content: center;
            }
            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                display: none;
                z-index: 1500;
                backdrop-filter: blur(2px);
            }
            .sidebar-overlay.active {
                display: block;
            }
            /* Hide desktop header on mobile fully, but keep LVB brand via toggle area */
            .header-desktop {
                display: none;
            }
        }

        /* Main content area for demo */
        .dashboard-content {
            padding: 32px 28px;
            max-width: 1300px;
            margin: 0 auto;
        }
        .welcome-card {
            background: white;
            border-radius: 24px;
            padding: 28px 32px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.02), 0 2px 6px rgba(0,0,0,0.05);
            border: 1px solid #eef2f6;
        }
        .welcome-card h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #1a2c3e;
        }
        .demo-stat-grid {
            display: flex;
            gap: 24px;
            margin-top: 32px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px 24px;
            flex: 1;
            min-width: 180px;
            border: 1px solid #e9edf2;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        @media (max-width: 640px) {
            .dashboard-content { padding: 20px 16px; }
        }
    </style>
</head>
<body>

    <!-- Mobile Nav Toggle (visible only on mobile) -->
    <button class="mobile-nav-toggle" id="mobileToggle" aria-label="Open Menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <!-- SIDEBAR (Integrated unique icons + logout) -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2 class="logo">L V B</h2>
            <button class="mobile-nav-close" id="mobileClose" aria-label="Close Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <!-- Dashboard - distinct: house / grid icon -->
                <li><a href="<?php echo $base; ?>/admin/dashboard" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-8H9v8H5a2 2 0 0 1-2-2z"/>
                    </svg>
                    Dashboard
                </a></li>
                <!-- Products - grid/list with rows -->
                <li><a href="<?php echo $base; ?>/admin/list_product" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Products
                </a></li>
                <!-- Categories - grid/vessel category icon -->
                <li><a href="<?php echo $base; ?>/admin/categories" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>
                    </svg>
                    Categories
                </a></li>
                <!-- Fragrance - flower / aromatic symbol -->
                <li><a href="<?php echo $base; ?>/admin/fragrance" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Fragrance
                </a></li>
                <!-- Colors - palette / droplet distinct -->
                <li><a href="<?php echo $base; ?>/admin/colors" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                        <circle cx="7.5" cy="16.5" r="1.5" fill="currentColor" stroke="none"/>
                        <circle cx="16.5" cy="13.5" r="1.5" fill="currentColor" stroke="none"/>
                        <circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none"/>
                    </svg>
                    Colors
                </a></li>
                <!-- Box - gift/package distinct -->
                <li><a href="<?php echo $base; ?>/admin/boxes" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.29 7 12 12 20.71 7"/>
                        <line x1="12" y1="22" x2="12" y2="12"/>
                    </svg>
                    Box
                </a></li>
                <!-- Accessories - Candle Care / Sparkle Gem Icon -->
                <li><a href="<?php echo $base; ?>/admin/accessories" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 2l2.4 4.8 5.3.8-3.8 3.7.9 5.3-4.8-2.5-4.8 2.5.9-5.3-3.8-3.7 5.3-.8z"/>
                    </svg>
                    Accessories
                </a></li>
                <!-- Orders - receipt/truck icon distinct -->
                <li><a href="<?php echo $base; ?>/admin/orders" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M22 12h-4l-3 9-4-18-3 9H2"/>
                    </svg>
                    Orders
                </a></li>
                <!-- Users - customer and admin management -->
                <li><a href="<?php echo $base; ?>/admin/users" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Users
                </a></li>
                <!-- Logout option (end session & redirect to /admin) -->
                <li class="logout-item"><a href="<?php echo $base; ?>/admin/logout" id="logoutBtn" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="9" y1="12" x2="21" y2="12"/>
                    </svg>
                    Logout
                </a></li>
            </ul>
        </nav>
    </aside>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

 



    <!-- JavaScript: Sidebar toggle, active link, Logout handler & session clear -->
    <script>
        (function() {
            // ----- SIDEBAR MOBILE LOGIC -----
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const openBtn = document.getElementById('mobileToggle');
            const closeBtn = document.getElementById('mobileClose');
            const navLinks = document.querySelectorAll('.nav-link');

            const toggleSidebar = (state) => {
                if (!sidebar) return;
                sidebar.classList.toggle('is-open', state);
                if (overlay) overlay.classList.toggle('active', state);
                document.body.style.overflow = state ? 'hidden' : '';
            };

            if (openBtn) openBtn.addEventListener('click', () => toggleSidebar(true));
            if (closeBtn) closeBtn.addEventListener('click', () => toggleSidebar(false));
            if (overlay) overlay.addEventListener('click', () => toggleSidebar(false));

            // Resize handler: close drawer if switching to desktop view
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) {
                    if (sidebar && sidebar.classList.contains('is-open')) toggleSidebar(false);
                }
            });

            // ----- ACTIVE LINK HIGHLIGHT (smart module & sub-route matching) -----
            const currentPath = window.location.pathname.toLowerCase();
            let matched = false;

            navLinks.forEach(link => {
                const href = (link.getAttribute('href') || '').toLowerCase();
                if (!href || href === '#' || href.includes('logout')) return;

                // Extract module route identifier (e.g. "fragrance", "categories", "boxes", "colors", "product", "accessories", "orders", "users", "dashboard")
                const extractModule = (path) => {
                    if (path.includes('fragrance')) return 'fragrance';
                    if (path.includes('categor')) return 'categories';
                    if (path.includes('color')) return 'colors';
                    if (path.includes('box')) return 'boxes';
                    if (path.includes('product')) return 'products';
                    if (path.includes('accessor')) return 'accessories';
                    if (path.includes('order')) return 'orders';
                    if (path.includes('user')) return 'users';
                    if (path.includes('dashboard')) return 'dashboard';
                    return '';
                };

                const currentMod = extractModule(currentPath);
                const linkMod = extractModule(href);

                if (currentMod && linkMod && currentMod === linkMod) {
                    link.classList.add('active');
                    matched = true;
                } else if (currentPath === href) {
                    link.classList.add('active');
                    matched = true;
                }
            });

            // Fallback: Default to dashboard if root or unmapped admin URL
            if (!matched && (currentPath.endsWith('/admin') || currentPath.endsWith('/admin/'))) {
                const dashLink = Array.from(navLinks).find(link => (link.getAttribute('href') || '').includes('dashboard'));
                if (dashLink) dashLink.classList.add('active');
            }

            // ----- LOGOUT FUNCTIONALITY: ends session and redirects to /admin -----
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // 1. Clear session / localStorage / sessionStorage (simulate logout)
                    // Usually for backend session you would call an API, but as per requirement we end the session.
                    // Clear any persisted tokens or admin data.
                    sessionStorage.clear();
                    localStorage.removeItem('adminToken');
                    localStorage.removeItem('adminAuth');
                    // Optional: clear cookies relevant to session
                    document.cookie.split(";").forEach(function(c) { 
                        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
                    });
                    
                    // Make a fetch call if you have a server-side logout endpoint (optional but recommended)
                    // For standard behavior, we just redirect to /admin after clearing frontend state.
                    // Also ensure to simulate ending server session by calling a logout API if needed.
                    // Since we want to end the PHP/Laravel session (if any), redirect to /admin? 
                    // But requirement: "which will end the session and redirect to /admin"
                    // We perform client-side redirection; also explicitly call a logout endpoint if exists.
                    
                    // Attempt to hit server logout endpoint if present (optional but safe)
                    fetch('/admin/logout', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .catch(() => {}) // ignore if endpoint missing
                        .finally(() => {
                            // redirect to /admin (login page)
                            window.location.href = '/admin';
                        });
                    // Edge: ensure redirect occurs even if fetch fails.
                    setTimeout(() => {
                        window.location.href = '/admin';
                    }, 200);
                });
            }

            // Optional: handle cart icon placeholder for consistency (just alert for demo)
            const cartBtn = document.getElementById('desktopCartBtn');
            if (cartBtn) {
                cartBtn.addEventListener('click', () => {
                    // demo: could redirect to cart page, but just placeholder
                    window.location.href = '/cart';
                });
            }
        })();
    </script>

    <!-- Small adjustment: Ensure sidebar nav active states stay correctly on demo path -->
    <script>
        // extra: for safety because we are on this page 'admin_dashboard' demonstration
        (function fixActiveHighlight() {
            const cur = window.location.pathname;
            const links = document.querySelectorAll('.sidebar-nav .nav-link');
            links.forEach(link => {
                const href = link.getAttribute('href');
                if (href && href !== '#' && (cur === href || (cur === '/' && href === '/admin_dashboard'))) {
                    link.classList.add('active');
                } else if (cur.includes('admin_dashboard') && href === '/admin_dashboard') {
                    link.classList.add('active');
                }
            });
        })();
    </script>
    <script src="<?= base_url('/public/assets/js/image-compressor.js') ?>"></script>
</body>
</html>