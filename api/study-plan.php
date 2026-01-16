<?php
/**
 * ACT AI Tutor - Study Plan API
 * 
 * API endpoints for study plan operations.
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
    case 'update_task':
        handleUpdateTask();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleGenerate() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $params = [
        'current_score' => $input['current_score'] ?? null,
        'target_score' => $input['target_score'] ?? 30,
        'test_date' => $input['test_date'] ?? date('Y-m-d', strtotime('+2 months')),
        'hours_per_day' => $input['hours_per_day'] ?? 2,
        'days_per_week' => $input['days_per_week'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        'weak_areas' => $input['weak_areas'] ?? [],
        'strong_areas' => $input['strong_areas'] ?? [],
        'learning_style' => $input['learning_style'] ?? 'Reading'
    ];
    
    $result = ai_generate_study_plan($params);
    
    if (!$result['success']) {
        json_response(false, null, $result['message'] ?? 'Failed to generate study plan');
    }
    
    // Save study plan
    $plan = [
        'target_score' => $params['target_score'],
        'test_date' => $params['test_date'],
        'plan' => $result['plan'],
        'params' => $params
    ];
    
    $planId = db_append_user($userId, 'study_plans', $plan);
    
    if (!$planId) {
        json_response(false, null, 'Failed to save study plan');
    }
    
    // Update user's target score and test date
    auth_update_user([
        'target_score' => $params['target_score'],
        'test_date' => $params['test_date']
    ]);
    
    json_response(true, $plan);
}

function handleGet() {
    global $userId;
    
    $planId = input('id', 'int');
    
    $plans = db_read_user($userId, 'study_plans');
    
    if ($planId) {
        $plan = db_get_user_item($userId, 'study_plans', $planId);
        json_response(true, $plan);
    } else {
        json_response(true, $plans);
    }
}

function handleUpdateTask() {
    global $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $planId = $input['plan_id'] ?? 0;
    $week = $input['week'] ?? 0;
    $day = $input['day'] ?? '';
    $taskIndex = $input['task_index'] ?? 0;
    $completed = $input['completed'] ?? false;
    
    $plan = db_get_user_item($userId, 'study_plans', $planId);
    
    if (!$plan) {
        json_response(false, null, 'Plan not found');
    }
    
    // Mark task as completed
    if (isset($plan['plan']['weeks'][$week - 1]['days'])) {
        foreach ($plan['plan']['weeks'][$week - 1]['days'] as &$dayData) {
            if ($dayData['day'] === $day && isset($dayData['tasks'][$taskIndex])) {
                $dayData['tasks'][$taskIndex]['completed'] = $completed;
                break;
            }
        }
    }
    
    if (db_update_user($userId, 'study_plans', $planId, ['plan' => $plan['plan']])) {
        json_response(true, null, 'Task updated');
    } else {
        json_response(false, null, 'Failed to update task');
    }
}
