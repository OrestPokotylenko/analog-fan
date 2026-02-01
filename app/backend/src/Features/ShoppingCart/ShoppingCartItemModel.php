<?php

namespace App\Features\ShoppingCart;

use App\Core\BaseModel;

class ShoppingCartItemModel extends BaseModel {
    private string $table = 'shopping_carts';

    public function addItemToCart($shoppingCartId, $itemId, $quantity): ShoppingCartItemDto {
        $sql = "INSERT INTO shopping_cart_items (shopping_cart_id, item_id, quantity)
                VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute([$shoppingCartId, $itemId, $quantity])) {
            throw new \Exception('Failed to add item to shopping cart');
        }

        $result = $this->fetchCartItemById((int)$this->pdo->lastInsertId());
        if (!$result) {
            throw new \Exception('Failed to fetch added cart item');
        }
        return $result;
    }

    public function fetchCartItemById($id): ?ShoppingCartItemDto {
        $stmt = $this->pdo->prepare("SELECT * FROM shopping_cart_items WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return null;
        return ShoppingCartItemDto::toDTO($row);
    }

    public function removeItemFromCart($id) {
        $stmt = $this->pdo->prepare("DELETE FROM shopping_cart_items WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getCartItems($shoppingCartId) {
        $sql = "SELECT 
                    sci.id,
                    sci.shopping_cart_id,
                    sci.item_id,
                    sci.quantity,
                    sci.added_at,
                    i.title,
                    i.description,
                    i.price,
                    i.images,
                    i.user_id as seller_id
                FROM shopping_cart_items sci
                JOIN items i ON sci.item_id = i.item_id
                WHERE sci.shopping_cart_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$shoppingCartId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            $dto = ShoppingCartItemDto::toDTO($row);
            // Add item details to DTO
            $dto->title = $row['title'] ?? '';
            $dto->description = $row['description'] ?? '';
            $dto->price = (float)($row['price'] ?? 0);
            $dto->images = $this->decodeImages($row['images'] ?? '[]');
            $dto->sellerId = (int)($row['seller_id'] ?? 0);
            return $dto->toArray();
        }, $rows);
    }

    private function decodeImages($value): array {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function updateCartItemQuantity($id, $quantity): ShoppingCartItemDto {
        $sql = "UPDATE shopping_cart_items SET quantity = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute([$quantity, $id])) {
            throw new \Exception('Failed to update cart item quantity');
        }

        $result = $this->fetchCartItemById($id);
        if (!$result) {
            throw new \Exception('Cart item not found after update');
        }
        return $result;
    }

    public function clearCart(int $shoppingCartId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM shopping_cart_items WHERE shopping_cart_id = ?");
        return $stmt->execute([$shoppingCartId]);
    }
}