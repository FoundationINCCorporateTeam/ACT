<?php
/**
 * ACT AI Tutor - Progress & Analytics Page
 * 
 * Comprehensive progress tracking and analytics.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Progress & Analytics';
$breadcrumbs = [['title' => 'Progress & Analytics']];
$userId = auth_user_id();
$user = auth_user();

// Get all user data for analytics
$quizzes = db_read_user($userId, 'quizzes');
$lessons = db_read_user($userId, 'lessons');
$tests = db_read_user($userId, 'tests');
$flashcards = db_read_user($userId, 'flashcards');
$essays = db_read_user($userId, 'essays');

// Calculate study streak
$studyStreak = calculate_study_streak($userId);
$totalStudyTime = $user['stats']['total_study_time'] ?? 0;
$xp = $user['stats']['xp'] ?? 0;
$level = $user['stats']['level'] ?? 1;

// Calculate completed items
$completedLessons = count(array_filter($lessons, fn($l) => $l['completed'] ?? false));
$completedQuizzes = count($quizzes);
$completedTests = count(array_filter($tests, fn($t) => $t['completed'] ?? false));
$masteredCards = count(array_filter($flashcards, fn($f) => ($f['mastery_level'] ?? 0) >= 5));

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

// Subject performance data
$subjectPerformance = [
    'english' => ['score' => 0, 'count' => 0],
    'math' => ['score' => 0, 'count' => 0],
    'reading' => ['score' => 0, 'count' => 0],
    'science' => ['score' => 0, 'count' => 0]
];

foreach ($quizzes as $quiz) {
    $subject = strtolower($quiz['subject'] ?? 'general');
    if (isset($subjectPerformance[$subject])) {
        $score = ($quiz['correct_count'] / max(1, $quiz['total_questions'])) * 100;
        $subjectPerformance[$subject]['score'] += $score;
        $subjectPerformance[$subject]['count']++;
    }
}

// Calculate averages
foreach ($subjectPerformance as $subject => &$data) {
    if ($data['count'] > 0) {
        $data['avg'] = round($data['score'] / $data['count']);
    } else {
        $data['avg'] = 0;
    }
}
unset($data);

// Weekly activity data (last 7 days)
$weeklyActivity = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime($date));
    $weeklyActivity[$date] = [
        'day' => $dayName,
        'lessons' => 0,
        'quizzes' => 0,
        'tests' => 0
    ];
}

foreach ($lessons as $lesson) {
    $date = date('Y-m-d', strtotime($lesson['created_at'] ?? 'now'));
    if (isset($weeklyActivity[$date])) {
        $weeklyActivity[$date]['lessons']++;
    }
}

foreach ($quizzes as $quiz) {
    $date = date('Y-m-d', strtotime($quiz['created_at'] ?? 'now'));
    if (isset($weeklyActivity[$date])) {
        $weeklyActivity[$date]['quizzes']++;
    }
}

foreach ($tests as $test) {
    $date = date('Y-m-d', strtotime($test['created_at'] ?? 'now'));
    if (isset($weeklyActivity[$date])) {
        $weeklyActivity[$date]['tests']++;
    }
}

// Get score history for chart
$scoreHistory = [];
$combinedItems = [];

foreach ($quizzes as $quiz) {
    $combinedItems[] = [
        'type' => 'quiz',
        'date' => $quiz['created_at'] ?? date('Y-m-d'),
        'score' => ($quiz['correct_count'] / max(1, $quiz['total_questions'])) * 100
    ];
}

foreach ($tests as $test) {
    if ($test['completed'] ?? false) {
        $combinedItems[] = [
            'type' => 'test',
            'date' => $test['completed_at'] ?? $test['created_at'] ?? date('Y-m-d'),
            'score' => ($test['composite_score'] ?? 0) / 36 * 100
        ];
    }
}

// Sort by date
usort($combinedItems, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

// Calculate improvement rate
$improvementRate = 0;
if (count($combinedItems) >= 2) {
    $firstHalf = array_slice($combinedItems, 0, ceil(count($combinedItems) / 2));
    $secondHalf = array_slice($combinedItems, ceil(count($combinedItems) / 2));
    
    $firstAvg = array_sum(array_column($firstHalf, 'score')) / max(1, count($firstHalf));
    $secondAvg = array_sum(array_column($secondHalf, 'score')) / max(1, count($secondHalf));
    
    $improvementRate = round($secondAvg - $firstAvg);
}

// Identify strengths and weaknesses
$topicPerformance = [];
foreach ($quizzes as $quiz) {
    $topic = $quiz['topic'] ?? 'General';
    if (!isset($topicPerformance[$topic])) {
        $topicPerformance[$topic] = ['correct' => 0, 'total' => 0];
    }
    $topicPerformance[$topic]['correct'] += $quiz['correct_count'] ?? 0;
    $topicPerformance[$topic]['total'] += $quiz['total_questions'] ?? 0;
}

$topicScores = [];
foreach ($topicPerformance as $topic => $data) {
    if ($data['total'] > 0) {
        $topicScores[$topic] = round(($data['correct'] / $data['total']) * 100);
    }
}

arsort($topicScores);
$strengths = array_slice($topicScores, 0, 5, true);
$weaknesses = array_slice(array_reverse($topicScores, true), 0, 5, true);

// Get achievements
$achievements = $user['achievements'] ?? [];

include __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Progress & Analytics</h1>
    <div class="flex gap-2">
        <button onclick="exportProgress()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center">
            <i class="fas fa-download mr-2"></i>
            Export
        </button>
        <button onclick="window.print()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
            <i class="fas fa-print mr-2"></i>
            Print Report
        </button>
    </div>
</div>

<!-- Summary Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl">🔥</span>
            <span class="text-2xl font-bold text-primary-600"><?= $studyStreak ?></span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">Day Streak</p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl">⏱️</span>
            <span class="text-2xl font-bold text-primary-600"><?= format_study_time($totalStudyTime) ?></span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">Study Time</p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl">📚</span>
            <span class="text-2xl font-bold text-primary-600"><?= $completedLessons ?></span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">Lessons Done</p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl">📝</span>
            <span class="text-2xl font-bold text-primary-600"><?= $completedQuizzes ?></span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">Quizzes Taken</p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl">🎯</span>
            <span class="text-2xl font-bold text-primary-600"><?= $avgQuizScore ?>%</span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">Avg Quiz Score</p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl">⭐</span>
            <span class="text-2xl font-bold text-accent-600"><?= number_format($xp) ?></span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">XP Points</p>
    </div>
</div>

<!-- Level Progress -->
<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Level <?= $level ?></h2>
            <p class="text-sm text-gray-600 dark:text-gray-400"><?= get_level_title($level) ?></p>
        </div>
        <div class="text-right">
            <span class="text-sm text-gray-600 dark:text-gray-400"><?= $xp ?> / <?= get_xp_for_level($level + 1) ?> XP</span>
        </div>
    </div>
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
        <?php 
        $currentLevelXp = get_xp_for_level($level);
        $nextLevelXp = get_xp_for_level($level + 1);
        $progress = (($xp - $currentLevelXp) / max(1, $nextLevelXp - $currentLevelXp)) * 100;
        ?>
        <div class="bg-gradient-to-r from-primary-600 to-accent-600 h-3 rounded-full transition-all duration-500" style="width: <?= min(100, max(0, $progress)) ?>%"></div>
    </div>
</div>

<!-- Main Analytics Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Score History Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Score History</h2>
        <div class="h-64">
            <canvas id="scoreHistoryChart"></canvas>
        </div>
        <div class="mt-4 flex items-center justify-center">
            <span class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <span class="w-3 h-3 rounded-full bg-primary-500 mr-2"></span>
                Quiz Scores
            </span>
            <span class="flex items-center text-sm text-gray-600 dark:text-gray-400 ml-4">
                <span class="w-3 h-3 rounded-full bg-accent-500 mr-2"></span>
                Test Scores
            </span>
        </div>
    </div>
    
    <!-- Subject Performance -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance by Subject</h2>
        <div class="h-64">
            <canvas id="subjectChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Weekly Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Weekly Activity</h2>
        <div class="h-64">
            <canvas id="weeklyActivityChart"></canvas>
        </div>
    </div>
    
    <!-- Improvement Rate -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Progress Metrics</h2>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Improvement Rate</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Average score change</p>
                </div>
                <div class="text-2xl font-bold <?= $improvementRate >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                    <?= $improvementRate >= 0 ? '+' : '' ?><?= $improvementRate ?>%
                </div>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Practice Tests</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Completed full tests</p>
                </div>
                <div class="text-2xl font-bold text-primary-600"><?= $completedTests ?></div>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Average ACT Score</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Practice test composite</p>
                </div>
                <div class="text-2xl font-bold text-accent-600"><?= $avgTestScore ?: 'N/A' ?></div>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Flashcards Mastered</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total cards learned</p>
                </div>
                <div class="text-2xl font-bold text-green-600"><?= $masteredCards ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Strengths and Weaknesses -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Strengths -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mr-2">
                <i class="fas fa-star text-green-600 dark:text-green-400"></i>
            </span>
            Your Strengths
        </h2>
        <?php if (!empty($strengths)): ?>
        <div class="space-y-3">
            <?php foreach ($strengths as $topic => $score): ?>
            <div class="flex items-center justify-between">
                <span class="text-gray-700 dark:text-gray-300"><?= h($topic) ?></span>
                <div class="flex items-center">
                    <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-3">
                        <div class="bg-green-500 h-2 rounded-full" style="width: <?= $score ?>%"></div>
                    </div>
                    <span class="text-sm font-medium text-green-600"><?= $score ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-gray-500 dark:text-gray-400">Complete more quizzes to identify your strengths.</p>
        <?php endif; ?>
    </div>
    
    <!-- Weaknesses -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <span class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mr-2">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
            </span>
            Areas to Improve
        </h2>
        <?php if (!empty($weaknesses)): ?>
        <div class="space-y-3">
            <?php foreach ($weaknesses as $topic => $score): ?>
            <div class="flex items-center justify-between">
                <span class="text-gray-700 dark:text-gray-300"><?= h($topic) ?></span>
                <div class="flex items-center">
                    <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-3">
                        <div class="bg-red-500 h-2 rounded-full" style="width: <?= $score ?>%"></div>
                    </div>
                    <span class="text-sm font-medium text-red-600"><?= $score ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="lessons.php" class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-700 text-sm font-medium">
            <i class="fas fa-arrow-right mr-1"></i>
            Generate lessons for weak areas
        </a>
        <?php else: ?>
        <p class="text-gray-500 dark:text-gray-400">Complete more quizzes to identify areas for improvement.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Achievements Section -->
<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Achievements</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <?php
        $allAchievements = [
            'first_lesson' => ['icon' => '📖', 'title' => 'First Lesson', 'desc' => 'Complete your first lesson'],
            'first_quiz' => ['icon' => '📝', 'title' => 'Quiz Starter', 'desc' => 'Complete your first quiz'],
            'first_test' => ['icon' => '📊', 'title' => 'Test Taker', 'desc' => 'Complete a practice test'],
            'streak_3' => ['icon' => '🔥', 'title' => '3 Day Streak', 'desc' => 'Study 3 days in a row'],
            'streak_7' => ['icon' => '🔥', 'title' => 'Week Warrior', 'desc' => 'Study 7 days in a row'],
            'streak_30' => ['icon' => '🏆', 'title' => 'Monthly Master', 'desc' => 'Study 30 days in a row'],
            'quiz_perfect' => ['icon' => '⭐', 'title' => 'Perfect Score', 'desc' => 'Get 100% on a quiz'],
            'lessons_10' => ['icon' => '📚', 'title' => 'Bookworm', 'desc' => 'Complete 10 lessons'],
            'quizzes_20' => ['icon' => '🎯', 'title' => 'Quiz Master', 'desc' => 'Complete 20 quizzes'],
            'score_30' => ['icon' => '🏅', 'title' => 'Top Scorer', 'desc' => 'Score 30+ on practice test'],
            'flashcards_100' => ['icon' => '🧠', 'title' => 'Memory Pro', 'desc' => 'Master 100 flashcards'],
            'xp_1000' => ['icon' => '💎', 'title' => 'XP Champion', 'desc' => 'Earn 1000 XP']
        ];
        foreach ($allAchievements as $key => $achievement):
            $earned = in_array($key, $achievements);
        ?>
        <div class="text-center p-4 rounded-lg <?= $earned ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' : 'bg-gray-50 dark:bg-gray-700/50 opacity-50' ?>">
            <div class="text-3xl mb-2 <?= !$earned ? 'grayscale' : '' ?>"><?= $achievement['icon'] ?></div>
            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= $achievement['title'] ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400"><?= $achievement['desc'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
    <div class="space-y-4">
        <?php
        // Combine all activities
        $activities = [];
        
        foreach (array_slice($lessons, 0, 5) as $lesson) {
            $activities[] = [
                'type' => 'lesson',
                'icon' => 'fas fa-book',
                'color' => 'text-blue-600',
                'bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'title' => 'Completed lesson: ' . ($lesson['title'] ?? 'Untitled'),
                'date' => $lesson['created_at'] ?? date('Y-m-d')
            ];
        }
        
        foreach (array_slice($quizzes, 0, 5) as $quiz) {
            $score = round(($quiz['correct_count'] / max(1, $quiz['total_questions'])) * 100);
            $activities[] = [
                'type' => 'quiz',
                'icon' => 'fas fa-clipboard-check',
                'color' => 'text-green-600',
                'bg' => 'bg-green-100 dark:bg-green-900/30',
                'title' => "Quiz completed: {$score}% ({$quiz['subject']} - {$quiz['topic']})",
                'date' => $quiz['created_at'] ?? date('Y-m-d')
            ];
        }
        
        foreach (array_slice(array_filter($tests, fn($t) => $t['completed'] ?? false), 0, 5) as $test) {
            $activities[] = [
                'type' => 'test',
                'icon' => 'fas fa-file-alt',
                'color' => 'text-purple-600',
                'bg' => 'bg-purple-100 dark:bg-purple-900/30',
                'title' => "Practice test completed: {$test['composite_score']}/36",
                'date' => $test['completed_at'] ?? $test['created_at'] ?? date('Y-m-d')
            ];
        }
        
        // Sort by date
        usort($activities, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        $activities = array_slice($activities, 0, 10);
        
        if (empty($activities)):
        ?>
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No activity yet. Start learning to track your progress!</p>
        <?php else: foreach ($activities as $activity): ?>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="w-10 h-10 rounded-full <?= $activity['bg'] ?> flex items-center justify-center mr-4">
                <i class="<?= $activity['icon'] ?> <?= $activity['color'] ?>"></i>
            </div>
            <div class="flex-grow">
                <p class="text-gray-900 dark:text-white"><?= h($activity['title']) ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= format_date($activity['date'], 'M j, Y \a\t g:i A') ?></p>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
// Score History Chart
const scoreHistoryCtx = document.getElementById('scoreHistoryChart');
if (scoreHistoryCtx) {
    const scoreHistory = <?= json_encode(array_values($combinedItems)) ?>;
    
    const quizData = scoreHistory.filter(item => item.type === 'quiz');
    const testData = scoreHistory.filter(item => item.type === 'test');
    
    new Chart(scoreHistoryCtx, {
        type: 'line',
        data: {
            labels: scoreHistory.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Quiz Scores',
                    data: scoreHistory.map(item => item.type === 'quiz' ? item.score : null),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    spanGaps: true
                },
                {
                    label: 'Test Scores',
                    data: scoreHistory.map(item => item.type === 'test' ? item.score : null),
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    fill: true,
                    tension: 0.4,
                    spanGaps: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Subject Performance Chart
const subjectCtx = document.getElementById('subjectChart');
if (subjectCtx) {
    const subjectData = <?= json_encode($subjectPerformance) ?>;
    
    new Chart(subjectCtx, {
        type: 'bar',
        data: {
            labels: ['English', 'Math', 'Reading', 'Science'],
            datasets: [{
                label: 'Average Score (%)',
                data: [
                    subjectData.english.avg,
                    subjectData.math.avg,
                    subjectData.reading.avg,
                    subjectData.science.avg
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(124, 58, 237, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)'
                ],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Weekly Activity Chart
const weeklyCtx = document.getElementById('weeklyActivityChart');
if (weeklyCtx) {
    const weeklyData = <?= json_encode(array_values($weeklyActivity)) ?>;
    
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: weeklyData.map(d => d.day),
            datasets: [
                {
                    label: 'Lessons',
                    data: weeklyData.map(d => d.lessons),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderRadius: 4
                },
                {
                    label: 'Quizzes',
                    data: weeklyData.map(d => d.quizzes),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 4
                },
                {
                    label: 'Tests',
                    data: weeklyData.map(d => d.tests),
                    backgroundColor: 'rgba(124, 58, 237, 0.8)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function exportProgress() {
    const data = {
        exportDate: new Date().toISOString(),
        summary: {
            studyStreak: <?= $studyStreak ?>,
            totalStudyTime: '<?= format_study_time($totalStudyTime) ?>',
            lessonsCompleted: <?= $completedLessons ?>,
            quizzesTaken: <?= $completedQuizzes ?>,
            testsCompleted: <?= $completedTests ?>,
            avgQuizScore: <?= $avgQuizScore ?>,
            avgTestScore: <?= $avgTestScore ?>,
            xp: <?= $xp ?>,
            level: <?= $level ?>
        },
        subjectPerformance: <?= json_encode($subjectPerformance) ?>,
        strengths: <?= json_encode($strengths) ?>,
        weaknesses: <?= json_encode($weaknesses) ?>
    };
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'act-progress-' + new Date().toISOString().split('T')[0] + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showToast('Progress exported successfully!', 'success');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
