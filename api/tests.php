<?php
/**
 * ACT AI Tutor - Tests API
 * 
 * API endpoints for practice test operations.
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
    case 'submit_section':
        handleSubmitSection();
        break;
    case 'complete':
        handleComplete();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleGenerate() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sections = $input['sections'] ?? ['english', 'math', 'reading', 'science'];
    $model = $input['model'] ?? DEFAULT_AI_MODEL;
    
    if (empty($sections)) {
        json_response(false, null, 'At least one section is required');
    }
    
    // Generate questions for each section
    $testSections = [];
    
    foreach ($sections as $section) {
        if (!isset(ACT_SECTIONS[$section])) continue;
        
        $sectionInfo = ACT_SECTIONS[$section];
        // Generate fewer questions for demo (10 per section instead of full)
        $numQuestions = min(10, $sectionInfo['questions']);
        
        $result = ai_generate_practice_test_section($section, $numQuestions, $model);
        
        if (!$result['success']) {
            json_response(false, null, "Failed to generate $section section: " . ($result['message'] ?? 'Unknown error'));
        }
        
        $testSections[$section] = [
            'name' => $sectionInfo['name'],
            'questions' => $result['data']['questions'],
            'passage' => $result['data']['passage'] ?? null,
            'time_limit' => $sectionInfo['minutes'] * 60,
            'answers' => [],
            'completed' => false
        ];
    }
    
    // Create test record
    $test = [
        'sections' => $testSections,
        'section_order' => $sections,
        'current_section' => $sections[0],
        'current_question' => 0,
        'in_progress' => true,
        'model' => $model,
        'start_time' => date('Y-m-d H:i:s')
    ];
    
    $testId = db_append_user($userId, 'tests', $test);
    
    if (!$testId) {
        json_response(false, null, 'Failed to save test');
    }
    
    // Return test info without answers
    $clientSections = [];
    foreach ($testSections as $key => $section) {
        $clientSections[$key] = [
            'name' => $section['name'],
            'questions' => array_map(function($q) {
                return [
                    'id' => $q['id'],
                    'question' => $q['question'],
                    'options' => $q['options']
                ];
            }, $section['questions']),
            'passage' => $section['passage'],
            'time_limit' => $section['time_limit']
        ];
    }
    
    json_response(true, [
        'id' => $testId,
        'sections' => $clientSections,
        'section_order' => $sections,
        'current_section' => $sections[0]
    ]);
}

function handleGet() {
    global $userId;
    
    $testId = input('id', 'int');
    
    if (!$testId) {
        json_response(false, null, 'Test ID is required');
    }
    
    $test = db_get_user_item($userId, 'tests', $testId);
    
    if (!$test) {
        json_response(false, null, 'Test not found');
    }
    
    // Remove answers from questions if in progress
    if ($test['in_progress'] ?? false) {
        foreach ($test['sections'] as $key => &$section) {
            $section['questions'] = array_map(function($q) {
                return [
                    'id' => $q['id'],
                    'question' => $q['question'],
                    'options' => $q['options']
                ];
            }, $section['questions']);
        }
    }
    
    json_response(true, $test);
}

function handleSubmitSection() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $testId = $input['test_id'] ?? 0;
    $section = $input['section'] ?? '';
    $answers = $input['answers'] ?? [];
    $timeTaken = $input['time_taken'] ?? 0;
    
    if (!$testId || !$section) {
        json_response(false, null, 'Test ID and section are required');
    }
    
    $test = db_get_user_item($userId, 'tests', $testId);
    
    if (!$test) {
        json_response(false, null, 'Test not found');
    }
    
    if (!isset($test['sections'][$section])) {
        json_response(false, null, 'Invalid section');
    }
    
    // Calculate section score
    $sectionData = $test['sections'][$section];
    $correct = 0;
    $total = count($sectionData['questions']);
    
    foreach ($sectionData['questions'] as $i => $question) {
        if (isset($answers[$i]) && $answers[$i] === $question['correct']) {
            $correct++;
        }
    }
    
    $rawScore = $correct;
    $scaledScore = raw_to_scaled_score($correct, $total);
    
    // Update section
    $test['sections'][$section]['answers'] = $answers;
    $test['sections'][$section]['completed'] = true;
    $test['sections'][$section]['time_taken'] = $timeTaken;
    $test['sections'][$section]['raw_score'] = $rawScore;
    $test['sections'][$section]['scaled_score'] = $scaledScore;
    
    // Move to next section
    $currentIndex = array_search($section, $test['section_order']);
    $nextSection = $test['section_order'][$currentIndex + 1] ?? null;
    
    $test['current_section'] = $nextSection;
    $test['current_question'] = 0;
    
    db_update_user($userId, 'tests', $testId, [
        'sections' => $test['sections'],
        'current_section' => $test['current_section'],
        'current_question' => 0
    ]);
    
    json_response(true, [
        'raw_score' => $rawScore,
        'scaled_score' => $scaledScore,
        'next_section' => $nextSection
    ]);
}

function handleComplete() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $testId = $input['test_id'] ?? 0;
    
    if (!$testId) {
        json_response(false, null, 'Test ID is required');
    }
    
    $test = db_get_user_item($userId, 'tests', $testId);
    
    if (!$test) {
        json_response(false, null, 'Test not found');
    }
    
    // Calculate composite score
    $sectionScores = [];
    $totalTime = 0;
    
    foreach ($test['sections'] as $key => $section) {
        if (isset($section['scaled_score'])) {
            $sectionScores[$key] = $section['scaled_score'];
        }
        $totalTime += $section['time_taken'] ?? 0;
    }
    
    $compositeScore = !empty($sectionScores) ? calculate_composite_score($sectionScores) : 0;
    $percentile = get_percentile($compositeScore);
    
    // Update test
    $updates = [
        'in_progress' => false,
        'completed' => true,
        'completed_at' => date('Y-m-d H:i:s'),
        'section_scores' => $sectionScores,
        'composite_score' => $compositeScore,
        'percentile' => $percentile,
        'total_time' => $totalTime
    ];
    
    db_update_user($userId, 'tests', $testId, $updates);
    
    // Add XP
    auth_add_xp(XP_ACTIONS['practice_test']);
    
    // Update progress
    $progress = db_read_user($userId, 'progress');
    $progress['tests_taken'] = ($progress['tests_taken'] ?? 0) + 1;
    $progress['study_time'] = ($progress['study_time'] ?? 0) + $totalTime;
    db_write_user($userId, 'progress', $progress);
    
    json_response(true, [
        'composite_score' => $compositeScore,
        'section_scores' => $sectionScores,
        'percentile' => $percentile
    ]);
}
