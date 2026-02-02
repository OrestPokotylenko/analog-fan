<?php

namespace App\External;

error_reporting(E_ALL & ~E_DEPRECATED);
use Cloudinary\Cloudinary;
use Exception;

class CloudinaryService {
    private Cloudinary $cloudinary;

    public function __construct() {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
                'api_key' => $_ENV['CLOUDINARY_API_KEY'],
                'api_secret' => $_ENV['CLOUDINARY_API_SECRET']
            ]
        ]);
    }

    public function uploadImages(array $tmpFiles): array {
        if (empty($tmpFiles)) {
            return [];
        }

        $uploadedUrls = [];

        foreach ($tmpFiles as $tmpFile) {
            try {
                if (!file_exists($tmpFile)) {
                    throw new Exception("File does not exist: $tmpFile");
                }

                $response = $this->cloudinary->uploadApi()->upload($tmpFile, [
                    'folder' => $_ENV['CLOUDINARY_FOLDER']
                ]);
                $uploadedUrls[] = $response['secure_url'];
            } catch (Exception $e) {
                throw new Exception('Failed to upload image: ' . $e->getMessage());
            }
        }

        return $uploadedUrls;
    }

    public function uploadImage(string $tmpFile): ?string {
        if (empty($tmpFile)) {
            return null;
        }

        try {
            if (!file_exists($tmpFile)) {
                return null;
            }

            $response = $this->cloudinary->uploadApi()->upload($tmpFile, [
                'folder' => $_ENV['CLOUDINARY_FOLDER']
            ]);
            
            return $response['secure_url'];
        } catch (Exception $e) {
            throw new Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    public function deleteImage($imageUrl): bool {
        try {
            preg_match('/\/([^\/]+\/[^\/]+)\.[a-z]+$/i', $imageUrl, $matches);
            
            if (!isset($matches[1])) {
                throw new Exception('Invalid image URL format');
            }

            $publicId = $matches[1];
            $this->cloudinary->uploadApi()->destroy($publicId);

            return true;
        } catch (Exception $e) {
            error_log('Cloudinary delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteImages(array $imageUrls): bool {
        foreach ($imageUrls as $url) {
            $this->deleteImage($url);
        }
        return true;
    }
}