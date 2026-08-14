<?php
namespace App\Controllers\Frontend;

class SearchController {
    public static function search() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');

        $query = trim($_GET['q'] ?? $_GET['query'] ?? $_POST['q'] ?? '');

        if (mb_strlen($query) < 1) {
            echo json_encode([
                'success' => true,
                'query'   => '',
                'count'   => 0,
                'results' => []
            ]);
            exit();
        }

        require_once dirname(__DIR__, 3) . '/db.php';

        // Calculate base URL
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (substr($scriptDir, -6) === '/logic') {
            $scriptDir = substr($scriptDir, 0, -6);
        }
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

        $searchTerm = '%' . $query . '%';
        $results = [];

        // 1. Search Products (Candles)
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.sku,
                p.description,
                p.image,
                p.wick_type,
                p.size_id,
                p.size_prices,
                p.color_id,
                p.fragrance_id,
                f.fragrance_id AS matched_frag_id,
                f.fragrance_name AS matched_frag_name,
                c.color_name,
                cat.category_name,
                cat.sku AS cat_sku
            FROM products p
            LEFT JOIN fragrances f ON (
                p.fragrance_id = f.fragrance_id 
                OR p.fragrance_id LIKE CONCAT('%\"', f.fragrance_id, '\"%')
                OR p.fragrance_id LIKE CONCAT('%[', f.fragrance_id, ']%')
                OR p.fragrance_id LIKE CONCAT('%,', f.fragrance_id, ',%')
                OR p.fragrance_id LIKE CONCAT('%,', f.fragrance_id, ']%')
                OR p.fragrance_id LIKE CONCAT('%[', f.fragrance_id, ',%')
            )
            LEFT JOIN colors c ON (
                p.color_id = c.color_id
                OR p.color_id LIKE CONCAT('%\"', c.color_id, '\"%')
                OR p.color_id LIKE CONCAT('%[', c.color_id, ']%')
            )
            LEFT JOIN categories cat ON (
                p.size_id LIKE CONCAT('%\"', cat.id, '\"%')
                OR p.size_id LIKE CONCAT('%[', cat.id, ']%')
                OR p.size_id LIKE CONCAT('%', cat.id, '%')
            )
            WHERE p.product_name LIKE ? 
               OR p.description LIKE ? 
               OR p.sku LIKE ?
               OR f.fragrance_name LIKE ?
               OR c.color_name LIKE ?
               OR cat.category_name LIKE ?
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 12
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $res = $stmt->get_result();

            while ($row = $res->fetch_assoc()) {
                // Calculate display price
                $displayPrice = '$35.00';
                if (!empty($row['size_prices'])) {
                    if (is_numeric($row['size_prices'])) {
                        $displayPrice = '$' . number_format((float)$row['size_prices'], 2);
                    } else {
                        $sp = json_decode($row['size_prices'], true);
                        if (is_array($sp) && !empty($sp)) {
                            $firstVal = reset($sp);
                            if (is_numeric($firstVal)) {
                                $displayPrice = '$' . number_format((float)$firstVal, 2);
                            }
                        }
                    }
                }

                // Image URL resolution
                $imageName = $row['image'] ?? '';
                $imagePath = '';
                if (!empty($imageName)) {
                    if (strpos($imageName, 'http') === 0) {
                        $imagePath = $imageName;
                    } elseif (strpos($imageName, 'uploads/') !== false) {
                        $imagePath = function_exists('base_url') ? base_url('/public/' . ltrim($imageName, '/')) : ($base . '/public/' . ltrim($imageName, '/'));
                    } else {
                        $imagePath = function_exists('base_url') ? base_url('/img/' . ltrim($imageName, '/')) : ($base . '/img/' . ltrim($imageName, '/'));
                    }
                }
                if (empty($imagePath)) {
                    $imagePath = 'https://placehold.co/400x400/14222b/FFFFFF?text=' . urlencode($row['product_name']);
                }

                // Auto-detect vessel code (c, d, e)
                $vesselCode = 'c';
                if (!empty($row['sku'])) {
                    $firstChar = strtolower(substr(trim($row['sku']), 0, 1));
                    if (in_array($firstChar, ['c', 'd', 'e'])) {
                        $vesselCode = $firstChar;
                    }
                }
                if ($vesselCode === 'c' && !empty($row['cat_sku'])) {
                    $csku = strtolower(trim($row['cat_sku']));
                    if (in_array($csku, ['c', 'd', 'e'])) {
                        $vesselCode = $csku;
                    }
                }
                if ($vesselCode === 'c' && !empty($row['wick_type'])) {
                    $wt = strtolower($row['wick_type']);
                    if (strpos($wt, 'triple') !== false) {
                        $vesselCode = 'e';
                    } elseif (strpos($wt, 'double') !== false) {
                        $vesselCode = 'd';
                    }
                }

                $fragParam = !empty($row['matched_frag_id']) ? '&fragrance=' . (int)$row['matched_frag_id'] : '';
                $colorParam = !empty($row['color_id']) ? '&color=' . (int)$row['color_id'] : '';

                $productUrl = function_exists('base_url') ? base_url('/shop?vessel=' . $vesselCode . '&product_id=' . $row['product_id'] . $fragParam . $colorParam) : ($base . '/shop?vessel=' . $vesselCode . '&product_id=' . $row['product_id'] . $fragParam . $colorParam);

                $results[] = [
                    'type'           => 'candle',
                    'id'             => (int)$row['product_id'],
                    'product_name'   => $row['product_name'],
                    'fragrance_name' => !empty($row['matched_frag_name']) ? $row['matched_frag_name'] : (!empty($row['color_name']) ? $row['color_name'] : 'Signature Candle'),
                    'description'    => !empty($row['description']) ? mb_strimwidth(strip_tags($row['description']), 0, 85, '...') : '',
                    'price'          => $displayPrice,
                    'image'          => $imagePath,
                    'vessel'         => $vesselCode,
                    'url'            => $productUrl
                ];
            }
        }

        // 2. Search Accessories
        $sqlAcc = "SELECT * FROM accessory WHERE name LIKE ? OR sku LIKE ? OR description LIKE ? LIMIT 4";
        $stmtAcc = $conn->prepare($sqlAcc);
        if ($stmtAcc) {
            $stmtAcc->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
            $stmtAcc->execute();
            $resAcc = $stmtAcc->get_result();
            while ($row = $resAcc->fetch_assoc()) {
                $img = $row['image'] ?? '';
                $imgPath = !empty($img) ? (function_exists('base_url') ? base_url('/public/uploads/accessories/' . basename($img)) : ($base . '/public/uploads/accessories/' . basename($img))) : 'https://placehold.co/400x400/14222b/FFFFFF?text=Accessory';
                $accUrl = function_exists('base_url') ? base_url('/accessories') : ($base . '/accessories');
                $results[] = [
                    'type'           => 'accessory',
                    'id'             => (int)$row['accessory_id'],
                    'product_name'   => $row['name'],
                    'fragrance_name' => 'Studio Accessory',
                    'description'    => !empty($row['description']) ? mb_strimwidth(strip_tags($row['description']), 0, 85, '...') : '',
                    'price'          => '$' . number_format((float)$row['price'], 2),
                    'image'          => $imgPath,
                    'vessel'         => '',
                    'url'            => $accUrl
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'query'   => $query,
            'count'   => count($results),
            'results' => $results
        ]);
        exit();
    }
}
?>
