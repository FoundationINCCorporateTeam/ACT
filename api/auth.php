<?php
/**
 * ACT AI Tutor - Authentication API
 * 
 * Handles login, logout, and session management.
 */

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheck();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'change-password':
        handleChangePassword();
        break;
    default:
        json_response(false, null, 'Invalid action');
}

function handleLogin() {
    $email = input('email', 'email');
    $password = input('password');
    $remember = input('remember', 'bool', false);
    
    if (!$email || !$password) {
        json_response(false, null, 'Email and password are required');
    }
    
    $result = auth_login($email, $password, $remember);
    
    if ($result['success']) {
        json_response(true, $result['user'], $result['message']);
    } else {
        json_response(false, null, $result['message']);
    }
}

function handleRegister() {
    $name = input('name');
    $email = input('email', 'email');
    $password = input('password');
    
    if (!$name || !$email || !$password) {
        json_response(false, null, 'All fields are required');
    }
    
    $result = auth_register($name, $email, $password);
    
    if ($result['success']) {
        // Auto-login after registration
        auth_login($email, $password);
        json_response(true, $result['user'], $result['message']);
    } else {
        json_response(false, null, $result['message']);
    }
}

function handleLogout() {
    auth_logout();
    
    // If AJAX request, return JSON
    if (is_ajax()) {
        json_response(true, null, 'Logged out successfully');
    }
    
    // Otherwise redirect
    header('Location: ../index.php');
    exit;
}

function handleCheck() {
    if (auth_check()) {
        $user = auth_user();
        json_response(true, $user, 'Authenticated');
    } else {
        json_response(false, null, 'Not authenticated');
    }
}

function handleUpdate() {
    if (!auth_check()) {
        json_response(false, null, 'Not authenticated');
    }
    
    $updates = [];
    
    if (isset($_POST['name'])) {
        $updates['name'] = htmlspecialchars(input('name'), ENT_QUOTES, 'UTF-8');
    }
    
    if (isset($_POST['target_score'])) {
        $updates['target_score'] = input('target_score', 'int');
    }
    
    if (isset($_POST['test_date'])) {
        $updates['test_date'] = input('test_date');
    }
    
    if (isset($_POST['settings'])) {
        $settings = input('settings', 'array');
        $currentUser = auth_user();
        $updates['settings'] = array_merge($currentUser['settings'] ?? [], $settings);
    }
    
    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            json_response(false, null, 'Invalid file type. Please upload an image.');
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            json_response(false, null, 'File too large. Maximum size is 2MB.');
        }
        
        $userId = auth_user_id();
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "avatar_{$userId}." . $ext;
        $path = UPLOADS_PATH . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $path)) {
            $updates['avatar'] = 'uploads/' . $filename;
        }
    }
    
    if (empty($updates)) {
        json_response(false, null, 'No updates provided');
    }
    
    if (auth_update_user($updates)) {
        $user = auth_user();
        json_response(true, $user, 'Profile updated successfully');
    } else {
        json_response(false, null, 'Failed to update profile');
    }
}

function handleChangePassword() {
    if (!auth_check()) {
        json_response(false, null, 'Not authenticated');
    }
    
    $currentPassword = input('current_password');
    $newPassword = input('new_password');
    
    if (!$currentPassword || !$newPassword) {
        json_response(false, null, 'All fields are required');
    }
    
    $result = auth_change_password($currentPassword, $newPassword);
    
    json_response($result['success'], null, $result['message']);
}
