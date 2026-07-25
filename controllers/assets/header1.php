<?php
/**
 * header1.php — Mobile Header (LVB Atelier)
 * Include on every page: <?php include 'views/header1.php'; ?>
 * Always pair with header.php on the same page.
 * This file loads cart.js at the bottom — it must come AFTER header.php in the HTML.
 * 
 * UPDATED: Added white → light blue (#D6E8F0) scroll transition
 */
?>
<style>
    /* ── Import Inter font (only 400 normal weight as in spec) ── */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400&display=swap');

    /* ── Mobile Header ── */
    .header-mobile {
        display: none;
        position: sticky;
        top: 0;
        z-index: 1002;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        background-color: #FFFFFF;  /* Changed from #f8fbfc to pure white */
        border-bottom: 1px solid #eef3f6;
        transition: background-color 0.25s ease-out, box-shadow 0.3s ease, border-bottom-color 0.2s ease;
    }

    /* Scroll state - light blue (#D6E8F0) */
    .header-mobile.header-scrolled {
        background-color: #D6E8F0;
        border-bottom-color: rgba(80, 100, 110, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

   

    .mobile-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    /* Cart icon wrapper inside mobile-actions */
    .mobile-cart-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        position: relative;
    }

    .menu-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px 0 8px 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Slide-down menu */
    .mobile-menu-container {
        display: none;
        position: relative;
        z-index: 1001;
        background-color: #FFFFFF;  /* Match header base color */
        width: 100%;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.23, 1, 0.32, 1), opacity 0.25s ease, background-color 0.25s ease;
        max-height: 0;
        opacity: 0;
        visibility: hidden;
        border-bottom: none;
    }

    .mobile-menu-container.active {
        max-height: 460px;
        opacity: 1;
        visibility: visible;
        border-bottom: 1px solid #e2e9ef;
        box-shadow: 0 8px 20px -8px rgba(0,0,0,0.04);
    }

    /* Menu scrolled background */
    .mobile-menu-container.scrolled-bg {
        background-color: #D6E8F0;
    }

    .mobile-nav-links {
        display: flex;
        flex-direction: column;
        padding: 1.5rem 2rem 2rem 2rem;
        gap: 1.8rem;
    }

    /* Mobile navigation links: Inter, 12px/16px line-height, 400 weight, letter-spacing 2.16px */
    .mobile-nav-links a {
        text-decoration: none;
        color: #2c3e46;
        font-family: 'Inter', 'Helvetica Neue', sans-serif;
        font-size: 12px;
        line-height: 16px;
        font-weight: 400;
        letter-spacing: 2.16px;
        text-transform: uppercase;
        transition: all 0.2s;
        display: inline-block;
        width: fit-content;
        border-bottom: 1px solid transparent;
        font-feature-settings: "cv11", "ss01";
        font-variation-settings: normal;
    }

    .mobile-nav-links a:hover {
        color: #000;
        border-bottom-color: #b0c4ce;
    }

    /* Shared icon style */
    .icon-svg {
        width: 24px;
        height: 24px;
        fill: none;
        stroke: #24303a;
        stroke-width: 1.5;
        stroke-linecap: round;
        stroke-linejoin: round;
        transition: stroke 0.2s;
    }

    .header-mobile.header-scrolled .icon-svg {
        stroke: #14222b;
    }

    /* Mobile backdrop */
    .mobile-backdrop {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.35);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0s linear 0.3s;
    }

    .mobile-backdrop.active {
        opacity: 1;
        visibility: visible;
        transition: opacity 0.25s ease, visibility 0s linear 0s;
    }

    body.menu-open { overflow: hidden; }

    /* Show mobile header on tablet/phone only */
    @media (max-width: 1024px) {
        .header-mobile { display: flex; }
        .mobile-menu-container { display: block; }
    }

    @media (max-width: 480px) {
        .header-mobile { padding: 14px 20px; }
        .mobile-nav-links {
            padding: 1.2rem 1.5rem 1.8rem 1.5rem;
            gap: 1.4rem;
        }
        .mobile-nav-links a { font-size: 12px; letter-spacing: 2.16px; line-height: 16px; }
        .header-mobile .logo { font-size: 20px; letter-spacing: 4px; }
    }
    .logo-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    line-height: 0;
}

.logo-img {
    display: block;
    height: 38px;
    width: auto;
    max-width: 160px;
    object-fit: contain;
    transition: transform 0.2s ease;
}

.logo-img:hover {
    transform: scale(1.01);
}
</style>

<?php
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
<!-- Backdrop: only used by mobile menu (cart has its own overlay) -->
<div class="mobile-backdrop" id="mobileBackdrop"></div>

<!-- MOBILE HEADER -->
<header class="header-mobile" id="mobileHeader">
   <a href="<?php echo $base; ?>/" class="logo-link">
    <img class="logo-img" src="/img/newlogo.jpg" alt="L V B luxury brand logo">
</a>
    <div class="mobile-actions">
        <!-- Cart icon — plain button, cart.js targets #mobileCartBtn -->
        <button class="mobile-cart-btn" id="mobileCartBtn" aria-label="Open cart">
            <svg class="icon-svg" viewBox="0 0 24 24">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4H6zM3 6h18M16 10a4 4 0 01-8 0" />
            </svg>
        </button>
        <!-- Hamburger toggle — completely separate from cart -->
        <button class="menu-btn" id="mobileMenuToggle" aria-label="Menu">
            <svg id="burgerIcon" class="icon-svg" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
            </svg>
            <svg id="closeIcon" class="icon-svg" viewBox="0 0 24 24" style="display:none;">
                <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
</header>

<!-- Slide-down mobile nav menu -->
<div class="mobile-menu-container" id="mobileMenuPanel">
    <div class="mobile-nav-links">
        <a href="<?php echo $base; ?>/about">About</a>
        <a href="<?php echo $base; ?>/contact">Contact</a>
    </div>
</div>

<!-- Mobile header scroll effect + hamburger menu logic -->
<script>
(function () {
    // ========== SCROLL EFFECT FOR MOBILE HEADER ==========
    var mobileHeader = document.getElementById('mobileHeader');
    var mobileMenuPanel = document.getElementById('mobileMenuPanel');
    var scrollThreshold = 20;
    
    function handleScroll() {
        if (!mobileHeader) return;
        var scrolled = window.scrollY > scrollThreshold;
        
        if (scrolled) {
            if (!mobileHeader.classList.contains('header-scrolled')) {
                mobileHeader.classList.add('header-scrolled');
            }
            if (mobileMenuPanel) {
                mobileMenuPanel.classList.add('scrolled-bg');
            }
        } else {
            if (mobileHeader.classList.contains('header-scrolled')) {
                mobileHeader.classList.remove('header-scrolled');
            }
            if (mobileMenuPanel) {
                mobileMenuPanel.classList.remove('scrolled-bg');
            }
        }
    }
    
    // Initial check
    handleScroll();
    
    // Attach scroll listener
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('load', handleScroll);
    
    // ========== HAMBURGER MENU LOGIC ==========
    var panel      = document.getElementById('mobileMenuPanel');
    var backdrop   = document.getElementById('mobileBackdrop');
    var toggleBtn  = document.getElementById('mobileMenuToggle');
    var burgerIcon = document.getElementById('burgerIcon');
    var closeIcon  = document.getElementById('closeIcon');
    var body       = document.body;
    var open       = false;

    function openMenu() {
        if (panel) panel.classList.add('active');
        if (backdrop) backdrop.classList.add('active');
        if (burgerIcon) burgerIcon.style.display = 'none';
        if (closeIcon) closeIcon.style.display  = 'block';
        body.classList.add('menu-open');
        open = true;
    }

    function closeMenu() {
        if (panel) panel.classList.remove('active');
        if (backdrop) backdrop.classList.remove('active');
        if (burgerIcon) burgerIcon.style.display = 'block';
        if (closeIcon) closeIcon.style.display  = 'none';
        body.classList.remove('menu-open');
        open = false;
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            open ? closeMenu() : openMenu();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            if (open) closeMenu();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && open) closeMenu();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024 && open) closeMenu();
    });

    var mobileLinks = document.querySelectorAll('.mobile-nav-links a');
    mobileLinks.forEach(function (a) {
        a.addEventListener('click', function () { if (open) closeMenu(); });
    });
})();
</script>

<!-- Cart — loaded here so both desktop & mobile DOM are ready when it runs -->
<script src="/views/home/cart.js"></script>