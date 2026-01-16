<?php
/**
 * ACT AI Tutor - Settings Page
 * 
 * User settings and preferences.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Settings';
$breadcrumbs = [['title' => 'Settings']];
$userId = auth_user_id();
$user = auth_user();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action', 'string');
    
    if ($action === 'update_settings') {
        $settings = [
            'theme' => input('theme', 'string') ?: 'light',
            'email_notifications' => input('email_notifications', 'bool'),
            'study_reminders' => input('study_reminders', 'bool'),
            'reminder_time' => input('reminder_time', 'string') ?: '09:00',
            'timezone' => input('timezone', 'string') ?: 'America/New_York',
            'default_difficulty' => input('default_difficulty', 'string') ?: 'intermediate',
            'default_model' => input('default_model', 'string') ?: 'deepseek/deepseek-v3.2:thinking',
            'auto_save' => input('auto_save', 'bool'),
            'sound_effects' => input('sound_effects', 'bool'),
            'show_timer' => input('show_timer', 'bool'),
            'daily_goal_minutes' => input('daily_goal_minutes', 'int') ?: 30
        ];
        
        $user['settings'] = array_merge($user['settings'] ?? [], $settings);
        db_save_user($userId, $user);
        flash('success', 'Settings saved successfully!');
        redirect('settings.php');
    }
    
    if ($action === 'change_password') {
        $currentPassword = input('current_password', 'string');
        $newPassword = input('new_password', 'string');
        $confirmPassword = input('confirm_password', 'string');
        
        if (!password_verify($currentPassword, $user['password_hash'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($newPassword) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } elseif ($newPassword !== $confirmPassword) {
            flash('error', 'New passwords do not match.');
        } else {
            $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            db_save_user($userId, $user);
            flash('success', 'Password changed successfully!');
        }
        redirect('settings.php');
    }
    
    if ($action === 'export_data') {
        // Export all user data
        $exportData = [
            'user' => [
                'email' => $user['email'],
                'name' => $user['name'],
                'created_at' => $user['created_at'],
                'stats' => $user['stats'] ?? [],
                'settings' => $user['settings'] ?? []
            ],
            'lessons' => db_read_user($userId, 'lessons'),
            'quizzes' => db_read_user($userId, 'quizzes'),
            'tests' => db_read_user($userId, 'tests'),
            'flashcards' => db_read_user($userId, 'flashcards'),
            'chats' => db_read_user($userId, 'chats'),
            'essays' => db_read_user($userId, 'essays'),
            'study_plans' => db_read_user($userId, 'study_plans'),
            'export_date' => date('Y-m-d H:i:s')
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="act-tutor-export-' . date('Y-m-d') . '.json"');
        echo json_encode($exportData, JSON_PRETTY_PRINT);
        exit;
    }
    
    if ($action === 'delete_account') {
        $confirmEmail = input('confirm_email', 'string');
        $password = input('delete_password', 'string');
        
        if ($confirmEmail !== $user['email']) {
            flash('error', 'Email confirmation does not match.');
        } elseif (!password_verify($password, $user['password_hash'])) {
            flash('error', 'Password is incorrect.');
        } else {
            // Delete all user data
            $dataDir = DATA_DIR . '/' . $userId;
            if (is_dir($dataDir)) {
                $files = glob($dataDir . '/*');
                foreach ($files as $file) {
                    unlink($file);
                }
                rmdir($dataDir);
            }
            
            // Delete user from users list
            $users = db_read('users');
            $users = array_filter($users, fn($u) => $u['id'] !== $userId);
            db_save('users', array_values($users));
            
            // Logout
            session_destroy();
            
            flash('success', 'Your account has been deleted.');
            redirect('index.php');
        }
        redirect('settings.php');
    }
}

$settings = $user['settings'] ?? [];
$timezones = DateTimeZone::listIdentifiers();

include __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
    </div>
    
    <!-- Settings Navigation -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button onclick="showSection('general')" id="tab-general" class="px-4 py-2 rounded-lg font-medium transition settings-tab active">
            <i class="fas fa-cog mr-2"></i>General
        </button>
        <button onclick="showSection('notifications')" id="tab-notifications" class="px-4 py-2 rounded-lg font-medium transition settings-tab">
            <i class="fas fa-bell mr-2"></i>Notifications
        </button>
        <button onclick="showSection('study')" id="tab-study" class="px-4 py-2 rounded-lg font-medium transition settings-tab">
            <i class="fas fa-book mr-2"></i>Study Preferences
        </button>
        <button onclick="showSection('security')" id="tab-security" class="px-4 py-2 rounded-lg font-medium transition settings-tab">
            <i class="fas fa-lock mr-2"></i>Security
        </button>
        <button onclick="showSection('data')" id="tab-data" class="px-4 py-2 rounded-lg font-medium transition settings-tab">
            <i class="fas fa-database mr-2"></i>Data
        </button>
    </div>
    
    <!-- General Settings -->
    <div id="section-general" class="settings-section">
        <form method="POST" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <input type="hidden" name="action" value="update_settings">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">General Settings</h2>
            
            <div class="space-y-6">
                <!-- Theme -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Theme</label>
                    <div class="flex gap-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="theme" value="light" <?= ($settings['theme'] ?? 'light') === 'light' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-16 h-16 rounded-lg border-2 peer-checked:border-primary-500 border-gray-200 dark:border-gray-600 bg-white flex items-center justify-center transition">
                                <i class="fas fa-sun text-2xl text-yellow-500"></i>
                            </div>
                            <span class="ml-2 text-gray-700 dark:text-gray-300">Light</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="theme" value="dark" <?= ($settings['theme'] ?? 'light') === 'dark' ? 'checked' : '' ?> class="sr-only peer">
                            <div class="w-16 h-16 rounded-lg border-2 peer-checked:border-primary-500 border-gray-200 dark:border-gray-600 bg-gray-900 flex items-center justify-center transition">
                                <i class="fas fa-moon text-2xl text-blue-400"></i>
                            </div>
                            <span class="ml-2 text-gray-700 dark:text-gray-300">Dark</span>
                        </label>
                    </div>
                </div>
                
                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timezone</label>
                    <select name="timezone" id="timezone" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <?php foreach ($timezones as $tz): ?>
                        <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? 'America/New_York') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sound Effects -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sound Effects</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Play sounds for achievements and notifications</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="sound_effects" value="1" <?= ($settings['sound_effects'] ?? true) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                
                <!-- Auto Save -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto Save</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Automatically save progress during quizzes and essays</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_save" value="1" <?= ($settings['auto_save'] ?? true) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                    </label>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <!-- Notifications Settings -->
    <div id="section-notifications" class="settings-section hidden">
        <form method="POST" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <input type="hidden" name="action" value="update_settings">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Notification Settings</h2>
            
            <div class="space-y-6">
                <!-- Email Notifications -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email Notifications</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Receive progress updates and tips via email</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="email_notifications" value="1" <?= ($settings['email_notifications'] ?? false) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                
                <!-- Study Reminders -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Study Reminders</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Get daily reminders to study</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="study_reminders" value="1" <?= ($settings['study_reminders'] ?? false) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                
                <!-- Reminder Time -->
                <div>
                    <label for="reminder_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reminder Time</label>
                    <input type="time" name="reminder_time" id="reminder_time" value="<?= h($settings['reminder_time'] ?? '09:00') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <!-- Study Preferences -->
    <div id="section-study" class="settings-section hidden">
        <form method="POST" class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <input type="hidden" name="action" value="update_settings">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Study Preferences</h2>
            
            <div class="space-y-6">
                <!-- Default Difficulty -->
                <div>
                    <label for="default_difficulty" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default Difficulty</label>
                    <select name="default_difficulty" id="default_difficulty" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="beginner" <?= ($settings['default_difficulty'] ?? 'intermediate') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                        <option value="intermediate" <?= ($settings['default_difficulty'] ?? 'intermediate') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                        <option value="advanced" <?= ($settings['default_difficulty'] ?? 'intermediate') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                        <option value="expert" <?= ($settings['default_difficulty'] ?? 'intermediate') === 'expert' ? 'selected' : '' ?>>Expert</option>
                    </select>
                </div>
                
                <!-- Default AI Model -->
                <div>
                    <label for="default_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default AI Model</label>
                    <select name="default_model" id="default_model" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <?php foreach (AI_MODELS as $model): ?>
                        <option value="<?= $model['id'] ?>" <?= ($settings['default_model'] ?? 'deepseek/deepseek-v3.2:thinking') === $model['id'] ? 'selected' : '' ?>><?= $model['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Daily Goal -->
                <div>
                    <label for="daily_goal_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Daily Study Goal (minutes)</label>
                    <input type="range" name="daily_goal_minutes" id="daily_goal_minutes" min="10" max="180" step="10" value="<?= $settings['daily_goal_minutes'] ?? 30 ?>" class="w-full" oninput="document.getElementById('goal_display').textContent = this.value + ' minutes'">
                    <p id="goal_display" class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= $settings['daily_goal_minutes'] ?? 30 ?> minutes</p>
                </div>
                
                <!-- Show Timer -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show Timer During Quizzes</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Display countdown timer during timed quizzes</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_timer" value="1" <?= ($settings['show_timer'] ?? true) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                    </label>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <!-- Security Settings -->
    <div id="section-security" class="settings-section hidden">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Change Password</h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Password</label>
                    <input type="password" name="new_password" id="new_password" required minlength="8" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Minimum 8 characters</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="8" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    Change Password
                </button>
            </form>
        </div>
        
        <!-- Session Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Session Information</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Last Login</span>
                    <span class="text-gray-900 dark:text-white font-medium"><?= format_date($user['last_login'] ?? $user['created_at'], 'M j, Y \a\t g:i A') ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Account Created</span>
                    <span class="text-gray-900 dark:text-white font-medium"><?= format_date($user['created_at'], 'M j, Y') ?></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">Session Expires</span>
                    <span class="text-gray-900 dark:text-white font-medium">When you logout or after 24 hours</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Data Settings -->
    <div id="section-data" class="settings-section hidden">
        <!-- Export Data -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Export Your Data</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Download all your data including lessons, quizzes, tests, and progress.</p>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="export_data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
                    <i class="fas fa-download mr-2"></i>
                    Export All Data (JSON)
                </button>
            </form>
        </div>
        
        <!-- Clear Data -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Clear Study Data</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Clear specific types of data from your account.</p>
            <div class="flex flex-wrap gap-2">
                <button onclick="clearData('lessons')" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Clear Lessons
                </button>
                <button onclick="clearData('quizzes')" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Clear Quizzes
                </button>
                <button onclick="clearData('tests')" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Clear Tests
                </button>
                <button onclick="clearData('chats')" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Clear Chat History
                </button>
            </div>
        </div>
        
        <!-- Delete Account -->
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-6 border border-red-200 dark:border-red-800 mt-6">
            <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Danger Zone
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Once you delete your account, there is no going back. Please be certain.</p>
            <button onclick="showDeleteModal()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                Delete Account
            </button>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="if(event.target === this) hideDeleteModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md mx-4 shadow-2xl">
        <h2 class="text-xl font-bold text-red-600 mb-4">Delete Your Account?</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">This action cannot be undone. All your data will be permanently deleted.</p>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="delete_account">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div>
                <label for="confirm_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type your email to confirm: <?= h($user['email']) ?></label>
                <input type="email" name="confirm_email" id="confirm_email" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
            </div>
            
            <div>
                <label for="delete_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Enter your password</label>
                <input type="password" name="delete_password" id="delete_password" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="hideDeleteModal()" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Delete Account
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-tab {
    background: #f3f4f6;
    color: #4b5563;
}
.settings-tab.active {
    background: #2563eb;
    color: white;
}
.dark .settings-tab {
    background: #374151;
    color: #9ca3af;
}
.dark .settings-tab.active {
    background: #2563eb;
    color: white;
}
</style>

<script>
function showSection(section) {
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.settings-tab').forEach(el => el.classList.remove('active'));
    
    // Show selected section
    document.getElementById('section-' + section).classList.remove('hidden');
    document.getElementById('tab-' + section).classList.add('active');
}

function showDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}

async function clearData(type) {
    if (!confirm(`Are you sure you want to clear all ${type}? This cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'clear_data',
                type: type
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} cleared successfully!`, 'success');
        } else {
            showToast(data.message || 'Failed to clear data', 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
}

// Handle theme change
document.querySelectorAll('input[name="theme"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.value === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
