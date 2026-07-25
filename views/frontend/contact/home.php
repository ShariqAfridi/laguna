<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Visit the Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400&family=Montserrat:wght@300;400&display=swap" rel="stylesheet">
    
    <style>
        /* No flex on body */
        body {
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Specific 200px Contact Header Section */
        .studio-contact-hero {
            width: 100%;
            height: 270px; /* Exact height requested */
            display: grid;
            place-items: center;
            /* Radial gradient matching the visual glow */
            background: linear-gradient(to bottom, #F7FCFD 0%, #DEEFF5 100%);            text-align: center;
            overflow: hidden;
            border-bottom: 1px solid #f0f4f7;
        }

        .studio-contact-inner {
            padding: 0 20px;
        }

        /* GET IN TOUCH - Tiny, spaced out caps */
        .studio-contact-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            letter-spacing: 0.5em;
            text-transform: uppercase;
            color: #7a8a99;
            margin-bottom: 12px;
            display: block;
        }

        /* Visit the studio. - Serif Headline */
        .studio-contact-headline {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 400;
            color: #1a2a36;
            margin: 0;
            line-height: 1.2;
        }

        /* Adjustments for mobile screens */
        @media (max-width: 480px) {
            .studio-contact-hero {
                height: 300px;
            }
            .studio-contact-label {
                letter-spacing: 0.3em;
                font-size: 9px;
            }
        }
    </style>
</head>
<body>

    <section class="studio-contact-hero">
        <div class="studio-contact-inner">
            <span class="studio-contact-label">Get in Touch</span>
            <h1 class="studio-contact-headline">Visit the studio.</h1>
        </div>
    </section>

</body>
</html>