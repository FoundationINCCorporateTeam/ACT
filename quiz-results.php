<?php
/**
 * ACT AI Tutor - Quiz Results Page
 * 
 * Detailed quiz results and review.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$userId = auth_user_id();
$quizId = input('id', 'int');

if (!$quizId) {
    flash('error', 'Quiz not found.');
    redirect('quiz.php');
}

$quiz = db_get_user_item($userId, 'quizzes', $quizId);

if (!$quiz || !isset($quiz['completed']) || !$quiz['completed']) {
    flash('error', 'Quiz not found or not completed.');
    redirect('quiz.php');
}

$pageTitle = 'Quiz Results';
$breadcrumbs = [
    ['title' => 'Quizzes', 'url' => 'quiz.php'],
    ['title' => $quiz['topic']],
    ['title' => 'Results']
];

$score = $quiz['score'] ?? 0;
$correct = $quiz['correct'] ?? 0;
$total = $quiz['total'] ?? 0;
$letterGrade = get_letter_grade($score);

// Get previous attempts for comparison
$allQuizzes = db_read_user($userId, 'quizzes');
$previousAttempts = array_filter($allQuizzes, function($q) use ($quiz, $quizId) {
    return $q['topic'] === $quiz['topic'] && 
           $q['id'] !== $quizId && 
           isset($q['completed']) && $q['completed'];
});
$previousAttempts = array_slice(array_reverse($previousAttempts), 0, 5);

// Calculate topic performance
$topicBreakdown = [];
foreach ($quiz['questions'] as $i => $question) {
    $questionTopic = $question['topic'] ?? $quiz['subject'];
    
    if (!isset($topicBreakdown[$questionTopic])) {
        $topicBreakdown[$questionTopic] = ['correct' => 0, 'total' => 0];
    }
    
    $topicBreakdown[$questionTopic]['total']++;
    
    $userAnswer = $quiz['answers'][$i] ?? null;
    if ($userAnswer === $question['correct']) {
        $topicBreakdown[$questionTopic]['correct']++;
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Results Header -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 mb-6 text-center">
    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-4 <?= $score >= 70 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' ?>">
        <span class="text-4xl font-bold <?= $score >= 70 ? 'text-green-600' : 'text-red-600' ?>"><?= $score ?>%</span>
    </div>
    
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        <?php if ($score === 100): ?>
            🎉 Perfect Score!
        <?php elseif ($score >= 90): ?>
            🌟 Excellent Work!
        <?php elseif ($score >= 70): ?>
            👍 Good Job!
        <?php elseif ($score >= 50): ?>
            📚 Keep Practicing
        <?php else: ?>
            💪 Don't Give Up!
        <?php endif; ?>
    </h1>
    
    <p class="text-gray-600 dark:text-gray-400 mb-4">
        You got <strong><?= $correct ?></strong> out of <strong><?= $total ?></strong> questions correct
    </p>
    
    <div class="flex justify-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
        <span><i class="fas fa-clock mr-1"></i><?= format_duration($quiz['time_taken'] ?? 0) ?></span>
        <span><i class="fas fa-signal mr-1"></i><?= ucfirst($quiz['difficulty']) ?></span>
        <span><i class="fas fa-calendar mr-1"></i><?= format_date($quiz['completed_at'] ?? $quiz['created_at']) ?></span>
    </div>
    
    <div class="flex justify-center space-x-4 mt-6">
        <a href="quiz.php" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
            <i class="fas fa-plus mr-2"></i>New Quiz
        </a>
        <button onclick="retakeQuiz(<?= $quizId ?>)" class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <i class="fas fa-redo mr-2"></i>Retake Quiz
        </button>
        <button onclick="printPage()" class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <i class="fas fa-print mr-2"></i>Print
        </button>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2">
        <!-- Performance by Topic -->
        <?php if (count($topicBreakdown) > 1): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance by Topic</h2>
            <div class="space-y-4">
                <?php foreach ($topicBreakdown as $topic => $data): ?>
                <?php $topicPercent = $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100) : 0; ?>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-700 dark:text-gray-300"><?= h($topic) ?></span>
                        <span class="text-sm font-medium <?= $topicPercent >= 70 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $data['correct'] ?>/<?= $data['total'] ?> (<?= $topicPercent ?>%)
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="<?= $topicPercent >= 70 ? 'bg-green-600' : 'bg-red-600' ?> h-2 rounded-full" style="width: <?= $topicPercent ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Question Review -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-6 border-b dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Question Review</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Click on any question to see detailed explanation
                </p>
            </div>
            
            <div class="divide-y dark:divide-gray-700">
                <?php foreach ($quiz['questions'] as $i => $question): ?>
                <?php 
                    $userAnswer = $quiz['answers'][$i] ?? null;
                    $isCorrect = $userAnswer === $question['correct'];
                    $isFlagged = in_array($i, $quiz['flagged'] ?? []);
                ?>
                <div class="p-6 <?= $isCorrect ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' ?> <?= $isFlagged ? 'border-l-4 border-yellow-500' : '' ?>">
                    <div class="flex items-start justify-between mb-4">
                        <span class="flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $isCorrect ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300' ?>">
                            <i class="fas <?= $isCorrect ? 'fa-check' : 'fa-times' ?> mr-1"></i>
                            Question <?= $i + 1 ?>
                        </span>
                        <?php if ($isFlagged): ?>
                        <span class="text-yellow-600 text-sm">
                            <i class="fas fa-flag mr-1"></i>Flagged
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="markdown-content mb-4">
                        <p class="text-gray-900 dark:text-white"><?= h($question['question']) ?></p>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <?php foreach ($question['options'] as $key => $value): ?>
                        <div class="p-3 rounded-lg border-2 
                                    <?php 
                                    if ($key === $question['correct']) {
                                        echo 'border-green-500 bg-green-100 dark:bg-green-900/50';
                                    } elseif ($key === $userAnswer && !$isCorrect) {
                                        echo 'border-red-500 bg-red-100 dark:bg-red-900/50';
                                    } else {
                                        echo 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800';
                                    }
                                    ?>">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full 
                                            <?php 
                                            if ($key === $question['correct']) {
                                                echo 'bg-green-500 text-white';
                                            } elseif ($key === $userAnswer && !$isCorrect) {
                                                echo 'bg-red-500 text-white';
                                            } else {
                                                echo 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                                            }
                                            ?> font-semibold text-sm mr-3"><?= $key ?></span>
                                <span class="text-gray-700 dark:text-gray-300"><?= h($value) ?></span>
                                
                                <?php if ($key === $question['correct']): ?>
                                <span class="ml-auto text-green-600 text-sm"><i class="fas fa-check"></i> Correct</span>
                                <?php elseif ($key === $userAnswer && !$isCorrect): ?>
                                <span class="ml-auto text-red-600 text-sm"><i class="fas fa-times"></i> Your answer</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($question['explanation'])): ?>
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">
                            <i class="fas fa-lightbulb mr-1"></i>Explanation
                        </h4>
                        <div class="text-blue-700 dark:text-blue-300 text-sm markdown-content" id="explanation-<?= $i ?>">
                            <?= h($question['explanation']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <button onclick="getAIExplanation(<?= $quizId ?>, <?= $i ?>)" 
                                class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                id="explain-btn-<?= $i ?>">
                            <i class="fas fa-robot mr-1"></i>Get Detailed AI Explanation
                        </button>
                        <div id="ai-explanation-<?= $i ?>" class="hidden mt-4 p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-200 dark:border-purple-800">
                            <h4 class="font-semibold text-purple-800 dark:text-purple-200 mb-2">
                                <i class="fas fa-brain mr-1"></i>AI Tutor Explanation
                            </h4>
                            <div class="text-purple-700 dark:text-purple-300 text-sm markdown-content ai-explanation-content"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quiz Summary</h3>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Topic</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= h($quiz['topic']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Subject</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= h($quiz['subject']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Difficulty</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= h(ucfirst($quiz['difficulty'])) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Time Taken</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= format_duration($quiz['time_taken'] ?? 0) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Avg. Time/Question</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        <?= $total > 0 ? format_duration(round(($quiz['time_taken'] ?? 0) / $total)) : 'N/A' ?>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Grade</dt>
                    <dd class="font-medium text-2xl <?= $letterGrade === 'A' || $letterGrade === 'B' ? 'text-green-600' : ($letterGrade === 'F' ? 'text-red-600' : 'text-yellow-600') ?>"><?= $letterGrade ?></dd>
                </div>
            </dl>
        </div>
        
        <!-- Previous Attempts -->
        <?php if (!empty($previousAttempts)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Previous Attempts</h3>
            <div class="space-y-3">
                <?php foreach ($previousAttempts as $attempt): ?>
                <a href="quiz-results.php?id=<?= $attempt['id'] ?>" 
                   class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500 dark:text-gray-400"><?= format_date($attempt['completed_at'] ?? $attempt['created_at']) ?></span>
                        <span class="font-bold <?= ($attempt['score'] ?? 0) >= 70 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $attempt['score'] ?? 0 ?>%
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($previousAttempts) >= 2): ?>
            <div class="mt-4">
                <canvas id="attempts-chart" height="150"></canvas>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Recommendations -->
        <div class="bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl p-6 text-white">
            <h3 class="text-lg font-semibold mb-4">What's Next?</h3>
            <div class="space-y-3">
                <?php if ($score < 70): ?>
                <a href="lessons.php?subject=<?= urlencode($quiz['subject']) ?>" 
                   class="block px-4 py-3 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-book mr-2"></i>Review <?= h($quiz['subject']) ?> Lessons
                </a>
                <?php endif; ?>
                <a href="chat.php?topic=<?= urlencode($quiz['topic']) ?>" 
                   class="block px-4 py-3 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-comments mr-2"></i>Ask AI About This Topic
                </a>
                <a href="flashcards.php?generate=1&topic=<?= urlencode($quiz['topic']) ?>" 
                   class="block px-4 py-3 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-layer-group mr-2"></i>Create Flashcards
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Render math in questions and explanations
document.addEventListener('DOMContentLoaded', function() {
    renderMath();
});

async function getAIExplanation(quizId, questionIndex) {
    const btn = document.getElementById('explain-btn-' + questionIndex);
    const container = document.getElementById('ai-explanation-' + questionIndex);
    const content = container.querySelector('.ai-explanation-content');
    
    btn.innerHTML = '<div class="spinner inline-block mr-2" style="width:16px;height:16px"></div>Generating...';
    btn.disabled = true;
    
    const result = await apiRequest('api/quiz.php?action=get_explanation', {
        quiz_id: quizId,
        question_index: questionIndex,
        csrf_token: csrfToken
    });
    
    if (result.success) {
        content.innerHTML = renderMarkdown(result.data.explanation);
        container.classList.remove('hidden');
        renderMath();
        btn.classList.add('hidden');
    } else {
        showToast(result.message || 'Failed to generate explanation', 'error');
        btn.innerHTML = '<i class="fas fa-robot mr-1"></i>Get Detailed AI Explanation';
        btn.disabled = false;
    }
}

async function retakeQuiz(id) {
    const result = await apiRequest('api/quiz.php?action=retake', {
        id: id,
        csrf_token: csrfToken
    });
    
    if (result.success) {
        window.location.href = 'quiz.php?resume=' + result.data.id;
    } else {
        showToast(result.message || 'Failed to create quiz', 'error');
    }
}

<?php if (!empty($previousAttempts) && count($previousAttempts) >= 2): ?>
// Chart for previous attempts
const attempts = <?= json_encode(array_merge($previousAttempts, [['score' => $score, 'completed_at' => $quiz['completed_at'] ?? $quiz['created_at']]])) ?>;
const ctx = document.getElementById('attempts-chart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: attempts.map(a => new Date(a.completed_at || a.created_at).toLocaleDateString()),
        datasets: [{
            label: 'Score',
            data: attempts.map(a => a.score || 0),
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
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
