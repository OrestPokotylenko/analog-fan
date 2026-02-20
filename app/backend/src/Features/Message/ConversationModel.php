<?php

namespace App\Features\Message;

use App\Core\BaseModel;
use Exception;

class ConversationModel extends BaseModel {
    private string $table = 'conversations';

    /**
     * Get or create a conversation between buyer and seller for an item
     */
    public function getOrCreateConversation($itemId, $buyerId, $sellerId) {
        // Check if conversation already exists
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} 
             WHERE item_id = ? AND buyer_id = ? AND seller_id = ? 
             LIMIT 1"
        );
        $stmt->execute([$itemId, $buyerId, $sellerId]);
        $conversation = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($conversation) {
            return $this->normalizeRow($conversation);
        }

        // Create new conversation
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (item_id, buyer_id, seller_id) 
             VALUES (?, ?, ?)"
        );
        
        if (!$stmt->execute([$itemId, $buyerId, $sellerId])) {
            throw new Exception('Failed to create conversation');
        }

        return $this->getConversationById((int)$this->pdo->lastInsertId());
    }

    /**
     * Get conversation by ID
     */
    public function getConversationById($conversationId) {
        $sql = "SELECT 
                    c.*,
                    i.title as item_title,
                    i.images as item_images,
                    i.price as item_price,
                    buyer.username as buyer_username,
                    seller.username as seller_username
                FROM {$this->table} c
                LEFT JOIN items i ON c.item_id = i.item_id
                LEFT JOIN users buyer ON c.buyer_id = buyer.user_id
                LEFT JOIN users seller ON c.seller_id = seller.user_id
                WHERE c.conversation_id = ? 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversationId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            $normalized = $this->normalizeRow($row);
            // Decode item images if present
            if (isset($normalized['itemImages'])) {
                $normalized['itemImages'] = json_decode($normalized['itemImages'], true) ?: [];
            }
            return $normalized;
        }
        
        return null;
    }

    /**
     * Get all conversations for a user with additional details
     */
    public function getUserConversations($userId) {
        $sql = "SELECT 
                    c.*,
                    i.title as item_title,
                    i.images as item_images,
                    i.price as item_price,
                    buyer.username as buyer_username,
                    seller.username as seller_username,
                    (SELECT message_text FROM messages WHERE conversation_id = c.conversation_id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE conversation_id = c.conversation_id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.conversation_id AND sender_id != ? AND is_read = FALSE) as unread_count
                FROM {$this->table} c
                LEFT JOIN items i ON c.item_id = i.item_id
                LEFT JOIN users buyer ON c.buyer_id = buyer.user_id
                LEFT JOIN users seller ON c.seller_id = seller.user_id
                WHERE c.buyer_id = ? OR c.seller_id = ?
                ORDER BY c.updated_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            $normalized = $this->normalizeRow($row);
            // Decode item images if present
            if (isset($normalized['itemImages'])) {
                $normalized['itemImages'] = json_decode($normalized['itemImages'], true) ?: [];
            }
            return $normalized;
        }, $rows);
    }

    /**
     * Update conversation timestamp
     */
    public function updateConversation($conversationId) {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET updated_at = CURRENT_TIMESTAMP WHERE conversation_id = ?"
        );
        return $stmt->execute([$conversationId]);
    }

    /**
     * Get unread message count for a user
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count 
                FROM messages m
                JOIN {$this->table} c ON m.conversation_id = c.conversation_id
                WHERE (c.buyer_id = ? OR c.seller_id = ?) 
                AND m.sender_id != ? 
                AND m.is_read = FALSE";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Normalize database row to camelCase
     */
    private function normalizeRow($row) {
        $normalized = [];
        foreach ($row as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
            $normalized[$camelKey] = $value;
        }
        return $normalized;
    }
}
