<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes from the studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400&family=Montserrat:wght@300;400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        .nfts-studio-section {
            margin: 0;
            padding: 10px;
            box-sizing: border-box;
            /* Radial gradient: White in middle to light bluish-grey at edges */
            background: linear-gradient(to bottom, #F7FCFD 0%, #DEEFF5 100%);            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            text-align: center;
            color: #1a2a36;
            font-family: 'Montserrat', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .nfts-studio-section * {
            box-sizing: border-box;
        }

        .nfts-content-box {
            max-width: 600px;
            width: 100%;
        }

        /* JOURNAL - Smaller and more spaced out */
        .nfts-meta-header {
            font-size: 11px;
            letter-spacing: 0.5em;
            text-transform: uppercase;
            font-weight: 300;
            margin-bottom: 25px;
            color: #556677;
            display: block;
        }

        /* Headline - Large Serif */
        .nfts-title-display {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(60px, 7vw, 52px); 
            font-weight: 400;
            line-height: 1.1;
            margin-bottom: 35px;
            color: #1a2a36;
            display: block;
        }

        /* Body - Thinner and lighter */
        .nfts-copy-text {
            font-family: inter;
            font-size: clamp(16px, 1.8vw, 16px);
            /*line-height: 1.7;*/
            font-weight: 300;
            max-width: 580px;
            margin: 0 auto 55px auto;
            color: #556697;
            display: block;
        }

        /* COMING SOON - Delicate small caps */
        .nfts-status-footer {
            font-size: 11px;
            letter-spacing: 0.4em;
            text-transform: uppercase;
            font-weight: 400;
            color: #556677;
            display: block;
        }

        /* Mobile specific spacing */
        @media (max-width: 480px) {
            .nfts-meta-header { letter-spacing: 0.3em; font-size: 10px; }
            .nfts-title-display { margin-bottom: 20px; }
            .nfts-copy-text { margin-bottom: 35px; }
            .nfts-status-footer { letter-spacing: 0.3em; }
        }
        .empty{
            height:150px;
            background: #ffffff;
        }
    </style>
</head>
<body>

    <section class="nfts-studio-section">
        <div class="nfts-content-box">
            <span class="nfts-meta-header">Journal</span>
            
            <h1 class="nfts-title-display">Notes from the studio.</h1>
            
            <p class="nfts-copy-text">
                We're preparing our first stories &mdash; fragrance development, <br>
                the people behind the pours, and the coast that shapes it <br>
                all.
            </p>
            
            <span class="nfts-status-footer">Coming Soon</span>
        </div>
    </section>

    <section class="empty">
    </section>

</body>
</html>