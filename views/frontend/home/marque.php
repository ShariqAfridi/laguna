<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600&display=swap" rel="stylesheet">
    <style>
        /* The container for the marquee */
        .lvb-marquee-container {
            background-color: #001a2c; /* Exact deep navy from image */
            overflow: hidden;
            white-space: nowrap;
            padding: 18px 0;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* The moving track */
        .lvb-marquee-track {
            display: flex;
            animation: lvb-scroll 30s linear infinite;
        }

        /* The text styling */
        .lvb-marquee-item {
            font-family: 'Montserrat', sans-serif;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 3px; /* Wide tracking from the image */
            display: flex;
            align-items: center;
            padding-right: 60px; /* Space between the repeating phrases */
        }

        /* The dot separator seen in the image */
        .lvb-marquee-dot {
            height: 4px;
            width: 4px;
            background-color: #5a6d7a;
            border-radius: 50%;
            margin: 0 40px;
            display: inline-block;
        }

        /* Infinite Scroll Animation */
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
            <div class="lvb-marquee-item">
                Free shipping on orders over $75
            </div>
            
            <div class="lvb-marquee-item">
                <span class="lvb-marquee-dot" style="margin-left: 0; margin-right: 40px;"></span>
                Free shipping on orders over $75
            </div>

            <div class="lvb-marquee-item">
                <span class="lvb-marquee-dot" style="margin-left: 0; margin-right: 40px;"></span>
                Free shipping on orders over $75
            </div>
            
            <div class="lvb-marquee-item">
                <span class="lvb-marquee-dot" style="margin-left: 0; margin-right: 40px;"></span>
                Free shipping on orders over $75
            </div>
        </div>
    </div>

</body>
</html>