<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Laguna Vibe — Handcrafted Candles</title>
    
    <!-- Fonts: Inter & Arial Rounded -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600&display=swap" rel="stylesheet">
    
    <style>
        /* ---------- RESET / GLOBAL SCOPING (no leaks) ---------- */
        .laguna-vibe-marquee-hero {
            all: initial;
            display: block;
            position: relative;
            width: 100%;
            height: 100svh;
            min-height: 660px;
            overflow: hidden;
            isolation: isolate;
            contain: layout style paint;
            align-content: center;
        }
        
        .laguna-vibe-marquee-hero *,
        .laguna-vibe-marquee-hero *::before,
        .laguna-vibe-marquee-hero *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        /* ---------- VIDEO + OVERLAY (exact reference gradient) ---------- */
        .lvh-video-layer {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .lvh-video-layer video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            pointer-events: none;
        }
        .lvh-gradient-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, 
                        rgba(6, 29, 43, 0.35) 0%, 
                        rgba(6, 29, 43, 0.25) 40%, 
                        rgba(6, 29, 43, 0.9) 100%);
            pointer-events: none;
            z-index: 1;
        }
        
        /* ---------- MAIN CONTENT (text + buttons) laptop-optimized ---------- */
        .lvh-content-wrapper {
            position: relative;
            z-index: 12;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            padding-top: 5rem;
        }
        
        @media (min-width: 1280px) {
            .lvh-content-wrapper {
                padding-top: 4rem;
            }
        }
        @media (max-height: 800px) and (min-width: 1024px) {
            .lvh-content-wrapper {
                padding-top: 2.8rem;
            }
        }
        
        /* Eyebrow: Handcrafted candles in the Spirit of Laguna Beach */
        .lvh-eyebrow-text {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 11px;
            line-height: 1.5;
            letter-spacing: 0.32em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 0.75rem;
            animation: lvh-fadeUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(12px);
        }
        
        /* Title: Arial Rounded MT Bold */
        .lvh-main-title {
            font-family: 'Arial Rounded MT Bold', 'Arial Rounded MT', 'Helvetica Rounded', Arial, sans-serif;
            font-weight: 400;
            font-size: clamp(44px, 10vw, 98px);
            line-height: 0.95;
            letter-spacing: 0.02em;
            color: #ffffff;
            margin-top: 0.25rem;
            margin-bottom: 0.75rem;
            animation: lvh-fadeUp 0.6s ease 0.1s forwards;
            opacity: 0;
            transform: translateY(12px);
        }
        
        /* Tagline */
        .lvh-tagline-text {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 17px;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.85);
            max-width: 460px;
            margin-bottom: 1.5rem;
            animation: lvh-fadeUp 0.6s ease 0.2s forwards;
            opacity: 0;
            transform: translateY(12px);
        }
        
        /* Buttons group - Inter 500 medium 12px/16px */
        .lvh-button-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.9rem;
            animation: lvh-fadeUp 0.6s ease 0.3s forwards;
            opacity: 0;
            transform: translateY(12px);
        }
        
        .lvh-btn-modern {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 12px;
            line-height: 1.333;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 0 1.6rem;
            height: 46px;
            min-width: 200px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            white-space: nowrap;
            border: none;
            background: transparent;
        }
        
        .lvh-btn-primary {
            background: #ffffff;
            color: #1a3a3f;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .lvh-btn-primary:hover {
            background: #f5f5f5;
            transform: scale(1.02);
        }
        .lvh-btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.6);
            color: #ffffff;
            background: transparent;
        }
        .lvh-btn-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.95);
            transform: scale(1.02);
        }
        
        /* ---------- INFINITE SCROLLING MARQUEE (HOME PAGE CANDLES STRIP CONCEPT) ---------- */
        .lvh-marquee-section {
            padding: 40px 0 50px 0;
            margin-top: 0;
            z-index: 15;
            background: #ffffff;
            position: relative;
            width: 100%;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .lvh-strip-header-container {
            text-align: center;
            max-width: 900px;
            margin: 0 auto 36px auto;
            padding: 0 20px;
        }

        .lvh-strip-title {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 20px;
            line-height: 1.3;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #1a2b3c;
            margin-bottom: 6px;
        }

        .lvh-strip-subtitle {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 14px;
            color: #5a6d7a;
            letter-spacing: 0.5px;
        }
        
        .lvh-marquee-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            pointer-events: auto;
            padding: 10px 0;
            margin: 0;
        }
        
        .lvh-marquee-track {
            display: flex;
            gap: 52px;
            animation: scrollMarquee 40s linear infinite;
            align-items: flex-end;
            will-change: transform;
            width: max-content;
        }
        
        .lvh-marquee-item {
            flex: 0 0 auto;
            width: 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            transition: transform 0.25s ease;
            cursor: pointer;
        }
        
        .lvh-marquee-item:hover {
            transform: translateY(-6px);
        }

        .lvh-candle-img-wrapper {
            width: 100%;
            height: 220px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .lvh-candle-img {
            max-width: 100%;
            max-height: 220px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .lvh-candle-meta {
            margin-top: 10px;
            text-align: center;
            min-height: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .lvh-candle-status {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 11px;
            color: #1a2b3c;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
     
        @media (min-width: 768px) {
            .lvh-marquee-item {
                width: 180px;
            }
            .lvh-candle-img-wrapper {
                height: 220px;
            }
            .lvh-candle-img {
                max-height: 220px;
            }
            .lvh-marquee-track {
                gap: 52px;
            }
        }
        
        @media (min-width: 1024px) {
            .lvh-marquee-item {
                width: 200px;
            }
            .lvh-candle-img-wrapper {
                height: 240px;
            }
            .lvh-candle-img {
                max-height: 240px;
            }
            .lvh-marquee-track {
                gap: 56px;
            }
        }
        
        @keyframes scrollMarquee {
            0% {
                transform: translate3d(0, 0, 0);
            }
            100% {
                transform: translate3d(-50%, 0, 0);
            }
        }
        
        @keyframes lvh-fadeUp {
            0% {
                opacity: 0;
                transform: translateY(18px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 640px) {
            .laguna-vibe-marquee-hero {
                min-height: 100vh;
            }
            .lvh-content-wrapper {
                padding-top: 3.5rem;
            }
            .lvh-main-title {
                font-size: 48px;
                line-height: 1.05;
            }
            .lvh-tagline-text {
                font-size: 15px;
                margin-bottom: 1.2rem;
            }
            .lvh-btn-modern {
                min-width: 165px;
                height: 42px;
                font-size: 11px;
            }
            .lvh-strip-title {
                font-size: 15px;
                letter-spacing: 2px;
            }
            .lvh-strip-subtitle {
                font-size: 12px;
            }
            .lvh-marquee-section {
                padding: 30px 0 40px 0;
            }
        }
        
        .lvh-marquee-container:hover .lvh-marquee-track {
            animation-play-state: paused;
        }
    </style>
</head>
<body>
    <section class="laguna-vibe-marquee-hero" aria-label="Laguna Vibe Hero with Scrolling Candles">
        
        <div class="lvh-video-layer">
            <video id="lvhHeroVideo" autoplay muted loop playsinline preload="auto">
                <source src="<?php echo base_url('/public/videos/Home-Video-comp.mp4'); ?>" type="video/mp4">
            </video>
        </div>
        <div class="lvh-gradient-overlay"></div>
        
        <!-- Main Text & Buttons Content -->
        <div class="lvh-content-wrapper">
            <div class="lvh-eyebrow-text">Handcrafted candles in the Spirit of Laguna Beach</div>
            <h1 class="lvh-main-title">Laguna Vibe</h1>
            <p class="lvh-tagline-text">Where the ocean meets the flame.</p>
            
            <div class="lvh-button-group">
                <a href="<?php echo $base; ?>/builder" class="lvh-btn-modern lvh-btn-primary">Create Your Candle</a>
                <a href="<?php echo $base; ?>/shop" class="lvh-btn-modern lvh-btn-outline">Most Popular Candles</a>
                <a href="<?php echo $base; ?>/accessories" class="lvh-btn-modern lvh-btn-outline">Accessories</a>
            </div>
        </div>
        
        <!-- Bottom Gradient -->
        <div class="lvh-bottom-gradient"></div>
     
    </section>
       
        <!-- INFINITE SCROLLING MARQUEE (HOME PAGE CANDLES STRIP CONCEPT) -->
        <div class="lvh-marquee-section">
            <div class="lvh-strip-header-container">
                <h2 class="lvh-strip-title">PERSONALIZED ENGRAVING AVAILABLE</h2>
                <p class="lvh-strip-subtitle">initials, names, dates, or a special message.</p>
            </div>

            <div class="lvh-marquee-container">
                <div class="lvh-marquee-track" id="marqueeTrack">
                    <?php
                    $heroCandlesSpec = [
                        ['file' => '00-11.png', 'code' => '00-11', 'coming_soon' => false],
                        ['file' => '00-12.png', 'code' => '00-12', 'coming_soon' => false],
                        ['file' => '00-14.png', 'code' => '00-14', 'coming_soon' => false],
                        ['file' => '00-06.png', 'code' => '00-06', 'coming_soon' => false],
                        ['file' => '00-02.png', 'code' => '00-02', 'coming_soon' => true],
                        ['file' => '00-09.png', 'code' => '00-09', 'coming_soon' => false],
                        ['file' => '00-13.png', 'code' => '00-13', 'coming_soon' => false],
                        ['file' => '00-07.png', 'code' => '00-07', 'coming_soon' => false],
                        ['file' => '00-03.png', 'code' => '00-03', 'coming_soon' => false],
                        ['file' => '00-15.png', 'code' => '00-15', 'coming_soon' => true],
                    ];

                    for ($set = 0; $set < 2; $set++):
                        foreach ($heroCandlesSpec as $candle):
                            $imgRel = 'public/uploads/hero-products/' . $candle['file'];
                            $imgUrl = base_url('/' . rawurlencode($imgRel));
                            $imgUrl = str_replace('%2F', '/', $imgUrl);
                    ?>
                        <div class="lvh-marquee-item">
                            <div class="lvh-candle-img-wrapper">
                                <img class="lvh-candle-img" src="<?= htmlspecialchars($imgUrl); ?>" alt="Candle <?= htmlspecialchars($candle['code']); ?>" loading="lazy">
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endfor; 
                    ?>
                </div>
            </div>
        </div>
        
    <script>
        (function() {
            // Video pointer events disabled & playback resilience
            const videoElem = document.getElementById('lvhHeroVideo');
            if (videoElem) {
                videoElem.style.pointerEvents = 'none';
                videoElem.muted = true;
                videoElem.defaultMuted = true;
                videoElem.playsInline = true;

                function playVideoSafe() {
                    const promise = videoElem.play();
                    if (promise !== undefined) {
                        promise.catch(() => {
                            const handleGesture = () => {
                                videoElem.play();
                                document.removeEventListener('click', handleGesture);
                                document.removeEventListener('touchstart', handleGesture);
                                document.removeEventListener('scroll', handleGesture);
                            };
                            document.addEventListener('click', handleGesture, { passive: true, once: true });
                            document.addEventListener('touchstart', handleGesture, { passive: true, once: true });
                            document.addEventListener('scroll', handleGesture, { passive: true, once: true });
                        });
                    }
                }

                playVideoSafe();

                videoElem.addEventListener('pause', () => {
                    if (!videoElem.ended && document.visibilityState === 'visible') {
                        playVideoSafe();
                    }
                });

                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        playVideoSafe();
                    }
                });
            }
          
        })();
    </script>
</body>
</html>