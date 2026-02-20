<?php

namespace App\Features\Message;

use Exception;

class MessageController {
    private ConversationModel $conversationModel;
    private MessageModel $messageModel;

    public function __construct() {
        $this->conversationModel = new ConversationModel();
        $this->messageModel = new MessageModel();
    }

    /**
     * Get or create a conversation
     * POST /conversations
     * Body: { itemId, buyerId, sellerId }
     */
    public function createConversation($data) {
        if (!isset($data['itemId']) || !isset($data['buyerId']) || !isset($data['sellerId'])) {
            throw new Exception('Missing required fields', 400);
        }

        $conversation = $this->conversationModel->getOrCreateConversation(
            $data['itemId'],
            $data['buyerId'],
            $data['sellerId']
        );

        return $conversation;
    }

    /**
     * Get all conversations for a user
     * GET /conversations/user/{userId}
     */
    public function getUserConversations($userId) {
        return $this->conversationModel->getUserConversations($userId);
    }

    /**
     * Get a specific conversation
     * GET /conversations/{conversationId}
     */
    public function getConversation($conversationId) {
        $conversation = $this->conversationModel->getConversationById($conversationId);
        
        if (!$conversation) {
            throw new Exception('Conversation not found', 404);
        }

        return $conversation;
    }

    /**
     * Send a message
     * POST /messages
     * Body: { conversationId, senderId, messageText }
     */
    public function sendMessage($data) {
        if (!isset($data['conversationId']) || !isset($data['senderId']) || !isset($data['messageText'])) {
            throw new Exception('Missing required fields', 400);
        }

        if (trim($data['messageText']) === '') {
            throw new Exception('Message text cannot be empty', 400);
        }

        // Create the message
        $message = $this->messageModel->createMessage(
            $data['conversationId'],
            $data['senderId'],
            $data['messageText']
        );

        // Update conversation timestamp
        $this->conversationModel->updateConversation($data['conversationId']);

        return $message;
    }

    /**
     * Get messages for a conversation
     * GET /conversations/{conversationId}/messages
     */
    public function getConversationMessages($conversationId) {
        return $this->messageModel->getConversationMessages($conversationId);
    }

    /**
     * Mark messages as read
     * PUT /conversations/{conversationId}/read
     * Body: { userId }
     */
    public function markMessagesAsRead($conversationId, $userId) {
        $this->messageModel->markMessagesAsRead($conversationId, $userId);
        return ['success' => true, 'message' => 'Messages marked as read'];
    }

    /**
     * Get unread message count for a user
     * GET /conversations/user/{userId}/unread-count
     */
    public function getUnreadCount($userId) {
        $count = $this->conversationModel->getUnreadCount($userId);
        return ['count' => $count];
    }
}
