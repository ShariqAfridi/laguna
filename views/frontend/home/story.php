<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Section</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Scoped styles - no global leaks */
        .studio-hero-section {
            all: initial;
            display: block;
            background-color: #002C4C;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 80px 20px;
            min-height: 300px;
            color: #f9fbfb;
            box-sizing: border-box;
        }

        .studio-hero-section *,
        .studio-hero-section *::before,
        .studio-hero-section *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Top Small Label - Inter 400 normal */
        .studio-hero-section .studio-label-top {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 6px;
            margin-bottom: 40px;
            opacity: 0.85;
            color: #f9fbfb;
        }

        /* Main Headline - Cormorant Garamond 400 normal */
        .studio-hero-section .studio-main-heading {
            font-family: 'Cormorant Garamond', 'Times New Roman', serif;
            font-weight: 400;
            font-size: 42px;
            line-height: 1.25;
            max-width: 800px;
            margin: 0 auto 45px auto;
            letter-spacing: -0.5px;
            color: #f9fbfb;
        }

        /* Bottom Link - Inter 400 normal */
        .studio-hero-section .studio-story-link {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-weight: 400;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 4px;
            text-decoration: none;
            color: #f9fbfb;
            display: inline-flex;
            align-items: center;
            transition: opacity 0.3s ease;
            border-bottom: 1px solid transparent;
            padding-bottom: 4px;
        }

        .studio-hero-section .studio-story-link:hover {
            opacity: 0.7;
            border-bottom-color: rgba(255, 255, 255, 0.5);
        }

        .studio-hero-section .studio-arrow {
            margin-left: 10px;
            font-size: 16px;
            vertical-align: middle;
        }

        .empty {
            background: #ffffff;
            height: 100px;
            width: 100%;
            display: block;
        }

        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            .studio-hero-section {
                padding: 60px 20px;
                min-height: 250px;
            }
            
            .studio-hero-section .studio-main-heading {
                font-size: 32px;
                max-width: 90%;
                margin-bottom: 35px;
            }
            
            .studio-hero-section .studio-label-top {
                font-size: 11px;
                letter-spacing: 5px;
                margin-bottom: 30px;
            }
            
            .studio-hero-section .studio-story-link {
                font-size: 11px;
                letter-spacing: 3px;
            }
        }

        @media (max-width: 480px) {
            .studio-hero-section {
                padding: 50px 16px;
                min-height: 220px;
            }
            
            .studio-hero-section .studio-main-heading {
                font-size: 28px;
                line-height: 1.3;
            }
            
            .studio-hero-section .studio-label-top {
                font-size: 10px;
                letter-spacing: 4px;
                margin-bottom: 25px;
            }
        }
    </style>
</head>
<body style="margin:0;">

    <section class="studio-hero-section">
        <span class="studio-label-top">The Studio</span>
        
        <h1 class="studio-main-heading">
            Slow-poured, in small batches,<br>
            two blocks from the Pacific.
        </h1>

<?php
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>
        <a href="<?php echo $base; ?>/about" class="studio-story-link">
            Our Story <span class="studio-arrow">→</span>
        </a>
    </section>
    <section class="empty">

    </section>

</body>
</html>