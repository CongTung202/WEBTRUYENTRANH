<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Exception;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected Cloudinary $cloudinary;
    protected ImageProcessingService $imageProcessor;

    public function __construct(ImageProcessingService $imageProcessor)
    {
        $this->imageProcessor = $imageProcessor;

        $cloudName = config('cloudinary.cloud_name', env('CLOUDINARY_CLOUD_NAME', 'dhefmthim'));
        $apiKey    = config('cloudinary.api_key', env('CLOUDINARY_API_KEY', '253544761519318'));
        $apiSecret = config('cloudinary.api_secret', env('CLOUDINARY_API_SECRET', 'IEoeKaJDt1t-P3i31TeOMzF4rKw'));

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true,
            ]
        ]);
    }

    /**
     * Upload an image to Cloudinary in WebP format inside a structured folder.
     * Example folder: "truyentranh/one-piece/chap-100"
     * Example publicId: "1" or "cover"
     *
     * @param string $sourcePath Path to local image or uploaded file
     * @param string $folder Cloudinary folder path
     * @param string|null $publicId Specific filename (optional)
     * @param bool $compressWebpFirst Whether to convert to WebP locally first
     * @return array ['url' => string, 'secure_url' => string, 'public_id' => string]
     */
    public function uploadImage(
        string $sourcePath, 
        string $folder = 'webtruyentranh/general', 
        ?string $publicId = null,
        bool $compressWebpFirst = true
    ): array {
        $workingFile = $sourcePath;
        $isTemp = false;

        try {
            if ($compressWebpFirst) {
                $converted = $this->imageProcessor->convertToWebp($sourcePath);
                if ($converted !== $sourcePath) {
                    $workingFile = $converted;
                    $isTemp = true;
                }
            }

            $options = [
                'folder' => trim($folder, '/'),
                'resource_type' => 'image',
                'format' => 'webp',
                'overwrite' => true,
            ];

            if ($publicId !== null && $publicId !== '') {
                $options['public_id'] = $publicId;
            }

            $response = $this->cloudinary->uploadApi()->upload($workingFile, $options);

            return [
                'url' => $response['url'] ?? '',
                'secure_url' => $response['secure_url'] ?? ($response['url'] ?? ''),
                'public_id' => $response['public_id'] ?? '',
                'format' => $response['format'] ?? 'webp',
                'width' => $response['width'] ?? null,
                'height' => $response['height'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error("Cloudinary upload failed: " . $e->getMessage());
            throw new Exception("Lỗi khi tải ảnh lên Cloudinary: " . $e->getMessage());
        } finally {
            if ($isTemp && file_exists($workingFile)) {
                $this->imageProcessor->cleanupTempFile($workingFile);
            }
        }
    }

    /**
     * Delete an asset from Cloudinary by public ID
     */
    public function deleteImage(string $publicId): bool
    {
        try {
            $this->cloudinary->uploadApi()->destroy($publicId);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Could not delete Cloudinary image {$publicId}: " . $e->getMessage());
            return false;
        }
    }
}
