<section class="lv-pack-section">
    <div class="lv-pack-container">
        <span class="lv-eyebrow">Packaging</span>
        <h2 class="lv-h2">Considered to the last fold.</h2>
        <p class="lv-intro">Two keepsake presentations — both designed in-house, finished by hand.</p>

        <div class="lv-pack-grid">
            <div class="lv-pack-card">
                <div class="lv-pack-img-placeholder">
                    <img src="<?= function_exists('base_url') ? base_url('/public/uploads/boxes/box_black_cubic_showcase.webp') : 'public/uploads/boxes/box_black_cubic_showcase.webp' ?>" alt="Black Cubic Keepsake Box" class="lv-pack-img" onerror="this.src='<?= function_exists('base_url') ? base_url('/public/uploads/boxes/box_black_cubic_showcase.jpg') : 'public/uploads/boxes/box_black_cubic_showcase.jpg' ?>'">
                </div>
                <div class="lv-pack-content">
                    <h4>Black Cubic Box</h4>
                    <p>Square keepsake presentation box with a lift-off lid in rich matte black.</p>
                </div>
            </div>

            <div class="lv-pack-card">
                <div class="lv-pack-img-placeholder">
                    <img src="<?= function_exists('base_url') ? base_url('/public/uploads/boxes/box_white_cubic_showcase.webp') : 'public/uploads/boxes/box_white_cubic_showcase.webp' ?>" alt="White Cubic Keepsake Box" class="lv-pack-img" onerror="this.src='<?= function_exists('base_url') ? base_url('/public/uploads/boxes/box_white_cubic_showcase.jpg') : 'public/uploads/boxes/box_white_cubic_showcase.jpg' ?>'">
                </div>
                <div class="lv-pack-content">
                    <h4>White Cubic Box</h4>
                    <p>Square keepsake presentation box with a lift-off lid in crisp satin white.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Scoped styles - no global leaks */
.lv-pack-section {
    all: initial;
    display: block;
    padding: 80px 100px;
    /* Gradient from #F7FBFC to #F5F9FB */
    background: linear-gradient(180deg, #F7FBFC 0%, #F5F9FB 100%);
    text-align: center;
    box-sizing: border-box;
}

.lv-pack-section *,
.lv-pack-section *::before,
.lv-pack-section *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.lv-pack-section .lv-pack-container {
    max-width: 900px;
    margin: 0 auto;
}

/* Packaging - Inter 400 normal */
.lv-pack-section .lv-eyebrow {
    display: block;
    font-family: 'Inter', 'Helvetica Neue', sans-serif;
    font-weight: 400;
    font-size: 11px;
    letter-spacing: 0.5em;
    text-transform: uppercase;
    color: #999;
    margin-bottom: 15px;
}

/* Considered to the last fold - Cormorant Garamond 400 normal */
.lv-pack-section .lv-h2 {
    font-family: 'Cormorant Garamond', 'Times New Roman', serif;
    font-weight: 400;
    font-size: clamp(32px, 4vw, 44px);
    color: #0f2a2f;
    margin-bottom: 20px;
}

/* Two keepsake presentations... - Inter 400 normal */
.lv-pack-section .lv-intro {
    font-family: 'Inter', 'Helvetica Neue', sans-serif;
    font-weight: 400;
    font-size: 1rem;
    color: #666;
    max-width: 550px;
    margin: 0 auto;
    line-height: 1.6;
}

.lv-pack-section .lv-pack-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    margin-top: 48px;
    justify-items: center;
    align-items: start;
    gap: 30px;
}

/* Reduced width cards */
.lv-pack-section .lv-pack-card {
    width: 100%;
    max-width: 380px;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    text-align: left;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(0, 75, 102, 0.06);
}

.lv-pack-section .lv-pack-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px rgba(0, 75, 102, 0.12);
}

.lv-pack-section .lv-pack-img-placeholder {
    width: 100%;
    aspect-ratio: 1.35 / 1;
    overflow: hidden;
    background-color: #e5cdb4;
}

.lv-pack-section .lv-pack-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    transition: transform 0.4s ease;
}

.lv-pack-section .lv-pack-card:hover .lv-pack-img {
    transform: scale(1.03);
}

.lv-pack-section .lv-pack-content {
    padding: 20px 24px 24px;
}

.lv-pack-section .lv-pack-content h4 {
    font-family: 'Cormorant Garamond', 'Times New Roman', serif;
    font-weight: 600;
    font-size: 1.5rem;
    color: #0f2a2f;
    margin-bottom: 8px;
}

.lv-pack-section .lv-pack-content p {
    font-family: 'Inter', 'Helvetica Neue', sans-serif;
    font-weight: 400;
    font-size: 0.85rem;
    line-height: 1.5;
    color: #6b7a7f;
    margin: 0;
}

/* Desktop */
@media (min-width: 1024px) {
    .lv-pack-section {
        padding: 80px 120px;
    }
    
    .lv-pack-section .lv-pack-grid {
        gap: 30px;
    }
    
    .lv-pack-section .lv-pack-card {
        max-width: 380px;
    }
}

/* Tablet */
@media (max-width: 1024px) and (min-width: 769px) {
    .lv-pack-section {
        padding: 70px 40px;
    }
    
    .lv-pack-section .lv-pack-grid {
        gap: 24px;
    }
    
    .lv-pack-section .lv-pack-card {
        max-width: 340px;
    }
    
    .lv-pack-section .lv-pack-content {
        padding: 16px 20px 18px;
    }
    
    .lv-pack-section .lv-pack-content h4 {
        font-size: 1.4rem;
    }
}

/* Mobile */
@media (max-width: 768px) {
    .lv-pack-section {
        padding: 60px 24px;
    }
    
    .lv-pack-section .lv-pack-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .lv-pack-section .lv-pack-card {
        max-width: 360px;
    }
    
    .lv-pack-section .lv-pack-img-placeholder {
        aspect-ratio: 1.35 / 1;
    }
    
    .lv-pack-section .lv-pack-content {
        padding: 16px 20px 18px;
    }
    
    .lv-pack-section .lv-pack-content h4 {
        font-size: 1.4rem;
    }
    
    .lv-pack-section .lv-pack-content p {
        font-size: 0.8rem;
    }
    
    .lv-pack-section .lv-h2 {
        font-size: 28px;
    }
    
    .lv-pack-section .lv-intro {
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .lv-pack-section {
        padding: 50px 20px;
    }
    
    .lv-pack-section .lv-pack-card {
        max-width: 320px;
    }
    
    .lv-pack-section .lv-pack-img-placeholder {
        aspect-ratio: 1.3 / 1;
    }
    
    .lv-pack-section .lv-pack-content {
        padding: 14px 18px 16px;
    }
    
    .lv-pack-section .lv-pack-content h4 {
        font-size: 1.3rem;
    }
}
</style>

<!-- Add Google Fonts if not already included -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400&family=Cormorant+Garamond:wght@400;600&display=swap" rel="stylesheet">