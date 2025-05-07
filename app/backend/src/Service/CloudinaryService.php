<?php

require_once(__DIR__ . '/../../vendor/autoload.php');

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $_ENV["CLOUDINARY_CLOUD_NAME"],
                'api_key' => $_ENV["CLOUDINARY_API_KEY"],
                'api_secret' => $_ENV["CLOUDINARY_API_SECRET"]
            ]
        ]);
    }

    public function uploadImages(array $images): array
    {
        $uploadedUrls = [];

        foreach ($images as $image) {
            try {
                if (!file_exists($image)) {
                    throw new Exception("File does not exist: $image");
                }

                $response = $this->cloudinary->uploadApi()->upload($image, [
                    'folder' => $_ENV["CLOUDINARY_FOLDER"]
                ]);
                $uploadedUrls[] = $response['secure_url'];
            } catch (Exception $e) {
                throw new Exception('Failed to upload image: ' . $e->getMessage());
            }
        }

        return $uploadedUrls;
    }
}