/**
 * LVB Atelier — Cart Drawer  (cart.js)
 * Place in: /views/cart.js
 * Loaded automatically by header1.php — do NOT include elsewhere.
 *
 * Triggers (click to open cart):
 *   #desktopCartBtn   — desktop bag icon  (header.php)
 *   #mobileCartBtn    — mobile bag icon   (header1.php)
 *   [data-cart-open]  — any other element on the page
 *
 * JS API (use anywhere after this script loads):
 *   LVBCart.open()
 *   LVBCart.close()
 *   LVBCart.toggle()
 *   LVBCart.addItem({ id, name, sku, scent, price, image? })
 */

(function () {
  'use strict';

  // Prevent duplicate script execution
  if (window.LVBCart && document.getElementById('lvbDrawer')) {
    if (typeof window.LVBCart.open === 'function') {
      window.LVBCart.getItems();
    }
    return;
  }

  /* ── 1. STYLES ── */
  var css = document.createElement('style');
  css.textContent = [
    /* overlay */
    '.lvb-overlay{position:fixed;inset:0;background:rgba(0,0,0,.38);z-index:3000;',
    'opacity:0;visibility:hidden;transition:opacity .3s,visibility 0s linear .3s}',
    '.lvb-overlay.open{opacity:1;visibility:visible;transition:opacity .3s,visibility 0s}',

    /* drawer */
    '.lvb-drawer{position:fixed;top:0;right:0;height:100%;width:420px;max-width:100vw;',
    'background:#f8fbfc;z-index:3001;display:flex;flex-direction:column;',
    'transform:translateX(100%);transition:transform .38s cubic-bezier(.23,1,.32,1);',
    'box-shadow:-8px 0 40px rgba(0,0,0,.1)}',
    '.lvb-drawer.open{transform:translateX(0)}',

    /* header row */
    '.lvb-ch{display:flex;align-items:center;justify-content:space-between;',
    'padding:32px 36px 22px;border-bottom:1px solid #e8eef2}',
    '.lvb-ct{font-family:Georgia,serif;font-size:22px;font-weight:400;color:#1e2a32;',
    'letter-spacing:.3px;margin:0}',
    '.lvb-cx{background:none;border:none;cursor:pointer;padding:4px;color:#7a8e99;',
    'display:flex;align-items:center;transition:color .2s}',
    '.lvb-cx:hover{color:#1e2a32}',
    '.lvb-cx svg{width:22px;height:22px;stroke:currentColor;stroke-width:1.6;fill:none}',

    /* body */
    '.lvb-cb{flex:1;overflow-y:auto;display:flex;flex-direction:column}',
    '.lvb-cb::-webkit-scrollbar{width:4px}',
    '.lvb-cb::-webkit-scrollbar-thumb{background:#d0dce4;border-radius:4px}',

    /* empty state */
    '.lvb-empty{flex:1;display:flex;flex-direction:column;align-items:center;',
    'justify-content:center;gap:24px;padding:60px 36px;text-align:center}',
    '.lvb-empty p{font-family:Georgia,serif;font-size:17px;color:#8fa3b0;font-style:italic;margin:0}',
    '.lvb-browse{display:inline-block;padding:13px 30px;border:1.5px solid #c2d0d8;',
    'background:#fff;color:#1e2a32;font-family:Georgia,serif;font-size:14px;',
    'letter-spacing:.5px;cursor:pointer;text-decoration:none;',
    'transition:background .2s,border-color .2s,color .2s}',
    '.lvb-browse:hover{background:#1e2a32;color:#f8fbfc;border-color:#1e2a32}',

    /* items list */
    '.lvb-items{list-style:none;margin:0;padding:0}',
    '.lvb-item{display:flex;align-items:flex-start;gap:16px;',
    'padding:22px 36px;border-bottom:1px solid #edf2f5;',
    'animation:lvbIn .25s ease}',
    '@keyframes lvbIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}',

    /* item image */
    '.lvb-img{width:72px;height:72px;object-fit:cover;border-radius:4px;',
    'background:#e8eef2;flex-shrink:0}',
    '.lvb-img-ph{width:72px;height:72px;background:#e8eef2;border-radius:4px;',
    'flex-shrink:0;display:flex;align-items:center;justify-content:center}',
    '.lvb-img-ph svg{width:28px;height:28px;stroke:#aabbc6;fill:none;stroke-width:1.4}',

    /* item info */
    '.lvb-info{flex:1;min-width:0}',
    '.lvb-name{font-family:serif;font-size:15px;color:#1e2a32;margin:0 0 14px}',
    '.lvb-sku{font-family:monospace;font-size:10px;color:#aabbc6;margin:0 0 4px;letter-spacing:0.5px}',
    '.lvb-scent{font-family:Georgia,serif;font-size:11px;text-transform:uppercase;',
    'letter-spacing:2px;color:#8fa3b0;margin:0 0 12px}',
    '.lvb-controls{display:flex;align-items:center;gap:8px;flex-wrap:wrap}',
    '.lvb-qbtn{background:none;border:1px solid #cdd8df;width:26px;height:26px;',
    'cursor:pointer;font-size:16px;color:#3a4f5c;display:flex;align-items:center;',
    'justify-content:center;transition:background .15s,border-color .15s;padding:0}',
    '.lvb-qbtn:hover{background:#1e2a32;color:#fff;border-color:#1e2a32}',
    '.lvb-qnum{font-family:Georgia,serif;font-size:14px;color:#1e2a32;',
    'min-width:18px;text-align:center}',
    '.lvb-actions-row{display:flex;align-items:center;gap:6px;margin-left:auto}',
    '.lvb-icon-btn{background:#fff;border:1px solid #cdd8df;border-radius:4px;width:28px;height:28px;',
    'cursor:pointer;color:#3a4f5c;display:inline-flex;align-items:center;justify-content:center;',
    'transition:all .18s ease;padding:0}',
    '.lvb-edit-btn:hover{background:#004b66;color:#ffffff;border-color:#004b66}',
    '.lvb-rm-btn:hover{background:#c0392b;color:#ffffff;border-color:#c0392b}',
    '.lvb-price{font-family:Georgia,serif;font-size:15px;color:#1e2a32;',
    'white-space:nowrap;padding-top:2px}',

    /* footer */
    '.lvb-footer{padding:24px 36px 32px;border-top:1px solid #e8eef2;background:#f8fbfc}',
    '.lvb-sub{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:20px}',
    '.lvb-sub-lbl{font-family:Georgia,serif;font-size:12px;text-transform:uppercase;',
    'letter-spacing:2px;color:#8fa3b0}',
    '.lvb-sub-amt{font-family:Georgia,serif;font-size:20px;color:#1e2a32}',
    '.lvb-checkout{display:block;width:100%;padding:15px;background:#002C4C;',
    'color:#f8fbfc;border:1px;border-radius:3px;cursor:pointer;font-family:Georgia,serif;font-size:13px;',
    'text-transform:uppercase;letter-spacing:2.5px;text-align:center;',
    'transition:background .2s}',
    '.lvb-checkout:hover{background:#2d3e4a}',

    /* badge */
    '.lvb-badge{position:absolute;top:-5px;right:-5px;background:#1e2a32;color:#f8fbfc;',
    'border-radius:50%;width:17px;height:17px;font-size:10px;display:none;',
    'align-items:center;justify-content:center;font-family:Georgia,serif;',
    'pointer-events:none}',

    /* make cart trigger containers position:relative for badge */
    '#desktopCartBtn,#mobileCartBtn{position:relative}',

    '@media(max-width:480px){',
    '.lvb-drawer{width:100vw}',
    '.lvb-ch{padding:24px 24px 18px}',
    '.lvb-item{padding:18px 24px}',
    '.lvb-footer{padding:20px 24px 28px}',
    '.lvb-empty{padding:48px 24px}',
    '}'
  ].join('');
  document.head.appendChild(css);

  /* ── 2. HTML ── */
  var overlay = document.getElementById('lvbOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'lvbOverlay';
    overlay.className = 'lvb-overlay';
    document.body.appendChild(overlay);
  }

  var drawer = document.getElementById('lvbDrawer');
  if (!drawer) {
    drawer = document.createElement('div');
    drawer.id = 'lvbDrawer';
    drawer.className = 'lvb-drawer';
    drawer.setAttribute('aria-label', 'Shopping Cart');
    drawer.innerHTML =
      '<div class="lvb-ch">' +
        '<h2 class="lvb-ct">Your Cart</h2>' +
        '<button class="lvb-cx" id="lvbClose" aria-label="Close">' +
          '<svg viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round"/></svg>' +
        '</button>' +
      '</div>' +
      '<div class="lvb-cb" id="lvbBody"></div>' +
      '<div class="lvb-footer" id="lvbFooter" style="display:none">' +
        '<div class="lvb-sub">' +
          '<span class="lvb-sub-lbl">Subtotal</span>' +
          '<span class="lvb-sub-amt" id="lvbSubtotal">$0</span>' +
        '</div>' +
        '<button class="lvb-checkout">Checkout</button>' +
      '</div>';
    document.body.appendChild(drawer);
  }

  /* ── 3. STATE & STORAGE ── */
  var STORAGE_KEY = 'lvb_cart';
  var items  = [];
  var isOpen = false;

  // Load cart from localStorage and sessionStorage
  function loadCartFromStorage() {
    try {
      var saved = localStorage.getItem(STORAGE_KEY);
      if (saved === null) {
        saved = sessionStorage.getItem(STORAGE_KEY);
      }
      if (saved !== null && saved !== undefined) {
        var parsed = JSON.parse(saved);
        if (Array.isArray(parsed)) {
          items = parsed;
        }
      }
    } catch (e) {
      console.warn('Failed to load cart from storage:', e);
      items = [];
    }
  }

  function syncServerCart(currentItems) {
    try {
      var base = (typeof window.basePath !== 'undefined') ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '');
      fetch(base + '/logic/sync_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart: currentItems })
      }).catch(function(e) { console.warn('Cart sync failed:', e); });
    } catch(e) {}
  }

  // Save cart to both localStorage & sessionStorage
  function saveCartToStorage() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(items));
      syncServerCart(items);
    } catch (e) {
      console.warn('Failed to save cart to storage:', e);
    }
  }

  /* ── 4. RENDER ── */
  function render() {
    var body    = document.getElementById('lvbBody');
    var footer  = document.getElementById('lvbFooter');
    var subtEl  = document.getElementById('lvbSubtotal');

    if (!items.length) {
      body.innerHTML =
        '<div class="lvb-empty">' +
          '<p>Your cart is quiet.</p>' +
          '<a href="#" class="lvb-browse" id="lvbBrowse">Browse the collection</a>' +
        '</div>';
      footer.style.display = 'none';
      var br = document.getElementById('lvbBrowse');
      if (br) br.addEventListener('click', function (e) { e.preventDefault(); closeCart(); });
    } else {
      var subtotal = items.reduce(function (s, i) { return s + i.price * i.qty; }, 0);
      body.innerHTML =
        '<ul class="lvb-items">' +
        items.map(function (item) {
          return '<li class="lvb-item" data-id="' + item.id + '">' +
            (item.image
              ? '<img class="lvb-img" src="' + item.image + '" alt="' + item.name + '">'
              : '<div class="lvb-img-ph"><svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4H6zM3 6h18M16 10a4 4 0 01-8 0" stroke-linecap="round" stroke-linejoin="round"/></svg></div>') +
            '<div class="lvb-info">' +
              '<p class="lvb-name">' + escapeHtml(item.name) + '</p>' +
              (item.sku ? '<p class="lvb-sku">SKU: ' + escapeHtml(item.sku) + '</p>' : '') +
              '<div class="lvb-controls">' +
                '<button class="lvb-qbtn" data-act="dec" data-id="' + item.id + '">−</button>' +
                '<span class="lvb-qnum">' + item.qty + '</span>' +
                '<button class="lvb-qbtn" data-act="inc" data-id="' + item.id + '">+</button>' +
                '<div class="lvb-actions-row">' +
                  '<button class="lvb-icon-btn lvb-edit-btn" data-act="edit" data-id="' + item.id + '" title="Edit custom candle">' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>' +
                  '</button>' +
                  '<button class="lvb-icon-btn lvb-rm-btn" data-act="rm" data-id="' + item.id + '" title="Remove item">' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>' +
                  '</button>' +
                '</div>' +
              '</div>' +
            '</div>' +
            '<div class="lvb-price">$' + (item.price * item.qty).toFixed(2) + '</div>' +
          '</li>';
        }).join('') +
        '</ul>';
      subtEl.textContent = '$' + subtotal.toFixed(2);
      footer.style.display = 'block';
    }

    updateBadges();
    saveCartToStorage(); // Save after every render
  }

  // Simple escape function to prevent XSS
  function escapeHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function updateBadges() {
    var total = items.length;
    ['desktopCartBtn', 'mobileCartBtn'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var badge = el.querySelector('.lvb-badge');
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'lvb-badge';
        el.appendChild(badge);
      }
      badge.textContent = total;
      badge.style.display = total ? 'flex' : 'none';
    });
  }

  /* ── 5. OPEN / CLOSE ── */
  function openCart() {
    render();
    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
    isOpen = true;
  }

  function closeCart() {
    overlay.classList.remove('open');
    drawer.classList.remove('open');
    document.body.style.overflow = '';
    isOpen = false;
  }

  function toggleCart() { isOpen ? closeCart() : openCart(); }

  /* ── 6. ITEM ACTIONS ── */
  function addItem(item) {
    // Validate required fields
    if (!item || !item.name || item.price === undefined || item.price === null || isNaN(item.price)) {
      console.warn('Invalid cart item:', item);
      return;
    }

    if (!item.id) {
      if (item.product_id) {
        item.id = 'prod_' + item.product_id + (item.size_id ? '_size' + item.size_id : '') + (item.box_id ? '_box' + item.box_id : '') + (item.sku ? '_' + item.sku : '');
      } else if (item.accessory_id) {
        item.id = 'acc_' + item.accessory_id + (item.sku ? '_' + item.sku : '');
      } else if (item.sku) {
        item.id = item.sku;
      } else {
        item.id = 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
      }
    }

    if (!item.sku) {
      item.sku = item.id;
    }

    var existing = null;
    for (var i = 0; i < items.length; i++) {
      if (items[i].id === item.id) { existing = items[i]; break; }
    }
    
    if (existing) {
      existing.qty += (item.qty || 1);
      if (item.sku && !existing.sku) {
        existing.sku = item.sku;
      }
    } else {
      items.push({ 
        qty: item.qty || 1, 
        image: item.image || '', 
        sku: item.sku || item.id,
        scent: item.scent || '', 
        id: item.id, 
        product_id: item.product_id || null,
        accessory_id: item.accessory_id || null,
        name: item.name,
        price: parseFloat(item.price),
        size_id: item.size_id || null,
        size_name: item.size_name || null,
        box_id: item.box_id || null,
        box_name: item.box_name || null,
        fragrance_id: item.fragrance_id || null,
        fragrance_name: item.fragrance_name || null,
        color_name: item.color_name || null,
        color_code: item.color_code || null,
        vessel: item.vessel || null,
        wick_type: item.wick_type || null
      });
    }
    
    render();
    if (!isOpen) openCart();
  }

  function removeItem(id) {
    items = items.filter(function (i) { return i.id !== id; });
    render();
  }

  function updateQty(id, delta) {
    for (var i = 0; i < items.length; i++) {
      if (items[i].id === id) {
        items[i].qty += delta;
        if (items[i].qty <= 0) { 
          removeItem(id); 
          return; 
        }
        render();
        return;
      }
    }
  }

  function clearCart() {
    items = [];
    render();
    if (isOpen) closeCart();
  }

  function getCartItems() {
    return items.slice(); // Return a copy of the cart items
  }

  function getCartTotal() {
    return items.reduce(function (s, i) { return s + i.price * i.qty; }, 0);
  }

  function getCartCount() {
    return items.length;
  }

  /* ── 7. EVENTS ── */
  // qty / remove / edit delegation
  var bodyElement = document.getElementById('lvbBody');
  if (bodyElement) {
    bodyElement.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-act]');
      if (!btn) return;
      var id  = btn.getAttribute('data-id');
      var act = btn.getAttribute('data-act');
      if (act === 'inc') updateQty(id, +1);
      if (act === 'dec') updateQty(id, -1);
      if (act === 'rm')  removeItem(id);
      if (act === 'edit') editItem(id);
    });
  }

  function buildEditUrl(item) {
    var vessel = item.vessel || (item.sku ? item.sku.charAt(0) : 'C');
    var params = new URLSearchParams();
    if (vessel) params.set('vessel', vessel);
    if (item.color_name) params.set('color', item.color_name);
    if (item.fragrance_name) params.set('frag', item.fragrance_name);
    if (item.box_name && item.box_name !== 'No Packaging' && item.box_name !== '—') params.set('box', item.box_name);

    var base = (typeof window.basePath !== 'undefined') ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '');
    var builderUrl = base + '/builder';
    return builderUrl + '?' + params.toString() + '#step1';
  }

  function editItem(id) {
    var item = items.find(function(i) { return i.id === id; });
    if (!item) return;
    closeCart();
    var editUrl = buildEditUrl(item);
    window.location.href = editUrl;
  }

  var closeBtn = document.getElementById('lvbClose');
  if (closeBtn) closeBtn.addEventListener('click', closeCart);
  overlay.addEventListener('click', closeCart);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) closeCart();
  });

  // Event delegation on document — works regardless of when script loads,
  // no timing issues, no readyState checks needed.
  document.addEventListener('click', function (e) {
    var target = e.target.closest('#desktopCartBtn, #mobileCartBtn, [data-cart-open]');
    if (target) {
      e.preventDefault();
      e.stopPropagation();
      toggleCart();
    }
  });

  // Checkout button handler
  var footerElement = document.getElementById('lvbFooter');
  if (footerElement) {
    footerElement.addEventListener('click', function (e) {
      if (e.target.classList.contains('lvb-checkout')) {
        // Store cart data in sessionStorage for checkout page
        sessionStorage.setItem('checkout_cart', JSON.stringify(items));
        // Redirect to checkout page with base path support
        var base = (typeof window.basePath !== 'undefined') ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '');
        window.location.href = base + '/checkout';
      }
    });
  }

  // Save cart before page unload (additional safety)
  window.addEventListener('beforeunload', function() {
    saveCartToStorage();
  });

  // Listen for storage or custom cart update events to keep drawer in sync
  window.addEventListener('storage', function(e) {
    if (!e.key || e.key === STORAGE_KEY) {
      loadCartFromStorage();
      render();
    }
  });

  window.addEventListener('lvb_cart_updated', function() {
    loadCartFromStorage();
    render();
  });

  /* ── 8. PUBLIC API ── */
  window.LVBCart = {
    open:       openCart,
    close:      closeCart,
    toggle:     toggleCart,
    addItem:    addItem,
    removeItem: removeItem,
    updateQty:  updateQty,
    clear:      clearCart,
    getItems:   getCartItems,
    getTotal:   getCartTotal,
    getCount:   getCartCount,
    render:     render,
    reload:     function() { loadCartFromStorage(); render(); }
  };

  /* ── 9. INITIALIZE ── */
  loadCartFromStorage();
  render(); // Initial render to show any saved items
  updateBadges(); // Ensure badges are updated

})();