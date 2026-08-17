<?php
// views/frontend/layouts/footer.php — Master Global Footer
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
<style>
/* ─── LVB Master Global Footer Styles ─── */
.lvb-footer-wrap {
    display: block;
    width: 100%;
    border-top: 1px solid #d1e5ed;
    background: linear-gradient(to bottom, #ffffff 0%, #d5ebf2 100%);
    color: #4a5a65;
    padding: 60px 40px 40px 40px;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
    position: relative;
    z-index: 10;
}

.lvb-footer-wrap *,
.lvb-footer-wrap *::before,
.lvb-footer-wrap *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.lvb-footer-wrap .lvb-footer-content {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 0.8fr 1.2fr;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Logo - Classical Roman serif (Trajan / Cinzel family) */
.lvb-footer-wrap .lvb-logo-text {
    font-family: 'Cinzel', serif;
    font-size: 34px;
    letter-spacing: 5px;
    color: #1e2d38;
    margin: 0 0 20px 0;
    text-transform: uppercase;
    font-weight: 600;
}

/* Summary text */
.lvb-footer-wrap .lvb-summary {
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 14px;
    line-height: 1.7;
    max-width: 280px;
    color: #5a6d7a;
}

/* Column Headers */
.lvb-footer-wrap .lvb-heading-small {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    font-size: 18px;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #1e2d38;
    margin-bottom: 22px;
}

/* Navigation links */
.lvb-footer-wrap .lvb-nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.lvb-footer-wrap .lvb-nav-item {
    margin-bottom: 12px;
}

.lvb-footer-wrap .lvb-nav-link {
    text-decoration: none;
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 14px;
    color: #5a6d7a;
    transition: color 0.2s, transform 0.2s;
    display: inline-block;
}

.lvb-footer-wrap .lvb-nav-link:hover {
    color: #0F4C5C;
    transform: translateX(3px);
}

/* Giving Back description */
.lvb-footer-wrap .lvb-giving-desc {
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 14px;
    line-height: 1.7;
    max-width: 310px;
    color: #5a6d7a;
}

/* Bottom bar */
.lvb-footer-wrap .lvb-bottom-bar {
    border-top: 1px solid #d1e5ed;
    max-width: 1200px;
    margin: 50px auto 0 auto;
    padding-top: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 12.5px;
    color: #7a8a94;
    letter-spacing: 0.3px;
}

/* Policy Links */
.lvb-footer-wrap .lvb-policy-links {
    display: flex;
    gap: 16px;
    align-items: center;
}

.lvb-footer-wrap .lvb-policy-link {
    text-decoration: none;
    color: #5a6d7a;
    transition: color 0.2s;
    font-size: 12.5px;
}

.lvb-footer-wrap .lvb-policy-link:hover {
    color: #0F4C5C;
}

.lvb-footer-wrap .lvb-divider {
    color: #d1e5ed;
}

/* Mobile & Tablet Responsiveness */
@media (max-width: 1024px) {
    .lvb-footer-wrap {
        padding: 50px 30px 35px 30px;
    }
    
    .lvb-footer-wrap .lvb-footer-content {
        grid-template-columns: 1fr 1fr;
        gap: 40px 30px;
    }
    
    .lvb-footer-wrap .lvb-logo-text {
        font-size: 30px;
    }
}

@media (max-width: 768px) {
    .lvb-footer-wrap .lvb-bottom-bar {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}

@media (max-width: 600px) {
    .lvb-footer-wrap {
        padding: 40px 20px 30px 20px;
    }
    
    .lvb-footer-wrap .lvb-footer-content {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    
    .lvb-footer-wrap .lvb-bottom-bar {
        margin-top: 36px;
    }
    
    .lvb-footer-wrap .lvb-logo-text {
        font-size: 28px;
    }
    
    .lvb-footer-wrap .lvb-heading-small {
        font-size: 17px;
        margin-bottom: 16px;
    }
    
    .lvb-footer-wrap .lvb-summary,
    .lvb-footer-wrap .lvb-nav-link,
    .lvb-footer-wrap .lvb-giving-desc {
        font-size: 13.5px;
    }

    .lvb-footer-wrap .lvb-policy-links {
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 12px;
    }
    
    .lvb-footer-wrap .lvb-divider {
        display: none;
    }
}
</style>

<footer class="lvb-footer-wrap" role="contentinfo">
    <div class="lvb-footer-content">
        
        <div class="lvb-column">
            <h2 class="lvb-logo-text">L V B</h2>
            <p class="lvb-summary">Hand-poured candles inspired by the Pacific. Designed and finished in Laguna Beach.</p>
        </div>

        <div class="lvb-column">
            <h3 class="lvb-heading-small">Shop</h3>
            <ul class="lvb-nav-list">
                <li class="lvb-nav-item"><a href="<?php echo $base; ?>/shop" class="lvb-nav-link">Collection</a></li>
                <li class="lvb-nav-item"><a href="<?php echo $base; ?>/builder" class="lvb-nav-link">Design Yours</a></li>
            </ul>
        </div>

        <div class="lvb-column">
            <h3 class="lvb-heading-small">Studio</h3>
            <ul class="lvb-nav-list">
                <li class="lvb-nav-item"><a href="<?php echo $base; ?>/about" class="lvb-nav-link">About</a></li>
                <li class="lvb-nav-item"><a href="<?php echo $base; ?>/journal" class="lvb-nav-link">Journal</a></li>
                <li class="lvb-nav-item"><a href="<?php echo $base; ?>/contact" class="lvb-nav-link">Contact</a></li>
            </ul>
        </div>

        <div class="lvb-column">
            <h3 class="lvb-heading-small">Giving Back</h3>
            <p class="lvb-giving-desc">A portion of every order is donated to ocean conservation and Laguna's coastal community programs.</p>
        </div>

    </div>

    <div class="lvb-bottom-bar">
        <span>© 2026 Laguna Vibe</span>
        
        <div class="lvb-policy-links">
            <a href="<?php echo $base; ?>/privacy" class="lvb-policy-link">Privacy Policy</a>
            <span class="lvb-divider">|</span>
            <a href="<?php echo $base; ?>/terms" class="lvb-policy-link">Terms of Service</a>
            <span class="lvb-divider">|</span>
            <a href="<?php echo $base; ?>/returns" class="lvb-policy-link">Return Policy</a>
            <span class="lvb-divider">|</span>
            <a href="javascript:void(0)" onclick="if(typeof window.openAdaPanel==='function'){window.openAdaPanel();}else if(document.getElementById('adaTriggerBtn')){document.getElementById('adaTriggerBtn').click();}" class="lvb-policy-link" style="font-weight:600;"><i class="fas fa-universal-access" style="color:#0F4C5C; margin-right:4px;"></i> Accessibility (ADA)</a>
        </div>

        <span>Handcrafted candles in the Spirit of Laguna Beach</span>
    </div>
</footer>

<?php 
if (file_exists(__DIR__ . '/popup_modal.php')) {
    include __DIR__ . '/popup_modal.php';
}
if (file_exists(__DIR__ . '/ada_widget.php')) {
    include __DIR__ . '/ada_widget.php';
}
?>
