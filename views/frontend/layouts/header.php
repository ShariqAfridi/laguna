<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base)) {
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  if (substr($scriptDir, -6) === '/logic') {
    $scriptDir = substr($scriptDir, 0, -6);
  }
  $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <base href="<?php echo rtrim($base, '/') . '/'; ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laguna Vibe</title>
  <link rel="icon" type="image/png" href="favicon.png">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://lagunavibe.com/">
  <meta property="og:title" content="Laguna Vibe">
  <meta property="og:description" content="Hand-poured candles inspired by the Pacific. Designed and finished in Laguna Beach.">
  <meta property="og:image" content="https://lagunavibe.com/img/logo.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:type" content="image/png">
  <meta property="og:site_name" content="Laguna Vibe">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600&family=Cinzel:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $base; ?>/public/assets/css/ada-compliance.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Georgia', serif;
      background-color: #ffffff;
    }

    /* DESKTOP HEADER - visible only on large screens */
    .header-desktop {
      position: sticky;
      top: 0;
      z-index: 1000;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 90px;
      
      /* DESKTOP: 20% padding on large screens (above 1440px) */
      padding: 0 20%;

      /* Base background: WHITE */
      background-color: #FFFFFF;
      
      transition: background-color 0.25s ease-out, 
                  box-shadow 0.3s ease,
                  border-bottom-color 0.2s ease;
      
      border-bottom: 1px solid rgba(0, 0, 0, 0.04);
      box-shadow: none;
    }

    /* LAPTOP / MEDIUM SCREENS (≤1440px): 2% padding */
    @media (max-width: 1440px) {
      .header-desktop {
        padding: 0 2%;
      }
    }

    /* Hide desktop header on tablet & mobile (below 1024px) */
    @media (max-width: 1024px) {
      .header-desktop {
            display:none !important;
      }
    }

    /* Scroll state: light blue #D6E8F0 */
    .header-desktop.header-scrolled {
      background-color: #D6E8F0;
      border-bottom: 1px solid rgba(80, 100, 110, 0.2);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
    }

    /* LOGO */
    .logo-link {
      flex: 1;
      display: flex;
      align-items: center;
      text-decoration: none;
      line-height: 0;
    }

    .logo-img {
      display: block;
      height: 72px;
      width: auto;
      max-width: 400px;
      object-fit: contain;
      transition: transform 0.2s ease;
    }

    .logo-img:hover {
      transform: scale(1.01);
    }

    /* NAVIGATION */
    .nav-links {
      display: flex;
      gap: 48px;
      flex: 2;
      justify-content: center;
    }

    .nav-links a {
      text-decoration: none;
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      letter-spacing: 2.2px;
      text-transform: uppercase;
      font-weight: 500;
      white-space: nowrap;
      transition: color 0.2s;
      color: #1e2f3a;
    }

    .header-desktop:not(.header-scrolled) .nav-links a {
      color: #2c3e4e;
    }

    .header-desktop.header-scrolled .nav-links a {
      color: #14222b;
    }

    .nav-links a:hover {
      color: #000000;
    }

    /* CART ICON */
    .cart-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: opacity 0.2s;
    }

    .cart-wrapper:hover {
      opacity: 0.75;
    }

    .icon-svg {
      width: 24px;
      height: 24px;
      stroke: #1e2f3a;
      stroke-width: 1.7;
      fill: none;
      transition: stroke 0.2s;
    }

    .header-desktop.header-scrolled .icon-svg {
      stroke: #14222b;
    }

    /* Simple spacer to demonstrate scroll effect */
    .content-placeholder {
      height: 200vh;
      background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%);
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding-top: 4rem;
    }

    .hint-text {
      font-family: 'Inter', sans-serif;
      font-size: 0.85rem;
      color: #a0abb3;
      letter-spacing: 0.5px;
      text-align: center;
      background: rgba(255,255,255,0.7);
      padding: 0.75rem 1.5rem;
      border-radius: 40px;
      backdrop-filter: blur(4px);
    }
  </style>
</head>
<body>

<?php
if (!isset($base)) {
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  if (substr($scriptDir, -6) === '/logic') {
    $scriptDir = substr($scriptDir, 0, -6);
  }
  $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
<header class="header-desktop" id="mainHeader">
  <a href="<?php echo $base; ?>/" class="logo-link">
    <img class="logo-img" src="<?php echo $base; ?>/img/logo-wihtout-bg.png" alt="L V B luxury brand logo">
  </a>

  <nav class="nav-links">
    <a href="<?php echo $base; ?>/">Home</a>
    <a href="<?php echo $base; ?>/about">About</a>
    <a href="<?php echo $base; ?>/shop">Shop</a>
    <a href="<?php echo $base; ?>/contact">Contact</a>
  </nav>

  <?php
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $isCustomerLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $customerName   = $_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? 'Customer Account');
    $customerEmail  = $_SESSION['user_email'] ?? '';
    $customerAvatar = $_SESSION['user_avatar'] ?? '';
  ?>

  <style>
    .user-dropdown-wrapper {
      position: relative;
      display: inline-block;
    }
    .user-dropdown-trigger {
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: inherit;
      outline: none;
    }
    .user-dropdown-menu {
      position: absolute;
      top: calc(100% + 6px);
      right: -8px;
      width: 240px;
      background: #FFFFFF;
      border: 1px solid #E2E9EF;
      border-radius: 12px;
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
      padding: 8px 0;
      display: none;
      z-index: 9999;
    }
    /* Invisible hover bridge to prevent closing when moving cursor from icon to menu */
    .user-dropdown-menu::before {
      content: '';
      position: absolute;
      top: -15px;
      left: -10px;
      right: -10px;
      height: 20px;
      background: transparent;
    }
    .user-dropdown-wrapper:hover .user-dropdown-menu,
    .user-dropdown-menu:hover,
    .user-dropdown-menu.show-menu {
      display: block;
      animation: navDropdownFade 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes navDropdownFade {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .dropdown-user-header {
      padding: 12px 18px;
      border-bottom: 1px solid #EEF3F6;
      margin-bottom: 4px;
      background: #F8FBFD;
      border-radius: 11px 11px 0 0;
    }
    .dropdown-user-name {
      font-weight: 600;
      font-size: 13px;
      color: #1E2F3A;
      font-family: 'Inter', sans-serif;
    }
    .dropdown-user-email {
      font-size: 11px;
      color: #6D8491;
      margin-top: 2px;
      word-break: break-all;
    }
    .user-dropdown-menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 18px;
      font-family: 'Inter', sans-serif;
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #2C3E4E;
      text-decoration: none;
      transition: background-color 0.15s ease, color 0.15s ease;
    }
    .user-dropdown-menu a:hover {
      background: #F4F8FA;
      color: #14222B;
    }
    .user-dropdown-menu a svg {
      width: 15px;
      height: 15px;
      stroke: #6D8491;
      stroke-width: 1.7;
      fill: none;
    }
    .user-dropdown-menu a:hover svg {
      stroke: #14222B;
    }
    .dropdown-divider {
      height: 1px;
      background: #EEF3F6;
      margin: 6px 0;
    }
  </style>

  <div style="flex:1; display:flex; align-items:center; justify-content:flex-end; gap:20px;">
    <!-- Search Icon Button -->
    <button class="user-dropdown-trigger" id="desktopSearchBtn" aria-label="Search Products" title="Search Products" style="background:none; border:none; cursor:pointer; padding:4px; display:flex; align-items:center; justify-content:center; color:inherit; outline:none;">
      <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
    </button>

    <!-- User Icon Dropdown Container -->
    <div class="user-dropdown-wrapper" id="userDropdownWrapper">
      <button class="user-dropdown-trigger" id="userDropdownTrigger" aria-label="User Account">
        <?php if (!empty($customerAvatar)): ?>
          <img src="<?php echo $base . '/' . ltrim(htmlspecialchars($customerAvatar), '/'); ?>" style="width:24px; height:24px; border-radius:50%; object-fit:cover; border:1.5px solid #D6E8F0;">
        <?php else: ?>
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        <?php endif; ?>
      </button>

      <div class="user-dropdown-menu" id="userDropdownMenu">
        <?php if ($isCustomerLoggedIn): ?>
          <div class="dropdown-user-header">
            <div class="dropdown-user-name"><?php echo htmlspecialchars($customerName); ?></div>
            <div class="dropdown-user-email"><?php echo htmlspecialchars($customerEmail); ?></div>
          </div>
          <a href="<?php echo $base; ?>/dashboard">
            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Dashboard
          </a>
          <a href="<?php echo $base; ?>/dashboard/orders">
            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            My Orders
          </a>
          <a href="<?php echo $base; ?>/dashboard/addresses">
            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Saved Addresses
          </a>
          <a href="<?php echo $base; ?>/dashboard/profile">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            My Profile
          </a>
          <div class="dropdown-divider"></div>
          <a href="<?php echo $base; ?>/logout" style="color:#C5221F;">
            <svg viewBox="0 0 24 24" style="stroke:#C5221F;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
          </a>
        <?php else: ?>
          <div class="dropdown-user-header">
            <div class="dropdown-user-name">Welcome to LVB</div>
            <div class="dropdown-user-email">Sign in to access your account</div>
          </div>
          <a href="<?php echo $base; ?>/login">
            <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Sign In
          </a>
          <a href="<?php echo $base; ?>/register">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Create Account
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Cart Icon -->
    <div class="cart-wrapper" id="desktopCartBtn">
      <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4H6zM3 6h18M16 10a4 4 0 01-8 0"/>
      </svg>
    </div>
  </div>
</header>

<script>
  (function() {
    const wrapper = document.getElementById('userDropdownWrapper');
    const trigger = document.getElementById('userDropdownTrigger');
    const menu    = document.getElementById('userDropdownMenu');
    let leaveTimer = null;
    
    if (wrapper && trigger && menu) {
      function showMenu() {
        if (leaveTimer) clearTimeout(leaveTimer);
        menu.classList.add('show-menu');
      }

      function hideMenuWithDelay() {
        leaveTimer = setTimeout(function() {
          menu.classList.remove('show-menu');
        }, 200);
      }

      wrapper.addEventListener('mouseenter', showMenu);
      wrapper.addEventListener('mouseleave', hideMenuWithDelay);

      trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        if (menu.classList.contains('show-menu')) {
          menu.classList.remove('show-menu');
        } else {
          showMenu();
        }
      });

      document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
          menu.classList.remove('show-menu');
        }
      });
    }
  })();
</script>


<script>
  (function() {
    const header = document.getElementById('mainHeader');
    if (!header) return;

    let scrollThreshold = 20;
    
    function handleScroll() {
      if (window.scrollY > scrollThreshold) {
        if (!header.classList.contains('header-scrolled')) {
          header.classList.add('header-scrolled');
        }
      } else {
        if (header.classList.contains('header-scrolled')) {
          header.classList.remove('header-scrolled');
        }
      }
    }
    
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    const cartBtn = document.getElementById('desktopCartBtn');
    if (cartBtn) {
      cartBtn.addEventListener('click', () => {
        console.log('LVB shopping cart');
        const svg = cartBtn.querySelector('.icon-svg');
        if (svg) {
          svg.style.transform = 'scale(0.95)';
          setTimeout(() => { if(svg) svg.style.transform = ''; }, 150);
        }
      });
    }
    
    window.addEventListener('load', handleScroll);
  })();
</script>

<?php include __DIR__ . '/search_modal.php'; ?>

</body>
</html>