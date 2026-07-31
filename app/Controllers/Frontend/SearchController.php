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

        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.description,
                p.image,
                p.wick_type,
                p.size_prices,
                p.fragrance_id,
                f.fragrance_name,
                f.fragrance_image
            FROM products p
            LEFT JOIN fragrances f ON (
                p.fragrance_id = f.fragrance_id 
                OR FIND_IN_SET(f.fragrance_id, REPLACE(REPLACE(p.fragrance_id, '[', ''), ']', ''))
            )
            WHERE p.product_name LIKE ? 
               OR p.description LIKE ? 
               OR f.fragrance_name LIKE ?
            GROUP BY p.product_id
            ORDER BY p.product_id DESC
            LIMIT 12
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Database query preparation error: ' . $conn->error
            ]);
            exit();
        }

        $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $res = $stmt->get_result();

        $results = [];
        while ($row = $res->fetch_assoc()) {
            // Calculate price
            $displayPrice = '$45.00';
            if (!empty($row['size_prices'])) {
                $sp = json_decode($row['size_prices'], true);
                if (is_array($sp) && !empty($sp)) {
                    $firstVal = reset($sp);
                    if (is_numeric($firstVal)) {
                        $displayPrice = '$' . number_format((float)$firstVal, 2);
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
                    $imagePath = $base . '/public/' . ltrim($imageName, '/');
                } else {
                    $imagePath = $base . '/img/' . ltrim($imageName, '/');
                }
            }
            if (empty($imagePath)) {
                $imagePath = 'https://placehold.co/400x400/14222b/FFFFFF?text=' . urlencode($row['product_name']);
            }

            $vesselCode = (isset($row['wick_type']) && $row['wick_type'] === 'double') ? 'd' : 'c';

            $results[] = [
                'id'             => (int)$row['product_id'],
                'product_name'   => $row['product_name'],
                'fragrance_name' => !empty($row['fragrance_name']) ? $row['fragrance_name'] : 'Signature Scent',
                'description'    => !empty($row['description']) ? mb_strimwidth(strip_tags($row['description']), 0, 85, '...') : '',
                'price'          => $displayPrice,
                'image'          => $imagePath,
                'vessel'         => $vesselCode,
                'url'            => $base . '/shop?vessel=' . $vesselCode . '&product_id=' . $row['product_id']
            ];
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
