<?php

namespace App\Features\Message;

use App\Core\BaseModel;
use Exception;

class MessageModel extends BaseModel {
    private string $table = 'messages';

    /**
     * Create a new message
     */
    public function createMessage($conversationId, $senderId, $messageText) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (conversation_id, sender_id, message_text) 
             VALUES (?, ?, ?)"
        );
        
        if (!$stmt->execute([$conversationId, $senderId, $messageText])) {
            throw new Exception('Failed to create message');
        }

        return $this->getMessageById((int)$this->pdo->lastInsertId());
    }

    /**
     * Get message by ID
     */
    public function getMessageById($messageId) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE message_id = ? LIMIT 1");
        $stmt->execute([$messageId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    /**
     * Get all messages for a conversation
     */
    public function getConversationMessages($conversationId) {
        $sql = "SELECT m.*, u.username as sender_username
                FROM {$this->table} m
                LEFT JOIN users u ON m.sender_id = u.user_id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversationId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map([$this, 'normalizeRow'], $rows);
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead($conversationId, $userId) {
        $sql = "UPDATE {$this->table} 
                SET is_read = TRUE 
                WHERE conversation_id = ? 
                AND sender_id != ? 
                AND is_read = FALSE";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$conversationId, $userId]);
    }

    /**
     * Get unread message count for a conversation
     */
    public function getUnreadCount($conversationId, $userId) {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE conversation_id = ? 
                AND sender_id != ? 
                AND is_read = FALSE";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$conversationId, $userId]);
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
