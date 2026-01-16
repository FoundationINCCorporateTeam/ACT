<?php
/**
 * ACT AI Tutor - Flashcards API
 * 
 * API endpoints for flashcard operations.
 */

require_once __DIR__ . '/../config.php';

if (!auth_check()) {
    json_response(false, null, 'Not authenticated');
}

$action = $_GET['action'] ?? '';
$userId = auth_user_id();

switch ($action) {
    case 'generate':
        handleGenerate();
        break;
    case 'get':
        handleGet();
        break;
    case 'rate':
        handleRate();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'add_card':
        handleAddCard();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleGenerate() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $subject = $input['subject'] ?? '';
    $topic = $input['topic'] ?? '';
    $count = (int)($input['count'] ?? 10);
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    
    if (empty($subject) || empty($topic)) {
        json_response(false, null, 'Subject and topic are required');
    }
    
    $result = ai_generate_flashcards($subject, $topic, $count, $model);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to generate flashcards');
    }
    
    // Add mastery level to each card
    $cards = array_map(function($card, $index) {
        return array_merge($card, [
            'id' => $index + 1,
            'mastery' => 0,
            'last_reviewed' => null
        ]);
    }, $result['flashcards'], array_keys($result['flashcards']));
    
    // Save deck
    $deck = [
        'subject' => $subject,
        'topic' => $topic,
        'cards' => $cards,
        'model' => $model
    ];
    
    $deckId = db_append_user($userId, 'flashcards', $deck);
    
    if (!$deckId) {
        json_response(false, null, 'Failed to save deck');
    }
    
    // Add XP
    auth_add_xp(XP_ACTIONS['flashcard_deck']);
    
    json_response(true, ['id' => $deckId, 'cards' => $cards]);
}

function handleGet() {
    global $userId;
    
    $deckId = input('id', 'int');
    
    if (!$deckId) {
        $decks = db_read_user($userId, 'flashcards');
        json_response(true, $decks);
    }
    
    $deck = db_get_user_item($userId, 'flashcards', $deckId);
    
    if (!$deck) {
        json_response(false, null, 'Deck not found');
    }
    
    json_response(true, $deck);
}

function handleRate() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $deckId = $input['deck_id'] ?? 0;
    $cardIndex = $input['card_index'] ?? 0;
    $rating = $input['rating'] ?? 1;
    
    if (!$deckId) {
        json_response(false, null, 'Deck ID is required');
    }
    
    $deck = db_get_user_item($userId, 'flashcards', $deckId);
    
    if (!$deck || !isset($deck['cards'][$cardIndex])) {
        json_response(false, null, 'Card not found');
    }
    
    // Update mastery based on rating
    $currentMastery = $deck['cards'][$cardIndex]['mastery'] ?? 0;
    
    if ($rating >= 3) {
        $newMastery = min(5, $currentMastery + 1);
    } elseif ($rating === 2) {
        $newMastery = $currentMastery;
    } else {
        $newMastery = max(0, $currentMastery - 1);
    }
    
    $deck['cards'][$cardIndex]['mastery'] = $newMastery;
    $deck['cards'][$cardIndex]['last_reviewed'] = date('Y-m-d H:i:s');
    
    if (db_update_user($userId, 'flashcards', $deckId, ['cards' => $deck['cards']])) {
        json_response(true, ['mastery' => $newMastery]);
    } else {
        json_response(false, null, 'Failed to update card');
    }
}

function handleDelete() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $deckId = $input['id'] ?? 0;
    
    if (!$deckId) {
        json_response(false, null, 'Deck ID is required');
    }
    
    if (db_delete_user($userId, 'flashcards', $deckId)) {
        json_response(true, null, 'Deck deleted');
    } else {
        json_response(false, null, 'Failed to delete deck');
    }
}

function handleAddCard() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $deckId = $input['deck_id'] ?? 0;
    $front = $input['front'] ?? '';
    $back = $input['back'] ?? '';
    $hint = $input['hint'] ?? '';
    
    if (!$deckId || empty($front) || empty($back)) {
        json_response(false, null, 'Deck ID, front, and back are required');
    }
    
    $deck = db_get_user_item($userId, 'flashcards', $deckId);
    
    if (!$deck) {
        json_response(false, null, 'Deck not found');
    }
    
    $newCard = [
        'id' => count($deck['cards']) + 1,
        'front' => $front,
        'back' => $back,
        'hint' => $hint,
        'mastery' => 0,
        'last_reviewed' => null
    ];
    
    $deck['cards'][] = $newCard;
    
    if (db_update_user($userId, 'flashcards', $deckId, ['cards' => $deck['cards']])) {
        json_response(true, $newCard);
    } else {
        json_response(false, null, 'Failed to add card');
    }
}
