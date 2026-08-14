<style>
    /* Laguna Vibe Global Footer */
    .lvb-footer-wrap {
        display: block;
        width: 100%;
        border-top: 1px solid #d1e5ed;
        background: linear-gradient(180deg, #ffffff 0%, #edf6f9 100%);
        color: #4a5a65;
        padding: 55px 40px 35px 40px;
        box-sizing: border-box;
        font-family: 'DM Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        clear: both;
    }

    .lvb-footer-wrap *,
    .lvb-footer-wrap *::before,
    .lvb-footer-wrap *::after {
        box-sizing: border-box;
    }

    .lvb-footer-wrap .lvb-footer-content {
        display: grid;
        grid-template-columns: 1.3fr 0.8fr 0.8fr 1.1fr;
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Logo - Elegant Serif Branding */
    .lvb-footer-wrap .lvb-logo-text {
        font-family: 'Playfair Display', 'Cinzel', 'Times New Roman', serif;
        font-size: 26px;
        letter-spacing: 2px;
        color: #0f4c5c;
        margin: 0 0 16px 0;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }

    /* Summary text */
    .lvb-footer-wrap .lvb-summary {
        font-size: 13.5px;
        line-height: 1.7;
        max-width: 290px;
        color: #5a6d7a;
        margin: 0;
    }

    /* Column Headers */
    .lvb-footer-wrap .lvb-heading-small {
        font-family: 'Playfair Display', 'Cormorant Garamond', serif;
        font-weight: 600;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #1e2d38;
        margin: 0 0 20px 0;
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
        font-size: 13.5px;
        color: #5a6d7a;
        transition: color 0.2s ease, transform 0.2s ease;
        display: inline-block;
    }

    .lvb-footer-wrap .lvb-nav-link:hover {
        color: #0f4c5c;
        transform: translateX(2px);
    }

    /* Giving Back description */
    .lvb-footer-wrap .lvb-giving-desc {
        font-size: 13.5px;
        line-height: 1.7;
        max-width: 320px;
        color: #5a6d7a;
        margin: 0;
    }

    /* Bottom bar */
    .lvb-footer-wrap .lvb-bottom-bar {
        border-top: 1px solid #d1e5ed;
        max-width: 1200px;
        margin: 45px auto 0 auto;
        padding-top: 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12.5px;
        color: #7a8a94;
        letter-spacing: 0.3px;
        flex-wrap: wrap;
        gap: 15px;
    }

    /* Policy Links */
    .lvb-footer-wrap .lvb-policy-links {
        display: flex;
        gap: 14px;
        align-items: center;
    }

    .lvb-footer-wrap .lvb-policy-link {
        text-decoration: none;
        color: #5a6d7a;
        transition: color 0.2s;
    }

    .lvb-footer-wrap .lvb-policy-link:hover {
        color: #0f4c5c;
    }

    .lvb-footer-wrap .lvb-divider {
        color: #d1e5ed;
    }

    /* Mobile Responsiveness */
    @media (max-width: 1024px) {
        .lvb-footer-wrap {
            padding: 45px 30px 30px 30px;
        }
        .lvb-footer-wrap .lvb-footer-content {
            grid-template-columns: 1fr 1fr;
            gap: 35px 30px;
        }
    }

    @media (max-width: 768px) {
        .lvb-footer-wrap .lvb-bottom-bar {
            flex-direction: column;
            gap: 14px;
            text-align: center;
            justify-content: center;
        }
    }

    @media (max-width: 600px) {
        .lvb-footer-wrap {
            padding: 35px 20px 25px 20px;
        }
        .lvb-footer-wrap .lvb-footer-content {
            grid-template-columns: 1fr;
            gap: 28px;
        }
        .lvb-footer-wrap .lvb-bottom-bar {
            margin-top: 35px;
        }
        .lvb-footer-wrap .lvb-logo-text {
            font-size: 22px;
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

<footer class="lvb-footer-wrap">
    <div class="lvb-footer-content">
        
        <div class="lvb-column">
            <h2 class="lvb-logo-text">Laguna Vibe</h2>
            <p class="lvb-summary">Hand-poured candles inspired by the Pacific. Designed and finished in Laguna Beach, California.</p>
        </div>

        <div class="lvb-column">
            <h3 class="lvb-heading-small">Shop</h3>
            <ul class="lvb-nav-list">
                <li class="lvb-nav-item"><a href="<?php echo $base ?? ''; ?>/shop" class="lvb-nav-link">Collection</a></li>
                <li class="lvb-nav-item"><a href="<?php echo $base ?? ''; ?>/builder" class="lvb-nav-link">Design Yours</a></li>
                <li class="lvb-nav-item"><a href="<?php echo $base ?? ''; ?>/accessories" class="lvb-nav-link">Accessories</a></li>
            </ul>
        </div>

        <div class="lvb-column">
            <h3 class="lvb-heading-small">Studio</h3>
            <ul class="lvb-nav-list">
                <li class="lvb-nav-item"><a href="<?php echo $base ?? ''; ?>/about" class="lvb-nav-link">About</a></li>
                <li class="lvb-nav-item"><a href="<?php echo $base ?? ''; ?>/journal" class="lvb-nav-link">Journal</a></li>
                <li class="lvb-nav-item"><a href="<?php echo $base ?? ''; ?>/contact" class="lvb-nav-link">Contact</a></li>
            </ul>
        </div>

        <div class="lvb-column">
            <h3 class="lvb-heading-small">Giving Back</h3>
            <p class="lvb-giving-desc">A portion of every order is donated to ocean conservation and Laguna's coastal community programs.</p>
        </div>

    </div>

    <div class="lvb-bottom-bar">
        <span>© <?php echo date('Y'); ?> Laguna Vibe. All rights reserved.</span>
        
        <div class="lvb-policy-links">
            <a href="<?php echo $base ?? ''; ?>/privacy" class="lvb-policy-link">Privacy Policy</a>
            <span class="lvb-divider">|</span>
            <a href="<?php echo $base ?? ''; ?>/terms" class="lvb-policy-link">Terms of Service</a>
            <span class="lvb-divider">|</span>
            <a href="<?php echo $base ?? ''; ?>/returns" class="lvb-policy-link">Return Policy</a>
        </div>

        <span>Handcrafted in the Spirit of Laguna Beach</span>
    </div>
</footer>

<?php 
$popupFile = __DIR__ . '/popup_modal.php';
if (file_exists($popupFile)) {
    include $popupFile;
}
?>
