<section class="lv-pack-section">
    <div class="lv-pack-container">
        <span class="lv-eyebrow">Packaging</span>
        <h2 class="lv-h2">Considered to the last fold.</h2>
        <p class="lv-intro">Two keepsake presentations — both designed in-house, finished by hand.</p>

        <div class="lv-pack-grid">
            <div class="lv-pack-card">
                <div class="lv-pack-img-placeholder">
                    <img src="img/box-square.jpg" alt="Cubic Box Packaging" class="lv-pack-img">
                </div>
                <div class="lv-pack-content">
                    <h4>Cubic Box</h4>
                    <p>Square keepsake presentation box with a lift-off lid.</p>
                </div>
            </div>

            <div class="lv-pack-card">
                <div class="lv-pack-img-placeholder">
                    <img src="img/box-tubular.jpg" alt="Tubular Box Packaging" class="lv-pack-img">
                </div>
                <div class="lv-pack-content">
                    <h4>Tubular Box</h4>
                    <p>Sculptural cylindrical keepsake box with a concealed lid.</p>
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
}

.lv-pack-section .lv-pack-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
}

.lv-pack-section .lv-pack-img-placeholder {
    width: 100%;
    aspect-ratio: 1.3 / 1;
    overflow: hidden;
    background-color: #f5f5f5;
}

.lv-pack-section .lv-pack-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.lv-pack-section .lv-pack-content {
    padding: 18px 22px 22px;
}

.lv-pack-section .lv-pack-content h4 {
    font-family: 'Cormorant Garamond', 'Times New Roman', serif;
    font-weight: 400;
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
        gap: 3px;
    }
    
    .lv-pack-section .lv-pack-card {
        max-width: 360px;
    }
}

/* Tablet */
@media (max-width: 1024px) and (min-width: 769px) {
    .lv-pack-section {
        padding: 70px 60px;
    }
    
    .lv-pack-section .lv-pack-grid {
        gap: 3px;
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
        gap: 16px;
    }
    
    .lv-pack-section .lv-pack-card {
        max-width: 340px;
    }
    
    .lv-pack-section .lv-pack-img-placeholder {
        aspect-ratio: 1.3 / 1;
    }
    
    .lv-pack-section .lv-pack-content {
        padding: 14px 18px 16px;
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
        max-width: 300px;
    }
    
    .lv-pack-section .lv-pack-img-placeholder {
        aspect-ratio: 1.2 / 1;
    }
    
    .lv-pack-section .lv-pack-content {
        padding: 12px 16px 14px;
    }
    
    .lv-pack-section .lv-pack-content h4 {
        font-size: 1.3rem;
    }
}
</style>

<!-- Add Google Fonts if not already included -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400&family=Cormorant+Garamond:wght@400&display=swap" rel="stylesheet">