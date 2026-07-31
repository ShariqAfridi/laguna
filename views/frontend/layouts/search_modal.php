<?php
/**
 * search_modal.php — LVB Atelier Slim Luxury Product Search Bar
 */
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
<style>
  /* ── Ultra-Slim Glassmorphic Search Overlay ── */
  .lvb-search-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(14, 23, 31, 0.55);
    backdrop-filter: blur(16px) saturate(150%);
    -webkit-backdrop-filter: blur(16px) saturate(150%);
    z-index: 99999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 50px 16px 20px 16px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.25s ease;
  }

  .lvb-search-overlay.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  /* Slim Modern Search Container */
  .lvb-search-container {
    width: 100%;
    max-width: 650px;
    background: rgba(255, 255, 255, 0.98);
    border-radius: 16px;
    box-shadow: 0 20px 50px -10px rgba(15, 23, 42, 0.25), 
                0 0 0 1px rgba(255, 255, 255, 0.9) inset,
                0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(214, 232, 240, 0.7);
    overflow: hidden;
    transform: translateY(-16px) scale(0.98);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    max-height: 80vh;
  }

  .lvb-search-overlay.active .lvb-search-container {
    transform: translateY(0) scale(1);
  }

  /* Slim Header Bar */
  .lvb-search-header {
    display: flex;
    align-items: center;
    padding: 12px 18px;
    background: #FFFFFF;
    gap: 12px;
    position: relative;
    height: 56px;
  }

  .lvb-search-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #F1F5F9;
    color: #1E2F3A;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .lvb-search-header:focus-within .lvb-search-icon-wrapper {
    background: #1E2F3A;
    color: #FFFFFF;
  }

  .lvb-search-input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-family: 'Inter', -apple-system, sans-serif;
    font-size: 15px;
    font-weight: 450;
    color: #0F172A;
    letter-spacing: -0.1px;
  }

  .lvb-search-input::placeholder {
    color: #94A3B8;
    font-weight: 400;
  }

  .lvb-search-clear-btn {
    background: #E2E8F0;
    border: none;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #475569;
    font-size: 12px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .lvb-search-clear-btn.visible {
    opacity: 1;
    visibility: visible;
  }

  .lvb-search-clear-btn:hover {
    background: #CBD5E1;
    color: #0F172A;
  }

  .lvb-search-close-btn {
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 5px 11px;
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .lvb-search-close-btn:hover {
    background: #1E2F3A;
    border-color: #1E2F3A;
    color: #FFFFFF;
  }

  /* Body & Results Section (Hidden until typing) */
  .lvb-search-body {
    padding: 16px 18px;
    overflow-y: auto;
    border-top: 1px solid #EEF3F6;
    background: #FAFCFD;
    max-height: 65vh;
  }

  .lvb-search-section-title {
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #64748B;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .lvb-search-badge {
    background: #E2E9EF;
    color: #1E2F3A;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
  }

  /* Slim Horizontal Product Items List */
  .lvb-search-results-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .lvb-search-item {
    background: #FFFFFF;
    border: 1px solid #E2E9EF;
    border-radius: 12px;
    padding: 10px 14px;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.2s ease;
  }

  .lvb-search-item:hover {
    border-color: #CBD5E1;
    background: #FFFFFF;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    transform: translateX(3px);
  }

  .lvb-search-item-thumb {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    background: #F8FAFC;
    flex-shrink: 0;
  }

  .lvb-search-item-info {
    flex: 1;
    min-width: 0;
  }

  .lvb-search-item-fragrance {
    font-family: 'Inter', sans-serif;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #64748B;
    margin-bottom: 2px;
  }

  .lvb-search-item-title {
    font-family: 'Cinzel', serif;
    font-size: 14px;
    font-weight: 600;
    color: #0F172A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .lvb-search-item-price {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #0F172A;
    flex-shrink: 0;
  }

  .lvb-search-item-arrow {
    color: #94A3B8;
    font-size: 14px;
    transition: transform 0.2s ease, color 0.2s ease;
    flex-shrink: 0;
  }

  .lvb-search-item:hover .lvb-search-item-arrow {
    transform: translateX(3px);
    color: #1E2F3A;
  }

  /* Empty State & Loader */
  .lvb-search-status-box {
    text-align: center;
    padding: 30px 16px;
    color: #64748B;
    font-family: 'Inter', sans-serif;
  }

  .lvb-search-spinner {
    width: 28px;
    height: 28px;
    border: 2.5px solid #E2E8F0;
    border-top-color: #1E2F3A;
    border-radius: 50%;
    animation: lvbSpin 0.75s linear infinite;
    margin: 0 auto 12px auto;
  }

  @keyframes lvbSpin {
    to { transform: rotate(360deg); }
  }

  @media (max-width: 640px) {
    .lvb-search-overlay {
      padding: 16px 10px;
    }
    .lvb-search-header {
      padding: 10px 14px;
      height: 50px;
    }
    .lvb-search-input {
      font-size: 14px;
    }
    .lvb-search-item {
      padding: 8px 12px;
      gap: 10px;
    }
    .lvb-search-item-thumb {
      width: 42px;
      height: 42px;
    }
  }
</style>

<div class="lvb-search-overlay" id="lvbSearchOverlay" aria-modal="true" role="dialog">
  <div class="lvb-search-container" id="lvbSearchContainer">
    
    <div class="lvb-search-header">
      <div class="lvb-search-icon-wrapper">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </div>
      
      <input type="text" class="lvb-search-input" id="lvbSearchInput" placeholder="Search products, fragrances, candles..." autocomplete="off" spellcheck="false">
      
      <button type="button" class="lvb-search-clear-btn" id="lvbSearchClearBtn" title="Clear search">✕</button>
      <button type="button" class="lvb-search-close-btn" id="lvbSearchCloseBtn">ESC</button>
    </div>

    <div class="lvb-search-body" id="lvbSearchBody" style="display: none;">
      <!-- Live Search Results Container -->
      <div id="lvbSearchResultsContainer" style="display: none;">
        <div class="lvb-search-section-title">
          <span>Matching Products</span>
          <span class="lvb-search-badge" id="lvbSearchResultsBadge">0</span>
        </div>
        <div class="lvb-search-results-list" id="lvbSearchResultsGrid"></div>
      </div>

      <!-- Loading State -->
      <div class="lvb-search-status-box" id="lvbSearchLoading" style="display: none;">
        <div class="lvb-search-spinner"></div>
        <div style="font-weight: 500; font-size: 13px; color: #1E2F3A;">Searching LVB collection...</div>
      </div>

      <!-- Empty State -->
      <div class="lvb-search-status-box" id="lvbSearchEmpty" style="display: none;">
        <div style="font-size: 24px; margin-bottom: 6px;">🔍</div>
        <div style="font-weight: 600; font-size: 14px; color: #1E2F3A; margin-bottom: 2px;">No matching products found</div>
        <div style="font-size: 12px; color: #64748B;">Try searching for a different scent or collection.</div>
      </div>
    </div>

  </div>
</div>

<script>
(function() {
  const overlay = document.getElementById('lvbSearchOverlay');
  const container = document.getElementById('lvbSearchContainer');
  const input = document.getElementById('lvbSearchInput');
  const clearBtn = document.getElementById('lvbSearchClearBtn');
  const closeBtn = document.getElementById('lvbSearchCloseBtn');
  const bodySection = document.getElementById('lvbSearchBody');
  const resultsContainer = document.getElementById('lvbSearchResultsContainer');
  const resultsBadge = document.getElementById('lvbSearchResultsBadge');
  const resultsGrid = document.getElementById('lvbSearchResultsGrid');
  const loadingState = document.getElementById('lvbSearchLoading');
  const emptyState = document.getElementById('lvbSearchEmpty');
  
  const apiEndpoint = '<?php echo $base; ?>/api/search';

  let debounceTimer = null;

  function openSearch() {
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
      input.focus();
    }, 100);
  }

  function closeSearch() {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  function clearSearch() {
    input.value = '';
    clearBtn.classList.remove('visible');
    bodySection.style.display = 'none';
    resultsContainer.style.display = 'none';
    loadingState.style.display = 'none';
    emptyState.style.display = 'none';
    input.focus();
  }

  async function performSearch(query) {
    query = query.trim();
    if (!query) {
      clearSearch();
      return;
    }

    clearBtn.classList.add('visible');
    bodySection.style.display = 'block';
    resultsContainer.style.display = 'none';
    emptyState.style.display = 'none';
    loadingState.style.display = 'block';

    try {
      const response = await fetch(`${apiEndpoint}?q=${encodeURIComponent(query)}`);
      const data = await response.json();

      loadingState.style.display = 'none';

      if (data.success && data.results && data.results.length > 0) {
        resultsGrid.innerHTML = '';
        resultsBadge.textContent = `${data.results.length}`;

        data.results.forEach(product => {
          const card = document.createElement('a');
          card.className = 'lvb-search-item';
          card.href = product.url;
          card.innerHTML = `
            <img src="${product.image}" alt="${escapeHtml(product.product_name)}" class="lvb-search-item-thumb" onerror="this.src='https://placehold.co/100x100/14222b/FFFFFF?text=LVB'">
            <div class="lvb-search-item-info">
              <div class="lvb-search-item-fragrance">${escapeHtml(product.fragrance_name)}</div>
              <div class="lvb-search-item-title">${escapeHtml(product.product_name)}</div>
            </div>
            <div class="lvb-search-item-price">${product.price}</div>
            <div class="lvb-search-item-arrow">&rarr;</div>
          `;
          resultsGrid.appendChild(card);
        });

        resultsContainer.style.display = 'block';
      } else {
        emptyState.style.display = 'block';
      }
    } catch (err) {
      console.error('Search request error:', err);
      loadingState.style.display = 'none';
      emptyState.style.display = 'block';
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;")
               .replace(/</g, "&lt;")
               .replace(/>/g, "&gt;")
               .replace(/"/g, "&quot;")
               .replace(/'/g, "&#039;");
  }

  // Input listener with debounce
  input.addEventListener('input', function() {
    const val = input.value;
    if (val.trim().length > 0) {
      clearBtn.classList.add('visible');
    } else {
      clearBtn.classList.remove('visible');
    }

    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      performSearch(val);
    }, 240);
  });

  // Clear & Close listeners
  clearBtn.addEventListener('click', clearSearch);
  closeBtn.addEventListener('click', closeSearch);

  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) {
      closeSearch();
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && overlay.classList.contains('active')) {
      closeSearch();
    }
  });

  // Expose global controller
  window.LVBSearchModal = {
    open: openSearch,
    close: closeSearch,
    search: performSearch
  };

  // Delegate search button clicks across header & mobile header
  document.addEventListener('click', function(e) {
    const trigger = e.target.closest('#desktopSearchBtn, #mobileSearchBtn, .lvb-search-trigger');
    if (trigger) {
      e.preventDefault();
      openSearch();
    }
  });
})();
</script>
