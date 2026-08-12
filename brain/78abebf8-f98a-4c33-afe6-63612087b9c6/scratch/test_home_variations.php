<?php
require_once 'db.php';

function base_url($path = '') {
    return 'http://localhost/laguna/' . ltrim($path, '/');
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

// 4. Fetch Products
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
                $row['image_url'] = base_url('/public/' . ltrim($dbImg, '/'));
            } else {
                $row['image_url'] = base_url('/' . ltrim($dbImg, '/'));
            }
        } else {
            $row['image_url'] = 'https://placehold.co/600x600?text=No+Image';
        }

        $products[] = $row;
    }
}

// Group into Color Variations
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
            $varImg = base_url('/' . ltrim($dbImg, '/'));
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
            'image_url' => $varImg,
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
                $item['image_url'] = base_url('/public/uploads/products/' . basename($custom_img));
            }
        }

        $item['sku'] = $vesselCode . sprintf('%02d', $primaryColorId) . sprintf('%02d', $fid);
        $colorVariations[$varKey]['items'][] = $item;
    }
}

echo "Total Variations Built: " . count($colorVariations) . "\n";
foreach (array_slice($colorVariations, 0, 6) as $key => $var) {
    echo "Variation [$key]: " . $var['color_name'] . " | " . $var['vessel_name'] . " | $" . $var['price'] . " | Fragrances: " . count($var['items']) . " | Img: " . $var['image_url'] . "\n";
}
