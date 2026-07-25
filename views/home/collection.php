<section class="lvc-collection">
  <div class="lvc-header">
    <div class="lvc-title-group">
      <span class="lvc-overline">THE COLLECTION</span>
      <h2 class="lvc-main-title">Ready to be lit.</h2>
    </div>
    <a href="/shop" class="lvc-shop-all">SHOP ALL →</a>
  </div>

  <div class="lvc-grid">
    <div class="lvc-card">
      <div class="lvc-img-container">
        <img src="img/lemonglass.png" alt="Wild Lemongrass candle">
      </div>
      <div class="lvc-info">
        <div class="lvc-text">
          <span class="lvc-p-name">Wild Lemongrass</span>
          <span class="lvc-p-desc">WILD LEMONGRASS</span>
        </div>
        <span class="lvc-price">$29</span>
      </div>
    </div>

    <div class="lvc-card">
      <div class="lvc-img-container">
        <img src="img/woods.png" alt="Mahogany Woods candle">
      </div>
      <div class="lvc-info">
        <div class="lvc-text">
          <span class="lvc-p-name">Mahogany Woods</span>
          <span class="lvc-p-desc">MAHOGANY WOODS</span>
        </div>
        <span class="lvc-price">$29</span>
      </div>
    </div>

    <div class="lvc-card">
      <div class="lvc-img-container">
        <img src="img/musk.png" alt="Amber Musk candle">
      </div>
      <div class="lvc-info">
        <div class="lvc-text">
          <span class="lvc-p-name">Amber Musk</span>
          <span class="lvc-p-desc">AMBER MUSK</span>
        </div>
        <span class="lvc-price">$29</span>
      </div>
    </div>

    <div class="lvc-card">
      <div class="lvc-img-container">
        <img src="img/fragrancefree.png" alt="Fragrance Free candle">
      </div>
      <div class="lvc-info">
        <div class="lvc-text">
          <span class="lvc-p-name">Fragrance Free</span>
          <span class="lvc-p-desc">FRAGRANCE FREE</span>
        </div>
        <span class="lvc-price">$29</span>
      </div>
    </div>

    <div class="lvc-card">
      <div class="lvc-img-container">
        <img src="img/lavender.png" alt="Lavender Fields candle">
      </div>
      <div class="lvc-info">
        <div class="lvc-text">
          <span class="lvc-p-name">Lavender Fields</span>
          <span class="lvc-p-desc">LAVENDER FIELDS</span>
        </div>
        <span class="lvc-price">$29</span>
      </div>
    </div>

    <div class="lvc-card">
      <div class="lvc-img-container">
        <img src="img/lattraction.png" alt="L'Attraction candle">
      </div>
      <div class="lvc-info">
        <div class="lvc-text">
          <span class="lvc-p-name">L'Attraction</span>
          <span class="lvc-p-desc">L'ATTRACTION</span>
        </div>
        <span class="lvc-price">$29</span>
      </div>
    </div>
  </div>
</section>

<style>
/* Scoped styles - no global leaks */
.lvc-collection {
  all: initial;
  display: block;
  width: 100%;
  /* Vertical gradient from #F7FCFD to #DEEFF4 */
  background: linear-gradient(180deg, #F7FCFD 0%, #DEEFF4 100%);
  padding: 80px 20px;
  box-sizing: border-box;
}

.lvc-collection *,
.lvc-collection *::before,
.lvc-collection *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.lvc-collection .lvc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 40px;
  max-width: 94%;
  margin-left: auto;
  margin-right: auto;
  flex-wrap: wrap;
  gap: 20px;
}

/* THE COLLECTION - Inter 400 normal */
.lvc-collection .lvc-overline {
  display: block;
  font-family: 'Inter', 'Helvetica Neue', sans-serif;
  font-weight: 400;
  font-size: 11px;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: #7a8b9a;
  margin-bottom: 8px;
}

/* Ready to be lit - Cormorant Garamond 400 normal */
.lvc-collection .lvc-main-title {
  font-family: 'Cormorant Garamond', 'Times New Roman', serif;
  font-weight: 400;
  font-size: 3rem;
  color: #1a2b3c;
  margin: 0;
}

/* SHOP ALL - Inter 400 normal */
.lvc-collection .lvc-shop-all {
  font-family: 'Inter', 'Helvetica Neue', sans-serif;
  font-weight: 400;
  font-size: 12px;
  letter-spacing: 2px;
  color: #555;
  text-decoration: none;
  border-bottom: 1px solid #ccc;
  transition: border-color 0.3s ease;
}

.lvc-collection .lvc-shop-all:hover {
  border-bottom-color: #1a2b3c;
  color: #1a2b3c;
}

.lvc-collection .lvc-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  max-width: 94%;
  margin: 0 auto;
}

.lvc-collection .lvc-card {
  flex: 0 0 calc(16.66% - 10px);
  background: white;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.lvc-collection .lvc-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.lvc-collection .lvc-img-container {
  aspect-ratio: 1 / 1.1;
  background: #f0f4f6;
}

.lvc-collection .lvc-img-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.lvc-collection .lvc-info {
  padding: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.lvc-collection .lvc-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* Product name - Cormorant Garamond 400 normal */
.lvc-collection .lvc-p-name {
  font-family: 'Cormorant Garamond', 'Times New Roman', serif;
  font-weight: 400;
  font-size: 1rem;
  color: #1a2b3c;
}

/* Product description (WILD LEMONGRASS) - Inter 400 normal */
.lvc-collection .lvc-p-desc {
  font-family: 'Inter', 'Helvetica Neue', sans-serif;
  font-weight: 400;
  font-size: 10px;
  letter-spacing: 0.5px;
  color: #94a3b8;
}

.lvc-collection .lvc-price {
  font-family: 'Inter', 'Helvetica Neue', sans-serif;
  font-weight: 400;
  font-size: 13px;
  color: #1a2b3c;
}

/* Tablet */
@media (max-width: 1024px) {
  .lvc-collection .lvc-card {
    flex: 0 0 calc(33.33% - 8px);
  }
  
  .lvc-collection .lvc-main-title {
    font-size: 2.5rem;
  }
}

/* Mobile */
@media (max-width: 600px) {
  .lvc-collection {
    padding: 50px 16px;
  }
  
  .lvc-collection .lvc-main-title {
    font-size: 2rem;
  }
  
  .lvc-collection .lvc-card {
    flex: 0 0 calc(50% - 6px);
  }
  
  .lvc-collection .lvc-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .lvc-collection .lvc-img-container {
    aspect-ratio: 1 / 1;
  }
  
  .lvc-collection .lvc-info {
    padding: 12px;
  }
  
  .lvc-collection .lvc-p-name {
    font-size: 0.9rem;
  }
}
</style>

<!-- Add Google Fonts if not already included -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400&family=Cormorant+Garamond:wght@400&display=swap" rel="stylesheet">