<?php

namespace App\Features\Item;

use App\External\CloudinaryService;
use Exception;

class ItemController {
    private ItemModel $itemModel;
    private CloudinaryService $cloudinary;

    public function __construct() {
        $this->itemModel = new ItemModel();
        $this->cloudinary = new CloudinaryService();
    }

    public function getItems() {
        return $this->itemModel->getItems();
    }

    public function getItemsByType($type) {
        return $this->itemModel->getItemsByType($type);
    }

    public function fetchItemById($id) {
        return $this->itemModel->fetchItemById($id);
    }

    public function fetchUserItems($userId) {
        return $this->itemModel->fetchUserItems($userId);
    }

    public function postItem($userId, $title, $description, $price, $type, $files) {
        if (empty($title) || empty($description) || $price <= 0 || empty($type)) {
            throw new Exception('Missing required fields', 400);
        }

        $imagesPath = [];
        if ($files && !empty($files['tmp_name'][0])) {
            $imagesPath = $this->uploadImages($files);
        }

        return $this->itemModel->postItem($userId, $title, $description, $price, $type, $imagesPath);
    }

    public function updateItemWithFiles($id, $userId, $title, $description, $price, $type, $files, $existingImages = []) {
        if (empty($title) || empty($description) || $price <= 0 || empty($type)) {
            throw new Exception('Missing required fields', 400);
        }

        $newImages = [];
        if ($files && !empty($files['tmp_name'][0])) {
            error_log('Uploading ' . count($files['tmp_name']) . ' new images');
            $newImages = $this->uploadImages($files);
            error_log('Upload returned: ' . json_encode($newImages));
        }

        $allImages = array_merge($existingImages, $newImages);
        error_log('Total images: ' . count($allImages) . ' - ' . json_encode($allImages));
        
        return $this->itemModel->updateItem($id, $userId, $title, $description, $price, $type, $allImages);
    }

    public function updateItem($id, $userId, $title, $description, $price, $type, $imagesPath = []) {
        if (!is_array($imagesPath)) {
            $imagesPath = [];
        }
        return $this->itemModel->updateItem($id, $userId, $title, $description, $price, $type, $imagesPath);
    }

    public function deleteItem($id, $userId) {
        $item = $this->fetchItemById($id);
        if (!$item) {
            throw new Exception('Item not found', 404);
        }

        $itemArray = is_array($item) ? $item : (array)$item;
        if (($itemArray['userId'] ?? null) !== $userId) {
            throw new Exception('Unauthorized', 403);
        }

        if (!empty($itemArray['imagesPath']) && is_array($itemArray['imagesPath'])) {
            foreach ($itemArray['imagesPath'] as $imageUrl) {
                $this->cloudinary->deleteImage($imageUrl);
            }
        }

        return $this->itemModel->deleteItem($id);
    }

    public function deleteImageFromItem($id, $userId, $imageIndex) {
        $item = $this->fetchItemById($id);
        if (!$item) {
            throw new Exception('Item not found', 404);
        }

        $itemArray = is_array($item) ? $item : (array)$item;
        if (($itemArray['userId'] ?? null) !== $userId) {
            throw new Exception('Unauthorized', 403);
        }

        $images = $itemArray['imagesPath'] ?? [];
        if (!isset($images[$imageIndex])) {
            throw new Exception('Image not found', 404);
        }

        $this->cloudinary->deleteImage($images[$imageIndex]);
        unset($images[$imageIndex]);
        $images = array_values($images);

        return $this->itemModel->updateItem($id, $userId, $itemArray['title'], $itemArray['description'], $itemArray['price'], $itemArray['type'], $images);
    }

    private function uploadImages(array $files): array {
        error_log('uploadImages called with: ' . json_encode($files));
        
        if (!isset($files['tmp_name']) || empty($files['tmp_name'][0])) {
            error_log('No tmp_name or empty tmp_name[0]');
            return [];
        }

        $tmpFiles = [];
        $count = count($files['tmp_name']);

        for ($i = 0; $i < $count; $i++) {
            $tmpPath = $files['tmp_name'][$i];
            
            // Check if file exists (handles both uploaded and manually created temp files)
            if (!empty($tmpPath) && file_exists($tmpPath)) {
                $tmpFiles[] = $tmpPath;
                error_log("File $i added: $tmpPath (size: " . filesize($tmpPath) . ")");
            } else {
                error_log("File $i skipped: $tmpPath (not found or empty)");
            }
        }

        if (empty($tmpFiles)) {
            error_log('No valid tmp files after loop');
            return [];
        }

        error_log('Calling cloudinary->uploadImages with ' . count($tmpFiles) . ' files');
        $result = $this->cloudinary->uploadImages($tmpFiles);
        error_log('Cloudinary returned: ' . json_encode($result));
        
        return $result;
    }
}