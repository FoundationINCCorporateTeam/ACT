<?php
/**
 * ACT AI Tutor - Authentication Utilities
 * 
 * Handles user registration, login, logout, and session management.
 */

/**
 * Register a new user
 * 
 * @param string $name The user's name
 * @param string $email The user's email
 * @param string $password The user's password
 * @return array Result with success status and message/user data
 */
function auth_register($name, $email, $password) {
    // Validate inputs
    $name = trim($name);
    $email = trim(strtolower($email));
    
    if (strlen($name) < 2) {
        return ['success' => false, 'message' => 'Name must be at least 2 characters.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }
    
    // Check if email already exists
    $users = db_read('users.json');
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }
    }
    
    // Generate new user ID
    $maxId = 0;
    foreach ($users as $user) {
        if ($user['id'] > $maxId) {
            $maxId = $user['id'];
        }
    }
    
    // Create new user
    $newUser = [
        'id' => $maxId + 1,
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'avatar' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'xp' => 0,
        'level' => 1,
        'streak' => 0,
        'last_activity' => null,
        'settings' => [
            'theme' => 'light',
            'notifications' => true,
            'study_reminders' => true,
            'timezone' => 'America/New_York'
        ],
        'achievements' => [],
        'target_score' => null,
        'test_date' => null
    ];
    
    $users[] = $newUser;
    
    if (!db_write('users.json', $users)) {
        return ['success' => false, 'message' => 'Failed to create account. Please try again.'];
    }
    
    // Initialize user data files
    db_write_user($newUser['id'], 'lessons', []);
    db_write_user($newUser['id'], 'quizzes', []);
    db_write_user($newUser['id'], 'tests', []);
    db_write_user($newUser['id'], 'chats', []);
    db_write_user($newUser['id'], 'essays', []);
    db_write_user($newUser['id'], 'flashcards', []);
    db_write_user($newUser['id'], 'study_plans', []);
    db_write_user($newUser['id'], 'progress', [
        'study_time' => 0,
        'lessons_completed' => 0,
        'quizzes_taken' => 0,
        'tests_taken' => 0,
        'essays_submitted' => 0,
        'daily_stats' => []
    ]);
    
    // Remove password from returned user data
    unset($newUser['password']);
    
    return ['success' => true, 'message' => 'Account created successfully!', 'user' => $newUser];
}

/**
 * Authenticate a user
 * 
 * @param string $email The user's email
 * @param string $password The user's password
 * @param bool $remember Whether to set a remember me cookie
 * @return array Result with success status and message/user data
 */
function auth_login($email, $password, $remember = false) {
    $email = trim(strtolower($email));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    
    $users = db_read('users.json');
    
    foreach ($users as &$user) {
        if ($user['email'] === $email) {
            if (password_verify($password, $user['password'])) {
                // Update last login
                $user['last_login'] = date('Y-m-d H:i:s');
                
                // Update streak
                $today = date('Y-m-d');
                $lastActivity = $user['last_activity'] ? date('Y-m-d', strtotime($user['last_activity'])) : null;
                
                if ($lastActivity === date('Y-m-d', strtotime('-1 day'))) {
                    $user['streak']++;
                } elseif ($lastActivity !== $today) {
                    $user['streak'] = 1;
                }
                
                $user['last_activity'] = date('Y-m-d H:i:s');
                
                db_write('users.json', $users);
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                // Set remember me cookie
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + REMEMBER_ME_LIFETIME;
                    
                    setcookie('remember_token', $token, $expiry, '/', '', true, true);
                    setcookie('remember_user', $user['id'], $expiry, '/', '', true, true);
                    
                    // Store token hash in user data
                    $user['remember_token'] = password_hash($token, PASSWORD_DEFAULT);
                    $user['remember_expiry'] = date('Y-m-d H:i:s', $expiry);
                    db_write('users.json', $users);
                }
                
                // Remove password from returned user data
                $userData = $user;
                unset($userData['password']);
                unset($userData['remember_token']);
                
                return ['success' => true, 'message' => 'Login successful!', 'user' => $userData];
            }
            
            return ['success' => false, 'message' => 'Incorrect password.'];
        }
    }
    
    return ['success' => false, 'message' => 'No account found with this email.'];
}

/**
 * Log out the current user
 */
function auth_logout() {
    // Clear session
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Clear remember me cookies
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/', '', true, true);
    }
    
    session_destroy();
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in
 */
function auth_check() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    
    // Check remember me cookie
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $users = db_read('users.json');
        $userId = (int)$_COOKIE['remember_user'];
        
        foreach ($users as $user) {
            if ($user['id'] === $userId && isset($user['remember_token'])) {
                if (password_verify($_COOKIE['remember_token'], $user['remember_token'])) {
                    if (isset($user['remember_expiry']) && strtotime($user['remember_expiry']) > time()) {
                        // Restore session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        
                        return true;
                    }
                }
            }
        }
    }
    
    return false;
}

/**
 * Get the current user's ID
 * 
 * @return int|null The user ID or null if not logged in
 */
function auth_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's data
 * 
 * @return array|null The user data or null if not logged in
 */
function auth_user() {
    if (!auth_check()) {
        return null;
    }
    
    $users = db_read('users.json');
    
    foreach ($users as $user) {
        if ($user['id'] === $_SESSION['user_id']) {
            unset($user['password']);
            unset($user['remember_token']);
            return $user;
        }
    }
    
    return null;
}

/**
 * Update the current user's data
 * 
 * @param array $updates The fields to update
 * @return bool True on success
 */
function auth_update_user($updates) {
    if (!auth_check()) {
        return false;
    }
    
    $users = db_read('users.json');
    
    foreach ($users as &$user) {
        if ($user['id'] === $_SESSION['user_id']) {
            // Don't allow updating sensitive fields directly
            unset($updates['id']);
            unset($updates['password']);
            unset($updates['email']);
            
            $user = array_merge($user, $updates);
            
            // Update session if name changed
            if (isset($updates['name'])) {
                $_SESSION['user_name'] = $updates['name'];
            }
            
            return db_write('users.json', $users);
        }
    }
    
    return false;
}

/**
 * Change user's password
 * 
 * @param string $currentPassword The current password
 * @param string $newPassword The new password
 * @return array Result with success status and message
 */
function auth_change_password($currentPassword, $newPassword) {
    if (!auth_check()) {
        return ['success' => false, 'message' => 'Not authenticated.'];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
    }
    
    $users = db_read('users.json');
    
    foreach ($users as &$user) {
        if ($user['id'] === $_SESSION['user_id']) {
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
            
            $user['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            
            if (db_write('users.json', $users)) {
                return ['success' => true, 'message' => 'Password changed successfully!'];
            }
            
            return ['success' => false, 'message' => 'Failed to update password.'];
        }
    }
    
    return ['success' => false, 'message' => 'User not found.'];
}

/**
 * Add XP to user and check for level up
 * 
 * @param int $xp The XP to add
 * @return array The updated XP and level info
 */
function auth_add_xp($xp) {
    if (!auth_check()) {
        return null;
    }
    
    $users = db_read('users.json');
    
    foreach ($users as &$user) {
        if ($user['id'] === $_SESSION['user_id']) {
            $user['xp'] += $xp;
            
            // Check for level up
            $newLevel = 1;
            foreach (LEVEL_THRESHOLDS as $level => $threshold) {
                if ($user['xp'] >= $threshold) {
                    $newLevel = $level;
                }
            }
            
            $leveledUp = $newLevel > $user['level'];
            $user['level'] = $newLevel;
            
            db_write('users.json', $users);
            
            return [
                'xp' => $user['xp'],
                'level' => $user['level'],
                'leveled_up' => $leveledUp,
                'xp_added' => $xp
            ];
        }
    }
    
    return null;
}

/**
 * Validate CSRF token
 * 
 * @param string $token The token to validate
 * @return bool True if valid
 */
function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token for forms
 * 
 * @return string The CSRF token
 */
function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Alias for get_csrf_token
 * 
 * @return string The CSRF token
 */
function csrf_token() {
    return get_csrf_token();
}

/**
 * Require authentication - redirects to login if not authenticated
 */
function require_auth() {
    if (!auth_check()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Update user's last activity timestamp
 */
function auth_update_activity() {
    if (!auth_check()) {
        return;
    }
    
    $users = db_read('users.json');
    $today = date('Y-m-d');
    
    foreach ($users as &$user) {
        if ($user['id'] === $_SESSION['user_id']) {
            $lastActivity = $user['last_activity'] ? date('Y-m-d', strtotime($user['last_activity'])) : null;
            
            // Update streak if needed
            if ($lastActivity === date('Y-m-d', strtotime('-1 day'))) {
                $user['streak']++;
            } elseif ($lastActivity !== $today) {
                if ($lastActivity !== null && $lastActivity !== date('Y-m-d', strtotime('-1 day'))) {
                    $user['streak'] = 1;
                }
            }
            
            $user['last_activity'] = date('Y-m-d H:i:s');
            db_write('users.json', $users);
            break;
        }
    }
}
