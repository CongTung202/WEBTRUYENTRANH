<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class ImageProcessingService
{
    /**
     * Convert an image file to WebP format with compression
     *
     * @param string $sourcePath Path to source image file
     * @param int $quality Compression quality (0-100)
     * @return string Path to temporary converted WebP file
     */
    public function convertToWebp(string $sourcePath, int $quality = 85): string
    {
        if (!file_exists($sourcePath)) {
            throw new Exception("File does not exist: {$sourcePath}");
        }

        // If file is already webp and we just want to return it
        $mime = mime_content_type($sourcePath);
        if ($mime === 'image/webp') {
            return $sourcePath;
        }

        if (!function_exists('imagewebp')) {
            Log::warning("GD imagewebp is not available. Using original file format.");
            return $sourcePath;
        }

        $image = null;
        try {
            $imageInfo = @getimagesize($sourcePath);
            if (!$imageInfo) {
                return $sourcePath;
            }

            $imageType = $imageInfo[2];

            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $image = @imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = @imagecreatefrompng($sourcePath);
                    if ($image) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                    break;
                case IMAGETYPE_GIF:
                    $image = @imagecreatefromgif($sourcePath);
                    if ($image) {
                        imagepalettetotruecolor($image);
                    }
                    break;
                case IMAGETYPE_BMP:
                    if (function_exists('imagecreatefrombmp')) {
                        $image = @imagecreatefrombmp($sourcePath);
                    }
                    break;
                case IMAGETYPE_WEBP:
                    return $sourcePath;
                default:
                    $data = @file_get_contents($sourcePath);
                    if ($data) {
                        $image = @imagecreatefromstring($data);
                    }
                    break;
            }

            if (!$image) {
                Log::warning("Failed to create GD image resource from: {$sourcePath}");
                return $sourcePath;
            }

            $tempDir = storage_path('app/temp_webp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $tempWebpPath = $tempDir . '/' . uniqid('webp_', true) . '.webp';
            $success = imagewebp($image, $tempWebpPath, $quality);
            imagedestroy($image);

            if ($success && file_exists($tempWebpPath)) {
                return $tempWebpPath;
            }

            return $sourcePath;
        } catch (\Throwable $e) {
            Log::error("Error converting image to WebP: " . $e->getMessage());
            if ($image && is_resource($image)) {
                imagedestroy($image);
            }
            return $sourcePath;
        }
    }

    /**
     * Clean up temporary WebP files
     */
    public function cleanupTempFile(string $filePath): void
    {
        if (str_contains($filePath, 'temp_webp') && file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
