<?php
/**
 * ACT AI Tutor - Essay API
 * 
 * API endpoints for essay operations.
 */

require_once __DIR__ . '/../config.php';

if (!auth_check()) {
    json_response(false, null, 'Not authenticated');
}

$action = $_GET['action'] ?? '';
$userId = auth_user_id();

switch ($action) {
    case 'generate_prompt':
        handleGeneratePrompt();
        break;
    case 'submit':
        handleSubmit();
        break;
    case 'get':
        handleGet();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleGeneratePrompt() {
    $input = json_decode(file_get_contents('php://input'), true);
    $category = $input['category'] ?? 'Education';
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    
    $result = ai_generate_essay_prompt($category, $model);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to generate prompt');
    }
    
    json_response(true, ['prompt' => $result['prompt']]);
}

function handleSubmit() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $prompt = $input['prompt'] ?? null;
    $content = $input['content'] ?? '';
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    
    if (!$prompt || empty($content)) {
        json_response(false, null, 'Prompt and essay content are required');
    }
    
    // Create prompt text for grading
    $promptText = $prompt['title'] . "\n\n" . $prompt['introduction'] . "\n\n";
    foreach ($prompt['perspectives'] as $p) {
        $promptText .= $p['label'] . ": " . $p['description'] . "\n";
    }
    $promptText .= "\n" . $prompt['task'];
    
    // Grade the essay
    $result = ai_grade_essay($promptText, $content, $model);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to grade essay');
    }
    
    // Save essay
    $essay = [
        'prompt' => $prompt,
        'content' => $content,
        'grades' => $result['grades'],
        'word_count' => str_word_count($content)
    ];
    
    $essayId = db_append_user($userId, 'essays', $essay);
    
    if (!$essayId) {
        json_response(false, null, 'Failed to save essay');
    }
    
    // Add XP
    auth_add_xp(XP_ACTIONS['essay_submitted']);
    
    // Update progress
    $progress = db_read_user($userId, 'progress');
    $progress['essays_submitted'] = ($progress['essays_submitted'] ?? 0) + 1;
    db_write_user($userId, 'progress', $progress);
    
    json_response(true, ['id' => $essayId, 'grades' => $result['grades']]);
}

function handleGet() {
    global $userId;
    
    $essayId = input('id', 'int');
    
    if (!$essayId) {
        json_response(false, null, 'Essay ID is required');
    }
    
    $essay = db_get_user_item($userId, 'essays', $essayId);
    
    if (!$essay) {
        json_response(false, null, 'Essay not found');
    }
    
    json_response(true, $essay);
}
