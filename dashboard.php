<?php
/**
 * ACT AI Tutor - Dashboard
 * 
 * Main dashboard with stats, quick actions, and overview.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Dashboard';
$user = auth_user();
$userId = auth_user_id();

// Get user's progress data
$progress = db_read_user($userId, 'progress');
$lessons = db_read_user($userId, 'lessons');
$quizzes = db_read_user($userId, 'quizzes');
$tests = db_read_user($userId, 'tests');

// Calculate stats
$lessonsCompleted = count(array_filter($lessons, fn($l) => $l['completed'] ?? false));
$totalLessons = count($lessons);
$quizzesTaken = count($quizzes);
$testsTaken = count($tests);

// Calculate average quiz score
$quizScores = array_filter(array_map(fn($q) => $q['score'] ?? null, $quizzes));
$avgQuizScore = !empty($quizScores) ? round(array_sum($quizScores) / count($quizScores)) : 0;

// Get latest test score
$latestTest = !empty($tests) ? end($tests) : null;
$latestTestScore = $latestTest['composite_score'] ?? null;

// Get study time from progress
$studyTime = $progress['study_time'] ?? 0;

// Get next level info
$levelInfo = get_next_level_info($user['xp']);

// Recent activity
$recentLessons = array_slice(array_reverse($lessons), 0, 5);
$recentQuizzes = array_slice(array_reverse($quizzes), 0, 5);

include __DIR__ . '/includes/header.php';
?>

<!-- Dashboard Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
        Welcome back, <?= h($user['name']) ?>!
    </h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">
        <?php if ($user['streak'] > 0): ?>
            <i class="fas fa-fire text-orange-500"></i>
            You're on a <?= $user['streak'] ?> day streak! Keep it up!
        <?php else: ?>
            Start studying today to build your streak!
        <?php endif; ?>
    </p>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <!-- Study Streak -->
    <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-xl p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/80 text-sm">Study Streak</p>
                <p class="text-3xl font-bold"><?= $user['streak'] ?></p>
                <p class="text-white/80 text-xs">days</p>
            </div>
            <i class="fas fa-fire text-4xl text-white/30"></i>
        </div>
    </div>
    
    <!-- XP & Level -->
    <div class="bg-gradient-to-br from-purple-500 to-indigo-500 rounded-xl p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/80 text-sm">Level <?= $user['level'] ?></p>
                <p class="text-3xl font-bold"><?= number_format($user['xp']) ?></p>
                <p class="text-white/80 text-xs">XP</p>
            </div>
            <i class="fas fa-star text-4xl text-white/30"></i>
        </div>
        <?php if ($levelInfo['next_level']): ?>
        <div class="mt-2">
            <div class="bg-white/20 rounded-full h-2">
                <div class="bg-white rounded-full h-2" style="width: <?= min(100, $levelInfo['progress']) ?>%"></div>
            </div>
            <p class="text-white/80 text-xs mt-1"><?= $levelInfo['xp_needed'] ?> XP to Level <?= $levelInfo['next_level'] ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Latest Test Score -->
    <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/80 text-sm">Latest ACT Score</p>
                <?php if ($latestTestScore): ?>
                <p class="text-3xl font-bold"><?= $latestTestScore ?></p>
                <p class="text-white/80 text-xs">/36 composite</p>
                <?php else: ?>
                <p class="text-xl font-bold mt-2">No tests yet</p>
                <?php endif; ?>
            </div>
            <i class="fas fa-trophy text-4xl text-white/30"></i>
        </div>
    </div>
    
    <!-- Quiz Average -->
    <div class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/80 text-sm">Quiz Average</p>
                <?php if ($quizzesTaken > 0): ?>
                <p class="text-3xl font-bold"><?= $avgQuizScore ?>%</p>
                <p class="text-white/80 text-xs"><?= $quizzesTaken ?> quizzes taken</p>
                <?php else: ?>
                <p class="text-xl font-bold mt-2">No quizzes yet</p>
                <?php endif; ?>
            </div>
            <i class="fas fa-chart-line text-4xl text-white/30"></i>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <a href="lessons.php" class="flex flex-col items-center p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
            <i class="fas fa-book-open text-2xl text-blue-600 dark:text-blue-400 mb-2"></i>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">New Lesson</span>
        </a>
        <a href="quiz.php" class="flex flex-col items-center p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/50 transition">
            <i class="fas fa-question-circle text-2xl text-purple-600 dark:text-purple-400 mb-2"></i>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Take Quiz</span>
        </a>
        <a href="practice-test.php" class="flex flex-col items-center p-4 bg-green-50 dark:bg-green-900/30 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition">
            <i class="fas fa-file-alt text-2xl text-green-600 dark:text-green-400 mb-2"></i>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Practice Test</span>
        </a>
        <a href="chat.php" class="flex flex-col items-center p-4 bg-pink-50 dark:bg-pink-900/30 rounded-lg hover:bg-pink-100 dark:hover:bg-pink-900/50 transition">
            <i class="fas fa-comments text-2xl text-pink-600 dark:text-pink-400 mb-2"></i>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">AI Tutor</span>
        </a>
        <a href="flashcards.php" class="flex flex-col items-center p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/50 transition">
            <i class="fas fa-layer-group text-2xl text-yellow-600 dark:text-yellow-400 mb-2"></i>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Flashcards</span>
        </a>
        <a href="study-plan.php" class="flex flex-col items-center p-4 bg-teal-50 dark:bg-teal-900/30 rounded-lg hover:bg-teal-100 dark:hover:bg-teal-900/50 transition">
            <i class="fas fa-calendar-alt text-2xl text-teal-600 dark:text-teal-400 mb-2"></i>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Study Plan</span>
        </a>
    </div>
</div>

<!-- Two Column Layout -->
<div class="grid lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Progress Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Progress</h2>
                <a href="progress.php" class="text-primary-600 hover:text-primary-700 text-sm">View Details →</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-3xl font-bold text-primary-600"><?= $totalLessons ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Lessons</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-3xl font-bold text-purple-600"><?= $quizzesTaken ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Quizzes</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-3xl font-bold text-green-600"><?= $testsTaken ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Practice Tests</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-3xl font-bold text-orange-600"><?= format_duration($studyTime) ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Study Time</p>
                </div>
            </div>
            
            <!-- Score Trend Chart -->
            <?php if (count($quizzes) >= 2): ?>
            <div class="mt-6">
                <canvas id="scoreChart" height="200"></canvas>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Lessons -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Lessons</h2>
                <a href="lessons.php" class="text-primary-600 hover:text-primary-700 text-sm">View All →</a>
            </div>
            <?php if (empty($recentLessons)): ?>
            <div class="text-center py-8">
                <i class="fas fa-book-open text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">No lessons yet</p>
                <a href="lessons.php" class="inline-block mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    Generate Your First Lesson
                </a>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentLessons as $lesson): ?>
                <a href="lesson-view.php?id=<?= $lesson['id'] ?>" class="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-white"><?= h($lesson['topic']) ?></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= h($lesson['subject']) ?> • <?= h(ucfirst($lesson['difficulty'])) ?>
                            </p>
                        </div>
                        <div class="flex items-center">
                            <?php if ($lesson['completed'] ?? false): ?>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs rounded-full">
                                <i class="fas fa-check mr-1"></i>Completed
                            </span>
                            <?php else: ?>
                            <span class="text-gray-400 dark:text-gray-500 text-sm">
                                <?= time_ago($lesson['created_at']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-8">
        <!-- Target Score -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Goals</h2>
            <?php if ($user['target_score']): ?>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-primary-100 dark:bg-primary-900 mb-4">
                    <span class="text-3xl font-bold text-primary-600 dark:text-primary-400"><?= $user['target_score'] ?></span>
                </div>
                <p class="text-gray-600 dark:text-gray-400">Target ACT Score</p>
                <?php if ($user['test_date']): ?>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                    <i class="fas fa-calendar mr-1"></i>
                    Test Date: <?= format_date($user['test_date']) ?>
                </p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="text-center">
                <i class="fas fa-bullseye text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Set your target score to track progress</p>
                <a href="settings.php" class="text-primary-600 hover:text-primary-700">Set Target Score →</a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Quizzes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Quizzes</h2>
                <a href="quiz.php" class="text-primary-600 hover:text-primary-700 text-sm">View All →</a>
            </div>
            <?php if (empty($recentQuizzes)): ?>
            <div class="text-center py-4">
                <i class="fas fa-question-circle text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                <p class="text-gray-500 dark:text-gray-400 text-sm">No quizzes yet</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentQuizzes as $quiz): ?>
                <a href="quiz-results.php?id=<?= $quiz['id'] ?>" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm"><?= h($quiz['topic']) ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= time_ago($quiz['created_at']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold <?= ($quiz['score'] ?? 0) >= 70 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $quiz['score'] ?? 0 ?>%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <?= $quiz['correct'] ?? 0 ?>/<?= $quiz['total'] ?? 0 ?>
                            </p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Tips -->
        <div class="bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl p-6 text-white">
            <h2 class="text-lg font-semibold mb-3">💡 Daily Tip</h2>
            <p class="text-white/90 text-sm">
                <?php
                $tips = [
                    "Review your mistakes from the last quiz to avoid repeating them.",
                    "Take timed practice tests to build stamina for test day.",
                    "Focus on your weak areas but don't neglect your strengths.",
                    "Use flashcards for quick review of key concepts.",
                    "Chat with the AI tutor when you're stuck on a concept.",
                    "Consistent daily practice beats occasional long sessions.",
                    "Read the question carefully before looking at the answers.",
                    "For math, always check your answer by plugging it back in."
                ];
                echo $tips[array_rand($tips)];
                ?>
            </p>
        </div>
    </div>
</div>

<?php if (count($quizzes) >= 2): ?>
<script>
    // Score trend chart
    const quizData = <?= json_encode(array_slice($quizzes, -10)) ?>;
    
    if (quizData.length >= 2) {
        const ctx = document.getElementById('scoreChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: quizData.map(q => new Date(q.created_at).toLocaleDateString()),
                datasets: [{
                    label: 'Quiz Score (%)',
                    data: quizData.map(q => q.score || 0),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: value => value + '%'
                        }
                    }
                }
            }
        });
    }
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
