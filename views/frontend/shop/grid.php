<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../../db.php';

// Get vessel, filter parameters, and target product ID from URL
$selectedVessel    = isset($_GET['vessel']) ? strtolower($_GET['vessel']) : '';
$targetProductId   = isset($_GET['product_id']) ? (int)$_GET['product_id'] : (isset($_GET['product']) ? (int)$_GET['product'] : 0);
$selectedColor     = isset($_GET['color']) ? (int)$_GET['color'] : 0;
$selectedFragrance = isset($_GET['fragrance']) ? (int)$_GET['fragrance'] : 0;

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
$validVessels = array_keys($categoriesMap);
if (empty($validVessels)) {
    $validVessels = ['c', 'd', 'e'];
}

// Auto-detect vessel if product_id is provided without vessel
if ($targetProductId > 0 && (empty($selectedVessel) || !in_array($selectedVessel, $validVessels))) {
    $vCheck = $conn->query("SELECT size_id, wick_type FROM products WHERE product_id = " . $targetProductId);
    if ($vCheck && $vRow = $vCheck->fetch_assoc()) {
        $p_sizes = json_decode($vRow['size_id'], true) ?: [];
        foreach ($categoriesMap as $sku => $cat) {
            if (in_array($cat['id'], $p_sizes)) {
                $selectedVessel = $sku;
                break;
            }
        }
    }
}

// If no vessel selected or invalid, show the vessel selection page
$showVesselSelection = empty($selectedVessel) || !in_array($selectedVessel, $validVessels);

// If vessel is selected, fetch products
if (!$showVesselSelection) {
    $vesselSizeId = isset($categoriesMap[$selectedVessel]) ? (int)$categoriesMap[$selectedVessel]['id'] : 6;
    $catWick = isset($categoriesMap[$selectedVessel]) ? strtolower($categoriesMap[$selectedVessel]['wick_type']) : 'single';
    $wickType = strpos($catWick, 'double') !== false ? 'double' : (strpos($catWick, 'triple') !== false ? 'triple' : 'single');

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
        $frag_result = $conn->query("SELECT fragrance_id, fragrance_name, fragrance_image, fragrance_description FROM fragrances");
        $fragrance_details = [];
        $fragrance_descriptions = [];
        if ($frag_result) {
            while ($row = $frag_result->fetch_assoc()) {
                $fragrances[$row['fragrance_id']] = $row['fragrance_name'];
                $fragrance_details[$row['fragrance_id']] = $row['fragrance_image'] ?? '';
                $fragrance_descriptions[$row['fragrance_id']] = $row['fragrance_description'] ?? '';
            }
        }

        $color_result = $conn->query("SELECT color_id, color_name, color_hex, double_wick_image FROM colors");
        if ($color_result) {
            while ($row = $color_result->fetch_assoc()) {
                $colors[$row['color_id']] = $row;
            }
        }

        $size_result = $conn->query("SELECT id AS size_id, category_name AS size_name, dimensions_subtitle AS size_details FROM categories ORDER BY sort_order ASC, id ASC");
        if ($size_result) {
            while ($row = $size_result->fetch_assoc()) {
                $sizes[$row['size_id']] = $row;
            }
        }

        $boxes = [];
        $box_result = $conn->query("SELECT box_id, sku, vessel_code, box_name, box_price FROM boxes WHERE status = 1 ORDER BY box_id");
        if ($box_result) {
            while ($row = $box_result->fetch_assoc()) {
                $boxes[$row['box_id']] = $row;
            }
        }

        while ($row = $result->fetch_assoc()) {
            if (is_string($row['size_prices']) && is_array(json_decode($row['size_prices'], true))) {
                $row['size_prices'] = json_decode($row['size_prices'], true);
            }
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
            if (!empty($image_name)) {
                if (strpos($image_name, 'http') === 0) {
                    $image_path = $image_name;
                } elseif (strpos($image_name, 'uploads/') !== false) {
                    $image_path = base_url('/public/' . ltrim($image_name, '/'));
                } else {
                    $image_path = $base . '/img/' . ltrim($image_name, '/');
                }
            }
            $row['image_url'] = $image_path ?: 'https://placehold.co/600x600?text=No+Image';

            $f_dec = json_decode($row['fragrance_id'], true);
            if (is_array($f_dec)) {
                $row['fragrance_ids'] = array_map('intval', $f_dec);
            } elseif (is_numeric($row['fragrance_id']) && $row['fragrance_id'] > 0) {
                $row['fragrance_ids'] = [(int)$row['fragrance_id']];
            } else {
                $row['fragrance_ids'] = [];
            }

            $fi_dec = json_decode($row['fragrance_images'], true);
            $row['fragrance_images_map'] = is_array($fi_dec) ? $fi_dec : [];

            $products[] = $row;
        }
    }
}

$box_prices = [];
if (!empty($boxes)) {
    foreach ($boxes as $bId => $bInfo) {
        $box_prices[$bId] = (float)($bInfo['box_price'] ?? 6);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LVB Shop — Laguna Vibe Beach</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="<?php echo $base; ?>/views/frontend/home/cart.js"></script>
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
        display: flex;
        justify-content: center;
        gap: 32px;
        max-width: 1200px;
        margin: 0 auto;
    }

    @media (max-width: 768px) {
        .vessel-selection {
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .vessel-card {
            max-width: 100% !important;
        }
    }

    .vessel-card {
        flex: 1;
        max-width: 380px;
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
        object-fit: contain;
        object-position: center;
        background: #faf9f6;
        padding: 16px;
        box-sizing: border-box;
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

    /* ── ELEGANT FULL-WIDTH SHOP FILTER BAR ── */
    .shop-filter-bar {
        display: flex;
        align-items: flex-end;
        gap: 20px;
        background: #ffffff;
        padding: 20px 24px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        margin-bottom: 32px;
        box-shadow: 0 4px 20px -2px rgba(0, 75, 102, 0.05);
        width: 100%;
        box-sizing: border-box;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1 1 0;
        min-width: 220px;
    }

    .filter-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .shop-filter-select {
        width: 100%;
        height: 44px;
        padding: 0 38px 0 16px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        font-size: 14px;
        font-weight: 500;
        color: #0f172a;
        cursor: pointer;
        outline: none;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        box-sizing: border-box;
    }

    .shop-filter-select:hover,
    .shop-filter-select:focus {
        border-color: #004b66;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(0, 75, 102, 0.1);
    }

    .filter-status-col {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-left: auto;
        height: 44px;
        flex-shrink: 0;
    }

    .filter-count {
        font-size: 13px;
        color: #334155;
        font-weight: 600;
        background: #f1f5f9;
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .reset-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        background: #fef2f2;
        color: #dc2626;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid #fecaca;
        white-space: nowrap;
    }

    .reset-filter-btn:hover {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }

    @media (max-width: 900px) {
        .shop-filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-status-col {
            margin-left: 0;
            justify-content: space-between;
        }
    }

    /* ── SHOP PAGINATION STYLES ── */
    .pagination-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin: 40px 0 60px 0;
        width: 100%;
    }

    .pagination-buttons {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 42px;
        padding: 0 14px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .page-btn:hover {
        border-color: #004b66;
        color: #004b66;
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .page-btn.active {
        background: #004b66;
        color: #ffffff;
        border-color: #004b66;
        box-shadow: 0 4px 12px rgba(0, 75, 102, 0.25);
    }

    .page-btn.disabled {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
        background: #f1f5f9;
        border-color: #e2e8f0;
    }

    .pagination-info {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }

    .product-grid,
    .products-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 28px;
        margin-bottom: 80px;
    }

    @media (max-width: 640px) {
        .product-grid,
        .products-grid { grid-template-columns: 1fr; gap: 20px; }
    }

    .product-card {
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e8eef2;
    }
    .product-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,75,102,0.08); }
    .product-image { width: 100%; aspect-ratio: 1/1; object-fit: cover; object-position: bottom; background: #faf9f6; display: block; }
    .product-info { padding: 16px 14px 14px; }
    .product-name {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 500;
        font-size: 20px;
        letter-spacing: 0.5px;
        color: #1a2a3a;
        margin-bottom: 4px;
    }
    .product-detail-row { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
    .product-fragrance { font-size: 11px; font-weight: 600; color: #004b66; background: #e0f2fe; padding: 3px 8px; border-radius: 12px; }
    .product-price { font-size: 14px; font-weight: 600; color: #004b66; }

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
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 24px;
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

    /* Floating glass thumbnail strip */
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

    /* Selected Scent Card on bottom left */
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

    /* Custom smooth scrollbar for right modal pane */
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
        font-size: 20px;
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

    /* Specification Grid */
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
        .modal-left  { width: 100%; flex: none; height: 340px; border-right: none; border-bottom: 1px solid #e2e8f0; }
        .modal-right { width: 100%; flex: none; padding: 24px 20px 28px; }
        .thumbnail-item { width: 44px; height: 44px; }
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
        <?php
        $catsQuery = $conn->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC");
        if ($catsQuery && $catsQuery->num_rows > 0):
            while ($catRow = $catsQuery->fetch_assoc()):
                $vesselCode = strtolower($catRow['sku'] ?? '');
                if (empty($vesselCode)) continue;
                
                $imageSrc = !empty($catRow['image']) ? base_url('/' . ltrim($catRow['image'], '/')) : '';
                $fallbackText = urlencode($catRow['category_name']);
                
                $wickText = htmlspecialchars($catRow['wick_type'] ?? '');
                $wickIcon = '🕯️';
                if (strpos(strtolower($wickText), 'double') !== false) {
                    $wickIcon = '🕯️🕯️';
                } elseif (strpos(strtolower($wickText), 'triple') !== false) {
                    $wickIcon = '🕯️🕯️🕯️';
                }
        ?>
        <a href="<?= base_url('/shop?vessel=' . urlencode($vesselCode)) ?>" class="vessel-card">
            <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($catRow['category_name']) ?> - <?= $wickText ?>" onerror="this.src='https://placehold.co/600x600?text=<?= $fallbackText ?>'">
            <div class="vessel-card-content">
                <h3><?= htmlspecialchars($catRow['category_name']) ?></h3>
                <div class="vessel-subtitle"><?= htmlspecialchars($catRow['dimensions_subtitle'] ?? '') ?></div>
                <div class="vessel-details">
                    <?php if (!empty($catRow['dimensions_subtitle'])): ?>
                        <?php 
                        $dims = $catRow['dimensions_subtitle'];
                        if (preg_match('/(\d+(?:\.\d+)?(?:["\']|inch)?\s*[×x]\s*\d+(?:\.\d+)?(?:["\']|inch)?)/i', $dims, $m)) {
                            $dims = $m[1];
                        }
                        ?>
                        <span class="vessel-detail-item"><?= htmlspecialchars($dims) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($catRow['burn_time_badge'])): ?>
                        <span class="vessel-detail-item"><?= htmlspecialchars($catRow['burn_time_badge']) ?></span>
                    <?php endif; ?>
                </div>
                <span class="vessel-wick"><?= $wickIcon ?> <?= $wickText ?></span>
                <div>
                    <span class="shop-now-btn">Shop Collection →</span>
                </div>
            </div>
        </a>
        <?php endwhile; endif; ?>
    </div>

<?php else: ?>
    <!-- PRODUCT LISTING PAGE -->
    <?php 
    $currentCategory = $categoriesMap[strtolower($selectedVessel)] ?? null;
    $vesselLabel = $currentCategory ? $currentCategory['category_name'] : 'Vessel ' . strtoupper($selectedVessel);
    $wickLabel = $currentCategory ? $currentCategory['wick_type'] : '';

    // Group products into main Color Variation Cards
    $colorVariations = [];
    foreach ($products as $p) {
        $cIds = is_array($p['color_id']) ? $p['color_id'] : [$p['color_id']];
        $primaryColorId = !empty($cIds) ? (int)$cIds[0] : 0;

        if (!isset($colorVariations[$primaryColorId])) {
            $colorName = isset($colors[$primaryColorId]) ? $colors[$primaryColorId]['color_name'] : 'Standard';
            $colorHex  = isset($colors[$primaryColorId]) ? $colors[$primaryColorId]['color_hex']  : '#f1f5f9';
            
            // Prefer custom uploaded product image over default vessel render
            $varImg = $p['image_url'];
            if ((empty($p['image']) || strpos($p['image'], 'uploads/products/') === false) && !empty($colors[$primaryColorId]['double_wick_image'])) {
                $dbImg = $colors[$primaryColorId]['double_wick_image'];
                $varImg = base_url('/' . ltrim($dbImg, '/'));
            }

            $colorVariations[$primaryColorId] = [
                'variation_id' => 'vessel_' . $selectedVessel . '_color_' . $primaryColorId,
                'color_id' => $primaryColorId,
                'color_name' => $colorName,
                'color_hex' => $colorHex,
                'vessel_name' => $vesselLabel,
                'wick_type' => $wickType,
                'image_url' => $varImg,
                'price' => is_numeric($p['size_prices']) ? (float)$p['size_prices'] : (is_array($p['size_prices']) ? (float)reset($p['size_prices']) : 35.00),
                'items' => []
            ];
        }

        $f_ids = !empty($p['fragrance_ids']) ? $p['fragrance_ids'] : [0];
        foreach ($f_ids as $fid) {
            $item = $p;
            $item['fragrance_id'] = $fid;
            $item['fragrance_name'] = $fragrances[$fid] ?? 'Luxury Candle';
            
            // Fragrance master image from fragrances table
            $item['fragrance_image'] = '';
            if ($fid > 0 && !empty($fragrance_details[$fid])) {
                $fImg = trim($fragrance_details[$fid]);
                if (strpos($fImg, 'http') === 0) {
                    $item['fragrance_image'] = $fImg;
                } elseif (strpos($fImg, 'uploads/') !== false) {
                    $item['fragrance_image'] = base_url('/public/' . ltrim($fImg, '/'));
                } else {
                    $item['fragrance_image'] = base_url('/' . ltrim($fImg, '/'));
                }
            }

            // Custom picture uploaded for this specific fragrance variation
            $f_key = (string)$fid;
            if (isset($p['fragrance_images_map'][$f_key]) && !empty($p['fragrance_images_map'][$f_key])) {
                $custom_img = trim($p['fragrance_images_map'][$f_key]);
                if (strpos($custom_img, 'http') === 0) {
                    $item['image'] = $custom_img;
                    $item['image_url'] = $custom_img;
                } else {
                    $item['image'] = 'uploads/products/' . basename($custom_img);
                    $item['image_url'] = base_url('/public/uploads/products/' . basename($custom_img));
                }
                $item['fragrance_image'] = $item['image_url'];
            }

            // Dynamic variation SKU: Vessel SKU + Color SKU + Fragrance SKU
            $vessel_letter = strtoupper($selectedVessel);
            $color_num = sprintf('%02d', $primaryColorId);
            $frag_num = sprintf('%02d', $fid);
            $item['sku'] = $vessel_letter . $color_num . $frag_num;

            $colorVariations[$primaryColorId]['items'][] = $item;
        }
    }
    ?>
    
    <div class="page-header">
        <div class="page-header-left">
            <h1><?= htmlspecialchars($vesselLabel) ?></h1>
            <div class="subtitle"><?= htmlspecialchars($wickLabel) ?> Collection</div>
        </div>
        <a href="<?php echo $base; ?>/shop" class="back-btn">← Back to Vessels</a>
    </div>



    <!-- VARIATION PRODUCTS GRID -->
    <div class="product-grid">
        <div class="no-products-message" id="filterNoProductsMessage" style="<?= empty($colorVariations) ? 'display:block;' : 'display:none;' ?>">
            <p>No <?= strtolower($wickLabel) ?> candles match your selected filters</p>
            <div style="font-size: 14px; margin-top: 8px;">Try clearing or changing your color or fragrance filters</div>
        </div>
        <?php if (!empty($colorVariations)): ?>
            <?php foreach ($colorVariations as $var): ?>
                <?php 
                $fragCount = count($var['items']);
                $cIdsStr = (string)$var['color_id'];
                $firstItem = $var['items'][0];
                ?>
                <div class="product-card"
                     data-color-ids="<?= htmlspecialchars($cIdsStr) ?>"
                     onclick="openVariationModal(<?= htmlspecialchars(json_encode($var)) ?>)">
                    <img class="product-image" src="<?= htmlspecialchars($var['image_url']) ?>" alt="<?= htmlspecialchars($var['vessel_name'] . ' - ' . $var['color_name']) ?>">
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($var['vessel_name'] . ' — ' . $var['color_name']) ?></div>
                        <div class="product-detail-row">
                            <span class="product-fragrance">🌸 <?= $fragCount ?> Fragrances Available</span>
                            <span class="product-price">$<?= number_format($var['price'], 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- PAGINATION CONTAINER -->
    <div class="pagination-container" id="shopPaginationContainer" style="display: none;">
        <div class="pagination-buttons" id="paginationButtons"></div>
        <div class="pagination-info" id="paginationInfo"></div>
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
                <div class="thumbnail-strip" id="thumbnailStrip">
                    <!-- Thumbnails will be rendered here -->
                </div>
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
            
            <!-- INTERACTIVE FRAGRANCE VARIATION SELECTOR -->
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

<script>
let currentVariation = null;
let currentProduct = null;
let currentSizeId = null;
let currentBasePrice = 35.00;
let selectedBoxId = null;
let boxPrice = 0;
let quantity = 1;
let imageUrls = [];
let vesselSizeId = <?= $vesselSizeId ?>;
let selectedVessel = <?= json_encode($selectedVessel) ?>;
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
    const modalLeft = document.querySelector('.modal-left');
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
    
    // Perceived brightness formula (0 - 255)
    let brightness = (r * 299 + g * 587 + b * 114) / 1000;
    
    const thumbStrip = document.getElementById('thumbnailStrip');
    
    if (brightness < 100) {
        // Dark color (e.g. Black Matte #1B222E, Charcoal Grey #3A444C, Smoky Grey #1E293B)
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
        // Very light / white color (e.g. White Frost #F4F1EA, Silver #E2E8F0)
        modalLeft.style.background = `radial-gradient(circle at center, #ffffff 0%, rgb(${r}, ${g}, ${b}) 100%)`;
        modalLeft.style.borderRight = '1px solid #e2e8f0';
        
        if (thumbStrip) {
            thumbStrip.style.background = 'rgba(255, 255, 255, 0.85)';
            thumbStrip.style.borderTop = '1px solid rgba(226, 232, 240, 0.8)';
        }
    } else {
        // Midtone / Vibrant color (e.g. Blush Pink #E58879, Blue Frost #23499D, Mocha #6B4E41, Purple #A68DAF)
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

    // Dynamically update popup backdrop theme matching the database color HEX
    let hexColor = variationData.color_hex || (colorsData && colorsData[variationData.color_id] ? colorsData[variationData.color_id].color_hex : null);
    applyDynamicModalTheme(hexColor);

    // Default to target fragrance or first available fragrance
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
    if (item.image && typeof item.image === 'string' && item.image.trim() !== '') {
        let img = item.image.trim();
        if (img.startsWith('http')) return img;
        if (img.includes('uploads/')) return '<?= base_url('/public/') ?>' + img.replace(/^\/+/, '');
        return '<?= $base ?>/img/' + img.replace(/^\/+/, '');
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
            <div style="font-size: 12px; font-weight: 600; color: #004b66;">$${parseFloat(item.size_prices || 35).toFixed(2)}</div>
        `;

        tile.onclick = function() {
            document.querySelectorAll('.fragrance-option-tile').forEach(el => el.classList.remove('active'));
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
        if (pSizes.length > 0) {
            currentSizeId = parseInt(pSizes[0]);
        }
    }
    if (!currentSizeId || !sizesData[currentSizeId]) {
        currentSizeId = vesselSizeId;
    }

    if (typeof productData.size_prices === 'number' || (typeof productData.size_prices === 'string' && !isNaN(parseFloat(productData.size_prices)))) {
        currentBasePrice = parseFloat(productData.size_prices);
    } else if (productData.size_prices && productData.size_prices[currentSizeId]) {
        currentBasePrice = parseFloat(productData.size_prices[currentSizeId]);
    } else {
        currentBasePrice = 35.00;
    }

    document.getElementById('modalTitle').innerText = productData.product_name || (productData.fragrance_name + ' Candle');
    
    // Build image array: Product variation candle image ONLY (no fragrance table image)
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
    if (productData.color_id && productData.color_id.length > 0) {
        const c = colorsData[productData.color_id[0]];
        if (c) colorNameText = c.color_name;
    }

    document.getElementById('modalColorValue').innerText = colorNameText.replace(/Â·|Â/g, '·').trim();
    document.getElementById('modalFragranceValue').innerText = productData.fragrance_name || 'Luxury Scent';
    document.getElementById('modalDesc').innerText = productData.description || ('Artisanal candle in a luminous ' + colorNameText.toLowerCase() + ' vessel.');

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
    document.querySelectorAll('.thumbnail-item').forEach((el, i) => {
        if (i === index) el.classList.add('active');
        else el.classList.remove('active');
    });
}

function getImageUrl(product) {
    if (product.image_url && product.image_url !== 'https://placehold.co/600x600?text=No+Image') return product.image_url;
    return 'https://placehold.co/600x600?text=No+Image';
}

function getProductVesselCode(product) {
    if (!product) return 'C';
    if (product.sku && typeof product.sku === 'string') {
        const firstLetter = product.sku.trim().charAt(0).toUpperCase();
        if (['C', 'D', 'E'].includes(firstLetter)) {
            return firstLetter;
        }
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
        c.innerHTML = '<div style="grid-column: 1 / -1; padding: 12px 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b; font-size: 13px; text-align: center; font-weight: 500;">No packaging box options available for Vessel ' + escapeHtml(vesselCode) + '</div>';
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
        const fragranceName = currentProduct.fragrance_name || 'Luxury Scent';
        let productDisplayName = currentProduct.product_name || (fragranceName + ' Candle');
        if (boxName) productDisplayName += ' + ' + boxName;

        const uniqueItemId = 'prod_' + (currentProduct.product_id || 'item') + '_frag' + (currentProduct.fragrance_id || '0') + (selectedBoxId ? '_box' + selectedBoxId : '') + '_' + sku;

        waitForLVBCart(function() {
            if (typeof LVBCart !== 'undefined' && LVBCart.addItem) {
                LVBCart.addItem({
                    id: uniqueItemId,
                    sku: sku,
                    name: productDisplayName,
                    scent: fragranceName,
                    price: currentBasePrice + boxPrice,
                    image: getImageUrl(currentProduct),
                    qty: quantity,
                    product_id: currentProduct.product_id,
                    size_id: currentSizeId,
                    size_name: selectedSizeName,
                    box_id: selectedBoxId,
                    box_name: boxName,
                    fragrance_id: currentProduct.fragrance_id,
                    fragrance_name: fragranceName
                });
                closeModal();
                showSuccessMessage('Added ' + quantity + '× ' + fragranceName + ' to cart');
            } else {
                console.error('LVBCart not available');
                showSuccessMessage('Error: Cart not loaded. Please refresh the page.');
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

function applyShopFilters() {
    const selectedColor = document.getElementById('filterColor').value;
    const selectedFrag = document.getElementById('filterFragrance').value;

    const cards = document.querySelectorAll('.product-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const cIds = card.getAttribute('data-color-ids') || '';
        let colorMatch = true;
        if (selectedColor && !cIds.split(',').includes(selectedColor)) {
            colorMatch = false;
        }

        if (colorMatch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const noMsg = document.getElementById('filterNoProductsMessage');
    if (noMsg) {
        noMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    const countDisplay = document.getElementById('filterCountDisplay');
    if (countDisplay) {
        countDisplay.innerText = `Showing ${visibleCount} ${visibleCount === 1 ? 'variation' : 'variations'}`;
    }
}
</script>
<?php endif; ?>

</body>
</html>