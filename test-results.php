<?php
/**
 * ACT AI Tutor - Test Results Page
 * 
 * Detailed practice test results and analytics.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$userId = auth_user_id();
$testId = input('id', 'int');

if (!$testId) {
    flash('error', 'Test not found.');
    redirect('practice-test.php');
}

$test = db_get_user_item($userId, 'tests', $testId);

if (!$test || !isset($test['completed']) || !$test['completed']) {
    flash('error', 'Test not found or not completed.');
    redirect('practice-test.php');
}

$pageTitle = 'Test Results';
$breadcrumbs = [
    ['title' => 'Practice Tests', 'url' => 'practice-test.php'],
    ['title' => 'Results']
];

$compositeScore = $test['composite_score'] ?? 0;
$sectionScores = $test['section_scores'] ?? [];
$percentile = $test['percentile'] ?? get_percentile($compositeScore);

include __DIR__ . '/includes/header.php';
?>

<!-- Score Card -->
<div class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-xl p-8 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center justify-between">
        <div class="text-center md:text-left mb-6 md:mb-0">
            <h1 class="text-3xl font-bold mb-2">Your ACT Practice Test Results</h1>
            <p class="text-white/80">Completed on <?= format_date($test['completed_at'] ?? $test['created_at'], 'F j, Y \a\t g:i A') ?></p>
        </div>
        
        <div class="text-center">
            <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center mb-2">
                <span class="text-5xl font-bold text-primary-600"><?= $compositeScore ?></span>
            </div>
            <p class="text-white/80">Composite Score</p>
            <p class="text-sm text-white/60"><?= $percentile ?>th percentile</p>
        </div>
    </div>
</div>

<!-- Section Scores -->
<div class="grid md:grid-cols-4 gap-4 mb-6">
    <?php 
    $sectionColors = [
        'english' => 'blue',
        'math' => 'green',
        'reading' => 'purple',
        'science' => 'orange'
    ];
    foreach ($sectionScores as $section => $score): 
    $color = $sectionColors[$section] ?? 'gray';
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm text-center">
        <div class="w-16 h-16 bg-<?= $color ?>-100 dark:bg-<?= $color ?>-900 rounded-full flex items-center justify-center mx-auto mb-3">
            <span class="text-2xl font-bold text-<?= $color ?>-600"><?= $score ?></span>
        </div>
        <h3 class="font-semibold text-gray-900 dark:text-white"><?= h(ACT_SECTIONS[$section]['name'] ?? ucfirst($section)) ?></h3>
        <p class="text-sm text-gray-500 dark:text-gray-400"><?= get_percentile($score) ?>th percentile</p>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Performance Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Score Breakdown</h2>
            <canvas id="score-chart" height="200"></canvas>
        </div>
        
        <!-- Section Details -->
        <?php foreach ($test['sections'] as $sectionKey => $section): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-6 border-b dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= h($section['name']) ?></h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Score: <?= $section['scaled_score'] ?? 0 ?> | 
                        Raw: <?= $section['raw_score'] ?? 0 ?>/<?= count($section['questions']) ?> |
                        Time: <?= format_duration($section['time_taken'] ?? 0) ?>
                    </p>
                </div>
                <button onclick="toggleSection('<?= $sectionKey ?>')" class="text-primary-600 hover:text-primary-700">
                    <i class="fas fa-chevron-down" id="icon-<?= $sectionKey ?>"></i>
                </button>
            </div>
            
            <div id="section-<?= $sectionKey ?>" class="hidden divide-y dark:divide-gray-700">
                <?php foreach ($section['questions'] as $i => $question): ?>
                <?php 
                    $userAnswer = $section['answers'][$i] ?? null;
                    $isCorrect = $userAnswer === $question['correct'];
                ?>
                <div class="p-4 <?= $isCorrect ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' ?>">
                    <div class="flex items-start justify-between mb-2">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            Q<?= $i + 1 ?> - <?= $isCorrect ? 'Correct' : 'Incorrect' ?>
                        </span>
                    </div>
                    <p class="text-gray-900 dark:text-white mb-2"><?= h($question['question']) ?></p>
                    
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <?php foreach ($question['options'] as $key => $value): ?>
                        <div class="p-2 rounded <?= $key === $question['correct'] ? 'bg-green-100 dark:bg-green-900' : ($key === $userAnswer && !$isCorrect ? 'bg-red-100 dark:bg-red-900' : 'bg-gray-100 dark:bg-gray-700') ?>">
                            <strong><?= $key ?>:</strong> <?= h(truncate($value, 50)) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($question['explanation'])): ?>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <strong>Explanation:</strong> <?= h($question['explanation']) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Test Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Test Summary</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Composite Score</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?= $compositeScore ?>/36</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">National Percentile</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= $percentile ?>th</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Total Time</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= format_duration($test['total_time'] ?? 0) ?></dd>
                </div>
            </dl>
        </div>
        
        <!-- Score Interpretation -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">What Your Score Means</h3>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <?php if ($compositeScore >= 30): ?>
                <p><strong class="text-green-600">Excellent!</strong> Your score is competitive for top universities.</p>
                <?php elseif ($compositeScore >= 24): ?>
                <p><strong class="text-blue-600">Good!</strong> Your score is above the national average and competitive for many colleges.</p>
                <?php elseif ($compositeScore >= 20): ?>
                <p><strong class="text-yellow-600">Average</strong> Your score is around the national average. Focus on your weak areas to improve.</p>
                <?php else: ?>
                <p><strong class="text-orange-600">Keep Practicing!</strong> With consistent study, you can significantly improve your score.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl p-6 text-white">
            <h3 class="text-lg font-semibold mb-4">Next Steps</h3>
            <div class="space-y-3">
                <a href="practice-test.php" class="block px-4 py-3 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-redo mr-2"></i>Take Another Test
                </a>
                <a href="study-plan.php" class="block px-4 py-3 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-calendar-alt mr-2"></i>Create Study Plan
                </a>
                <a href="lessons.php" class="block px-4 py-3 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-book mr-2"></i>Review Lessons
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSection(sectionKey) {
    const section = document.getElementById('section-' + sectionKey);
    const icon = document.getElementById('icon-' + sectionKey);
    
    section.classList.toggle('hidden');
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-up');
}

// Score chart
const sectionScores = <?= json_encode($sectionScores) ?>;
const labels = Object.keys(sectionScores).map(s => s.charAt(0).toUpperCase() + s.slice(1));
const scores = Object.values(sectionScores);

const ctx = document.getElementById('score-chart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Section Score',
            data: scores,
            backgroundColor: ['#3b82f6', '#22c55e', '#8b5cf6', '#f97316'],
            borderRadius: 8
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
                max: 36,
                ticks: {
                    stepSize: 6
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
