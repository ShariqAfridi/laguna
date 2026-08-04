<?php
require_once __DIR__ . '/config/database.php';
$conn = get_db_connection();

$exactColorMap = [
    '02' => ['name' => 'White Frost', 'img' => 'public/assets/img/color111.webp'],
    '03' => ['name' => 'Black Matte', 'img' => 'public/assets/img/color112.webp'],
    '05' => ['name' => 'Peach Frost', 'img' => 'public/assets/img/color5.webp'],
    '07' => ['name' => 'Mocha Frost', 'img' => 'public/assets/img/color7.webp'],
    '08' => ['name' => 'Blue Frost', 'img' => 'public/assets/img/color114.webp'],
    '09' => ['name' => 'Purple Frost', 'img' => 'public/assets/img/color9.webp'],
    '12' => ['name' => 'Blush Pink', 'img' => 'public/assets/img/color115.webp'],
    '13' => ['name' => 'Charcoal Grey Matte', 'img' => 'public/assets/img/color116.webp'],
    '14' => ['name' => 'Gold Electroplate', 'img' => 'public/assets/img/color1.webp'],
    '15' => ['name' => 'Silver Electroplate', 'img' => 'public/assets/img/color117.webp'],
    '16' => ['name' => 'Smoky Grey Electroplate', 'img' => 'public/assets/img/color118.webp'],
    '17' => ['name' => 'Green Frost Dark', 'img' => 'public/assets/img/color4.webp'],
    '18' => ['name' => 'Red Frost', 'img' => 'public/assets/img/color3.webp']
];

foreach ($exactColorMap as $sku => $info) {
    $stmt = $conn->prepare("UPDATE colors SET color_image = ? WHERE sku = ? OR color_name = ?");
    $stmt->bind_param("sss", $info['img'], $sku, $info['name']);
    $stmt->execute();
    $stmt->close();
}

echo "=== FINAL COLORS TABLE IN DB ===\n";
$res = $conn->query("SELECT color_id, sku, color_name, color_hex, color_image FROM colors ORDER BY CAST(sku AS UNSIGNED) ASC");
while ($r = $res->fetch_assoc()) {
    echo "ID: {$r['color_id']} | SKU: {$r['sku']} | Name: {$r['color_name']} | HEX: {$r['color_hex']} | Image: {$r['color_image']}\n";
}
