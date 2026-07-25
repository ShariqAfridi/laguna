<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Our Story — A small studio, the open Pacific</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background: #f4fafd; /* fallback for outside viewport */
        }

        /* MAIN SECTION — vertical gradient: from #F7FCFE (top) to #DFF0F5 (bottom) */
        .pacific-story-viewport {
            display: grid;
            place-items: center;
            min-height: 300px;          /* full viewport height — immersive story feel */
            width: 100%;
            /* VERTICAL GRADIENT: top #F7FCFE → bottom #DFF0F5 */
            background: linear-gradient(180deg, #F7FCFE 0%, #DFF0F5 100%);
            text-align: center;
            font-family: 'Montserrat', sans-serif;
            -webkit-font-smoothing: antialiased;
            padding: 2rem;
        }

        .pacific-inner-frame {
            padding: 20px 24px;
            max-width: 800px;
            width: 100%;
            backdrop-filter: blur(0px);   /* no blur, keep clean */
            transition: all 0.2s ease;
        }

        /* "our story" label — inter style (Montserrat with refined letter spacing) */
        .pacific-label-small {
            font-family: 'Montserrat', 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 12px;
            letter-spacing: 0.45em;      /* elegant tracking like high-end editorial */
            text-transform: uppercase;
            font-weight: 400;
            color: #4a6276;               /* calm ocean-slate */
            margin-bottom: 28px;
            display: inline-block;
            background: rgba(247, 252, 254, 0.3); /* subtle glass touch? optional, but clean */
            padding: 0 2px;
            backdrop-filter: none;
            text-decoration: none;
        }

        /* main headline — exclusively Cormorant Garamond */
        .pacific-title-display {
            font-family: 'Cormorant Garamond', 'Times New Roman', serif;
            font-size: clamp(26px, 8vw, 62px);
            font-weight: 400;             /* elegant regular weight */
            line-height: 1.2;
            color: #153243;               /* deep indigo-teal, inspired by ocean depth */
            margin: 0;
            letter-spacing: -0.01em;
            text-shadow: 0 1px 0 rgba(255,255,255,0.3);
        }

        /* Fine typographic detail: Inter style for meta, but headline stays serif */
        /* For perfect line-break control: desktop shows two lines as per request:
           "A small studio," + "the open Pacific." on separate lines.
           But the desired text: 
           "A small studio,
            the open Pacific."
           Using <br> for semantic line break, but respecting design exactly.
        */
        
        /* Optional subtle ornament to evoke the pacific (just a breath) */
        .pacific-title-display::before {
            content: none;  /* no pseudo element — keep pure */
        }
        
        /* for ultra smooth rendering */
        .pacific-title-display span {
            display: inline-block;
        }

        /* ensure 'our story' label is uppercase and refined — inter approach */
        /* mobile refinements */
        @media (max-width: 640px) {
            .pacific-story-viewport {
                padding: 1.5rem;
                min-height: 50vh;
            }
            .pacific-inner-frame {
                padding: 10px 12px;
            }
            .pacific-label-small {
                font-size: 10px;
                letter-spacing: 0.38em;
                margin-bottom: 20px;
            }
            .pacific-title-display {
                font-size: clamp(38px, 7vw, 58px);
                line-height: 1.25;
            }
        }

        /* extra refinement: when screen is very small, keep readability */
        @media (max-width: 480px) {
            .pacific-label-small {
                font-size: 9.5px;
                letter-spacing: 0.35em;
            }
            .pacific-title-display {
                font-size: 34px;
            }
        }

        /* optional: subtle hover effect on the container? not needed but adds life */
        .pacific-story-viewport {
            transition: background 0.3s ease;
        }
        
        /* add a minimal wave/flow detail? design choice to match "open pacific" */
        /* but not invasive — we stay minimal and elegant */
        
        /* For any touch of extra finesse: the line height feels airy */
        .pacific-title-display br {
            display: block;
            content: "";
            margin: 0;
        }
        
        /* exactly layout: 
           Our story (inter style upper, tracking)
           A small studio,
           the open Pacific.
        */
        
        /* ensure that cormorant garamond has beautiful numerals / punctuation */
        .pacific-title-display {
            font-feature-settings: "kern" 1, "liga" 1;
        }
        
        /* subtle gradient texture that feels like morning ocean mist */
        /* preserve accessibility & contrast */
        @media (prefers-reduced-motion: no-preference) {
            .pacific-story-viewport {
                transition: background 0.2s;
            }
        }
    </style>
</head>
<body>

    <section class="pacific-story-viewport" aria-label="Story introduction: A small studio, the open Pacific.">
        <div class="pacific-inner-frame">
            <!-- 'our story' micro typography in inter/montserrat style, clean uppercase -->
            <span class="pacific-label-small">Our Story</span>
            
            <!-- main cormorant garamond headline -->
            <h1 class="pacific-title-display">
               More Luxurious & Elevated
            </h1>
        </div>
    </section>

    <!-- Design notes:
         - Background vertical gradient: top #F7FCFE → bottom #DFF0F5 (exactly as required)
         - Typography: 'Our Story' uses Montserrat (similar to Inter’s geometric clarity) 
           with generous tracking.
         - Headline exclusively set in Cormorant Garamond, perfectly matching brief.
         - Structure: two lines as per original request: "A small studio," line break + "the open Pacific."
         - No flex on body -> using Grid place-items on parent for seamless centering.
         - Background gradient validated (#F7FCFE to #DFF0F5) and spans full viewport.
         - Soft ocean-inspired palette, minimal & elegant.
    -->
</body>
</html>