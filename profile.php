<?php
/**
 * ACT AI Tutor - Profile Page
 * 
 * User profile and statistics.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Profile';
$breadcrumbs = [['title' => 'Profile']];
$userId = auth_user_id();
$user = auth_user();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = input('action', 'string');
    
    if ($action === 'update_profile') {
        $name = input('name', 'string');
        $bio = input('bio', 'string');
        $target_score = input('target_score', 'int');
        $test_date = input('test_date', 'string');
        
        if (empty($name)) {
            flash('error', 'Name is required.');
        } else {
            $user['name'] = $name;
            $user['bio'] = $bio;
            $user['target_score'] = $target_score;
            $user['test_date'] = $test_date;
            
            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                    flash('error', 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.');
                } elseif ($_FILES['avatar']['size'] > $maxSize) {
                    flash('error', 'File too large. Maximum size is 5MB.');
                } else {
                    // Create uploads directory if it doesn't exist
                    $uploadDir = __DIR__ . '/uploads/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $filename = $userId . '_' . time() . '.' . $ext;
                    $filepath = $uploadDir . '/' . $filename;
                    
                    // Delete old avatar if exists
                    if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])) {
                        unlink(__DIR__ . '/' . $user['avatar']);
                    }
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {
                        $user['avatar'] = 'uploads/avatars/' . $filename;
                    }
                }
            }
            
            db_save_user($userId, $user);
            flash('success', 'Profile updated successfully!');
        }
        redirect('profile.php');
    }
}

// Get user stats
$quizzes = db_read_user($userId, 'quizzes');
$lessons = db_read_user($userId, 'lessons');
$tests = db_read_user($userId, 'tests');
$flashcards = db_read_user($userId, 'flashcards');

$stats = $user['stats'] ?? [];
$studyStreak = calculate_study_streak($userId);
$totalStudyTime = $stats['total_study_time'] ?? 0;
$xp = $stats['xp'] ?? 0;
$level = $stats['level'] ?? 1;

$completedLessons = count(array_filter($lessons, fn($l) => $l['completed'] ?? false));
$completedQuizzes = count($quizzes);
$completedTests = count(array_filter($tests, fn($t) => $t['completed'] ?? false));

// Calculate average scores
$avgQuizScore = 0;
if (!empty($quizzes)) {
    $scores = array_map(fn($q) => ($q['correct_count'] / max(1, $q['total_questions'])) * 100, $quizzes);
    $avgQuizScore = round(array_sum($scores) / count($scores));
}

$avgTestScore = 0;
if (!empty($completedTests)) {
    $completedTestsList = array_filter($tests, fn($t) => $t['completed'] ?? false);
    $scores = array_map(fn($t) => $t['composite_score'] ?? 0, $completedTestsList);
    $avgTestScore = round(array_sum($scores) / count($completedTestsList));
}

// Days until test
$daysUntilTest = null;
if (!empty($user['test_date'])) {
    $testDate = new DateTime($user['test_date']);
    $today = new DateTime();
    if ($testDate > $today) {
        $daysUntilTest = $today->diff($testDate)->days;
    }
}

// Get achievements
$achievements = $user['achievements'] ?? [];

include __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Profile Header -->
    <div class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-xl p-8 mb-6 text-white">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <!-- Avatar -->
            <div class="relative group">
                <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
                <img src="<?= h($user['avatar']) ?>" alt="Avatar" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                <?php else: ?>
                <div class="w-32 h-32 rounded-full bg-white/20 flex items-center justify-center border-4 border-white shadow-lg">
                    <span class="text-5xl font-bold"><?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?></span>
                </div>
                <?php endif; ?>
                <button onclick="document.getElementById('avatarInput').click()" class="absolute bottom-0 right-0 w-10 h-10 bg-white text-primary-600 rounded-full flex items-center justify-center shadow-lg hover:bg-gray-100 transition opacity-0 group-hover:opacity-100">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            
            <!-- User Info -->
            <div class="text-center md:text-left flex-grow">
                <h1 class="text-3xl font-bold mb-1"><?= h($user['name'] ?? 'Student') ?></h1>
                <p class="text-white/80 mb-2"><?= h($user['email']) ?></p>
                <?php if (!empty($user['bio'])): ?>
                <p class="text-white/90 mb-4"><?= h($user['bio']) ?></p>
                <?php endif; ?>
                <div class="flex flex-wrap items-center gap-4 justify-center md:justify-start">
                    <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        <i class="fas fa-star mr-1"></i>
                        Level <?= $level ?>
                    </span>
                    <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        <i class="fas fa-fire mr-1"></i>
                        <?= $studyStreak ?> day streak
                    </span>
                    <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        <i class="fas fa-gem mr-1"></i>
                        <?= number_format($xp) ?> XP
                    </span>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="text-center">
                <?php if ($avgTestScore > 0): ?>
                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-2">
                    <span class="text-3xl font-bold text-primary-600"><?= $avgTestScore ?></span>
                </div>
                <p class="text-sm text-white/80">Best ACT Score</p>
                <?php elseif ($user['target_score'] ?? null): ?>
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-2 border-2 border-white/50 border-dashed">
                    <span class="text-3xl font-bold"><?= $user['target_score'] ?></span>
                </div>
                <p class="text-sm text-white/80">Target Score</p>
                <?php else: ?>
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-2 border-2 border-white/50 border-dashed">
                    <i class="fas fa-target text-3xl"></i>
                </div>
                <p class="text-sm text-white/80">Set a Goal</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-book text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $completedLessons ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Lessons</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-clipboard-check text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $completedQuizzes ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Quizzes</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-file-alt text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $completedTests ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tests</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= format_study_time($totalStudyTime) ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Study Time</p>
        </div>
    </div>
    
    <!-- Test Countdown -->
    <?php if ($daysUntilTest !== null): ?>
    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold mb-1">ACT Test Day Countdown</h2>
                <p class="text-white/80"><?= format_date($user['test_date'], 'l, F j, Y') ?></p>
            </div>
            <div class="text-center">
                <div class="text-5xl font-bold"><?= $daysUntilTest ?></div>
                <div class="text-white/80">days left</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Edit Profile Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Profile</h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="this.form.submit()">
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                    <input type="text" name="name" id="name" value="<?= h($user['name'] ?? '') ?>" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bio</label>
                    <textarea name="bio" id="bio" rows="3" placeholder="Tell us about yourself..." class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"><?= h($user['bio'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label for="target_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Target ACT Score</label>
                    <input type="number" name="target_score" id="target_score" min="1" max="36" value="<?= $user['target_score'] ?? '' ?>" placeholder="e.g., 30" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="test_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ACT Test Date</label>
                    <input type="date" name="test_date" id="test_date" value="<?= h($user['test_date'] ?? '') ?>" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <button type="submit" class="w-full px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    Save Profile
                </button>
            </form>
        </div>
        
        <!-- Achievements -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Achievements</h2>
            
            <?php
            $allAchievements = [
                'first_lesson' => ['icon' => '📖', 'title' => 'First Lesson', 'desc' => 'Complete your first lesson'],
                'first_quiz' => ['icon' => '📝', 'title' => 'Quiz Starter', 'desc' => 'Complete your first quiz'],
                'first_test' => ['icon' => '📊', 'title' => 'Test Taker', 'desc' => 'Complete a practice test'],
                'streak_3' => ['icon' => '🔥', 'title' => '3 Day Streak', 'desc' => 'Study 3 days in a row'],
                'streak_7' => ['icon' => '🔥', 'title' => 'Week Warrior', 'desc' => 'Study 7 days in a row'],
                'quiz_perfect' => ['icon' => '⭐', 'title' => 'Perfect Score', 'desc' => 'Get 100% on a quiz'],
                'lessons_10' => ['icon' => '📚', 'title' => 'Bookworm', 'desc' => 'Complete 10 lessons'],
                'score_30' => ['icon' => '🏅', 'title' => 'Top Scorer', 'desc' => 'Score 30+ on practice test']
            ];
            
            $earnedAchievements = array_filter($allAchievements, fn($key) => in_array($key, $achievements), ARRAY_FILTER_USE_KEY);
            $unearned = array_diff_key($allAchievements, $earnedAchievements);
            ?>
            
            <?php if (!empty($earnedAchievements)): ?>
            <div class="space-y-3 mb-4">
                <?php foreach (array_slice($earnedAchievements, 0, 4, true) as $key => $achievement): ?>
                <div class="flex items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <span class="text-2xl mr-3"><?= $achievement['icon'] ?></span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white"><?= $achievement['title'] ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= $achievement['desc'] ?></p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 ml-auto"></i>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($unearned)): ?>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Locked Achievements</h3>
            <div class="space-y-2">
                <?php foreach (array_slice($unearned, 0, 3, true) as $key => $achievement): ?>
                <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg opacity-60">
                    <span class="text-2xl mr-3 grayscale"><?= $achievement['icon'] ?></span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white"><?= $achievement['title'] ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= $achievement['desc'] ?></p>
                    </div>
                    <i class="fas fa-lock text-gray-400 ml-auto"></i>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <a href="progress.php" class="block mt-4 text-center text-primary-600 hover:text-primary-700 text-sm font-medium">
                View All Achievements →
            </a>
        </div>
    </div>
    
    <!-- Performance Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mt-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance Summary</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Score Progress -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Average Scores</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Quiz Average</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?= $avgQuizScore ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: <?= $avgQuizScore ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">ACT Practice Average</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?= $avgTestScore ?: 'N/A' ?></span>
                        </div>
                        <?php if ($avgTestScore > 0): ?>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full transition-all duration-500" style="width: <?= ($avgTestScore / 36) * 100 ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (($user['target_score'] ?? 0) > 0 && $avgTestScore > 0): ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Progress to Goal</span>
                            <span class="font-medium text-gray-900 dark:text-white"><?= $avgTestScore ?> / <?= $user['target_score'] ?></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-primary-500 h-2 rounded-full transition-all duration-500" style="width: <?= min(100, ($avgTestScore / $user['target_score']) * 100) ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Level Progress -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Level Progress</h3>
                <div class="text-center p-6 bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 rounded-xl">
                    <div class="relative inline-block">
                        <svg class="w-32 h-32" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                            <?php 
                            $currentLevelXp = get_xp_for_level($level);
                            $nextLevelXp = get_xp_for_level($level + 1);
                            $progress = (($xp - $currentLevelXp) / max(1, $nextLevelXp - $currentLevelXp)) * 100;
                            $circumference = 2 * pi() * 45;
                            $offset = $circumference * (1 - $progress / 100);
                            ?>
                            <circle cx="50" cy="50" r="45" fill="none" stroke="url(#levelGradient)" stroke-width="8" stroke-linecap="round" stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $offset ?>" transform="rotate(-90 50 50)"/>
                            <defs>
                                <linearGradient id="levelGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#2563eb"/>
                                    <stop offset="100%" style="stop-color:#7c3aed"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center flex-col">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white"><?= $level ?></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">LEVEL</span>
                        </div>
                    </div>
                    <p class="mt-4 font-medium text-gray-900 dark:text-white"><?= get_level_title($level) ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= number_format($xp) ?> / <?= number_format($nextLevelXp) ?> XP to next level</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mt-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="lessons.php" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-2">
                    <i class="fas fa-book text-blue-600 dark:text-blue-400"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">New Lesson</span>
            </a>
            
            <a href="quiz.php" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-2">
                    <i class="fas fa-clipboard-check text-green-600 dark:text-green-400"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Take Quiz</span>
            </a>
            
            <a href="practice-test.php" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-2">
                    <i class="fas fa-file-alt text-purple-600 dark:text-purple-400"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Practice Test</span>
            </a>
            
            <a href="progress.php" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mb-2">
                    <i class="fas fa-chart-line text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">View Progress</span>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
