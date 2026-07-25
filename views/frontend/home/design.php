<section class="dyc-container">
  <header class="dyc-header">
    <span class="dyc-overline">BEGIN HERE</span>
    <h1 class="dyc-title">Design Your Candle</h1>
    <p class="dyc-subtitle">
      Three considered ways to begin — each customisable, each hand-poured to order.
    </p>
  </header>

  <div class="dyc-grid">
    <div class="dyc-card dyc-card-blue">
      <span class="dyc-card-number">01</span>
      <h2 class="dyc-card-title">Everyday Candles</h2>
      <p class="dyc-card-text">Hand-poured rituals for daily moments. A single signature scent in your chosen vessel.</p>
      <a href="<?php echo $base; ?>/builder" class="dyc-link">BEGIN →</a>
    </div>

    <div class="dyc-card dyc-card-pink">
      <span class="dyc-card-number">02</span>
      <h2 class="dyc-card-title">Gift Sets</h2>
      <p class="dyc-card-text">Thoughtfully composed pairings, presented in a keepsake box ready to be given.</p>
      <a href="<?php echo $base; ?>/builder" class="dyc-link">BEGIN →</a>
    </div>

    <div class="dyc-card dyc-card-gray">
      <span class="dyc-card-number">03</span>
      <h2 class="dyc-card-title">Collection Candles</h2>
      <p class="dyc-card-text">Limited compositions inspired by Laguna Beach — vessel, fragrance and box considered as one.</p>
      <a href="<?php echo $base; ?>/builder" class="dyc-link">BEGIN →</a>
    </div>
  </div>
</section>

<style>
/* Scoped styles - no global leaks */
.dyc-container {
  all: initial;
  display: block;
  padding: clamp(40px, 6vw, 60px) clamp(16px, 5vw, 40px);
  max-width: 1300px;
  margin: 0 auto;
  text-align: center;
  background: linear-gradient(180deg, #F7FBFC 0%, #F4F8FA 100%);
  color: #0f1c2d;
  box-sizing: border-box;
}

.dyc-container *,
.dyc-container *::before,
.dyc-container *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

/* Overline */
.dyc-container .dyc-overline {
  display: block;
  font-family: 'Inter', 'Helvetica Neue', sans-serif;
  font-size: 11px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  margin-bottom: 16px;
  color: #7a828a;
}

/* Title */
.dyc-container .dyc-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(2rem, 5vw, 3.5rem);
  margin-bottom: 8px;
  letter-spacing: -0.02em;
}

/* Subtitle */
.dyc-container .dyc-subtitle {
  font-family: 'Inter', sans-serif;
  font-size: clamp(0.9rem, 2.5vw, 1rem);
  color: #5a636c;
  max-width: 600px;
  margin: 0 auto 30px;
  line-height: 1.5;
}

/* Grid */
.dyc-container .dyc-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

/* Tablet */
@media (min-width: 640px) {
  .dyc-container .dyc-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .dyc-container .dyc-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Card */
.dyc-container .dyc-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: clamp(18px, 4vw, 25px);
  text-align: left;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  overflow: hidden;
}

/* Hover only on desktop devices */
@media (hover: hover) {
  .dyc-container .dyc-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e0;
  }
}

/* Gradients */
.dyc-container .dyc-card-blue {
  background: radial-gradient(circle at top right, rgba(215, 227, 235, 0.5) 0%, #ffffff 60%);
}

.dyc-container .dyc-card-pink {
  background: radial-gradient(circle at top right, rgba(245, 230, 225, 0.5) 0%, #ffffff 60%);
}

.dyc-container .dyc-card-gray {
  background: radial-gradient(circle at top right, rgba(220, 220, 225, 0.5) 0%, #ffffff 60%);
}

/* Card number */
.dyc-container .dyc-card-number {
  display: block;
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  color: #94a3b8;
  margin-bottom: 20px;
  letter-spacing: 0.2em;
}

/* Card title */
.dyc-container .dyc-card-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(1.3rem, 3vw, 1.9rem);
  margin-bottom: 12px;
  color: #1e293b;
}

/* Card text */
.dyc-container .dyc-card-text {
  font-family: 'Inter', sans-serif;
  font-size: clamp(0.85rem, 2.5vw, 1rem);
  color: #475569;
  line-height: 1.6;
  margin-bottom: 12px;
}

/* Link */
.dyc-container .dyc-link {
  display: inline-block;
  text-decoration: none;
  color: #0f1c2d;
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  letter-spacing: 0.25em;
  border-bottom: 1px solid transparent;
  transition: border-color 0.3s;
}

.dyc-container .dyc-link:hover {
  border-bottom-color: #0f1c2d;
}
</style>

<!-- Add Google Fonts if not already included in parent page -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400&family=Cormorant+Garamond:wght@400&display=swap" rel="stylesheet">