<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Cormorant+Garamond:wght@400;500;600&family=Cinzel:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Scoped styles - no global leaks */
        .lvb-footer-wrap {
            all: initial;
            display: block;
            border-top: 1px solid #d1e5ed;
            background: linear-gradient(to bottom, #ffffff 0%, #d5ebf2 100%);
            color: #4a5a65;
            padding: 50px 40px 40px 40px;
            box-sizing: border-box;
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
            font-family: 'Cinzel', 'Trajan Pro', 'Times New Roman', serif;
            font-size: 34px;
            letter-spacing: 5px;
            color: #1e2d38;
            margin: 0 0 25px 0;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* Summary text - Inter 400 normal */
        .lvb-footer-wrap .lvb-summary {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 14px;
            line-height: 1.7;
            max-width: 260px;
            color: #5a6d7a;
        }

        /* Column Headers - Cormorant Garamond 400 normal */
        .lvb-footer-wrap .lvb-heading-small {
            font-family: 'Cormorant Garamond', 'Times New Roman', serif;
            font-weight: 400;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #1e2d38;
            margin-bottom: 25px;
        }

        /* Navigation links - Inter 400 normal */
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
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 14px;
            color: #5a6d7a;
            transition: color 0.2s;
        }

        .lvb-footer-wrap .lvb-nav-link:hover {
            color: #1e2d38;
        }

        /* Giving Back description - Inter 400 normal */
        .lvb-footer-wrap .lvb-giving-desc {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 14px;
            line-height: 1.7;
            max-width: 310px;
            color: #5a6d7a;
        }

        /* Bottom bar - Inter 400 normal */
        .lvb-footer-wrap .lvb-bottom-bar {
            border-top: 1px solid #d1e5ed;
            max-width: 1200px;
            margin: 50px auto 0 auto;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 12px;
            color: #7a8a94;
            letter-spacing: 0.3px;
        }

        /* New Policy Links Alignment styles */
        .lvb-footer-wrap .lvb-policy-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .lvb-footer-wrap .lvb-policy-link {
            text-decoration: none;
            color: #5a6d7a;
            transition: color 0.2s;
        }

        .lvb-footer-wrap .lvb-policy-link:hover {
            color: #1e2d38;
        }

        .lvb-footer-wrap .lvb-divider {
            color: #d1e5ed;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1024px) {
            .lvb-footer-wrap {
                padding: 40px 30px 30px 30px;
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
                gap: 15px;
                text-align: center;
            }
        }

        @media (max-width: 600px) {
            .lvb-footer-wrap {
                padding: 40px 20px 30px 20px;
            }
            
            .lvb-footer-wrap .lvb-footer-content {
                grid-template-columns: 1fr;
                gap: 35px;
            }
            
            .lvb-footer-wrap .lvb-bottom-bar {
                margin-top: 40px;
            }
            
            .lvb-footer-wrap .lvb-logo-text {
                font-size: 28px;
            }
            
            .lvb-footer-wrap .lvb-heading-small {
                font-size: 18px;
                margin-bottom: 20px;
            }
            
            .lvb-footer-wrap .lvb-summary,
            .lvb-footer-wrap .lvb-nav-link,
            .lvb-footer-wrap .lvb-giving-desc {
                font-size: 13px;
            }

            .lvb-footer-wrap .lvb-policy-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px 12px;
            }
            
            .lvb-footer-wrap .lvb-divider {
                display: none; /* Hide dividers on very small layouts for better wrapping */
            }
        }
        
        @media (max-width: 480px) {
            .lvb-footer-wrap {
                padding: 35px 16px 25px 16px;
            }
        }
    <link rel="stylesheet" href="<?php echo $base; ?>/public/assets/css/ada-compliance.css">
</head>
<body style="margin:0;">

    <footer class="lvb-footer-wrap">
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
                <a href="javascript:void(0)" onclick="if(document.getElementById('adaStatementModal')) document.getElementById('adaStatementModal').style.display='flex'" class="lvb-policy-link" style="font-weight:600;"><i class="fas fa-universal-access" style="color:#0F4C5C; margin-right:4px;"></i> Accessibility (ADA)</a>
            </div>

            <span>Handcrafted candles in the Spirit of Laguna Beach</span>
        </div>
    </footer>

    <?php include __DIR__ . '/popup_modal.php'; ?>
    <?php include __DIR__ . '/ada_widget.php'; ?>

</body>
</html>
