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
                'cloud_name' => 'doxhlabtl',
                'api_key' => '911999698828372',
                'api_secret' => 'hNArUovrzXa-o4qxFksnmZOl43o'
            ]
        ]);
    }

    public function uploadImages(array $images): array
    {
        $uploadedUrls = [];

        foreach ($images as $image) {
            try {
                // Ensure the image is a valid file path
                if (!file_exists($image)) {
                    throw new Exception("File does not exist: $image");
                }

                $response = $this->cloudinary->uploadApi()->upload($image, [
                    'folder' => 'analog-fan' // Replace with your desired folder name
                ]);
                $uploadedUrls[] = $response['secure_url']; // Get the secure URL of the uploaded image
            } catch (Exception $e) {
                throw new Exception('Failed to upload image: ' . $e->getMessage());
            }
        }

        return $uploadedUrls;
    }
}