<?php
// app/Helpers/ImageOptimizer.php — High-Efficiency Image Compression Helper
namespace App\Helpers;

class ImageOptimizer {
    /**
     * Resizes and compresses an uploaded image automatically to the smallest size and crisp quality.
     * Never rejects uploaded images due to file size — automatically compresses all incoming files.
     *
     * @param array $file $_FILES['input_name']
     * @param string $targetSubdir Subdirectory under public/ (e.g. 'uploads/products/')
     * @param string $prefix File prefix (e.g. 'candle_')
     * @param int $maxDim Max width/height in pixels (default 1400)
     * @param int $maxSizeBytes Target size threshold in bytes (default 1,048,576 = 1MB)
     * @param int $initialQuality Initial compression quality 0-100 (default 85)
     * @return array ['success' => bool, 'path' => string, 'error' => string, 'original_size' => int, 'compressed_size' => int]
     */
    public static function optimize($file, $targetSubdir = 'uploads/products/', $prefix = 'img_', $maxDim = 1400, $maxSizeBytes = 1048576, $initialQuality = 85) {
        if (empty($file) || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errCode = $file['error'] ?? 'NO_FILE';
            return ['success' => false, 'path' => null, 'error' => "Upload error code: $errCode"];
        }

        $tmpPath = $file['tmp_name'];
        $originalSize = @filesize($tmpPath) ?: 0;

        // Prepare absolute output directory
        $publicDir = dirname(__DIR__, 2) . '/public/';
        $cleanSubdir = trim($targetSubdir, '/\\') . '/';
        $absTargetDir = $publicDir . $cleanSubdir;

        if (!file_exists($absTargetDir)) {
            @mkdir($absTargetDir, 0777, true);
        }

        // Validate MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($tmpPath);
        }

        if (!$mime || !in_array($mime, $allowedMimes)) {
            return ['success' => false, 'path' => null, 'error' => "Invalid image format ($mime). Supported: JPG, PNG, GIF, WEBP."];
        }

        // Fallback if GD extension is missing
        if (!extension_loaded('gd')) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid($prefix, true) . '.' . $ext;
            $destination = $absTargetDir . $filename;

            if (move_uploaded_file($tmpPath, $destination)) {
                return [
                    'success' => true,
                    'path' => $cleanSubdir . $filename,
                    'error' => null,
                    'original_size' => $originalSize,
                    'compressed_size' => filesize($destination)
                ];
            }
            return ['success' => false, 'path' => null, 'error' => "Failed to save uploaded file."];
        }

        // Load image resource via GD
        $srcImg = null;
        switch ($mime) {
            case 'image/jpeg':
                $srcImg = @imagecreatefromjpeg($tmpPath);
                break;
            case 'image/png':
                $srcImg = @imagecreatefrompng($tmpPath);
                break;
            case 'image/webp':
                $srcImg = @imagecreatefromwebp($tmpPath);
                break;
            case 'image/gif':
                $srcImg = @imagecreatefromgif($tmpPath);
                break;
        }

        if (!$srcImg) {
            // Direct save fallback if GD parse fails
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid($prefix, true) . '.' . $ext;
            $destination = $absTargetDir . $filename;
            if (move_uploaded_file($tmpPath, $destination)) {
                return ['success' => true, 'path' => $cleanSubdir . $filename, 'error' => null, 'original_size' => $originalSize, 'compressed_size' => filesize($destination)];
            }
            return ['success' => false, 'path' => null, 'error' => "Could not read image contents."];
        }

        $origWidth = imagesx($srcImg);
        $origHeight = imagesy($srcImg);

        // Calculate proportional dimensions
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > $maxDim || $origHeight > $maxDim) {
            if ($origWidth >= $origHeight) {
                $newWidth = $maxDim;
                $newHeight = (int)round(($origHeight / $origWidth) * $maxDim);
            } else {
                $newHeight = $maxDim;
                $newWidth = (int)round(($origWidth / $origHeight) * $maxDim);
            }
        }

        // Create canvas
        $dstImg = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve alpha transparency for PNG / WEBP / GIF
        if ($mime === 'image/png' || $mime === 'image/webp' || $mime === 'image/gif') {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
            imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            $white = imagecolorallocate($dstImg, 255, 255, 255);
            imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $white);
        }

        // Resample with high quality interpolation
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Determine output extension and format (prefer WebP)
        $useWebp = function_exists('imagewebp');
        $ext = $useWebp ? 'webp' : 'jpg';
        $filename = uniqid($prefix, true) . '.' . $ext;
        $destination = $absTargetDir . $filename;

        // Compression loop: optimize quality to get smallest clear file size
        $quality = $initialQuality;
        $saved = false;

        do {
            if ($useWebp) {
                $saved = @imagewebp($dstImg, $destination, $quality);
            } else {
                $saved = @imagejpeg($dstImg, $destination, $quality);
            }

            if (!$saved || !file_exists($destination)) {
                break;
            }

            $currentSize = filesize($destination);
            if ($currentSize <= $maxSizeBytes || $quality <= 40) {
                break;
            }

            $quality -= 10;
        } while ($quality >= 40);

        imagedestroy($srcImg);
        imagedestroy($dstImg);

        if (!$saved || !file_exists($destination)) {
            // Direct save fallback if GD export fails
            $filename = uniqid($prefix, true) . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $destination = $absTargetDir . $filename;
            if (move_uploaded_file($tmpPath, $destination)) {
                return ['success' => true, 'path' => $cleanSubdir . $filename, 'error' => null, 'original_size' => $originalSize, 'compressed_size' => filesize($destination)];
            }
            return ['success' => false, 'path' => null, 'error' => "Failed to save compressed image file."];
        }

        $finalSize = filesize($destination);

        return [
            'success' => true,
            'path' => $cleanSubdir . $filename,
            'error' => null,
            'original_size' => $originalSize,
            'compressed_size' => $finalSize,
            'quality_used' => $quality,
            'width' => $newWidth,
            'height' => $newHeight
        ];
    }
}
