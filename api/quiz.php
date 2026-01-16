<?php
/**
 * ACT AI Tutor - Quiz API
 * 
 * API endpoints for quiz operations.
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
    case 'submit':
        handleSubmit();
        break;
    case 'save_progress':
        handleSaveProgress();
        break;
    case 'abandon':
        handleAbandon();
        break;
    case 'retake':
        handleRetake();
        break;
    case 'get_explanation':
        handleGetExplanation();
        break;
    case 'get':
        handleGet();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleGenerate() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $subject = $input['subject'] ?? '';
    $topic = $input['topic'] ?? '';
    $numQuestions = (int)($input['num_questions'] ?? 10);
    $difficulty = $input['difficulty'] ?? 'intermediate';
    $timeLimit = $input['time_limit'] ?? null;
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    
    if (empty($subject) || empty($topic)) {
        json_response(false, null, 'Subject and topic are required');
    }
    
    // Generate quiz using AI
    $result = ai_generate_quiz($subject, $topic, $numQuestions, $difficulty, $model);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to generate quiz');
    }
    
    // Save quiz
    $quiz = [
        'subject' => $subject,
        'topic' => $topic,
        'difficulty' => $difficulty,
        'num_questions' => $numQuestions,
        'time_limit' => $timeLimit,
        'model' => $model,
        'questions' => $result['questions'],
        'in_progress' => true,
        'answers' => [],
        'flagged' => [],
        'eliminated' => [],
        'notes' => []
    ];
    
    $quizId = db_append_user($userId, 'quizzes', $quiz);
    
    if (!$quizId) {
        json_response(false, null, 'Failed to save quiz');
    }
    
    $quiz['id'] = $quizId;
    
    // Don't send correct answers to client
    $clientQuestions = array_map(function($q) {
        return [
            'id' => $q['id'],
            'question' => $q['question'],
            'options' => $q['options']
        ];
    }, $result['questions']);
    
    json_response(true, [
        'id' => $quizId,
        'questions' => $clientQuestions,
        'time_limit' => $timeLimit
    ], 'Quiz generated successfully');
}

function handleSubmit() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $quizId = $input['id'] ?? 0;
    $answers = $input['answers'] ?? [];
    $timeTaken = $input['time_taken'] ?? 0;
    
    if (!$quizId) {
        json_response(false, null, 'Quiz ID is required');
    }
    
    $quiz = db_get_user_item($userId, 'quizzes', $quizId);
    
    if (!$quiz) {
        json_response(false, null, 'Quiz not found');
    }
    
    // Calculate score
    $correct = 0;
    $total = count($quiz['questions']);
    $results = [];
    
    foreach ($quiz['questions'] as $i => $question) {
        $userAnswer = $answers[$i] ?? null;
        $isCorrect = $userAnswer === $question['correct'];
        
        if ($isCorrect) {
            $correct++;
        }
        
        $results[] = [
            'question_index' => $i,
            'user_answer' => $userAnswer,
            'correct_answer' => $question['correct'],
            'is_correct' => $isCorrect,
            'explanation' => $question['explanation'] ?? ''
        ];
    }
    
    $score = $total > 0 ? round(($correct / $total) * 100) : 0;
    
    // Update quiz with results
    $updates = [
        'in_progress' => false,
        'completed' => true,
        'answers' => $answers,
        'results' => $results,
        'correct' => $correct,
        'total' => $total,
        'score' => $score,
        'time_taken' => $timeTaken,
        'completed_at' => date('Y-m-d H:i:s')
    ];
    
    if (!db_update_user($userId, 'quizzes', $quizId, $updates)) {
        json_response(false, null, 'Failed to save results');
    }
    
    // Add XP
    $xp = XP_ACTIONS['quiz_completed'];
    if ($score === 100) {
        $xp = XP_ACTIONS['quiz_perfect'];
    }
    $xpResult = auth_add_xp($xp);
    
    // Update progress stats
    $progress = db_read_user($userId, 'progress');
    $progress['quizzes_taken'] = ($progress['quizzes_taken'] ?? 0) + 1;
    db_write_user($userId, 'progress', $progress);
    
    json_response(true, [
        'score' => $score,
        'correct' => $correct,
        'total' => $total,
        'xp_earned' => $xp,
        'leveled_up' => $xpResult['leveled_up'] ?? false
    ], 'Quiz submitted successfully');
}

function handleSaveProgress() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $quizId = $input['id'] ?? 0;
    
    if (!$quizId) {
        json_response(false, null, 'Quiz ID is required');
    }
    
    $updates = [];
    
    if (isset($input['answers'])) {
        $updates['answers'] = $input['answers'];
    }
    if (isset($input['flagged'])) {
        $updates['flagged'] = $input['flagged'];
    }
    if (isset($input['eliminated'])) {
        $updates['eliminated'] = $input['eliminated'];
    }
    if (isset($input['notes'])) {
        $updates['notes'] = $input['notes'];
    }
    if (isset($input['current_question'])) {
        $updates['current_question'] = $input['current_question'];
    }
    
    if (db_update_user($userId, 'quizzes', $quizId, $updates)) {
        json_response(true, null, 'Progress saved');
    } else {
        json_response(false, null, 'Failed to save progress');
    }
}

function handleAbandon() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $quizId = $input['id'] ?? 0;
    
    if (!$quizId) {
        json_response(false, null, 'Quiz ID is required');
    }
    
    if (db_delete_user($userId, 'quizzes', $quizId)) {
        json_response(true, null, 'Quiz abandoned');
    } else {
        json_response(false, null, 'Failed to abandon quiz');
    }
}

function handleRetake() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $quizId = $input['id'] ?? 0;
    
    if (!$quizId) {
        json_response(false, null, 'Quiz ID is required');
    }
    
    $oldQuiz = db_get_user_item($userId, 'quizzes', $quizId);
    
    if (!$oldQuiz) {
        json_response(false, null, 'Quiz not found');
    }
    
    // Create new quiz with same questions but shuffled
    $questions = $oldQuiz['questions'];
    shuffle($questions);
    
    // Re-number questions
    foreach ($questions as $i => &$q) {
        $q['id'] = $i + 1;
    }
    
    $newQuiz = [
        'subject' => $oldQuiz['subject'],
        'topic' => $oldQuiz['topic'],
        'difficulty' => $oldQuiz['difficulty'],
        'num_questions' => count($questions),
        'time_limit' => $oldQuiz['time_limit'] ?? null,
        'model' => $oldQuiz['model'] ?? DEFAULT_AI_MODEL,
        'questions' => $questions,
        'in_progress' => true,
        'answers' => [],
        'flagged' => [],
        'eliminated' => [],
        'notes' => [],
        'retake_of' => $quizId
    ];
    
    $newQuizId = db_append_user($userId, 'quizzes', $newQuiz);
    
    if (!$newQuizId) {
        json_response(false, null, 'Failed to create quiz');
    }
    
    // Don't send correct answers to client
    $clientQuestions = array_map(function($q) {
        return [
            'id' => $q['id'],
            'question' => $q['question'],
            'options' => $q['options']
        ];
    }, $questions);
    
    json_response(true, [
        'id' => $newQuizId,
        'questions' => $clientQuestions,
        'time_limit' => $oldQuiz['time_limit'] ?? null
    ], 'Quiz ready');
}

function handleGetExplanation() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $quizId = $input['quiz_id'] ?? 0;
    $questionIndex = $input['question_index'] ?? 0;
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    
    if (!$quizId) {
        json_response(false, null, 'Quiz ID is required');
    }
    
    $quiz = db_get_user_item($userId, 'quizzes', $quizId);
    
    if (!$quiz) {
        json_response(false, null, 'Quiz not found');
    }
    
    if (!isset($quiz['questions'][$questionIndex])) {
        json_response(false, null, 'Question not found');
    }
    
    $question = $quiz['questions'][$questionIndex];
    $userAnswer = $quiz['answers'][$questionIndex] ?? null;
    
    // Generate detailed explanation
    $result = ai_explain_question($question, $userAnswer, $model);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to generate explanation');
    }
    
    json_response(true, [
        'explanation' => $result['content']
    ], 'Explanation generated');
}

function handleGet() {
    global $userId;
    
    $quizId = input('id', 'int');
    
    if (!$quizId) {
        json_response(false, null, 'Quiz ID is required');
    }
    
    $quiz = db_get_user_item($userId, 'quizzes', $quizId);
    
    if (!$quiz) {
        json_response(false, null, 'Quiz not found');
    }
    
    json_response(true, $quiz);
}
