<?php
/**
 * collection.php — THE COLLECTION Section with Vibrant Multi-Color Candle Assortment
 */
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}

require_once __DIR__ . '/../../../db.php';

// Vibrant multi-color candle images from slider assets
$vibrantColorPool = [
    [
        'name' => 'Azure Frost',
        'desc' => 'OCEAN BREEZE',
        'price' => '$29',
        'image' => 'assets/slider/00_08_BLUR FROST.png',
        'vessel' => 'c'
    ],
    [
        'name' => 'Purple Frost',
        'desc' => 'LAVENDER FIELDS',
        'price' => '$29',
        'image' => 'assets/slider/00_09_PURPLE FROST.png',
        'vessel' => 'c'
    ],
    [
        'name' => 'Emerald Frost',
        'desc' => 'WILD LEMONGRASS',
        'price' => '$29',
        'image' => 'assets/slider/00_17_01 GREEN FROST TEMPLET.png',
        'vessel' => 'c'
    ],
    [
        'name' => 'Ruby Red Frost',
        'desc' => 'MAHOGANY WOODS',
        'price' => '$29',
        'image' => 'assets/slider/00_18_RED FROST TEMPLET.png',
        'vessel' => 'c'
    ],
    [
        'name' => 'Teal Horizon',
        'desc' => 'SEA SALT ATTRACTION',
        'price' => '$35',
        'image' => 'assets/slider/C0811.png',
        'vessel' => 'd'
    ],
    [
        'name' => 'Golden Amber',
        'desc' => 'AMBER MUSK',
        'price' => '$35',
        'image' => 'assets/slider/D1205.png',
        'vessel' => 'd'
    ],
    [
        'name' => 'Rose Quartz',
        'desc' => 'ROSE & OUD',
        'price' => '$35',
        'image' => 'assets/slider/D1604.png',
        'vessel' => 'd'
    ],
    [
        'name' => 'Deep Emerald',
        'desc' => 'PINE & MINT',
        'price' => '$29',
        'image' => 'assets/slider/E0208.png',
        'vessel' => 'c'
    ],
    [
        'name' => 'Midnight Slate',
        'desc' => 'MOONLIT WATERS',
        'price' => '$35',
        'image' => 'assets/slider/H50-3 VESSEL INSIDE.png',
        'vessel' => 'd'
    ],
    [
        'name' => 'Cyan Glow',
        'desc' => 'TIDAL WAVE',
        'price' => '$29',
        'image' => 'assets/slider/C0304.png',
        'vessel' => 'c'
    ]
];

// Shuffle color pool to show different multi-colored candles
shuffle($vibrantColorPool);

$sql = "
    SELECT p.*, f.fragrance_name 
    FROM products p 
    LEFT JOIN fragrances f ON (
        p.fragrance_id = f.fragrance_id 
        OR FIND_IN_SET(f.fragrance_id, REPLACE(REPLACE(p.fragrance_id, '[', ''), ']', ''))
    )
    GROUP BY p.product_id 
    ORDER BY RAND() 
    LIMIT 6
";

$res = $conn->query($sql);
$collectionProducts = [];
$index = 0;

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $sp = !empty($row['size_prices']) ? json_decode($row['size_prices'], true) : [];
        $displayPrice = '$29';
        if (is_array($sp) && !empty($sp)) {
            $firstVal = reset($sp);
            if (is_numeric($firstVal)) {
                $displayPrice = '$' . number_format((float)$firstVal, 0);
            }
        }

        // Assign a distinct colorful candle image from the shuffled pool
        $colorItem = $vibrantColorPool[$index % count($vibrantColorPool)];
        $imagePath = $base . '/' . $colorItem['image'];

        // DB image resolution if available and unique
        $dbImg = $row['image'] ?? '';
        if (!empty($dbImg) && strpos($dbImg, 'placehold') === false && strpos($dbImg, 'lemonglass') === false) {
            if (strpos($dbImg, 'http') === 0) {
                $imagePath = $dbImg;
            } elseif (strpos($dbImg, 'uploads/') !== false) {
                $imagePath = $base . '/public/' . ltrim($dbImg, '/');
            } elseif (strpos($dbImg, 'assets/') !== false) {
                $imagePath = $base . '/' . ltrim($dbImg, '/');
            }
        }

        $vesselCode = (isset($row['wick_type']) && $row['wick_type'] === 'double') ? 'd' : 'c';

        $collectionProducts[] = [
            'id'             => (int)$row['product_id'],
            'product_name'   => $row['product_name'],
            'fragrance_name' => !empty($row['fragrance_name']) ? $row['fragrance_name'] : $colorItem['desc'],
            'price'          => $displayPrice,
            'image'          => $imagePath,
            'url'            => $base . '/shop?vessel=' . $vesselCode . '&product_id=' . $row['product_id']
        ];
        $index++;
    }
}

// Fallback logic to ensure 6 distinct colorful items
if (count($collectionProducts) < 6) {
    while (count($collectionProducts) < 6 && $index < count($vibrantColorPool)) {
        $colorItem = $vibrantColorPool[$index % count($vibrantColorPool)];
        $collectionProducts[] = [
            'id'             => 100 + $index,
            'product_name'   => $colorItem['name'],
            'fragrance_name' => $colorItem['desc'],
            'price'          => $colorItem['price'],
            'image'          => $base . '/' . $colorItem['image'],
            'url'            => $base . '/shop?vessel=' . $colorItem['vessel']
        ];
        $index++;
    }
}
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
    <?php foreach ($collectionProducts as $p): ?>
      <a href="<?php echo $p['url']; ?>" class="lvc-card" style="text-decoration:none;">
        <div class="lvc-img-container">
          <img src="<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>" onerror="this.src='https://placehold.co/400x500/14222b/FFFFFF?text=LVB+Atelier'">
        </div>
        <div class="lvc-info">
          <div class="lvc-text">
            <span class="lvc-p-name"><?php echo htmlspecialchars($p['product_name']); ?></span>
            <span class="lvc-p-desc"><?php echo htmlspecialchars(strtoupper($p['fragrance_name'])); ?></span>
          </div>
          <span class="lvc-price"><?php echo $p['price']; ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<style>
/* Scoped styles - no global leaks */
.lvc-collection {
  all: initial;
  display: block;
  width: 100%;
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
  max-width: 94%;
  margin: 0 auto;
}

.lvc-collection .lvc-card {
  flex: 0 0 calc(16.66% - 12px);
  background: white;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: flex;
  flex-direction: column;
}

.lvc-collection .lvc-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
}

.lvc-collection .lvc-img-container {
  aspect-ratio: 1 / 1.1;
  background: #f0f4f6;
  overflow: hidden;
}

.lvc-collection .lvc-img-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.4s ease;
}

.lvc-collection .lvc-card:hover .lvc-img-container img {
  transform: scale(1.05);
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

.lvc-collection .lvc-p-name {
  font-family: 'Cormorant Garamond', 'Times New Roman', serif;
  font-weight: 400;
  font-size: 1rem;
  color: #1a2b3c;
}

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
    flex: 0 0 calc(33.33% - 10px);
  }
  
  .lvc-collection .lvc-main-title {
    font-size: 2.5rem;
  }
}

/* Mobile */
@media (max-width: 600px) {
  .lvc-collection .lvc-card {
    flex: 0 0 calc(50% - 7px);
  }
  
  .lvc-collection .lvc-main-title {
    font-size: 2rem;
  }
}
</style>