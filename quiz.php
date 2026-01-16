<?php
/**
 * ACT AI Tutor - Quiz Page
 * 
 * Generate and take quizzes.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Quizzes';
$breadcrumbs = [['title' => 'Quizzes']];
$userId = auth_user_id();

// Get user's quizzes
$quizzes = db_read_user($userId, 'quizzes');
$quizzes = array_reverse($quizzes);

// Check if there's a quiz in progress
$inProgressQuiz = null;
foreach ($quizzes as $quiz) {
    if (isset($quiz['in_progress']) && $quiz['in_progress'] === true) {
        $inProgressQuiz = $quiz;
        break;
    }
}

// Get pre-filled values from URL
$preSubject = input('subject');
$preTopic = input('topic');

// Get filter parameters
$filterSubject = input('filter_subject');
$page = input('page', 'int', 1);

if ($filterSubject) {
    $quizzes = array_filter($quizzes, fn($q) => $q['subject'] === $filterSubject);
}

$paginated = paginate(array_values($quizzes), $page, 10);

include __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Quizzes</h1>
    <button onclick="showGenerateModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
        <i class="fas fa-plus mr-2"></i>
        New Quiz
    </button>
</div>

<?php if ($inProgressQuiz): ?>
<!-- Resume Quiz Banner -->
<div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-exclamation-triangle mr-2"></i>Quiz In Progress
            </h3>
            <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                You have an unfinished quiz on <strong><?= h($inProgressQuiz['topic']) ?></strong>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="quiz.php?resume=<?= $inProgressQuiz['id'] ?>" 
               class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                Resume Quiz
            </a>
            <button onclick="abandonQuiz(<?= $inProgressQuiz['id'] ?>)" 
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Abandon
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-primary-600"><?= count($quizzes) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Quizzes</p>
    </div>
    <?php
    $scores = array_filter(array_map(fn($q) => $q['score'] ?? null, $quizzes));
    $avgScore = !empty($scores) ? round(array_sum($scores) / count($scores)) : 0;
    $perfectCount = count(array_filter($quizzes, fn($q) => ($q['score'] ?? 0) === 100));
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-green-600"><?= $avgScore ?>%</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Average Score</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-purple-600"><?= $perfectCount ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Perfect Scores</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-orange-600">
            <?php
            $totalQuestions = array_sum(array_map(fn($q) => $q['total'] ?? 0, $quizzes));
            $totalCorrect = array_sum(array_map(fn($q) => $q['correct'] ?? 0, $quizzes));
            echo $totalCorrect . '/' . $totalQuestions;
            ?>
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Questions Correct</p>
    </div>
</div>

<!-- Quiz History -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="p-6 border-b dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quiz History</h2>
            <select onchange="filterBySubject(this.value)" 
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">All Subjects</option>
                <?php foreach (array_keys(ACT_TOPICS) as $subject): ?>
                <option value="<?= h($subject) ?>" <?= $filterSubject === $subject ? 'selected' : '' ?>><?= h($subject) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <?php if (empty($paginated['items'])): ?>
    <div class="p-12 text-center">
        <i class="fas fa-question-circle text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Quizzes Yet</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Generate a quiz to test your knowledge!</p>
        <button onclick="showGenerateModal()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
            <i class="fas fa-plus mr-2"></i>Create Your First Quiz
        </button>
    </div>
    <?php else: ?>
    <div class="divide-y dark:divide-gray-700">
        <?php foreach ($paginated['items'] as $quiz): ?>
        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h3 class="font-medium text-gray-900 dark:text-white"><?= h($quiz['topic']) ?></h3>
                        <span class="px-2 py-0.5 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-xs font-medium rounded-full">
                            <?= h($quiz['subject']) ?>
                        </span>
                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded-full">
                            <?= h(ucfirst($quiz['difficulty'])) ?>
                        </span>
                    </div>
                    <div class="flex items-center space-x-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                        <span><?= $quiz['total'] ?? 0 ?> questions</span>
                        <span><?= time_ago($quiz['created_at']) ?></span>
                        <?php if (isset($quiz['time_taken'])): ?>
                        <span><i class="fas fa-clock mr-1"></i><?= format_duration($quiz['time_taken']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-2xl font-bold <?= ($quiz['score'] ?? 0) >= 70 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $quiz['score'] ?? 0 ?>%
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?= $quiz['correct'] ?? 0 ?>/<?= $quiz['total'] ?? 0 ?> correct
                        </p>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <a href="quiz-results.php?id=<?= $quiz['id'] ?>" 
                           class="px-3 py-1.5 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-800 transition text-sm">
                            Review
                        </a>
                        <button onclick="retakeQuiz(<?= $quiz['id'] ?>)" 
                                class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">
                            Retake
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($paginated['total_pages'] > 1): ?>
    <div class="p-4 border-t dark:border-gray-700 flex justify-center space-x-2">
        <?php for ($i = 1; $i <= $paginated['total_pages']; $i++): ?>
        <a href="?page=<?= $i ?>&filter_subject=<?= urlencode($filterSubject) ?>" 
           class="px-3 py-1 <?= $i === $page ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' ?> rounded-lg transition">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Quiz Taking Modal -->
<div id="quiz-modal" class="fixed inset-0 z-50 hidden">
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Quiz content will be injected here -->
    </div>
</div>

<script>
const topics = <?= json_encode(ACT_TOPICS) ?>;
const models = <?= json_encode(AI_MODELS) ?>;
const questionCounts = <?= json_encode(QUIZ_QUESTION_COUNTS) ?>;
const preSubject = <?= json_encode($preSubject) ?>;
const preTopic = <?= json_encode($preTopic) ?>;

// Show generate modal on page load if topic is pre-filled
if (preSubject || preTopic) {
    setTimeout(() => showGenerateModal(), 500);
}

function showGenerateModal() {
    const modalContent = `
        <form id="generate-quiz-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                <select name="subject" id="quiz-subject-select" required onchange="updateQuizTopics()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    <option value="">Select a subject</option>
                    ${Object.keys(topics).map(s => `<option value="${s}" ${preSubject === s ? 'selected' : ''}>${s}</option>`).join('')}
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Topic</label>
                <select name="topic" id="quiz-topic-select"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    <option value="">Select a topic or enter custom below</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Or Enter Custom Topic</label>
                <input type="text" name="custom_topic" id="quiz-custom-topic" placeholder="Enter your own topic..." value="${preTopic || ''}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Number of Questions</label>
                    <select name="num_questions" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        ${questionCounts.map(n => `<option value="${n}" ${n === 10 ? 'selected' : ''}>${n} questions</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Difficulty</label>
                    <select name="difficulty" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate" selected>Intermediate</option>
                        <option value="advanced">Advanced</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Limit (optional)</label>
                    <select name="time_limit"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        <option value="">No time limit</option>
                        <option value="5">5 minutes</option>
                        <option value="10">10 minutes</option>
                        <option value="15">15 minutes</option>
                        <option value="20">20 minutes</option>
                        <option value="30">30 minutes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">AI Model</label>
                    <select name="model"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        ${Object.entries(models).map(([k, v]) => `<option value="${k}" ${k === 'deepseek/deepseek-v3.2:thinking' ? 'selected' : ''}>${v}</option>`).join('')}
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    <i class="fas fa-magic mr-2"></i>Generate Quiz
                </button>
            </div>
        </form>
    `;
    
    showModal(modalContent, { title: 'Generate New Quiz', size: 'lg' });
    
    // Update topics if subject is pre-selected
    if (preSubject) {
        setTimeout(() => updateQuizTopics(), 100);
    }
    
    document.getElementById('generate-quiz-form').addEventListener('submit', handleGenerateQuiz);
}

function updateQuizTopics() {
    const subject = document.getElementById('quiz-subject-select').value;
    const topicSelect = document.getElementById('quiz-topic-select');
    
    topicSelect.innerHTML = '<option value="">Select a topic or enter custom below</option>';
    
    if (subject && topics[subject]) {
        Object.entries(topics[subject]).forEach(([topic, desc]) => {
            const option = document.createElement('option');
            option.value = topic;
            option.textContent = topic;
            topicSelect.appendChild(option);
        });
    }
}

async function handleGenerateQuiz(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    const topic = formData.get('custom_topic') || formData.get('topic');
    const subject = formData.get('subject');
    
    if (!subject) {
        showToast('Please select a subject', 'error');
        return;
    }
    
    if (!topic) {
        showToast('Please select or enter a topic', 'error');
        return;
    }
    
    setLoading(submitBtn, true);
    
    // Show progress modal
    showModal(`
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Generating Your Quiz</h3>
            <p class="text-gray-500 dark:text-gray-400">Creating ${formData.get('num_questions')} questions...</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">This may take 30-60 seconds</p>
        </div>
    `, { closeable: false });
    
    try {
        const result = await apiRequest('api/quiz.php?action=generate', {
            subject: subject,
            topic: topic,
            num_questions: parseInt(formData.get('num_questions')),
            difficulty: formData.get('difficulty'),
            time_limit: formData.get('time_limit') ? parseInt(formData.get('time_limit')) : null,
            model: formData.get('model'),
            csrf_token: csrfToken
        });
        
        if (result.success) {
            closeModal();
            startQuiz(result.data);
        } else {
            closeModal();
            showToast(result.message || 'Failed to generate quiz', 'error');
        }
    } catch (error) {
        closeModal();
        showToast('An error occurred. Please try again.', 'error');
    }
}

function startQuiz(quizData) {
    const quizModal = document.getElementById('quiz-modal');
    quizModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Initialize quiz state
    window.currentQuiz = {
        id: quizData.id,
        questions: quizData.questions,
        answers: {},
        flagged: [],
        eliminated: {},
        currentQuestion: 0,
        startTime: Date.now(),
        timeLimit: quizData.time_limit ? quizData.time_limit * 60 : null,
        notes: {}
    };
    
    renderQuiz();
    
    // Start timer if time limit is set
    if (window.currentQuiz.timeLimit) {
        startTimer();
    }
    
    // Auto-save every 30 seconds
    window.quizAutoSave = setInterval(saveQuizProgress, 30000);
}

function renderQuiz() {
    const quiz = window.currentQuiz;
    const question = quiz.questions[quiz.currentQuestion];
    const total = quiz.questions.length;
    const current = quiz.currentQuestion + 1;
    
    const quizModal = document.getElementById('quiz-modal');
    
    quizModal.innerHTML = `
        <div class="min-h-screen flex flex-col">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10">
                <div class="max-w-4xl mx-auto px-4 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                Question ${current} of ${total}
                            </span>
                            <button onclick="toggleFlag(${quiz.currentQuestion})" 
                                    class="p-2 rounded-lg ${quiz.flagged.includes(quiz.currentQuestion) ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'} hover:bg-yellow-200 transition"
                                    title="Flag for review">
                                <i class="fas fa-flag"></i>
                            </button>
                        </div>
                        
                        ${quiz.timeLimit ? `
                            <div id="quiz-timer" class="flex items-center space-x-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                <i class="fas fa-clock text-gray-600 dark:text-gray-400"></i>
                                <span id="timer-display" class="font-mono font-semibold text-gray-900 dark:text-white"></span>
                            </div>
                        ` : ''}
                        
                        <button onclick="showQuizOverview()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            <i class="fas fa-th mr-2"></i>Overview
                        </button>
                    </div>
                    
                    <!-- Progress bar -->
                    <div class="mt-4 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-primary-600 h-2 rounded-full transition-all" style="width: ${(current / total) * 100}%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Question Content -->
            <div class="flex-1 py-8">
                <div class="max-w-3xl mx-auto px-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8">
                        <div class="markdown-content mb-8" id="question-content">
                            <p class="text-xl text-gray-900 dark:text-white">${question.question}</p>
                        </div>
                        
                        <div class="space-y-3">
                            ${Object.entries(question.options).map(([key, value]) => `
                                <button onclick="selectAnswer('${key}')" 
                                        class="w-full text-left p-4 rounded-lg border-2 transition
                                               ${quiz.answers[quiz.currentQuestion] === key 
                                                   ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30' 
                                                   : 'border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600'}
                                               ${(quiz.eliminated[quiz.currentQuestion] || []).includes(key) ? 'option-eliminated' : ''}"
                                        id="option-${key}">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full 
                                                 ${quiz.answers[quiz.currentQuestion] === key 
                                                     ? 'bg-primary-600 text-white' 
                                                     : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'} 
                                                 font-semibold mr-3">${key}</span>
                                    <span class="text-gray-900 dark:text-white">${value}</span>
                                    <button onclick="event.stopPropagation(); eliminateOption(${quiz.currentQuestion}, '${key}')" 
                                            class="float-right text-gray-400 hover:text-red-500 transition"
                                            title="Eliminate option">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </button>
                            `).join('')}
                        </div>
                        
                        <!-- Notes -->
                        <div class="mt-6">
                            <button onclick="toggleNotes()" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                <i class="fas fa-sticky-note mr-1"></i>Scratch pad
                            </button>
                            <div id="notes-section" class="hidden mt-2">
                                <textarea id="question-notes" placeholder="Write your notes here..."
                                          class="w-full h-24 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 resize-none"
                                          onchange="saveNote(${quiz.currentQuestion})">${quiz.notes[quiz.currentQuestion] || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Navigation -->
            <div class="bg-white dark:bg-gray-800 border-t dark:border-gray-700 sticky bottom-0">
                <div class="max-w-4xl mx-auto px-4 py-4">
                    <div class="flex items-center justify-between">
                        <button onclick="prevQuestion()" 
                                class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition ${current === 1 ? 'invisible' : ''}">
                            <i class="fas fa-chevron-left mr-2"></i>Previous
                        </button>
                        
                        <div class="flex space-x-2">
                            ${quiz.questions.map((_, i) => `
                                <button onclick="goToQuestion(${i})" 
                                        class="w-8 h-8 rounded-lg text-sm font-medium transition
                                               ${i === quiz.currentQuestion 
                                                   ? 'bg-primary-600 text-white' 
                                                   : quiz.answers[i] 
                                                       ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' 
                                                       : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'}
                                               ${quiz.flagged.includes(i) ? 'ring-2 ring-yellow-500' : ''}">
                                    ${i + 1}
                                </button>
                            `).join('')}
                        </div>
                        
                        ${current === total ? `
                            <button onclick="submitQuiz()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                Submit Quiz<i class="fas fa-check ml-2"></i>
                            </button>
                        ` : `
                            <button onclick="nextQuestion()" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                                Next<i class="fas fa-chevron-right ml-2"></i>
                            </button>
                        `}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Render math
    renderMath();
}

function selectAnswer(key) {
    window.currentQuiz.answers[window.currentQuiz.currentQuestion] = key;
    renderQuiz();
}

function toggleFlag(index) {
    const quiz = window.currentQuiz;
    const flagIndex = quiz.flagged.indexOf(index);
    
    if (flagIndex > -1) {
        quiz.flagged.splice(flagIndex, 1);
    } else {
        quiz.flagged.push(index);
    }
    
    renderQuiz();
}

function eliminateOption(questionIndex, optionKey) {
    const quiz = window.currentQuiz;
    
    if (!quiz.eliminated[questionIndex]) {
        quiz.eliminated[questionIndex] = [];
    }
    
    const optionIndex = quiz.eliminated[questionIndex].indexOf(optionKey);
    
    if (optionIndex > -1) {
        quiz.eliminated[questionIndex].splice(optionIndex, 1);
    } else {
        quiz.eliminated[questionIndex].push(optionKey);
    }
    
    renderQuiz();
}

function toggleNotes() {
    const section = document.getElementById('notes-section');
    section.classList.toggle('hidden');
}

function saveNote(index) {
    const notes = document.getElementById('question-notes').value;
    window.currentQuiz.notes[index] = notes;
}

function prevQuestion() {
    if (window.currentQuiz.currentQuestion > 0) {
        window.currentQuiz.currentQuestion--;
        renderQuiz();
    }
}

function nextQuestion() {
    if (window.currentQuiz.currentQuestion < window.currentQuiz.questions.length - 1) {
        window.currentQuiz.currentQuestion++;
        renderQuiz();
    }
}

function goToQuestion(index) {
    window.currentQuiz.currentQuestion = index;
    renderQuiz();
}

function showQuizOverview() {
    const quiz = window.currentQuiz;
    const answered = Object.keys(quiz.answers).length;
    const flagged = quiz.flagged.length;
    const unanswered = quiz.questions.length - answered;
    
    showModal(`
        <div class="space-y-4">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-4 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <p class="text-2xl font-bold text-green-600">${answered}</p>
                    <p class="text-sm text-green-700 dark:text-green-400">Answered</p>
                </div>
                <div class="p-4 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <p class="text-2xl font-bold text-yellow-600">${flagged}</p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-400">Flagged</p>
                </div>
                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <p class="text-2xl font-bold text-gray-600">${unanswered}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-400">Unanswered</p>
                </div>
            </div>
            
            <div class="grid grid-cols-5 gap-2">
                ${quiz.questions.map((_, i) => `
                    <button onclick="closeModal(); goToQuestion(${i})" 
                            class="p-3 rounded-lg text-sm font-medium
                                   ${quiz.answers[i] ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'}
                                   ${quiz.flagged.includes(i) ? 'ring-2 ring-yellow-500' : ''}">
                        ${i + 1}
                    </button>
                `).join('')}
            </div>
            
            <div class="flex justify-center space-x-4 pt-4">
                <button onclick="closeModal()" class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Continue Quiz
                </button>
                <button onclick="closeModal(); submitQuiz()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Submit Quiz
                </button>
            </div>
        </div>
    `, { title: 'Quiz Overview' });
}

function startTimer() {
    const quiz = window.currentQuiz;
    
    function updateTimer() {
        const elapsed = Math.floor((Date.now() - quiz.startTime) / 1000);
        const remaining = quiz.timeLimit - elapsed;
        
        if (remaining <= 0) {
            clearInterval(window.quizTimer);
            submitQuiz(true);
            return;
        }
        
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        const display = document.getElementById('timer-display');
        
        if (display) {
            display.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (remaining <= 300) {
                display.classList.add('text-red-600');
                document.getElementById('quiz-timer').classList.add('bg-red-100', 'dark:bg-red-900/30');
            }
        }
    }
    
    updateTimer();
    window.quizTimer = setInterval(updateTimer, 1000);
}

async function saveQuizProgress() {
    const quiz = window.currentQuiz;
    
    await apiRequest('api/quiz.php?action=save_progress', {
        id: quiz.id,
        answers: quiz.answers,
        flagged: quiz.flagged,
        eliminated: quiz.eliminated,
        notes: quiz.notes,
        current_question: quiz.currentQuestion,
        csrf_token: csrfToken
    });
}

async function submitQuiz(timeUp = false) {
    const quiz = window.currentQuiz;
    const unanswered = quiz.questions.length - Object.keys(quiz.answers).length;
    
    if (!timeUp && unanswered > 0) {
        showModal(`
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                <p class="text-gray-700 dark:text-gray-300 mb-4">
                    You have <strong>${unanswered}</strong> unanswered question${unanswered > 1 ? 's' : ''}.
                </p>
                <div class="flex justify-center space-x-4">
                    <button onclick="closeModal()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Review Answers
                    </button>
                    <button onclick="closeModal(); confirmSubmit()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Submit Anyway
                    </button>
                </div>
            </div>
        `, { title: 'Unanswered Questions' });
        return;
    }
    
    confirmSubmit();
}

async function confirmSubmit() {
    const quiz = window.currentQuiz;
    
    // Stop timers
    if (window.quizTimer) clearInterval(window.quizTimer);
    if (window.quizAutoSave) clearInterval(window.quizAutoSave);
    
    const timeTaken = Math.floor((Date.now() - quiz.startTime) / 1000);
    
    showModal(`
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Submitting Quiz</h3>
            <p class="text-gray-500 dark:text-gray-400">Calculating your results...</p>
        </div>
    `, { closeable: false });
    
    const result = await apiRequest('api/quiz.php?action=submit', {
        id: quiz.id,
        answers: quiz.answers,
        time_taken: timeTaken,
        csrf_token: csrfToken
    });
    
    closeModal();
    document.getElementById('quiz-modal').classList.add('hidden');
    document.body.style.overflow = '';
    
    if (result.success) {
        window.location.href = 'quiz-results.php?id=' + quiz.id;
    } else {
        showToast(result.message || 'Failed to submit quiz', 'error');
    }
}

function filterBySubject(subject) {
    window.location.href = 'quiz.php?filter_subject=' + encodeURIComponent(subject);
}

async function abandonQuiz(id) {
    if (confirm('Are you sure you want to abandon this quiz? Your progress will be lost.')) {
        await apiRequest('api/quiz.php?action=abandon', {
            id: id,
            csrf_token: csrfToken
        });
        location.reload();
    }
}

async function retakeQuiz(id) {
    // Load quiz data and regenerate
    const result = await apiRequest('api/quiz.php?action=retake', {
        id: id,
        csrf_token: csrfToken
    });
    
    if (result.success) {
        startQuiz(result.data);
    } else {
        showToast(result.message || 'Failed to start quiz', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
