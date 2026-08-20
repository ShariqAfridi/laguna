<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Cards</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Cormorant+Garamond:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Scoped styles - no global leaks */
        .promo-section {
            all: initial;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 100%;
            margin: 0 auto;
            padding: 60px 20px;
            background: radial-gradient(circle at top, #EDF6F9 0%, #f0f7fb 100%);
            box-sizing: border-box;
        }

        .promo-section *,
        .promo-section *::before,
        .promo-section *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .promo-section .promo-card {
            border-radius: 20px;
            padding: 28px 32px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.02);
            min-height: 280px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .promo-section .promo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        }

        /* Dark Card */
        .promo-section .card-dark {
            background-color: #517085;
            color: #ffffff;
        }

        /* White Card */
        .promo-section .card-white {
            background-color: #ffffff;
            color: #23313d;
        }

        /* Light Blue Gradient Card */
        .promo-section .card-light {
            background: linear-gradient(135deg, #E8F4F8 0%, #CDDFE7 100%);
            color: #23313d;
        }

        .promo-section .promo-icon {
            margin-bottom: 16px;
            width: 32px;
            height: 32px;
        }

        .promo-section .promo-icon svg {
            width: 28px;
            height: 28px;
            stroke-width: 1.5;
        }

        /* All headings - Cormorant Garamond 400 normal */
        .promo-section .promo-title {
            font-family: 'Cormorant Garamond', 'Times New Roman', serif;
            font-weight: 400;
            font-size: 28px;
            margin-bottom: 12px;
            color: inherit;
        }

        /* Card white heading color override */
        .promo-section .card-white .promo-title {
            color: #23313d;
        }

        /* Card light heading color override */
        .promo-section .card-light .promo-title {
            color: #23313d;
        }

        /* All body text - Inter 400 normal */
        .promo-section .promo-text {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 14px;
            line-height: 1.65;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        /* Specific text colors for the light cards */
        .promo-section .card-white .promo-text, 
        .promo-section .card-light .promo-text {
            color: #5a6d7a;
        }

        .promo-section .card-dark .promo-text {
            color: rgba(255, 255, 255, 0.85);
        }

        /* Links/Buttons - Inter 400 normal */
        .promo-section .promo-link {
            text-decoration: none;
            text-transform: uppercase;
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 11px;
            letter-spacing: 1.8px;
            color: inherit;
            padding-bottom: 8px;
            border-bottom: 1px solid transparent;
            transition: border-color 0.3s ease;
            display: inline-block;
            width: fit-content;
        }

        .promo-section .promo-link:hover {
            border-bottom-color: currentColor;
        }

        /* Promo code - Cormorant Garamond 400 normal */
        .promo-section .promo-code {
            display: inline-block;
            border: 1px dotted #cbd5e0;
            padding: 8px 22px;
            border-radius: 50px;
            font-family: 'Cormorant Garamond', 'Times New Roman', serif;
            font-weight: 400;
            letter-spacing: 2px;
            font-size: 14px;
            color: #23313d;
            background: rgba(255, 255, 255, 0.5);
        }

        /* Dark card code override */
        .promo-section .card-dark .promo-code {
            border-color: rgba(255, 255, 255, 0.3);
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }

        /* With every candle text - Inter 400 normal */
        .promo-section .promo-link-span {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 11px;
            letter-spacing: 1.8px;
            color: #5a6d7a;
            display: inline-block;
            text-transform: uppercase;
        }

        /* Mobile Adjustments */
        @media (max-width: 900px) {
            .promo-section {
                grid-template-columns: 1fr;
                max-width: 450px;
                margin: 0 auto;
                padding: 40px 20px;
                gap: 20px;
            }
            
            .promo-section .promo-card {
                min-height: auto;
                padding: 24px 28px;
            }
            
            .promo-section .promo-title {
                font-size: 26px;
            }
            
            .promo-section .promo-code {
                font-size: 13px;
                padding: 6px 18px;
            }
        }

        @media (max-width: 480px) {
            .promo-section {
                padding: 30px 16px;
            }
            
            .promo-section .promo-card {
                padding: 20px 24px;
            }
            
            .promo-section .promo-title {
                font-size: 24px;
            }
            
            .promo-section .promo-text {
                font-size: 13px;
            }
            
            .promo-section .promo-code {
                font-size: 12px;
                padding: 6px 16px;
            }
        }
    </style>
</head>
<body style="margin:0; background:#f0f7fb;">

    <section class="promo-section">
        
        <div class="promo-card card-dark">
            <div class="promo-icon"><i data-lucide="sparkles"></i></div>
            <h2 class="promo-title">Spring Promotion</h2>
            <p class="promo-text">Build any custom candle this season and receive a complimentary keepsake matchbox with your order.</p>
<?php
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
            <a href="<?php echo $base; ?>/builder" class="promo-link">Start the Builder →</a>
        </div>

        <div class="promo-card card-white">
            <div class="promo-icon" style="color: #1d3557;"><i data-lucide="tag"></i></div>
            <h2 class="promo-title">Promo Code</h2>
            <p class="promo-text">Apply a code at checkout for an instant discount on your order. New codes drop with every collection.</p>
            <?php
            require_once __DIR__ . '/../../../db.php';
            $activeCouponCode = 'LAGUNA20';
            if (isset($conn) && $conn) {
                $cQuery = $conn->query("SELECT code FROM coupons WHERE status = 1 ORDER BY id DESC LIMIT 1");
                if ($cQuery && $cRow = $cQuery->fetch_assoc()) {
                    $activeCouponCode = htmlspecialchars($cRow['code']);
                }
            }
            ?>
            <div>
                <span class="promo-code">TRY <?= $activeCouponCode; ?></span>
            </div>
        </div>

        <div class="promo-card card-light">
            <div class="promo-icon" style="color: #1d3557;"><i data-lucide="heart"></i></div>
            <h2 class="promo-title">Giving Back</h2>
            <p class="promo-text">A portion of every order is donated to charitable causes — protecting the coast and communities we love.</p>
            <span class="promo-link-span">With every candle, a contribution</span>
        </div>

    </section>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>