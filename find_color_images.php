<?php
require_once __DIR__ . '/config/database.php';
$conn = get_db_connection();

echo "=== COLORS TABLE DATA ===\n";
$res = $conn->query("SELECT color_id, sku, color_name, color_hex, color_image FROM colors");
while ($r = $res->fetch_assoc()) {
    echo "ID: {$r['color_id']} | SKU: {$r['sku']} | Name: {$r['color_name']} | Image: " . ($r['color_image'] ?: 'EMPTY') . "\n";
}

echo "\n=== FILES IN public/uploads/colors/ ===\n";
foreach (glob(__DIR__ . '/public/uploads/colors/*') as $f) {
    echo "  " . basename($f) . " (" . filesize($f) . " bytes)\n";
}

echo "\n=== COLOR FILES IN public/assets/img/ ===\n";
foreach (glob(__DIR__ . '/public/assets/img/color*') as $f) {
    echo "  " . basename($f) . "\n";
}
