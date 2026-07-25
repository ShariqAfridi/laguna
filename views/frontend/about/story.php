<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laguna Vibe Beach - About</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* Base Styling */
        .vibe-page-body {
            margin: 0;
            padding: 0;
            background-color: #f8fbfc;
            -webkit-font-smoothing: antialiased;
        }

        /* Main Section Wrapper with Side Padding */
        .vibe-about-section {
            width: 100%;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            padding: 60px 8%; 
            box-sizing: border-box;
            gap: 40px;
        }

        /* Left Side: Image with Rounded Borders */
        .vibe-image-container {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .vibe-featured-img {
            width: 100%;
            max-width: 550px;
            aspect-ratio: 4 / 5;
            object-fit: cover;
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: block;
        }

        /* Right Side: Text Content */
        .vibe-text-content {
            padding-left: 20px;
            max-width: 550px;
        }

        .vibe-paragraph {
            font-family: inter;
            font-size: 17px;
            line-height: 1.8;
            color: #556677;
            font-weight: 300;
            margin-bottom: 25px;
        }

        /* Marine Wildlife Impact Block */
        .vibe-impact-block {
            border-left: 2px solid #4a9bb0;
            padding: 18px 22px;
            margin-bottom: 25px;
            background: linear-gradient(135deg, rgba(74,155,176,0.06) 0%, rgba(74,155,176,0.02) 100%);
            border-radius: 0 8px 8px 0;
        }

        .vibe-impact-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #4a9bb0;
            font-weight: 500;
            margin-bottom: 10px;
            display: block;
        }

        .vibe-impact-block .vibe-paragraph {
            margin-bottom: 0;
        }

        /* CTA Link */
        .vibe-cta-link {
            display: inline-block;
            margin-top: 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: 500;
            color: #1a2a36;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .vibe-cta-link:hover {
            opacity: 0.7;
        }

        /* Responsive Fixes */
        @media (max-width: 992px) {
            .vibe-about-section {
                grid-template-columns: 1fr;
                padding: 40px 5%;
                text-align: center;
            }
            .vibe-text-content {
                padding-left: 0;
                margin: 0 auto;
            }
            .vibe-featured-img {
                max-width: 100%;
            }
            .vibe-impact-block {
                text-align: left;
            }
        }
    </style>
</head>
<body class="vibe-page-body">

    <section class="vibe-about-section">
        <div class="vibe-image-container">
            <img src="<?php echo $base; ?>/img/about.webp" alt="Candle on the beach" class="vibe-featured-img">
        </div>

        <div class="vibe-text-content">
            <p class="vibe-paragraph">
                Inspired by the timeless beauty of Laguna Beach, 
                Laguna Vibe captures the spirit of California coastal 
                living through scent.
            </p>

            <p class="vibe-paragraph">
                Each candle is hand-poured in small batches near 
                the Pacific, blending premium ingredients with 
                artisan craftsmanship.
            </p>

            <p class="vibe-paragraph">
                Designed to evoke warmth, serenity, and 
                effortless luxury, our fragrances bring the
                calming essence of the coast into your home.
            </p>

            <div class="vibe-impact-block">
                <span class="vibe-impact-label">Marine Wildlife Impact Program</span>
                <p class="vibe-paragraph">
                    Our oceans are home to incredible wildlife facing growing threats from pollution, habitat loss, and climate change. That's why we donate 2% from every sale to organizations dedicated to marine animal rescue, endangered species protection, and ocean habitat restoration. Every purchase helps create a healthier future for marine life — one candle at a time.
                </p>
            </div>

<?php
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
            <a href="<?php echo $base; ?>/builder" class="vibe-cta-link">Begin Your Candle &rarr;</a>
        </div>
    </section>

</body>
</html>