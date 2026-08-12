<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600&display=swap" rel="stylesheet">
    <style>
        /* The container for the marquee */
        .lvb-marquee-container {
            background-color: #001a2c; /* Deep navy */
            overflow: hidden;
            white-space: nowrap;
            padding: 14px 0;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
        }

        /* The moving track */
        .lvb-marquee-track {
            display: flex;
            width: max-content;
            animation: lvb-scroll 25s linear infinite;
        }

        /* Group wrapper for 100% seamless looping */
        .lvb-marquee-group {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        /* The text styling */
        .lvb-marquee-text {
            font-family: 'Montserrat', sans-serif;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        /* The dot separator */
        .lvb-marquee-dot {
            height: 4px;
            width: 4px;
            background-color: #5a6d7a;
            border-radius: 50%;
            margin: 0 45px;
            display: inline-block;
            vertical-align: middle;
        }

        /* Infinite Scroll Animation: Exactly -50% for seamless loop */
        @keyframes lvb-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Stop animation on hover for readability */
        .lvb-marquee-container:hover .lvb-marquee-track {
            animation-play-state: paused;
        }
    </style>
</head>
<body>

    <div class="lvb-marquee-container">
        <div class="lvb-marquee-track">
            <!-- Group 1 -->
            <div class="lvb-marquee-group">
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
            </div>
            <!-- Group 2 (Identical duplicate for 100% gapless seamless loop) -->
            <div class="lvb-marquee-group">
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
                <span class="lvb-marquee-text">Free shipping on orders over $75</span><span class="lvb-marquee-dot"></span>
            </div>
        </div>
    </div>

</body>
</html>
