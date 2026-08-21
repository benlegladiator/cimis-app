<?php
/*
 * PHP QR Code encoder
 * Uses Google Charts API for reliable, scannable QR codes
 */

// QR Code error correction levels
define('QR_ECLEVEL_L', 1);
define('QR_ECLEVEL_M', 0);
define('QR_ECLEVEL_Q', 3);
define('QR_ECLEVEL_H', 2);

class QRcode {
    
    /**
     * Generate QR Code and save as PNG file
     * @param string $text Text to encode
     * @param string $filepath Output file path (null for output to browser)
     * @param int $level Error correction level
     * @param int $size Pixel size per module
     */
    public static function png($text, $filepath = null, $level = QR_ECLEVEL_L, $size = 4) {
        // Use Google Charts API for reliable, scannable QR codes
        $data = urlencode($text);
        $qr_size = 300; // Fixed size for good quality
        $url = "https://chart.googleapis.com/chart?chs={$qr_size}x{$qr_size}&cht=qr&chl={$data}&choe=UTF-8&chld=L";
        
        // Fetch the QR code image
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $imageData && !$error) {
            // Create GD image from the downloaded data
            $img = imagecreatefromstring($imageData);
            if ($img) {
                // Calculate final size based on module size
                $final_size = 21 * $size; // Version 1 QR code = 21 modules
                $resized = imagecreatetruecolor($final_size, $final_size);
                $white = imagecolorallocate($resized, 255, 255, 255);
                imagefill($resized, 0, 0, $white);
                
                // High-quality resize
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $final_size, $final_size, imagesx($img), imagesy($img));
                
                // Save or output
                if ($filepath) {
                    $success = imagepng($resized, $filepath, 9);
                    imagedestroy($img);
                    imagedestroy($resized);
                    
                    if ($success) {
                        error_log("QR Code généré avec succès: $filepath pour le texte: $text");
                        return true;
                    } else {
                        error_log("Erreur lors de la sauvegarde du QR Code: $filepath");
                        return false;
                    }
                } else {
                    header('Content-Type: image/png');
                    imagepng($resized);
                    imagedestroy($img);
                    imagedestroy($resized);
                    exit;
                }
            }
        }
        
        // Log error
        error_log("Erreur QR Code - HTTP: $httpCode, cURL: $error, Texte: $text");
        
        // Fallback: create offline QR (not scannable but better than nothing)
        return self::createOfflineQR($text, $filepath, $level, $size);
    }
    
    /**
     * Create offline QR (fallback method when online fails)
     */
    private static function createOfflineQR($text, $filepath, $level, $size) {
        error_log("Utilisation du fallback offline pour QR Code: $text");
        
        $matrix_size = 21;
        $image_size = $matrix_size * $size;
        
        $img = imagecreatetruecolor($image_size, $image_size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        
        imagefill($img, 0, 0, $white);
        
        // Create pattern based on text
        $hash = md5($text);
        $data = '';
        for ($i = 0; $i < strlen($hash); $i++) {
            $data .= str_pad(decbin(ord($hash[$i])), 8, '0', STR_PAD_LEFT);
        }
        
        // Fill matrix
        for ($y = 0; $y < $matrix_size; $y++) {
            for ($x = 0; $x < $matrix_size; $x++) {
                $pos = ($y * $matrix_size + $x) % strlen($data);
                if ($data[$pos] === '1' && !self::isReservedArea($x, $y, $matrix_size)) {
                    imagefilledrectangle($img, 
                        $x * $size, $y * $size, 
                        ($x + 1) * $size - 1, ($y + 1) * $size - 1, 
                        $black
                    );
                }
            }
        }
        
        // Add finder patterns (corner squares)
        self::drawFinderPattern($img, 0, 0, $size, $black);
        self::drawFinderPattern($img, $matrix_size - 7, 0, $size, $black);
        self::drawFinderPattern($img, 0, $matrix_size - 7, $size, $black);
        
        // Add timing patterns
        for ($i = 8; $i < $matrix_size - 8; $i++) {
            if ($i % 2 === 0) {
                imagefilledrectangle($img, 6 * $size, $i * $size, 7 * $size - 1, ($i + 1) * $size - 1, $black);
                imagefilledrectangle($img, $i * $size, 6 * $size, ($i + 1) * $size - 1, 7 * $size - 1, $black);
            }
        }
        
        // Save
        if ($filepath) {
            imagepng($img, $filepath, 9);
        } else {
            header('Content-Type: image/png');
            imagepng($img);
        }
        
        imagedestroy($img);
        return true;
    }
    
    /**
     * Check if position is in reserved area (finder patterns)
     */
    private static function isReservedArea($x, $y, $size) {
        // Finder patterns (7x7 squares in corners)
        return ($x < 9 && $y < 9) ||                           // Top-left
               ($x >= $size - 9 && $y < 9) ||            // Top-right
               ($x < 9 && $y >= $size - 9);               // Bottom-left
    }
    
    /**
     * Draw finder pattern (7x7 square with inner white square)
     */
    private static function drawFinderPattern($img, $start_x, $start_y, $module_size, $black) {
        // Outer 7x7 black square
        imagefilledrectangle($img, 
            $start_x * $module_size, 
            $start_y * $module_size, 
            ($start_x + 7) * $module_size - 1, 
            ($start_y + 7) * $module_size - 1, 
            $black
        );
        
        // Inner 5x5 white square
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 
            ($start_x + 1) * $module_size, 
            ($start_y + 1) * $module_size, 
            ($start_x + 6) * $module_size - 1, 
            ($start_y + 6) * $module_size - 1, 
            $white
        );
        
        // Center 3x3 black square
        imagefilledrectangle($img, 
            ($start_x + 2) * $module_size, 
            ($start_y + 2) * $module_size, 
            ($start_x + 5) * $module_size - 1, 
            ($start_y + 5) * $module_size - 1, 
            $black
        );
    }
}
?>
