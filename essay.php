<?php
/**
 * ACT AI Tutor - Essay Practice Page
 * 
 * Essay writing practice with AI grading.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Essay Practice';
$breadcrumbs = [['title' => 'Essay Practice']];
$userId = auth_user_id();

// Get user's essays
$essays = db_read_user($userId, 'essays');
$essays = array_reverse($essays);

include __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Essay Practice</h1>
    <button onclick="showNewEssayModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
        <i class="fas fa-pen-fancy mr-2"></i>
        New Essay
    </button>
</div>

<!-- ACT Writing Info -->
<div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl p-6 mb-6 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold mb-2">ACT Writing Test</h2>
            <p class="text-white/80">40 minutes to analyze a complex issue and present your perspective</p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-bold">2-12</p>
            <p class="text-white/80 text-sm">Score Range</p>
        </div>
    </div>
    <div class="grid grid-cols-4 gap-4 mt-6">
        <div class="bg-white/20 rounded-lg p-3 text-center">
            <p class="font-semibold">Ideas & Analysis</p>
            <p class="text-sm text-white/80">1-6 points</p>
        </div>
        <div class="bg-white/20 rounded-lg p-3 text-center">
            <p class="font-semibold">Development</p>
            <p class="text-sm text-white/80">1-6 points</p>
        </div>
        <div class="bg-white/20 rounded-lg p-3 text-center">
            <p class="font-semibold">Organization</p>
            <p class="text-sm text-white/80">1-6 points</p>
        </div>
        <div class="bg-white/20 rounded-lg p-3 text-center">
            <p class="font-semibold">Language Use</p>
            <p class="text-sm text-white/80">1-6 points</p>
        </div>
    </div>
</div>

<!-- Essay History -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="p-6 border-b dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Essays</h2>
    </div>
    
    <?php if (empty($essays)): ?>
    <div class="p-12 text-center">
        <i class="fas fa-pen-fancy text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Essays Yet</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Practice writing essays to improve your ACT Writing score.</p>
        <button onclick="showNewEssayModal()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
            <i class="fas fa-pen-fancy mr-2"></i>Write Your First Essay
        </button>
    </div>
    <?php else: ?>
    <div class="divide-y dark:divide-gray-700">
        <?php foreach ($essays as $essay): ?>
        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900 dark:text-white"><?= h($essay['prompt']['title'] ?? 'Essay') ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <?= time_ago($essay['created_at']) ?> • 
                        <?= str_word_count($essay['content'] ?? '') ?> words
                    </p>
                </div>
                
                <?php if (isset($essay['grades'])): ?>
                <div class="flex items-center space-x-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary-600"><?= $essay['grades']['overall_score'] ?? 0 ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Overall</p>
                    </div>
                    <a href="essay.php?view=<?= $essay['id'] ?>" 
                       class="px-4 py-2 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-800 transition">
                        View Details
                    </a>
                </div>
                <?php else: ?>
                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 text-sm rounded-full">
                    <i class="fas fa-clock mr-1"></i>Pending
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Essay Writing Modal -->
<div id="essay-modal" class="fixed inset-0 z-50 hidden bg-gray-900/50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white" id="essay-modal-title">Write Your Essay</h2>
                <div class="flex items-center space-x-4">
                    <div id="essay-timer" class="hidden px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg font-mono font-bold text-gray-900 dark:text-white">
                        40:00
                    </div>
                    <button onclick="closeEssayModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6" id="essay-content">
                <!-- Content will be injected here -->
            </div>
            
            <div class="p-6 border-t dark:border-gray-700" id="essay-footer">
                <!-- Footer actions -->
            </div>
        </div>
    </div>
</div>

<script>
const models = <?= json_encode(AI_MODELS) ?>;
let currentPrompt = null;
let essayTimer = null;
let timeRemaining = 40 * 60;

function showNewEssayModal() {
    showModal(`
        <form id="prompt-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prompt Category</label>
                <select name="category" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="Education">Education</option>
                    <option value="Technology">Technology</option>
                    <option value="Society">Society</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timer</label>
                <select name="timer"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="0">No timer (practice mode)</option>
                    <option value="40" selected>40 minutes (official time)</option>
                    <option value="60">60 minutes (extended)</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">Generate Prompt</button>
            </div>
        </form>
    `, { title: 'New Essay' });
    
    document.getElementById('prompt-form').addEventListener('submit', generatePrompt);
}

async function generatePrompt(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    showModal(`
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
            <p class="text-gray-500">Generating your essay prompt...</p>
        </div>
    `, { closeable: false });
    
    const result = await apiRequest('api/essay.php?action=generate_prompt', {
        category: formData.get('category'),
        csrf_token: csrfToken
    });
    
    closeModal();
    
    if (result.success) {
        currentPrompt = result.data.prompt;
        timeRemaining = parseInt(formData.get('timer')) * 60 || 0;
        showEssayEditor();
    } else {
        showToast(result.message || 'Failed to generate prompt', 'error');
    }
}

function showEssayEditor() {
    const modal = document.getElementById('essay-modal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    document.getElementById('essay-modal-title').textContent = currentPrompt.title;
    
    document.getElementById('essay-content').innerHTML = `
        <div class="mb-6 p-6 bg-gray-50 dark:bg-gray-700 rounded-xl">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">${currentPrompt.title}</h3>
            <p class="text-gray-700 dark:text-gray-300 mb-4">${currentPrompt.introduction}</p>
            
            <div class="space-y-3">
                ${currentPrompt.perspectives.map(p => `
                    <div class="p-3 bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-600">
                        <p class="font-medium text-gray-900 dark:text-white">${p.label}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${p.description}</p>
                    </div>
                `).join('')}
            </div>
            
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 italic">${currentPrompt.task}</p>
        </div>
        
        <div class="relative">
            <textarea id="essay-textarea" placeholder="Write your essay here..."
                      class="w-full h-96 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 resize-none"
                      oninput="updateWordCount()"></textarea>
            <div class="absolute bottom-3 right-3 text-sm text-gray-400">
                <span id="word-count">0</span> words
            </div>
        </div>
    `;
    
    document.getElementById('essay-footer').innerHTML = `
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Aim for 400-600 words
            </div>
            <div class="flex space-x-3">
                <button onclick="closeEssayModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg">Cancel</button>
                <button onclick="submitEssay()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Submit for Grading
                </button>
            </div>
        </div>
    `;
    
    // Start timer if set
    if (timeRemaining > 0) {
        document.getElementById('essay-timer').classList.remove('hidden');
        startTimer();
    }
}

function updateWordCount() {
    const text = document.getElementById('essay-textarea').value;
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    document.getElementById('word-count').textContent = words;
}

function startTimer() {
    updateTimerDisplay();
    essayTimer = setInterval(() => {
        timeRemaining--;
        updateTimerDisplay();
        
        if (timeRemaining <= 0) {
            clearInterval(essayTimer);
            showToast('Time is up! Submitting your essay...', 'warning');
            submitEssay();
        }
    }, 1000);
}

function updateTimerDisplay() {
    const minutes = Math.floor(timeRemaining / 60);
    const seconds = timeRemaining % 60;
    const display = document.getElementById('essay-timer');
    display.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    
    if (timeRemaining <= 300) {
        display.classList.add('bg-red-100', 'dark:bg-red-900', 'text-red-600');
    }
}

function closeEssayModal() {
    if (essayTimer) clearInterval(essayTimer);
    document.getElementById('essay-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function submitEssay() {
    const essay = document.getElementById('essay-textarea').value.trim();
    
    if (essay.length < 100) {
        showToast('Please write at least 100 characters', 'error');
        return;
    }
    
    if (essayTimer) clearInterval(essayTimer);
    
    // Show grading modal
    showModal(`
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Grading Your Essay</h3>
            <p class="text-gray-500 dark:text-gray-400">Our AI is analyzing your writing...</p>
        </div>
    `, { closeable: false });
    
    const result = await apiRequest('api/essay.php?action=submit', {
        prompt: currentPrompt,
        content: essay,
        csrf_token: csrfToken
    });
    
    closeModal();
    closeEssayModal();
    
    if (result.success) {
        showToast('Essay graded successfully!', 'success');
        window.location.href = 'essay.php?view=' + result.data.id;
    } else {
        showToast(result.message || 'Failed to grade essay', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
