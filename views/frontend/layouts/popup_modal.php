<?php
/**
 * popup_modal.php — Grand Opening Luxury Promo Popup Modal
 * Fully responsive across mobile, tablet, laptop, desktop, and landscape orientations.
 */
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
<!-- Grand Opening Popup Modal -->
<div id="lvbGrandOpeningPopup" class="lvb-popup-overlay" role="dialog" aria-modal="true" aria-label="Grand Opening Offer" tabindex="-1">
    <div class="lvb-popup-wrapper">
        <button type="button" class="lvb-popup-close-btn" id="lvbPopupCloseBtn" aria-label="Close promotional offer">
            <svg class="lvb-popup-close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="lvb-popup-image-container">
            <img src="<?php echo $base; ?>/img/popup-grand-opening.jpg" 
                 alt="Laguna Vibe Grand Opening - 20% Off Your First Order - Code: WELCOME20" 
                 class="lvb-popup-main-img" 
                 loading="eager" 
                 draggable="false" />
        </div>
    </div>
</div>

<style>
/* ── Grand Opening Popup Overlay ── */
.lvb-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    width: 100dvw;
    height: 100vh;
    height: 100dvh;
    background: rgba(10, 16, 22, 0.68);
    backdrop-filter: blur(12px) saturate(150%);
    -webkit-backdrop-filter: blur(12px) saturate(150%);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: max(16px, env(safe-area-inset-top, 16px)) max(16px, env(safe-area-inset-right, 16px)) max(16px, env(safe-area-inset-bottom, 16px)) max(16px, env(safe-area-inset-left, 16px));
    box-sizing: border-box;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.38s cubic-bezier(0.16, 1, 0.3, 1), 
                visibility 0.38s ease;
    touch-action: pan-y;
}

.lvb-popup-overlay.lvb-popup-active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

/* ── Popup Card Wrapper ── */
.lvb-popup-wrapper {
    position: relative;
    width: fit-content;
    max-width: min(480px, calc(100vw - 32px));
    max-height: min(86vh, 86dvh);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: clamp(16px, 3vw, 24px);
    background: #000000;
    box-shadow: 0 32px 75px -15px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.22) inset,
                0 8px 24px -4px rgba(0, 0, 0, 0.35);
    overflow: hidden;
    transform: scale(0.92) translateY(16px);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                opacity 0.4s ease;
    user-select: none;
    -webkit-user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.lvb-popup-overlay.lvb-popup-active .lvb-popup-wrapper {
    transform: scale(1) translateY(0);
}

/* ── Close Button (Accessible Touch Target) ── */
.lvb-popup-close-btn {
    position: absolute;
    top: clamp(10px, 2.5vw, 16px);
    right: clamp(10px, 2.5vw, 16px);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(14, 18, 24, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    padding: 0;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: background-color 0.2s ease, transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    outline: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.lvb-popup-close-btn:hover {
    background: rgba(14, 18, 24, 0.9);
    border-color: rgba(255, 255, 255, 0.7);
    transform: scale(1.08);
}

.lvb-popup-close-btn:active {
    transform: scale(0.94);
}

.lvb-popup-close-btn:focus-visible {
    box-shadow: 0 0 0 3px rgba(214, 232, 240, 0.7);
}

.lvb-popup-close-icon {
    width: 18px;
    height: 18px;
    stroke: #ffffff;
    display: block;
    transition: transform 0.25s ease;
}

.lvb-popup-close-btn:hover .lvb-popup-close-icon {
    transform: rotate(90deg);
}

/* ── Image Container & Hero Image ── */
.lvb-popup-image-container {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    line-height: 0;
    overflow: hidden;
    border-radius: inherit;
}

.lvb-popup-main-img {
    width: auto;
    height: auto;
    max-width: min(480px, calc(100vw - 32px));
    max-height: min(86vh, 86dvh);
    object-fit: contain;
    display: block;
    border-radius: inherit;
    pointer-events: auto;
}

/* ── Tablet & Small Laptops ── */
@media (max-width: 900px) {
    .lvb-popup-wrapper {
        max-width: min(440px, calc(100vw - 32px));
        max-height: min(84vh, 84dvh);
    }
    .lvb-popup-main-img {
        max-width: min(440px, calc(100vw - 32px));
        max-height: min(84vh, 84dvh);
    }
}

/* ── Mobile Devices (Portrait) ── */
@media (max-width: 600px) {
    .lvb-popup-overlay {
        padding: 12px 10px;
    }
    
    .lvb-popup-wrapper {
        max-width: calc(100vw - 24px);
        max-height: min(88vh, 88dvh);
        border-radius: 18px;
    }

    .lvb-popup-main-img {
        max-width: calc(100vw - 24px);
        max-height: min(88vh, 88dvh);
        border-radius: 18px;
    }

    .lvb-popup-close-btn {
        top: 10px;
        right: 10px;
        width: 36px;
        height: 36px;
    }

    .lvb-popup-close-icon {
        width: 16px;
        height: 16px;
    }
}

/* ── Ultra-compact Mobile Screens (≤380px) ── */
@media (max-width: 380px) {
    .lvb-popup-wrapper,
    .lvb-popup-main-img {
        max-width: calc(100vw - 16px);
        max-height: min(86vh, 86dvh);
        border-radius: 14px;
    }

    .lvb-popup-close-btn {
        top: 8px;
        right: 8px;
        width: 34px;
        height: 34px;
    }
}

/* ── Mobile & Tablet Landscape Mode ── */
@media (max-height: 560px) {
    .lvb-popup-overlay {
        padding: 8px 12px;
    }

    .lvb-popup-wrapper,
    .lvb-popup-main-img {
        max-height: min(92vh, 92dvh);
        max-width: 85vw;
        border-radius: 16px;
    }

    .lvb-popup-close-btn {
        top: 8px;
        right: 8px;
        width: 32px;
        height: 32px;
    }

    .lvb-popup-close-icon {
        width: 15px;
        height: 15px;
    }
}
</style>

<script>
(function() {
    const POPUP_DELAY_MS = 600; // 600ms natural delay after load

    function initPopup() {
        const overlay = document.getElementById('lvbGrandOpeningPopup');
        const closeBtn = document.getElementById('lvbPopupCloseBtn');

        if (!overlay || !closeBtn) return;

        function showPopup() {
            overlay.classList.add('lvb-popup-active');
            document.body.style.overflow = 'hidden'; // prevent background scrolling
            closeBtn.focus();
        }

        function hidePopup() {
            overlay.classList.remove('lvb-popup-active');
            document.body.style.overflow = '';
        }

        // Close on close button click
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            hidePopup();
        });

        // Close when clicking overlay backdrop outside image wrapper
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                hidePopup();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.classList.contains('lvb-popup-active')) {
                hidePopup();
            }
        });

        // Expose helpers to control popup anytime
        window.openGrandOpeningPopup = function() {
            showPopup();
        };

        window.closeGrandOpeningPopup = function() {
            hidePopup();
        };

        // Trigger on every load/reload
        setTimeout(showPopup, POPUP_DELAY_MS);
    }

    if (document.readyState === 'complete') {
        initPopup();
    } else {
        window.addEventListener('load', initPopup);
    }
})();
</script>
