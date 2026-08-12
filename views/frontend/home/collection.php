<?php
/**
 * collection.php — THE COLLECTION Section synced with database products & Quick View Modal (identical to Shop Page)
 */
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}

require_once __DIR__ . '/../../../db.php';

// Helper for image URLs
if (!function_exists('lvc_base_url')) {
    function lvc_base_url($path = '') {
        global $base;
        return $base . '/' . ltrim($path, '/');
    }
}

// Fetch active categories to construct valid vessels map and size details
$categoriesMap = [];
$catQuery = $conn->query("SELECT id, category_name, LOWER(sku) AS sku, wick_type, dimensions_subtitle, burn_time_badge FROM categories WHERE status = 1");
if ($catQuery) {
    while ($row = $catQuery->fetch_assoc()) {
        if (!empty($row['sku'])) {
            $dims = $row['dimensions_subtitle'] ?? '';
            $diam = '';
            if (preg_match('/^(\d+(?:\.\d+)?(?:["\']|inch)?)/i', trim($dims), $m)) {
                $diam = $m[1];
                if (strpos($diam, '"') === false && strpos($diam, "'") === false) {
                    $diam .= '"';
                }
            } elseif (!empty($dims)) {
                $diam = $dims;
            }

            $burn = strtoupper(trim($row['burn_time_badge'] ?? ''));
            $burn = str_replace(['HOURS', 'HOUR'], 'HRS', $burn);
            
            $detailsParts = array_filter([$diam, $burn]);
            $row['size_details'] = !empty($detailsParts) ? implode(' · ', $detailsParts) : '';

            $categoriesMap[$row['sku']] = $row;
        }
    }
}

// 1. Fetch Sizes
$sizes = [];
$sRes = $conn->query("SELECT * FROM sizes");
if ($sRes && $sRes->num_rows > 0) {
    while ($r = $sRes->fetch_assoc()) {
        $sizes[$r['size_id']] = $r;
    }
}

// 2. Fetch Colors
$colors = [];
$cRes = $conn->query("SELECT * FROM colors");
if ($cRes && $cRes->num_rows > 0) {
    while ($r = $cRes->fetch_assoc()) {
        $colors[$r['color_id']] = $r;
    }
}

// 3. Fetch Fragrances
$fragrances = [];
$fragrance_details = [];
$fragrance_descriptions = [];
$fRes = $conn->query("SELECT * FROM fragrances WHERE status = 1 ORDER BY sort_order ASC, fragrance_name ASC");
if ($fRes && $fRes->num_rows > 0) {
    while ($r = $fRes->fetch_assoc()) {
        $fid = (int)$r['fragrance_id'];
        $fragrances[$fid] = $r['fragrance_name'];
        $fragrance_details[$fid] = $r['fragrance_image'] ?? '';
        $fragrance_descriptions[$fid] = $r['fragrance_description'] ?? '';
    }
}

// 4. Fetch Keepsake Boxes
$boxes = [];
$bRes = $conn->query("SELECT * FROM boxes WHERE status = 1 ORDER BY box_name ASC");
if ($bRes && $bRes->num_rows > 0) {
    while ($r = $bRes->fetch_assoc()) {
        $boxes[$r['box_id']] = $r;
    }
}

// 5. Fetch Products & Build Variations
$pRes = $conn->query("SELECT * FROM products WHERE (qty > 0 OR qty IS NULL) ORDER BY product_id ASC");
$products = [];
if ($pRes && $pRes->num_rows > 0) {
    while ($row = $pRes->fetch_assoc()) {
        $row['size_id'] = !empty($row['size_id']) ? json_decode($row['size_id'], true) : [];
        $row['color_id'] = !empty($row['color_id']) ? json_decode($row['color_id'], true) : [];
        
        $fIds = [];
        if (!empty($row['fragrance_id'])) {
            $decoded = json_decode($row['fragrance_id'], true);
            if (is_array($decoded)) {
                $fIds = array_map('intval', $decoded);
            } else {
                $fIds = array_map('intval', explode(',', str_replace(['[', ']', '"'], '', $row['fragrance_id'])));
            }
        }
        $row['fragrance_ids'] = $fIds;
        $row['fragrance_images_map'] = !empty($row['fragrance_images']) ? json_decode($row['fragrance_images'], true) : [];
        $row['size_prices'] = !empty($row['size_prices']) ? json_decode($row['size_prices'], true) : [];

        $dbImg = $row['image'] ?? '';
        if (!empty($dbImg)) {
            if (strpos($dbImg, 'http') === 0) {
                $row['image_url'] = $dbImg;
            } elseif (strpos($dbImg, 'uploads/') !== false) {
                $row['image_url'] = lvc_base_url('public/' . ltrim($dbImg, '/'));
            } else {
                $row['image_url'] = lvc_base_url(ltrim($dbImg, '/'));
            }
        } else {
            $row['image_url'] = 'https://placehold.co/600x600?text=No+Image';
        }

        $products[] = $row;
    }
}

// Group products into Color Variation Cards
$colorVariations = [];
foreach ($products as $p) {
    $cIds = is_array($p['color_id']) ? $p['color_id'] : [$p['color_id']];
    $primaryColorId = !empty($cIds) ? (int)$cIds[0] : 0;
    
    $vesselCode = 'C';
    if (!empty($p['sku']) && in_array(strtoupper($p['sku'][0]), ['C', 'D', 'E'])) {
        $vesselCode = strtoupper($p['sku'][0]);
    }

    $varKey = $vesselCode . '_' . $primaryColorId;

    if (!isset($colorVariations[$varKey])) {
        $colorName = isset($colors[$primaryColorId]) ? $colors[$primaryColorId]['color_name'] : 'Standard';
        $colorHex  = isset($colors[$primaryColorId]) ? $colors[$primaryColorId]['color_hex']  : '#f1f5f9';
        
        $varImg = $p['image_url'];
        if ((empty($p['image']) || strpos($p['image'], 'uploads/products/') === false) && !empty($colors[$primaryColorId]['double_wick_image'])) {
            $dbImg = $colors[$primaryColorId]['double_wick_image'];
            $varImg = lvc_base_url(ltrim($dbImg, '/'));
        }

        $priceVal = 29.00;
        if (is_numeric($p['size_prices'])) {
            $priceVal = (float)$p['size_prices'];
        } elseif (is_array($p['size_prices']) && !empty($p['size_prices'])) {
            $priceVal = (float)reset($p['size_prices']);
        }

        $colorVariations[$varKey] = [
            'variation_id' => 'vessel_' . strtolower($vesselCode) . '_color_' . $primaryColorId,
            'color_id' => $primaryColorId,
            'color_name' => $colorName,
            'color_hex' => $colorHex,
            'vessel_name' => 'Vessel ' . $vesselCode,
            'vessel_code' => strtolower($vesselCode),
            'image_url' => $varImg, // Main Product Vessel Image
            'price' => $priceVal,
            'items' => []
        ];
    }

    $f_ids = !empty($p['fragrance_ids']) ? $p['fragrance_ids'] : [0];
    foreach ($f_ids as $fid) {
        $item = $p;
        $item['fragrance_id'] = $fid;
        $item['fragrance_name'] = $fragrances[$fid] ?? 'Luxury Candle';
        
        $f_key = (string)$fid;
        if (isset($p['fragrance_images_map'][$f_key]) && !empty($p['fragrance_images_map'][$f_key])) {
            $custom_img = trim($p['fragrance_images_map'][$f_key]);
            if (strpos($custom_img, 'http') === 0) {
                $item['image_url'] = $custom_img;
            } else {
                $item['image_url'] = lvc_base_url('public/uploads/products/' . basename($custom_img));
            }
        }

        $item['sku'] = $vesselCode . sprintf('%02d', $primaryColorId) . sprintf('%02d', $fid);
        $colorVariations[$varKey]['items'][] = $item;
    }
}

// Slice top 6 color variations for homepage section
$homepageVariations = array_slice($colorVariations, 0, 6);
?>

<section class="lvc-collection">
  <div class="lvc-header">
    <div class="lvc-title-group">
      <span class="lvc-overline">THE COLLECTION</span>
      <h2 class="lvc-main-title">Ready to be lit.</h2>
    </div>
    <a href="<?php echo $base; ?>/shop" class="lvc-shop-all">SHOP ALL →</a>
  </div>

  <div class="lvc-grid">
    <?php foreach ($homepageVariations as $var): 
        $firstItem = !empty($var['items']) ? $var['items'][0] : null;
        $fragranceSubtitle = $firstItem ? strtoupper($firstItem['fragrance_name']) : (count($var['items']) . ' FRAGRANCES AVAILABLE');
        // ALWAYS use the main product image for the card on the homepage
        $cardImage = $var['image_url'];
    ?>
      <div class="lvc-card" 
           style="cursor: pointer;" 
           onclick="openVariationModal(<?= htmlspecialchars(json_encode($var), ENT_QUOTES, 'UTF-8') ?>)">
        <div class="lvc-img-container">
          <img src="<?php echo htmlspecialchars($cardImage); ?>" 
               alt="<?php echo htmlspecialchars($var['color_name']); ?>" 
               onerror="this.src='https://placehold.co/400x500/14222b/FFFFFF?text=LVB+Candle'">
        </div>
        <div class="lvc-info">
          <div class="lvc-text">
            <span class="lvc-p-name"><?php echo htmlspecialchars($var['color_name']); ?></span>
            <span class="lvc-p-desc"><?php echo htmlspecialchars($fragranceSubtitle); ?></span>
          </div>
          <span class="lvc-price">$<?php echo number_format($var['price'], 0); ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Homepage Quick View Product Modal (Exactly identical to Shop Page modal) -->
<div class="modal-overlay" id="productModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-left">
            <div class="modal-image-container">
                <img id="modalMainImage" src="" alt="Product">
                <div class="thumbnail-strip" id="thumbnailStrip"></div>
            </div>
            <div class="selected-scent-card" id="selectedScentCard" style="display: none;">
                <div class="scent-card-tag">SELECTED SCENT</div>
                <div class="scent-card-title" id="scentCardTitle"></div>
                <div class="scent-card-notes" id="scentCardNotes"></div>
            </div>
        </div>
        <div class="modal-right">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <p id="modalDesc" class="modal-desc"></p>
            </div>
            <div class="modal-specs-grid">
                <div class="info-row">
                    <span class="info-label">SKU</span>
                    <span id="modalSKU" class="info-value"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Size</span>
                    <span id="modalSizeValue" class="info-value"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Color</span>
                    <span id="modalColorValue" class="info-value"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fragrance</span>
                    <span id="modalFragranceValue" class="info-value"></span>
                </div>
            </div>
            
            <hr class="modal-divider">
            <div>
                <div class="section-label">Select Fragrance <span id="modalFragranceCountText">Pick your scent</span></div>
                <div id="fragranceOptionsContainer" style="display: flex; flex-direction: column; gap: 6px; max-height: 180px; overflow-y: auto; padding-right: 4px;"></div>
            </div>

            <hr class="modal-divider">
            <div>
                <div class="section-label">Keepsake Box <span>Optional</span></div>
                <div class="option-grid" id="boxOptions"></div>
            </div>
            <hr class="modal-divider">
            <div class="qty-total-block">
                <div class="row-between">
                    <span class="info-label">Quantity</span>
                    <div class="qty-input">
                        <button type="button" onclick="updateModalQty(-1)">−</button>
                        <span id="modalQty">1</span>
                        <button type="button" onclick="updateModalQty(1)">+</button>
                    </div>
                </div>
                <div class="row-between">
                    <span class="total-label">Total</span>
                    <span id="modalTotal" class="total-price">$0.00</span>
                </div>
            </div>
            <button class="add-to-cart-btn" id="addToCartAction">
                Add To Cart · <span id="btnPrice">$0.00</span>
            </button>
            <div id="stockWarning" class="stock-status"></div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap');

/* Homepage Collection Section Styling */
.lvc-collection {
  display: block;
  width: 100%;
  background: linear-gradient(180deg, #F7FCFD 0%, #DEEFF4 100%);
  padding: 80px 20px;
  box-sizing: border-box;
}

.lvc-collection .lvc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 40px;
  max-width: 96%;
  margin-left: auto;
  margin-right: auto;
  flex-wrap: wrap;
  gap: 20px;
}

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

.lvc-collection .lvc-main-title {
  font-family: 'Cormorant Garamond', 'Times New Roman', serif;
  font-weight: 400;
  font-size: 3rem;
  color: #1a2b3c;
  margin: 0;
}

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
  gap: 14px;
  max-width: 96%;
  margin: 0 auto;
}

.lvc-collection .lvc-card {
  flex: 0 0 calc(16.666% - 12px);
  background: white;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}

.lvc-collection .lvc-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 14px 28px rgba(0, 0, 0, 0.1);
}

.lvc-collection .lvc-img-container {
  height: 320px;
  background: #f0f4f6;
  overflow: hidden;
  position: relative;
}

.lvc-collection .lvc-img-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: bottom center;
  background: #faf9f6;
  display: block;
  transition: transform 0.5s ease;
}

.lvc-collection .lvc-card:hover .lvc-img-container img {
  transform: scale(1.06);
}

.lvc-collection .lvc-info {
  padding: 14px 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
}

.lvc-collection .lvc-text {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.lvc-collection .lvc-p-name {
  font-family: 'Cormorant Garamond', 'Times New Roman', serif;
  font-weight: 500;
  font-size: 1.05rem;
  color: #1a2b3c;
  line-height: 1.2;
}

.lvc-collection .lvc-p-desc {
  font-family: 'Inter', 'Helvetica Neue', sans-serif;
  font-weight: 500;
  font-size: 10px;
  letter-spacing: 0.5px;
  color: #8492a6;
}

.lvc-collection .lvc-price {
  font-family: 'Inter', 'Helvetica Neue', sans-serif;
  font-weight: 600;
  font-size: 14px;
  color: #1a2b3c;
}

/* Tablet */
@media (max-width: 1200px) {
  .lvc-collection .lvc-card {
    flex: 0 0 calc(33.333% - 10px);
  }
}

/* Mobile */
@media (max-width: 600px) {
  .lvc-collection .lvc-card {
    flex: 0 0 calc(50% - 7px);
  }
  
  .lvc-collection .lvc-img-container {
    height: 220px;
  }

  .lvc-collection .lvc-main-title {
    font-size: 2rem;
  }
}

/* EXACT SHOP PAGE MODAL STYLES */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 24px;
    box-sizing: border-box;
}

.modal-content {
    background: #ffffff;
    width: 92%;
    max-width: 960px;
    max-height: 90vh;
    display: flex;
    border-radius: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 25px 60px -15px rgba(0, 75, 102, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.2);
    animation: modalFadeUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalFadeUp {
    from { opacity: 0; transform: scale(0.96) translateY(16px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-left {
    width: 48%;
    flex: 0 0 48%;
    display: flex;
    flex-direction: column;
    background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%);
    position: relative;
    border-right: 1px solid #e2e8f0;
    padding: 16px;
    gap: 12px;
    box-sizing: border-box;
}

.modal-image-container {
    flex: 1;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    border-radius: 16px;
    min-height: 300px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    background: rgba(0, 0, 0, 0.03);
}

.modal-image-container img {
    width: 100%;
    height: 100%;
    max-height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    border-radius: 16px;
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.thumbnail-strip {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    display: flex;
    gap: 8px;
    padding: 5px 10px;
    justify-content: center;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 999px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
    max-width: 90%;
}

.thumbnail-item {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    border: 2px solid rgba(255, 255, 255, 0.6);
    cursor: pointer;
    object-fit: cover;
    transition: all 0.2s ease;
    background: white;
    padding: 2px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.thumbnail-item:hover {
    transform: scale(1.1);
    border-color: #ffffff;
}
.thumbnail-item.active {
    border-color: #ffffff;
    box-shadow: 0 0 0 2.5px #004b66, 0 4px 12px rgba(0, 0, 0, 0.35);
    transform: scale(1.08);
}

.selected-scent-card {
    margin: 0;
    padding: 14px 18px;
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.scent-card-tag {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #004b66;
    margin-bottom: 4px;
}

.scent-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    font-family: inherit;
}

.scent-card-notes {
    font-size: 11px;
    line-height: 1.6;
    color: #334155;
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-weight: 500;
}

.scent-note-row strong {
    font-weight: 700;
    color: #0f172a;
    letter-spacing: 0.5px;
}

.modal-right {
    width: 52%;
    flex: 0 0 52%;
    padding: 32px 36px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    overflow-y: auto;
}

.modal-right::-webkit-scrollbar,
#fragranceOptionsContainer::-webkit-scrollbar {
    width: 5px;
}
.modal-right::-webkit-scrollbar-track,
#fragranceOptionsContainer::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}
.modal-right::-webkit-scrollbar-thumb,
#fragranceOptionsContainer::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.modal-right::-webkit-scrollbar-thumb:hover,
#fragranceOptionsContainer::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.modal-close {
    position: absolute;
    right: 18px;
    top: 18px;
    cursor: pointer;
    font-size: 14px;
    z-index: 20;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(6px);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
    color: #475569;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.modal-close:hover {
    background: #004b66;
    color: #ffffff;
    border-color: #004b66;
    transform: rotate(90deg);
}

.modal-header h2 {
    font-family: 'Cinzel', 'Cormorant Garamond', serif;
    font-weight: 600;
    font-size: 28px;
    color: #0f172a;
    margin-bottom: 6px;
    line-height: 1.25;
    letter-spacing: 0.3px;
}

.modal-desc {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
}

.modal-divider { border: none; border-top: 1px solid #f1f5f9; margin: 4px 0; }

.modal-specs-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px 20px;
    background: #f8fafc;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
}

.info-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.info-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.4px;
    color: #94a3b8;
}
.info-value {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
}

.section-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.4px;
    color: #64748b;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.section-label span { font-weight: 600; text-transform: none; letter-spacing: 0; font-size: 12px; color: #004b66; background: #e0f2fe; padding: 2px 10px; border-radius: 12px; }

.fragrance-option-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1.5px solid #e2e8f0;
    padding: 10px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
}
.fragrance-option-tile:hover {
    border-color: #004b66;
    background: #f8fafc;
    transform: translateY(-1px);
}
.fragrance-option-tile.active {
    border-color: #004b66;
    background: #f0f7fa;
    box-shadow: 0 4px 14px rgba(0, 75, 102, 0.12);
}
.fragrance-tile-title { font-size: 13px; font-weight: 600; color: #0f172a; }
.fragrance-tile-sku { font-size: 10px; color: #64748b; font-family: monospace; margin-top: 1px; }

.option-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; }
.option-tile {
    border: 1.5px solid #e2e8f0;
    padding: 10px 14px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}
.option-tile:hover { border-color: #004b66; background: #f8fafc; }
.option-tile.active { border-color: #004b66; background: #f0f7fa; box-shadow: 0 4px 12px rgba(0, 75, 102, 0.1); }
.tile-title { font-size: 13px; font-weight: 600; color: #0f172a; display: block; }
.tile-sub   { font-size: 11px; color: #64748b; margin-top: 3px; font-weight: 500; display: block; }

.qty-total-block {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #f8fafc;
    padding: 16px;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
}
.row-between { display: flex; justify-content: space-between; align-items: center; }

.total-label {
    font-family: 'Cinzel', serif;
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
}
.total-price {
    font-family: 'Cinzel', serif;
    font-size: 26px;
    font-weight: 700;
    color: #004b66;
}

.qty-input {
    display: flex;
    align-items: center;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.qty-input button {
    border: none;
    background: none;
    width: 36px;
    height: 36px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    line-height: 1;
    transition: background 0.15s, color 0.15s;
    color: #0f172a;
}
.qty-input button:hover { background: #004b66; color: #ffffff; }
.qty-input span {
    min-width: 36px;
    text-align: center;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.add-to-cart-btn {
    background: linear-gradient(135deg, #004b66 0%, #00364a 100%);
    color: white;
    border: none;
    padding: 16px 24px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.5px;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 20px -4px rgba(0, 75, 102, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.add-to-cart-btn:hover {
    background: linear-gradient(135deg, #003d54 0%, #002838 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -4px rgba(0, 75, 102, 0.45);
}
.add-to-cart-btn:active { transform: translateY(0); }

.stock-status { font-size: 12px; color: #ef4444; margin-top: 6px; text-align: center; }

.cart-success {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #10b981;
    color: white;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    z-index: 3000;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}

@media (max-width: 820px) {
    .modal-content { flex-direction: column; max-width: 95%; max-height: 92vh; overflow-y: auto; }
    .modal-left  { width: 100%; height: 340px; border-right: none; border-bottom: 1px solid #e2e8f0; }
    .modal-right { width: 100%; padding: 24px 20px 28px; }
    .thumbnail-item { width: 44px; height: 44px; }
}
</style>

<script>
let currentVariation = null;
let currentProduct = null;
let currentSizeId = null;
let currentBasePrice = 29.00;
let selectedBoxId = null;
let boxPrice = 0;
let quantity = 1;
let imageUrls = [];

let sizesData = <?= json_encode($sizes) ?>;
let colorsData = <?= json_encode($colors) ?>;
let fragrancesData = <?= json_encode($fragrances) ?>;
let fragranceDescriptionsData = <?= json_encode($fragrance_descriptions) ?>;
let boxesData = <?= json_encode($boxes) ?>;
let categoriesData = <?= json_encode($categoriesMap) ?>;

function waitForLVBCart(callback) {
    if (typeof LVBCart !== 'undefined' && LVBCart && typeof LVBCart.addItem === 'function') {
        callback();
    } else {
        setTimeout(function() { waitForLVBCart(callback); }, 50);
    }
}

function applyDynamicModalTheme(hexColor) {
    const modalLeft = document.querySelector('#productModal .modal-left');
    if (!modalLeft) return;
    
    if (!hexColor || typeof hexColor !== 'string' || hexColor.trim() === '') {
        hexColor = '#f1f5f9';
    }
    
    let hex = hexColor.replace('#', '').trim();
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    
    let r = parseInt(hex.substring(0, 2), 16);
    let g = parseInt(hex.substring(2, 4), 16);
    let b = parseInt(hex.substring(4, 6), 16);

    if (isNaN(r) || isNaN(g) || isNaN(b)) {
        r = 240; g = 240; b = 240;
    }
    
    let brightness = (r * 299 + g * 587 + b * 114) / 1000;
    const thumbStrip = document.getElementById('thumbnailStrip');
    
    if (brightness < 100) {
        let lighterR = Math.min(255, r + 45);
        let lighterG = Math.min(255, g + 45);
        let lighterB = Math.min(255, b + 45);
        
        modalLeft.style.background = `radial-gradient(circle at center, rgb(${lighterR}, ${lighterG}, ${lighterB}) 0%, rgb(${r}, ${g}, ${b}) 100%)`;
        modalLeft.style.borderRight = '1px solid rgba(255, 255, 255, 0.12)';
        if (thumbStrip) {
            thumbStrip.style.background = 'rgba(15, 23, 42, 0.65)';
            thumbStrip.style.borderTop = '1px solid rgba(255, 255, 255, 0.12)';
        }
    } else if (brightness > 220) {
        modalLeft.style.background = `radial-gradient(circle at center, #ffffff 0%, rgb(${r}, ${g}, ${b}) 100%)`;
        modalLeft.style.borderRight = '1px solid #e2e8f0';
        if (thumbStrip) {
            thumbStrip.style.background = 'rgba(255, 255, 255, 0.85)';
            thumbStrip.style.borderTop = '1px solid rgba(226, 232, 240, 0.8)';
        }
    } else {
        let lighterR = Math.min(255, r + 55);
        let lighterG = Math.min(255, g + 55);
        let lighterB = Math.min(255, b + 55);
        
        modalLeft.style.background = `radial-gradient(circle at 50% 40%, rgb(${lighterR}, ${lighterG}, ${lighterB}) 0%, rgb(${r}, ${g}, ${b}) 100%)`;
        modalLeft.style.borderRight = '1px solid rgba(0, 0, 0, 0.08)';
        if (thumbStrip) {
            thumbStrip.style.background = 'rgba(255, 255, 255, 0.85)';
            thumbStrip.style.borderTop = '1px solid rgba(226, 232, 240, 0.8)';
        }
    }
}

function openVariationModal(variationData, targetFragId) {
    currentVariation = variationData;
    selectedBoxId = null;
    boxPrice = 0;
    quantity = 1;

    if (!variationData || !variationData.items || variationData.items.length === 0) return;

    let hexColor = variationData.color_hex || (colorsData && colorsData[variationData.color_id] ? colorsData[variationData.color_id].color_hex : null);
    applyDynamicModalTheme(hexColor);

    let activeItem = variationData.items[0];
    if (targetFragId > 0) {
        const found = variationData.items.find(i => parseInt(i.fragrance_id) === parseInt(targetFragId));
        if (found) activeItem = found;
    }

    renderFragranceOptions(variationData.items, activeItem.fragrance_id);
    switchFragranceItem(activeItem);
    renderBoxOptions();

    document.getElementById('stockWarning').innerHTML = '';
    document.getElementById('productModal').style.display = 'flex';
}

function getImageUrl(item) {
    if (!item) return 'https://placehold.co/600x600?text=No+Image';
    if (item.image_url && typeof item.image_url === 'string' && item.image_url.trim() !== '') {
        return item.image_url.trim();
    }
    return 'https://placehold.co/600x600?text=No+Image';
}

function renderFragranceOptions(items, activeFragId) {
    const container = document.getElementById('fragranceOptionsContainer');
    const countText = document.getElementById('modalFragranceCountText');
    if (!container) return;
    container.innerHTML = '';

    if (countText) {
        countText.innerText = items.length + (items.length === 1 ? ' Fragrance Available' : ' Fragrances Available');
    }

    items.forEach(item => {
        const tile = document.createElement('div');
        const isActive = (parseInt(item.fragrance_id) === parseInt(activeFragId));
        tile.className = 'fragrance-option-tile' + (isActive ? ' active' : '');
        
        let varImg = getImageUrl(item);
        let iconHtml = `<img src="${escapeHtml(varImg)}" style="width: 36px; height: 36px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);" alt="${escapeHtml(item.fragrance_name)}">`;

        tile.innerHTML = `
            ${iconHtml}
            <div style="flex: 1;">
                <div class="fragrance-tile-title">${escapeHtml(item.fragrance_name)}</div>
                <div class="fragrance-tile-sku">SKU: ${escapeHtml(item.sku)}</div>
            </div>
            <div style="font-size: 12px; font-weight: 600; color: #004b66;">$${parseFloat(item.size_prices || 29).toFixed(2)}</div>
        `;

        tile.onclick = function() {
            document.querySelectorAll('#productModal .fragrance-option-tile').forEach(el => el.classList.remove('active'));
            tile.classList.add('active');
            switchFragranceItem(item);
        };

        container.appendChild(tile);
    });
}

function switchFragranceItem(productData) {
    currentProduct = productData;
    currentSizeId = null;

    if (productData.size_id) {
        let pSizes = Array.isArray(productData.size_id) ? productData.size_id : [productData.size_id];
        if (pSizes.length > 0) currentSizeId = parseInt(pSizes[0]);
    }

    if (typeof productData.size_prices === 'number' || (typeof productData.size_prices === 'string' && !isNaN(parseFloat(productData.size_prices)))) {
        currentBasePrice = parseFloat(productData.size_prices);
    } else if (productData.size_prices && is_array(productData.size_prices)) {
        currentBasePrice = parseFloat(reset(productData.size_prices));
    } else {
        currentBasePrice = 29.00;
    }

    document.getElementById('modalTitle').innerText = (currentVariation && currentVariation.color_name) ? currentVariation.color_name : (productData.color_name || productData.product_name || 'Candle');
    
    imageUrls = [];
    const candleImg = getImageUrl(productData);
    if (candleImg) imageUrls.push(candleImg);
    
    setMainImage(imageUrls[0], 0);
    renderThumbnails();
    
    document.getElementById('modalSKU').innerText = getFullProductSKU(selectedBoxId);

    let vesselCode = getProductVesselCode(currentProduct).toLowerCase();
    let catObj = (categoriesData && categoriesData[vesselCode]) ? categoriesData[vesselCode] : (categoriesData ? categoriesData['c'] : null);

    let sizeLabel = catObj ? catObj.category_name : ('Vessel ' + vesselCode.toUpperCase());
    let sizeDetails = catObj ? catObj.size_details : '3" · 45 HRS';

    document.getElementById('modalSizeValue').innerText = sizeLabel + (sizeDetails ? ' (' + sizeDetails + ')' : '');

    let colorNameText = 'Standard';
    if (currentVariation && currentVariation.color_name) {
        colorNameText = currentVariation.color_name;
    } else if (productData.color_id) {
        let cId = Array.isArray(productData.color_id) ? productData.color_id[0] : productData.color_id;
        if (colorsData[cId]) colorNameText = colorsData[cId].color_name;
    }

    document.getElementById('modalColorValue').innerText = colorNameText.replace(/Â·|Â/g, '·').trim();
    document.getElementById('modalFragranceValue').innerText = productData.fragrance_name || 'Luxury Scent';
    document.getElementById('modalDesc').innerText = productData.description || ('Handcrafted candle in a luminous ' + colorNameText.toLowerCase() + ' vessel.');

    updateSelectedScentCard(productData.fragrance_id, productData.fragrance_name);
    updateDisplay();
}

function updateSelectedScentCard(fragId, fragName) {
    const scentCard = document.getElementById('selectedScentCard');
    const titleEl   = document.getElementById('scentCardTitle');
    const notesEl   = document.getElementById('scentCardNotes');
    
    if (!scentCard || !titleEl || !notesEl) return;
    titleEl.innerText = fragName || 'Luxury Scent';
    
    let desc = (typeof fragranceDescriptionsData !== 'undefined' && fragranceDescriptionsData[fragId]) ? fragranceDescriptionsData[fragId] : '';
    desc = desc.trim();
    
    if (!desc) {
        scentCard.style.display = 'none';
        return;
    }
    
    scentCard.style.display = 'block';
    
    if (desc.includes('|') || desc.includes('TOP:') || desc.includes('MID:') || desc.includes('BASE:')) {
        let parts = desc.split('|').map(p => p.trim()).filter(Boolean);
        let html = '';
        parts.forEach(part => {
            let colonIdx = part.indexOf(':');
            if (colonIdx !== -1) {
                let label = part.substring(0, colonIdx).trim();
                let val = part.substring(colonIdx + 1).trim();
                html += `<div class="scent-note-row"><strong>${escapeHtml(label.toUpperCase())}:</strong> ${escapeHtml(val.toUpperCase())}</div>`;
            } else {
                html += `<div class="scent-note-row">${escapeHtml(part.toUpperCase())}</div>`;
            }
        });
        notesEl.innerHTML = html;
    } else {
        notesEl.innerHTML = `<div class="scent-note-row">${escapeHtml(desc.toUpperCase())}</div>`;
    }
}

function renderThumbnails() {
    const strip = document.getElementById('thumbnailStrip');
    if (!strip) return;
    strip.innerHTML = '';
    
    if (imageUrls.length <= 1) {
        strip.style.display = 'none';
        return;
    }
    strip.style.display = 'flex';
    
    imageUrls.forEach((url, index) => {
        const thumb = document.createElement('img');
        thumb.className = 'thumbnail-item' + (index === 0 ? ' active' : '');
        thumb.src = url;
        thumb.alt = 'Image ' + (index + 1);
        thumb.onerror = function() { this.style.display = 'none'; };
        thumb.onclick = function() { setMainImage(url, index); };
        strip.appendChild(thumb);
    });
}

function setMainImage(imageUrl, index) {
    const imgEl = document.getElementById('modalMainImage');
    if (imgEl) imgEl.src = imageUrl;
    document.querySelectorAll('#productModal .thumbnail-item').forEach((el, i) => {
        if (i === index) el.classList.add('active');
        else el.classList.remove('active');
    });
}

function getProductVesselCode(product) {
    if (!product) return 'C';
    if (product.sku && typeof product.sku === 'string') {
        const firstLetter = product.sku.trim().charAt(0).toUpperCase();
        if (['C', 'D', 'E'].includes(firstLetter)) return firstLetter;
    }
    return 'C';
}

function renderBoxOptions() {
    const c = document.getElementById('boxOptions');
    if (!c) return;
    c.innerHTML = '';
    
    let vesselCode = getProductVesselCode(currentProduct);
    let filteredBoxes = {};
    if (boxesData && Object.keys(boxesData).length > 0) {
        Object.keys(boxesData).forEach(function(bid) {
            const b = boxesData[bid];
            if (b && b.vessel_code && b.vessel_code.toUpperCase() === vesselCode) {
                filteredBoxes[bid] = b;
            }
        });
    }

    let ids = Object.keys(filteredBoxes).sort(function(a,b){ return parseInt(a) - parseInt(b); });

    if (ids.length === 0) {
        c.innerHTML = '<div style="grid-column: 1 / -1; padding: 10px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b; font-size: 12px; text-align: center;">No packaging box options available for Vessel ' + escapeHtml(vesselCode) + '</div>';
        selectedBoxId = null;
        boxPrice = 0;
        updateDisplay();
        return;
    }

    ids.forEach(function(bid) {
        const box = filteredBoxes[bid]; if (!box) return;
        const price = parseFloat(box.box_price || 6);
        const tile = document.createElement('div');
        tile.className = 'option-tile' + (selectedBoxId === parseInt(bid) ? ' active' : '');
        tile.innerHTML = '<span class="tile-title">' + escapeHtml(box.box_name) + '</span><span class="tile-sub">+ $' + price.toFixed(2) + '</span>';
        tile.onclick = (function(bid, price) { return function() { selectBox(tile, bid, price); }; })(parseInt(bid), price);
        c.appendChild(tile);
    });
}

function selectBox(el, boxId, price) {
    if (selectedBoxId === boxId) {
        selectedBoxId = null; 
        boxPrice = 0; 
        el.classList.remove('active');
    } else {
        var tiles = document.querySelectorAll('#boxOptions .option-tile');
        for (var i = 0; i < tiles.length; i++) tiles[i].classList.remove('active');
        el.classList.add('active'); 
        selectedBoxId = boxId; 
        boxPrice = price;
    }
    updateDisplay();
    if (currentProduct) {
        document.getElementById('modalSKU').innerText = getFullProductSKU(selectedBoxId);
    }
}

function getFullProductSKU(boxId) {
    if (!currentProduct) return '';
    let baseSKU = currentProduct.sku;
    let boxCode = '';
    if (boxId) {
        if (boxesData && boxesData[boxId] && boxesData[boxId].sku) {
            boxCode = boxesData[boxId].sku;
        } else if (boxesData && boxesData[boxId]) {
            const bName = (boxesData[boxId].box_name || '').toLowerCase();
            boxCode = bName.includes('black') ? 'B01B' : 'B01W';
        }
    }
    return baseSKU + boxCode;
}

function updateModalQty(delta) {
    const newQty = quantity + delta;
    if (newQty >= 1) {
        quantity = newQty;
        updateDisplay();
    }
}

function updateDisplay() {
    const total = (currentBasePrice + boxPrice) * quantity;
    document.getElementById('modalQty').innerText = quantity;
    document.getElementById('modalTotal').innerHTML = '$' + total.toFixed(2);
    document.getElementById('btnPrice').innerHTML = '$' + total.toFixed(2);
}

function closeModal() {
    document.getElementById('productModal').style.display = 'none';
}

const addCartBtn = document.getElementById('addToCartAction');
if (addCartBtn) {
    addCartBtn.onclick = function() {
        if (!currentProduct) return;

        let vesselCode = getProductVesselCode(currentProduct).toLowerCase();
        let catObj = (categoriesData && categoriesData[vesselCode]) ? categoriesData[vesselCode] : (categoriesData ? categoriesData['c'] : null);

        let sizeLabel = catObj ? catObj.category_name : ('Vessel ' + vesselCode.toUpperCase());
        let sizeDetails = catObj ? catObj.size_details : '3" · 45 HRS';
        const selectedSizeName = sizeLabel + (sizeDetails ? ' (' + sizeDetails + ')' : '');

        let boxName = null;
        if (selectedBoxId && boxesData[selectedBoxId]) boxName = boxesData[selectedBoxId].box_name;

        const sku = getFullProductSKU(selectedBoxId);
        let productDisplayName = currentProduct.product_name || (currentProduct.fragrance_name + ' Candle');
        if (boxName) productDisplayName += ' + ' + boxName;

        const uniqueItemId = 'prod_' + (currentProduct.product_id || 'item') + '_size' + (currentSizeId || '0') + (selectedBoxId ? '_box' + selectedBoxId : '') + '_' + sku;

        waitForLVBCart(function() {
            if (typeof LVBCart !== 'undefined' && LVBCart.addItem) {
                LVBCart.addItem({
                    id: uniqueItemId,
                    sku: sku,
                    name: productDisplayName,
                    scent: selectedSizeName,
                    price: currentBasePrice + boxPrice,
                    image: getImageUrl(currentProduct),
                    qty: quantity,
                    product_id: currentProduct.product_id,
                    size_id: currentSizeId,
                    size_name: selectedSizeName,
                    box_id: selectedBoxId,
                    box_name: boxName,
                    fragrance_id: currentProduct.fragrance_id,
                    fragrance_name: currentProduct.fragrance_name
                });
                closeModal();
                showSuccessMessage('Added to cart (SKU: ' + sku + ')');
            } else {
                console.error('LVBCart not available');
            }
        });
    };
}

function showSuccessMessage(msg) {
    const d = document.createElement('div');
    d.className = 'cart-success';
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(function() { d.remove(); }, 3000);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
</script>