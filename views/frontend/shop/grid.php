<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "db.php";

// Get vessel from URL parameter
$selectedVessel = isset($_GET['vessel']) ? strtolower($_GET['vessel']) : '';

// If no vessel selected or invalid, show the vessel selection page
$showVesselSelection = empty($selectedVessel) || !in_array($selectedVessel, ['c', 'd']);

// If vessel is selected, fetch products
if (!$showVesselSelection) {
    $vesselSizeId = $selectedVessel === 'c' ? 4 : 2;
    $wickType = $selectedVessel === 'c' ? 'single' : 'double';

    // Fetch products
    $sql = "SELECT * FROM products ORDER BY product_id DESC";
    $result = $conn->query($sql);
    
    $products = [];
    $fragrances = [];
    $colors = [];
    $sizes = [];
    $boxes = [];

    if ($result) {
        // Get lookup data
        $frag_result = $conn->query("SELECT fragrance_id, fragrance_name FROM fragrances");
        if ($frag_result) {
            while ($row = $frag_result->fetch_assoc()) {
                $fragrances[$row['fragrance_id']] = $row['fragrance_name'];
            }
        }

        $color_result = $conn->query("SELECT color_id, color_name, color_hex FROM colors");
        if ($color_result) {
            while ($row = $color_result->fetch_assoc()) {
                $colors[$row['color_id']] = $row;
            }
        }

        $size_result = $conn->query("SELECT size_id, size_name, size_details FROM sizes ORDER BY size_id");
        if ($size_result) {
            while ($row = $size_result->fetch_assoc()) {
                $sizes[$row['size_id']] = $row;
            }
        }

        $boxes = [];
        $box_result = $conn->query("SELECT box_id, box_name FROM boxes ORDER BY box_id");
        if ($box_result) {
            while ($row = $box_result->fetch_assoc()) {
                $boxes[$row['box_id']] = $row;
            }
        }

        while ($row = $result->fetch_assoc()) {
            $row['size_prices'] = json_decode($row['size_prices'], true);
            $row['size_id'] = json_decode($row['size_id'], true);
            $row['color_id'] = json_decode($row['color_id'], true);
            $row['box_id'] = json_decode($row['box_id'], true);
            $row['wick_type'] = $row['wick_type'] ?? 'single';

            // Skip products with 'none' wick type
            if ($row['wick_type'] === 'none') {
                continue;
            }

            // Check if product matches the vessel selection
            if ($row['wick_type'] !== $wickType) {
                continue;
            }

            if (!isset($row['size_id']) || !is_array($row['size_id']) || !in_array($vesselSizeId, $row['size_id'])) {
                continue;
            }

            // Get image URL
            $image_name = $row['image'];
            $image_path = '';
            $paths_to_check = [
                $_SERVER['DOCUMENT_ROOT'] . '/img/' . $image_name,
                dirname(__DIR__, 3) . '/img/' . $image_name,
                __DIR__ . '/../img/' . $image_name,
                __DIR__ . '/../../img/' . $image_name
            ];
            foreach ($paths_to_check as $path) {
                if (file_exists($path)) {
                    $image_path = '/img/' . $image_name;
                    break;
                }
            }
            $row['image_url'] = $image_path ?: 'https://placehold.co/600x600?text=No+Image';

            $fragrance_id = is_numeric($row['fragrance_id']) ? $row['fragrance_id'] : 0;
            $row['fragrance_name'] = $fragrances[$fragrance_id] ?? 'Luxury Candle';
            
            // Get fragrance image
            $fragrance_image_name = '';
            if ($fragrance_id > 0) {
                // Map fragrance IDs to their image filenames
                $fragranceImageMap = [
                    1 => '01 FRAGRABCE FREE',
                    2 => '02 AMBER MUSK FRAGRANT',
                    3 => '03',
                    4 => '04 PINE & SALT AIR FRAGRANT',
                    5 => '05 CHAMPAGNE LUXE FRAGRANT',
                    6 => '06 CITRUS AGAVE ZEST FRAGRANT',
                    7 => '07',
                    8 => '08 EVENING TIDE FRAGRANT',
                    9 => '09 LAVENDER FIELD FRAGRENT',
                    10 => '10 WILD LEMONGRASS FRAGRANT',
                    11 => '11 MAHOGANY WOODS FREGRENT',
                    12 => '12',
                    13 => '13 L_ATTRACTION FRAGRANT',
                    14 => '14 VANILLA FIELDS FRAGRANT'
                ];
                
                $fragranceFileName = $fragranceImageMap[$fragrance_id] ?? '';
                if ($fragranceFileName) {
                    // Check for webp version first
                    $webp_path = '/img/' . $fragranceFileName . '.webp';
                    $png_path = '/img/' . $fragranceFileName . '.png';
                    
                    // Check if webp exists
                    $webp_full_path = $_SERVER['DOCUMENT_ROOT'] . $webp_path;
                    $png_full_path = $_SERVER['DOCUMENT_ROOT'] . $png_path;
                    
                    if (file_exists($webp_full_path)) {
                        $fragrance_image_name = $webp_path;
                    } elseif (file_exists($png_full_path)) {
                        $fragrance_image_name = $png_path;
                    } else {
                        // Try alternative paths
                        $paths_to_check_frag = [
                            dirname(__DIR__, 3) . '/img/' . $fragranceFileName . '.webp',
                            __DIR__ . '/../img/' . $fragranceFileName . '.webp',
                            __DIR__ . '/../../img/' . $fragranceFileName . '.webp'
                        ];
                        foreach ($paths_to_check_frag as $path) {
                            if (file_exists($path)) {
                                $fragrance_image_name = '/img/' . $fragranceFileName . '.webp';
                                break;
                            }
                        }
                    }
                }
            }
            $row['fragrance_image'] = $fragrance_image_name;
            $products[] = $row;
        }
    }
}

$box_prices = [1 => 6, 2 => 5, 3 => 6, 4 => 5];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LVB Shop — Laguna Vibe Beach</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="/views/cart.js"></script>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        background-color: #F7FBFC;
        font-family: 'Inter', sans-serif;
    }

    .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 40px 24px;
    }

    .shop-header {
        text-align: center;
        margin-bottom: 60px;
    }
    .shop-header h1 {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 400;
        font-size: 48px;
        letter-spacing: 1px;
        color: #1a2a3a;
    }
    .shop-header p {
        font-size: 14px;
        color: #8fa3b0;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-top: 8px;
    }

    /* Vessel Selection Styles */
    .vessel-selection {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        max-width: 900px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .vessel-selection {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    .vessel-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 3px solid transparent;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .vessel-card:hover { 
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.12);
    }

    .vessel-card img { 
        width: 100%;
        height: 380px; 
        object-fit: cover;
        display: block;
    }

    .vessel-card-content {
        padding: 24px;
    }

    .vessel-card-content h3 {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 500;
        font-size: 28px;
        color: #1a2a3a;
        margin-bottom: 6px;
    }

    .vessel-card-content .vessel-subtitle {
        font-size: 14px;
        color: #8fa3b0;
        letter-spacing: 1px;
        margin-bottom: 12px;
    }

    .vessel-card-content .vessel-details {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .vessel-card-content .vessel-detail-item {
        font-size: 13px;
        color: #1a2a3a;
        background: #f0f7fa;
        padding: 4px 14px;
        border-radius: 20px;
    }

    .vessel-card-content .vessel-wick {
        display: inline-block;
        font-size: 13px;
        font-weight: 600;
        color: #004b66;
        background: #e8f0f4;
        padding: 6px 16px;
        border-radius: 20px;
    }

    .shop-now-btn {
        display: inline-block;
        margin-top: 16px;
        background: #004b66;
        color: white;
        padding: 10px 28px;
        border-radius: 999px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 1px;
        transition: background 0.2s;
    }

    .shop-now-btn:hover {
        background: #003d54;
    }

    /* Product Grid Styles */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 48px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .page-header-left h1 {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 400;
        font-size: 40px;
        color: #1a2a3a;
    }

    .page-header-left .subtitle {
        font-size: 14px;
        color: #8fa3b0;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-top: 4px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: #1a2a3a;
        padding: 10px 20px;
        border-radius: 999px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        border: 1px solid #e8eef2;
        transition: all 0.2s;
    }

    .back-btn:hover {
        border-color: #004b66;
        color: #004b66;
    }

    .vessel-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 8px;
    }

    .vessel-badge.single {
        background: #dbeafe;
        color: #1e40af;
    }

    .vessel-badge.double {
        background: #fef3c7;
        color: #92400e;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
    }

    @media (max-width: 968px) { 
        .product-grid { grid-template-columns: repeat(2,1fr); gap: 24px; } 
    }
    @media (max-width: 640px) { 
        .product-grid { grid-template-columns: 1fr; } 
    }

    .product-card {
        cursor: pointer;
        transition: transform 0.3s ease;
        background: white;
        border-radius: 4px;
        overflow: hidden;
    }
    .product-card:hover { transform: translateY(-4px); }
    .product-image { width: 100%; aspect-ratio: 1/1; object-fit: cover; background: #f0f0f0; display: block; }
    .product-info { padding: 16px 12px 12px; }
    .product-name {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 400;
        font-size: 18px;
        letter-spacing: 0.5px;
        color: #1a2a3a;
        margin-bottom: 6px;
    }
    .product-detail-row { display: flex; justify-content: space-between; align-items: baseline; margin-top: 4px; }
    .product-fragrance { font-size: 11px; font-weight: 400; color: #8fa3b0; text-transform: uppercase; letter-spacing: 1px; }
    .product-price { font-size: 13px; font-weight: 500; color: #004b66; }

    .no-products-message {
        grid-column: 1/-1;
        text-align: center;
        padding: 80px 20px;
        color: #8fa3b0;
    }

    .no-products-message p {
        font-size: 18px;
        margin-bottom: 8px;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.75);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }

    .modal-content {
        background: white;
        width: 100%;
        max-width: 600px;
        max-height: 95vh;
        display: flex;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
    }

    .modal-left {
        width: 50%;
        flex: 0 0 50%;
        display: flex;
        flex-direction: column;
        background: #f5f5f5;
        position: relative;
    }
    
    .modal-image-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        padding: 10px;
        min-height: 250px;
    }
    .modal-image-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        transition: opacity 0.3s ease;
    }

    /* Thumbnail strip */
    .thumbnail-strip {
        display: flex;
        gap: 8px;
        padding: 10px 12px 12px 12px;
        justify-content: center;
        background: rgba(255,255,255,0.95);
        border-top: 1px solid #e8eef2;
        flex-wrap: wrap;
    }
    .thumbnail-item {
        width: 50px;
        height: 50px;
        border-radius: 6px;
        border: 2px solid #e8eef2;
        cursor: pointer;
        object-fit: cover;
        transition: all 0.2s ease;
        background: white;
        padding: 3px;
    }
    .thumbnail-item:hover {
        transform: scale(1.05);
        border-color: #004b66;
    }
    .thumbnail-item.active {
        border-color: #004b66;
        box-shadow: 0 0 0 2px #004b66;
    }

    .modal-right {
        width: 50%;
        flex: 0 0 50%;
        padding: 5px 32px 5px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        overflow-y: auto;
    }

    .modal-close {
        position: absolute;
        right: 14px;
        top: 14px;
        cursor: pointer;
        font-size: 16px;
        z-index: 10;
        background: rgba(255,255,255,0.92);
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e0e0e0;
        transition: background 0.2s;
        color: #333;
    }
    .modal-close:hover { background: #ebebeb; }

    .modal-header h2 {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 500;
        font-size: 26px;
        color: #1a2a3a;
        margin-bottom: 6px;
        line-height: 1.2;
    }
    .modal-desc {
        font-size: 12px;
        color: #666;
        line-height: 1.55;
    }

    .modal-divider { border: none; border-top: 1px solid #e8eef2; margin: 2px 0; }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0px 0;
    }
    .info-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.8px;
        color: #8fa3b0;
    }
    .info-value {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 400;
        color: #1a2a3a;
        text-align: right;
    }

    .section-label {
        font-size: 9px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.6px;
        color: #8fa3b0;
        margin-bottom: 7px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .section-label span { font-weight: 400; text-transform: none; letter-spacing: 0; font-size: 10px; color: #aab8c2; }

    .option-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 8px; }
    .option-tile {
        border: 1px solid #e8eef2;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: border-color 0.18s, background 0.18s;
        background: white;
    }
    .option-tile:hover { border-color: #004b66; }
    .option-tile.active { border-color: #004b66; background: #f0f7fa; }
    .tile-title { font-size: 12px; font-weight: 500; color: #1a2a3a; display: block; }
    .tile-sub   { font-size: 10px; color: #8fa3b0; margin-top: 2px; display: block; }
    .tile-price { font-size: 11px; font-weight: 600; color: #004b66; margin-top: 5px; display: block; }

    .qty-total-block { display: flex; flex-direction: column; gap: 10px; }
    .row-between { display: flex; justify-content: space-between; align-items: center; }

    .qty-input {
        display: flex;
        align-items: center;
        border: 1px solid #dde6eb;
        border-radius: 999px;
        overflow: hidden;
    }
    .qty-input button {
        border: none;
        background: none;
        width: 32px;
        height: 32px;
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
        transition: background 0.15s;
        color: #1a2a3a;
    }
    .qty-input button:hover { background: #f0f0f0; }
    .qty-input span {
        min-width: 32px;
        text-align: center;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        color: #1a2a3a;
    }

    .total-label {
        font-family: 'Cormorant Garamond', serif;
        font-size: 20px;
        font-weight: 400;
        color: #1a2a3a;
    }
    .total-price {
        font-family: 'Cormorant Garamond', serif;
        font-size: 22px;
        font-weight: 600;
        color: #004b66;
    }

    .add-to-cart-btn {
        background: #004b66;
        color: white;
        border: none;
        padding: 14px 20px;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1.8px;
        cursor: pointer;
        transition: background 0.2s;
        border-radius: 999px;
        width: 100%;
        margin-top: 2px;
    }
    .add-to-cart-btn:hover { background: #003d54; }

    .stock-status { font-size: 10px; color: #e74c3c; }

    .cart-success {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #004b66;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 3000;
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to   { transform: translateX(0);    opacity: 1; }
    }

    @media (max-width: 680px) {
        .modal-content { flex-direction: column; max-width: 96%; max-height: 92vh; overflow-y: auto; }
        .modal-left  { width: 100%; flex: none; height: 320px; }
        .modal-right { width: 100%; flex: none; padding: 20px 18px 24px; }
        .thumbnail-item {
            width: 40px;
            height: 40px;
        }
    }
</style>
</head>
<body>

<div class="container">

<?php if ($showVesselSelection): ?>
    <!-- VESSEL SELECTION PAGE -->
    <div class="shop-header">
        <h1>Choose Your Vessel</h1>
        <p>Select the perfect candle vessel for your space</p>
    </div>

    <div class="vessel-selection">
        <a href="?vessel=c" class="vessel-card">
            <img src="/img/vessel2.webp" alt="Vessel C - Single Wick" onerror="this.src='https://placehold.co/600x600?text=Vessel+C'">
            <div class="vessel-card-content">
                <h3>Vessel C</h3>
                <div class="vessel-subtitle">3" DIAMETER × 3.5" HEIGHT</div>
                <div class="vessel-details">
                    <span class="vessel-detail-item">3" × 3.5"</span>
                    <span class="vessel-detail-item">45 hours burn time</span>
                </div>
                <span class="vessel-wick">🕯️ Single Wick</span>
                <div>
                    <span class="shop-now-btn">Shop Collection →</span>
                </div>
            </div>
        </a>

        <a href="?vessel=d" class="vessel-card">
            <img src="/img/vessel1.webp" alt="Vessel D - Double Wick" onerror="this.src='https://placehold.co/600x600?text=Vessel+D'">
            <div class="vessel-card-content">
                <h3>Vessel D</h3>
                <div class="vessel-subtitle">3.5" DIAMETER × 4" HEIGHT</div>
                <div class="vessel-details">
                    <span class="vessel-detail-item">3.5" × 4"</span>
                    <span class="vessel-detail-item">60 hours burn time</span>
                </div>
                <span class="vessel-wick">🕯️🕯️ Double Wick</span>
                <div>
                    <span class="shop-now-btn">Shop Collection →</span>
                </div>
            </div>
        </a>
    </div>

<?php else: ?>
    <!-- PRODUCT LISTING PAGE -->
    <?php 
    $vesselLabel = $selectedVessel === 'c' ? 'Vessel C' : 'Vessel D';
    $wickLabel = $selectedVessel === 'c' ? 'Single Wick' : 'Double Wick';
    ?>
    
    <div class="page-header">
        <div class="page-header-left">
            <h1><?= $vesselLabel ?></h1>
            <div class="subtitle"><?= ucfirst($wickLabel) ?> Collection</div>
           
        </div>
        <a href="/shop" class="back-btn">← Back to Vessels</a>
    </div>

    <div class="product-grid">
        <?php if (empty($products)): ?>
            <div class="no-products-message">
                <p>No <?= strtolower($wickLabel) ?> candles available</p>
                <div style="font-size: 14px; margin-top: 8px;">Please check back later for new arrivals</div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): 
                $displayPrice = 29;
                if (isset($product['size_prices']) && isset($product['size_prices'][$vesselSizeId])) {
                    $displayPrice = $product['size_prices'][$vesselSizeId];
                } elseif (isset($product['size_prices']) && !empty($product['size_prices'])) {
                    $displayPrice = reset($product['size_prices']);
                }
            ?>
                <div class="product-card" onclick="openModal(<?= htmlspecialchars(json_encode($product)) ?>)">
                    <img class="product-image" src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
                        <div class="product-detail-row">
                            <span class="product-fragrance"><?= htmlspecialchars($product['fragrance_name']) ?></span>
                            <span class="product-price">$<?= number_format($displayPrice, 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

</div>

<!-- Modal (only shown when products are displayed) -->
<?php if (!$showVesselSelection): ?>
<div class="modal-overlay" id="productModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-left">
            <div class="modal-image-container">
                <img id="modalMainImage" src="" alt="Product">
            </div>
            <div class="thumbnail-strip" id="thumbnailStrip">
                <!-- Thumbnails will be rendered here -->
            </div>
        </div>
        <div class="modal-right">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <p id="modalDesc" class="modal-desc"></p>
            </div>
            <hr class="modal-divider">
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

<script>
function waitForLVBCart(callback) {
    if (typeof LVBCart !== 'undefined' && LVBCart && typeof LVBCart.addItem === 'function') {
        callback();
    } else {
        setTimeout(function() { waitForLVBCart(callback); }, 50);
    }
}

const colorsData = <?php 
    $colors_js = [];
    $color_res = $conn->query("SELECT color_id, color_name, color_hex FROM colors");
    if ($color_res) { while ($row = $color_res->fetch_assoc()) $colors_js[$row['color_id']] = $row; }
    echo json_encode($colors_js);
?>;
const sizesData = <?php
    $sizes_js = [];
    $size_res = $conn->query("SELECT size_id, size_name, size_details FROM sizes ORDER BY size_id");
    if ($size_res) { while ($row = $size_res->fetch_assoc()) $sizes_js[$row['size_id']] = $row; }
    echo json_encode($sizes_js);
?>;
const boxesData = <?php
    $boxes_js = [];
    $box_res = $conn->query("SELECT box_id, box_name FROM boxes ORDER BY box_id");
    if ($box_res) { while ($row = $box_res->fetch_assoc()) $boxes_js[$row['box_id']] = $row; }
    echo json_encode($boxes_js);
?>;
const boxPrices = { 1: 6, 2: 5, 3: 6, 4: 5 };
const vesselSizeId = <?= $vesselSizeId ?>;

let currentProduct = null;
let currentBasePrice = 0;
let currentSizeId = null;
let selectedBoxId = null;
let boxPrice = 0;
let quantity = 1;
let currentImageIndex = 0;
let imageUrls = [];

document.getElementById('productModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function getImageUrl(product) {
    if (product.image_url && product.image_url !== 'https://placehold.co/600x600?text=No+Image') return product.image_url;
    if (product.image_base64 && product.image_base64 !== 'https://placehold.co/600x600?text=No+Image') return product.image_base64;
    return 'https://placehold.co/600x600?text=No+Image';
}

function showSuccessMessage(msg) {
    const d = document.createElement('div');
    d.className = 'cart-success';
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(function() { d.remove(); }, 3000);
}

function generateSKU(sizeId, colorId, fragranceId, boxId) {
    const containerMap = { 2: 'D', 4: 'C' };
    const container = containerMap[sizeId] || 'C';

    const colorMap = {
        1: '03', 2: '03', 3: '15', 4: '12',
        5: '01', 6: '09', 7: '16', 8: '13',
        9: '08', 10: '07'
    };
    const colorCode = colorMap[colorId] || '01';

    const fragranceMap = {
        1: '02', 2: '05', 3: '08', 4: '01', 5: '13',
        6: '09', 7: '11', 8: '14', 9: '10', 10: '06', 11: '04'
    };
    const fragranceCode = fragranceMap[fragranceId] || '01';

    const boxMap = { 1: 'B01W', 2: 'B02W', 3: 'B01B', 4: 'B02B' };
    const boxCode = (boxId && boxMap[boxId]) ? boxMap[boxId] : 'B01W';

    return container + colorCode + fragranceCode + boxCode;
}

function setMainImage(imageUrl, index) {
    document.getElementById('modalMainImage').src = imageUrl;
    currentImageIndex = index;
    
    // Update active state on thumbnails
    document.querySelectorAll('.thumbnail-item').forEach((el, i) => {
        if (i === index) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}

function openModal(productData) {
    currentProduct = productData;
    currentSizeId = vesselSizeId;
    selectedBoxId = null;
    boxPrice = 0;
    quantity = 1;

    if (productData.size_prices && productData.size_prices[vesselSizeId]) {
        currentBasePrice = productData.size_prices[vesselSizeId];
    } else if (productData.size_prices && Object.keys(productData.size_prices).length) {
        const first = Object.keys(productData.size_prices)[0];
        currentSizeId = parseInt(first);
        currentBasePrice = productData.size_prices[first];
    } else {
        currentBasePrice = 29;
    }

    document.getElementById('modalTitle').innerText = productData.product_name;
    
    // Build image array
    imageUrls = [];
    const candleImg = getImageUrl(productData);
    imageUrls.push(candleImg);
    
    if (productData.fragrance_image && productData.fragrance_image !== '') {
        imageUrls.push(productData.fragrance_image);
    }
    
    // Set initial image
    setMainImage(imageUrls[0], 0);
    
    // Render thumbnails
    renderThumbnails();
    
    if (productData.sku) {
        document.getElementById('modalSKU').innerText = productData.sku;
    } else {
        const colorId = productData.color_id && productData.color_id[0] ? productData.color_id[0] : 5;
        const sku = generateSKU(currentSizeId, colorId, productData.fragrance_id, null);
        document.getElementById('modalSKU').innerText = sku;
    }

    // Set size value
    const sizeLabel = currentSizeId == 4 ? 'Vessel C' : 'Vessel D';
    const sizeDetails = currentSizeId == 4 ? '3" · 45 HRS' : '3.5" · 60 HRS';
    document.getElementById('modalSizeValue').innerText = sizeLabel + ' (' + sizeDetails + ')';

    let colorNameText = 'Standard';
    if (productData.color_id && productData.color_id.length > 0) {
        const c = colorsData[productData.color_id[0]];
        if (c) colorNameText = c.color_name;
    }
    document.getElementById('modalColorValue').innerText = colorNameText.replace(/Â·|Â/g, '·').trim();
    document.getElementById('modalFragranceValue').innerText = productData.fragrance_name || 'Luxury Scent';
    document.getElementById('modalDesc').innerText = productData.description || 'Artisanal candle in a luminous ' + colorNameText.toLowerCase() + ' vessel.';

    renderBoxOptions();
    document.getElementById('stockWarning').innerHTML = '';
    updateDisplay();
    document.getElementById('productModal').style.display = 'flex';
}

function renderThumbnails() {
    const strip = document.getElementById('thumbnailStrip');
    strip.innerHTML = '';
    
    const labels = ['Candle', 'Fragrance'];
    
    imageUrls.forEach((url, index) => {
        const thumb = document.createElement('img');
        thumb.className = 'thumbnail-item' + (index === 0 ? ' active' : '');
        thumb.src = url;
        thumb.alt = labels[index] || 'Image ' + (index + 1);
        thumb.title = labels[index] || 'View image';
        thumb.onerror = function() { this.style.display = 'none'; };
        thumb.onclick = function() {
            setMainImage(url, index);
        };
        strip.appendChild(thumb);
    });
}

function renderBoxOptions() {
    const c = document.getElementById('boxOptions');
    c.innerHTML = '';
    const ids = Object.keys(boxesData).sort(function(a,b){return parseInt(a)-parseInt(b);});
    if (ids.length) {
        ids.forEach(function(bid) {
            const box = boxesData[bid]; if (!box) return;
            const price = boxPrices[bid] || 0;
            const tile = document.createElement('div');
            tile.className = 'option-tile' + (selectedBoxId===parseInt(bid)?' active':'');
            tile.innerHTML = '<span class="tile-title">' + escapeHtml(box.box_name) + '</span><span class="tile-sub">+ $' + price + '</span>';
            tile.onclick = (function(bid, price) { return function() { selectBox(tile, bid, price); }; })(parseInt(bid), price);
            c.appendChild(tile);
        });
    }
}

function selectBox(el, boxId, price) {
    if (selectedBoxId === boxId) {
        selectedBoxId = null; boxPrice = 0; el.classList.remove('active');
    } else {
        var tiles = document.querySelectorAll('#boxOptions .option-tile');
        for (var i = 0; i < tiles.length; i++) tiles[i].classList.remove('active');
        el.classList.add('active'); selectedBoxId = boxId; boxPrice = price;
    }
    updateDisplay();
    
    if (currentProduct) {
        const colorId = currentProduct.color_id && currentProduct.color_id[0] ? currentProduct.color_id[0] : 5;
        const newSKU = generateSKU(currentSizeId, colorId, currentProduct.fragrance_id, selectedBoxId);
        document.getElementById('modalSKU').innerText = newSKU;
    }
}

function updateModalQty(delta) {
    const newQty = quantity + delta;
    if (newQty >= 1) {
        quantity = newQty;
        updateDisplay();
        document.getElementById('stockWarning').innerHTML = '';
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

document.getElementById('addToCartAction').onclick = function() {
    if (!currentProduct) return;

    const sizeLabel = currentSizeId == 4 ? 'Vessel C' : 'Vessel D';
    const sizeDetails = currentSizeId == 4 ? '3" · 45 HRS' : '3.5" · 60 HRS';
    const selectedSizeName = sizeLabel + ' (' + sizeDetails + ')';

    let boxName = null;
    if (selectedBoxId && boxesData[selectedBoxId]) boxName = boxesData[selectedBoxId].box_name;

    const colorId = currentProduct.color_id && currentProduct.color_id[0] ? currentProduct.color_id[0] : 5;
    const sku = generateSKU(currentSizeId, colorId, currentProduct.fragrance_id, selectedBoxId);
    
    let productDisplayName = currentProduct.product_name;
    if (boxName) productDisplayName += ' + ' + boxName;

    waitForLVBCart(function() {
        if (typeof LVBCart !== 'undefined' && LVBCart.addItem) {
            LVBCart.addItem({
                id: sku,
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
            showSuccessMessage('Error: Cart not loaded. Please refresh the page.');
        }
    });
};

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
<?php endif; ?>

</body>
</html>