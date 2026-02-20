<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Features\Message\MessageModel;
use App\Features\Message\ConversationModel;

class MessageWebSocket implements MessageComponentInterface {
    protected $clients; // Array of all connections indexed by resourceId
    protected $userConnections; // Map userId to array of connection resourceIds
    protected $messageModel;
    protected $conversationModel;
    protected $pdo; // Shared PDO connection

    public function __construct() {
        $this->clients = [];
        $this->userConnections = [];
        $this->messageModel = null;
        $this->conversationModel = null;
        $this->pdo = null;
        echo "WebSocket server initialized\n";
    }

    private function initModels() {
        if ($this->messageModel === null) {
            $this->messageModel = new MessageModel();
            $this->conversationModel = new ConversationModel();
        }
    }
    
    private function getPdo() {
        if ($this->pdo === null) {
            $this->pdo = new \PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset={$_ENV['DB_CHARSET']}",
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        }
        return $this->pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients[$conn->resourceId] = $conn;
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        // Handle authentication - client sends their userId
        if (isset($data['type']) && $data['type'] === 'auth') {
            $userId = $data['userId'];
            if (!isset($this->userConnections[$userId])) {
                $this->userConnections[$userId] = [];
            }
            $this->userConnections[$userId][$from->resourceId] = $from;
            $from->userId = $userId;
            echo "User {$userId} authenticated on connection {$from->resourceId}\n";
            return;
        }

        // Handle new message
        if (isset($data['type']) && $data['type'] === 'send_message') {
            try {
                $this->initModels(); // Lazy load database models
                
                $conversationId = $data['conversationId'];
                $senderId = $data['senderId'];
                $messageText = $data['messageText'];
                
                // Save message to database
                $message = $this->messageModel->createMessage(
                    $conversationId,
                    $senderId,
                    $messageText
                );
                
                // Get sender username
                $senderUsername = $this->getUsernameById($senderId);
                $message['senderUsername'] = $senderUsername;
                
                // Update conversation timestamp
                $this->conversationModel->updateConversation($conversationId);
                
                // Get conversation details to find recipient
                $conversation = $this->conversationModel->getConversationById($conversationId);
                $recipientId = ($conversation['buyerId'] == $senderId) 
                    ? $conversation['sellerId'] 
                    : $conversation['buyerId'];
                
                // Broadcast to both sender and recipient
                $broadcastData = [
                    'type' => 'new_message',
                    'message' => $message,
                    'conversationId' => $conversationId
                ];
                
                // Send to recipient
                if (isset($this->userConnections[$recipientId])) {
                    foreach ($this->userConnections[$recipientId] as $conn) {
                        $conn->send(json_encode($broadcastData));
                    }
                }
                
                // Send confirmation to sender
                if (isset($this->userConnections[$senderId])) {
                    foreach ($this->userConnections[$senderId] as $conn) {
                        $conn->send(json_encode($broadcastData));
                    }
                }
                
                echo "Message sent in conversation {$conversationId} from user {$senderId}\n";
                
            } catch (\Exception $e) {
                $from->send(json_encode([
                    'type' => 'error',
                    'message' => $e->getMessage()
                ]));
                echo "Error sending message: {$e->getMessage()}\n";
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->clients[$conn->resourceId]);
        
        // Remove from user connections
        if (isset($conn->userId)) {
            $userId = $conn->userId;
            if (isset($this->userConnections[$userId])) {
                unset($this->userConnections[$userId][$conn->resourceId]);
                if (empty($this->userConnections[$userId])) {
                    unset($this->userConnections[$userId]);
                }
            }
            echo "User {$userId} disconnected ({$conn->resourceId})\n";
        } else {
            echo "Connection {$conn->resourceId} closed\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Broadcast a message to specific users
     */
    public function broadcastToUsers(array $userIds, $message) {
        foreach ($userIds as $userId) {
            if (isset($this->userConnections[$userId])) {
                foreach ($this->userConnections[$userId] as $conn) {
                    $conn->send(json_encode($message));
                }
            }
        }
    }

    /**
     * Get username by user ID
     */
    private function getUsernameById($userId) {
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['username'] : 'Unknown User';
        } catch (\Exception $e) {
            echo "Error fetching username: {$e->getMessage()}\n";
            return 'Unknown User';
        }
    }
}
