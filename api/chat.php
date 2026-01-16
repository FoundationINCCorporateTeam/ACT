<?php
/**
 * ACT AI Tutor - Chat API
 * 
 * API endpoints for AI chat operations.
 */

require_once __DIR__ . '/../config.php';

if (!auth_check()) {
    json_response(false, null, 'Not authenticated');
}

$action = $_GET['action'] ?? '';
$userId = auth_user_id();

switch ($action) {
    case 'send':
        handleSend();
        break;
    case 'rename':
        handleRename();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'list':
        handleList();
        break;
    case 'get':
        handleGet();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleSend() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $chatId = $input['chat_id'] ?? null;
    $message = $input['message'] ?? '';
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    $context = $input['context'] ?? [];
    
    if (empty($message)) {
        json_response(false, null, 'Message is required');
    }
    
    // Get or create conversation
    $isNewChat = false;
    $chat = null;
    
    if ($chatId) {
        $chat = db_get_user_item($userId, 'chats', $chatId);
    }
    
    if (!$chat) {
        // Create new conversation
        $isNewChat = true;
        $chat = [
            'title' => generateTitle($message),
            'messages' => [],
            'model' => $model
        ];
    }
    
    // Add user message
    $chat['messages'][] = [
        'role' => 'user',
        'content' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Get AI response
    $conversationHistory = array_map(function($m) {
        return ['role' => $m['role'], 'content' => $m['content']];
    }, array_slice($chat['messages'], -20));
    
    $result = ai_chat($conversationHistory, $message, $model, $context);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to get AI response');
    }
    
    // Add AI response
    $aiResponse = $result['content'];
    $chat['messages'][] = [
        'role' => 'assistant',
        'content' => $aiResponse,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Save conversation
    if ($isNewChat) {
        $chatId = db_append_user($userId, 'chats', $chat);
    } else {
        db_update_user($userId, 'chats', $chatId, [
            'messages' => $chat['messages'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Add XP for chat session
    auth_add_xp(XP_ACTIONS['chat_session']);
    
    json_response(true, [
        'chat_id' => $chatId,
        'response' => $aiResponse,
        'title' => $isNewChat ? $chat['title'] : null
    ]);
}

function handleRename() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $chatId = $input['chat_id'] ?? 0;
    $title = $input['title'] ?? '';
    
    if (!$chatId || empty($title)) {
        json_response(false, null, 'Chat ID and title are required');
    }
    
    if (db_update_user($userId, 'chats', $chatId, ['title' => $title])) {
        json_response(true, null, 'Conversation renamed');
    } else {
        json_response(false, null, 'Failed to rename conversation');
    }
}

function handleDelete() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $chatId = $input['chat_id'] ?? 0;
    
    if (!$chatId) {
        json_response(false, null, 'Chat ID is required');
    }
    
    if (db_delete_user($userId, 'chats', $chatId)) {
        json_response(true, null, 'Conversation deleted');
    } else {
        json_response(false, null, 'Failed to delete conversation');
    }
}

function handleList() {
    global $userId;
    
    $chats = db_read_user($userId, 'chats');
    
    // Return only metadata, not full messages
    $chatList = array_map(function($chat) {
        return [
            'id' => $chat['id'],
            'title' => $chat['title'] ?? 'New Conversation',
            'message_count' => count($chat['messages'] ?? []),
            'created_at' => $chat['created_at'],
            'updated_at' => $chat['updated_at'] ?? $chat['created_at']
        ];
    }, $chats);
    
    // Sort by updated_at desc
    usort($chatList, function($a, $b) {
        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
    });
    
    json_response(true, $chatList);
}

function handleGet() {
    global $userId;
    
    $chatId = input('chat_id', 'int');
    
    if (!$chatId) {
        json_response(false, null, 'Chat ID is required');
    }
    
    $chat = db_get_user_item($userId, 'chats', $chatId);
    
    if (!$chat) {
        json_response(false, null, 'Conversation not found');
    }
    
    json_response(true, $chat);
}

/**
 * Generate a title from the first message
 */
function generateTitle($message) {
    // Truncate and clean the message for a title
    $title = trim($message);
    $title = preg_replace('/\s+/', ' ', $title);
    
    if (strlen($title) > 50) {
        $title = substr($title, 0, 47) . '...';
    }
    
    return $title ?: 'New Conversation';
}
