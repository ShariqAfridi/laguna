<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>L V B — Luxury Fashion | Desktop Header</title>
  <link rel="icon" type="image/png" href="favicon.png">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://lagunavibe.com/">
  <meta property="og:title" content="L V B - Luxury Fashion">
  <meta property="og:description" content="Discover premium fashion and luxury styles at LVB. Shop latest collections online.">
  <meta property="og:image" content="https://lagunavibe.com/img/logo.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:type" content="image/png">
  <meta property="og:site_name" content="LVB">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600&family=Cinzel:wght@400;500;600&display=swap" rel="stylesheet">

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
      height: 65px;
      
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
      height: 38px;
      width: auto;
      max-width: 160px;
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
      flex: 1;
      display: flex;
      justify-content: flex-end;
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

<header class="header-desktop" id="mainHeader">
  <a href="/" class="logo-link">
    <img class="logo-img" src="/img/newlogo.jpg" alt="L V B luxury brand logo">
  </a>

  <nav class="nav-links">
    <a href="/about">About</a>
    <a href="/contact">Contact</a>
  </nav>

  <div class="cart-wrapper" id="desktopCartBtn">
    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4H6zM3 6h18M16 10a4 4 0 01-8 0"/>
    </svg>
  </div>
</header>


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

</body>
</html>