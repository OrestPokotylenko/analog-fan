<?php

use App\Features\Message\MessageController;
use App\Core\Route;

header('Content-Type: application/json');

$messageController = new MessageController();

if (!function_exists('http_status_from_exception')) {
    function http_status_from_exception($e): int {
        $code = $e->getCode();
        return (is_numeric($code) && (int)$code >= 100 && (int)$code <= 599)
            ? (int)$code
            : 500;
    }
}

Route::add('/api/conversations', function() use ($messageController) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $conversation = $messageController->createConversation($data);
        http_response_code(201);
        echo json_encode($conversation);
    } catch (Exception $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
}, 'post');

Route::add('/api/conversations/user/{userId}', function($userId) use ($messageController) {
    try {
        $conversations = $messageController->getUserConversations($userId);
        http_response_code(200);
        echo json_encode($conversations);
    } catch (Exception $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/conversations/user/{userId}/unread-count', function($userId) use ($messageController) {
    try {
        $result = $messageController->getUnreadCount($userId);
        http_response_code(200);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/conversations/{conversationId}', function($conversationId) use ($messageController) {
    try {
        $conversation = $messageController->getConversation($conversationId);
        http_response_code(200);
        echo json_encode($conversation);
    } catch (Exception $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/conversations/{conversationId}/messages', function($conversationId) use ($messageController) {
    try {
        $messages = $messageController->getConversationMessages($conversationId);
        http_response_code(200);
        echo json_encode($messages);
    } catch (Exception $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
});

Route::add('/api/conversations/{conversationId}/read', function($conversationId) use ($messageController) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['userId'])) {
            throw new Exception('Missing userId', 400);
        }
        $result = $messageController->markMessagesAsRead($conversationId, $data['userId']);
        http_response_code(200);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
}, 'put');

Route::add('/api/messages', function() use ($messageController) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $message = $messageController->sendMessage($data);
        http_response_code(201);
        echo json_encode($message);
    } catch (Exception $e) {
        http_response_code(http_status_from_exception($e));
        echo json_encode(['error' => $e->getMessage()]);
    }
}, 'post');