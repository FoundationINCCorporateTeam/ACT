<?php
/**
 * ACT AI Tutor - Lessons API
 * 
 * API endpoints for lesson operations.
 */

require_once __DIR__ . '/../config.php';

// Ensure user is authenticated for API calls
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
    case 'list':
        handleList();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'favorite':
        handleFavorite();
        break;
    case 'complete':
        handleComplete();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'note':
        handleNote();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleGenerate() {
    global $userId;
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $subject = $input['subject'] ?? '';
    $topic = $input['topic'] ?? '';
    $difficulty = $input['difficulty'] ?? 'intermediate';
    $length = $input['length'] ?? 'medium';
    $focusAreas = $input['focus'] ?? ['Concepts', 'Examples', 'Practice Problems'];
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    
    if (empty($subject) || empty($topic)) {
        json_response(false, null, 'Subject and topic are required');
    }
    
    // Generate lesson using AI
    $result = ai_generate_lesson($subject, $topic, $difficulty, $length, $focusAreas, $model);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to generate lesson');
    }
    
    // Save lesson
    $lesson = [
        'subject' => $subject,
        'topic' => $topic,
        'difficulty' => $difficulty,
        'length' => $length,
        'focus_areas' => $focusAreas,
        'model' => $model,
        'content' => $result['content'],
        'completed' => false,
        'favorite' => false,
        'notes' => '',
        'progress' => 0
    ];
    
    $lessonId = db_append_user($userId, 'lessons', $lesson);
    
    if (!$lessonId) {
        json_response(false, null, 'Failed to save lesson');
    }
    
    // Add XP
    auth_add_xp(XP_ACTIONS['lesson_completed'] / 2); // Half XP for generating, full on complete
    
    $lesson['id'] = $lessonId;
    json_response(true, $lesson, 'Lesson generated successfully');
}

function handleGet() {
    global $userId;
    
    $id = input('id', 'int');
    
    if (!$id) {
        json_response(false, null, 'Lesson ID is required');
    }
    
    $lesson = db_get_user_item($userId, 'lessons', $id);
    
    if (!$lesson) {
        json_response(false, null, 'Lesson not found');
    }
    
    json_response(true, $lesson);
}

function handleList() {
    global $userId;
    
    $lessons = db_read_user($userId, 'lessons');
    
    // Apply filters
    $subject = input('subject');
    $difficulty = input('difficulty');
    $completed = input('completed');
    $search = input('search');
    
    if ($subject) {
        $lessons = array_filter($lessons, fn($l) => $l['subject'] === $subject);
    }
    if ($difficulty) {
        $lessons = array_filter($lessons, fn($l) => $l['difficulty'] === $difficulty);
    }
    if ($completed !== null) {
        $completed = filter_var($completed, FILTER_VALIDATE_BOOLEAN);
        $lessons = array_filter($lessons, fn($l) => ($l['completed'] ?? false) === $completed);
    }
    if ($search) {
        $lessons = search_items($lessons, $search, ['topic', 'subject', 'content']);
    }
    
    // Sort by date (newest first)
    $lessons = sort_items(array_values($lessons), 'created_at', 'desc');
    
    json_response(true, $lessons);
}

function handleUpdate() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    
    if (!$id) {
        json_response(false, null, 'Lesson ID is required');
    }
    
    $updates = [];
    
    if (isset($input['notes'])) {
        $updates['notes'] = $input['notes'];
    }
    if (isset($input['progress'])) {
        $updates['progress'] = min(100, max(0, (int)$input['progress']));
    }
    
    if (empty($updates)) {
        json_response(false, null, 'No updates provided');
    }
    
    if (db_update_user($userId, 'lessons', $id, $updates)) {
        json_response(true, null, 'Lesson updated');
    } else {
        json_response(false, null, 'Failed to update lesson');
    }
}

function handleFavorite() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    
    if (!$id) {
        json_response(false, null, 'Lesson ID is required');
    }
    
    $lesson = db_get_user_item($userId, 'lessons', $id);
    
    if (!$lesson) {
        json_response(false, null, 'Lesson not found');
    }
    
    $newFavorite = !($lesson['favorite'] ?? false);
    
    if (db_update_user($userId, 'lessons', $id, ['favorite' => $newFavorite])) {
        json_response(true, ['favorite' => $newFavorite], $newFavorite ? 'Added to favorites' : 'Removed from favorites');
    } else {
        json_response(false, null, 'Failed to update');
    }
}

function handleComplete() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    
    if (!$id) {
        json_response(false, null, 'Lesson ID is required');
    }
    
    $lesson = db_get_user_item($userId, 'lessons', $id);
    
    if (!$lesson) {
        json_response(false, null, 'Lesson not found');
    }
    
    $wasCompleted = $lesson['completed'] ?? false;
    $newCompleted = !$wasCompleted;
    
    if (db_update_user($userId, 'lessons', $id, ['completed' => $newCompleted, 'progress' => $newCompleted ? 100 : 0])) {
        // Add XP only if marking as complete for first time
        if ($newCompleted && !$wasCompleted) {
            auth_add_xp(XP_ACTIONS['lesson_completed'] / 2);
            
            // Update progress stats
            $progress = db_read_user($userId, 'progress');
            $progress['lessons_completed'] = ($progress['lessons_completed'] ?? 0) + 1;
            db_write_user($userId, 'progress', $progress);
        }
        
        json_response(true, ['completed' => $newCompleted], $newCompleted ? 'Lesson marked as complete' : 'Lesson marked as incomplete');
    } else {
        json_response(false, null, 'Failed to update');
    }
}

function handleDelete() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    
    if (!$id) {
        json_response(false, null, 'Lesson ID is required');
    }
    
    if (db_delete_user($userId, 'lessons', $id)) {
        json_response(true, null, 'Lesson deleted');
    } else {
        json_response(false, null, 'Failed to delete lesson');
    }
}

function handleNote() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $notes = $input['notes'] ?? '';
    
    if (!$id) {
        json_response(false, null, 'Lesson ID is required');
    }
    
    if (db_update_user($userId, 'lessons', $id, ['notes' => $notes])) {
        json_response(true, null, 'Notes saved');
    } else {
        json_response(false, null, 'Failed to save notes');
    }
}
