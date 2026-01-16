<?php
/**
 * ACT AI Tutor - Lesson View Page
 * 
 * Display individual lesson content.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$userId = auth_user_id();
$lessonId = input('id', 'int');

if (!$lessonId) {
    flash('error', 'Lesson not found.');
    redirect('lessons.php');
}

$lesson = db_get_user_item($userId, 'lessons', $lessonId);

if (!$lesson) {
    flash('error', 'Lesson not found.');
    redirect('lessons.php');
}

$pageTitle = $lesson['topic'];
$breadcrumbs = [
    ['title' => 'Lessons', 'url' => 'lessons.php'],
    ['title' => $lesson['topic']]
];

// Get related lessons
$allLessons = db_read_user($userId, 'lessons');
$relatedLessons = array_filter($allLessons, function($l) use ($lesson, $lessonId) {
    return $l['id'] !== $lessonId && 
           ($l['subject'] === $lesson['subject'] || $l['topic'] === $lesson['topic']);
});
$relatedLessons = array_slice($relatedLessons, 0, 3);

include __DIR__ . '/includes/header.php';
?>

<div class="lg:flex lg:gap-8">
    <!-- Main Content -->
    <div class="lg:flex-1">
        <!-- Lesson Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-sm font-medium rounded-full">
                        <?= h($lesson['subject']) ?>
                    </span>
                    <span class="ml-2 px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-full">
                        <?= h(ucfirst($lesson['difficulty'])) ?>
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="toggleFavorite()" id="favorite-btn" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Add to favorites">
                        <i class="<?= ($lesson['favorite'] ?? false) ? 'fas text-yellow-500' : 'far text-gray-400' ?> fa-star text-xl"></i>
                    </button>
                    <button onclick="printPage()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Print">
                        <i class="fas fa-print text-xl text-gray-400 hover:text-gray-600"></i>
                    </button>
                    <button onclick="shareLesson()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Share">
                        <i class="fas fa-share-alt text-xl text-gray-400 hover:text-gray-600"></i>
                    </button>
                </div>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?= h($lesson['topic']) ?></h1>
            
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 space-x-4">
                <span><i class="fas fa-clock mr-1"></i><?= h($lesson['length'] ?? 'medium') ?> read</span>
                <span><i class="fas fa-calendar mr-1"></i><?= format_date($lesson['created_at']) ?></span>
                <?php if ($lesson['model'] ?? null): ?>
                <span><i class="fas fa-robot mr-1"></i><?= h(AI_MODELS[$lesson['model']] ?? $lesson['model']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                <span class="text-sm text-gray-500 dark:text-gray-400" id="progress-text"><?= $lesson['progress'] ?? 0 ?>%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" id="progress-bar" style="width: <?= $lesson['progress'] ?? 0 ?>%"></div>
            </div>
            <div class="flex items-center justify-between mt-3">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" id="completed-checkbox" <?= ($lesson['completed'] ?? false) ? 'checked' : '' ?> 
                           onchange="toggleComplete()"
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Mark as completed</span>
                </label>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    <?php if ($lesson['completed'] ?? false): ?>
                    <i class="fas fa-check-circle text-green-500 mr-1"></i> Completed
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <!-- Table of Contents -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6" id="toc-container">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-list mr-2"></i>Table of Contents
            </h3>
            <nav id="table-of-contents" class="space-y-2">
                <!-- Generated by JavaScript -->
            </nav>
        </div>
        
        <!-- Lesson Content -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <div id="lesson-content" class="markdown-content prose prose-lg dark:prose-invert max-w-none">
                <!-- Content will be rendered here -->
            </div>
        </div>
        
        <!-- Personal Notes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-sticky-note mr-2"></i>Personal Notes
                </h3>
                <span id="notes-status" class="text-xs text-gray-500 dark:text-gray-400"></span>
            </div>
            <textarea id="notes-textarea" placeholder="Add your personal notes here..."
                      class="w-full h-32 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 resize-none"><?= h($lesson['notes'] ?? '') ?></textarea>
        </div>
        
        <!-- Actions -->
        <div class="flex flex-wrap gap-4 mb-6">
            <a href="quiz.php?subject=<?= urlencode($lesson['subject']) ?>&topic=<?= urlencode($lesson['topic']) ?>" 
               class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
                <i class="fas fa-question-circle mr-2"></i>Take Quiz on This Topic
            </a>
            <a href="flashcards.php?generate=1&topic=<?= urlencode($lesson['topic']) ?>&subject=<?= urlencode($lesson['subject']) ?>" 
               class="px-6 py-3 bg-accent-600 text-white rounded-lg hover:bg-accent-700 transition flex items-center">
                <i class="fas fa-layer-group mr-2"></i>Generate Flashcards
            </a>
            <a href="chat.php?topic=<?= urlencode($lesson['topic']) ?>" 
               class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center">
                <i class="fas fa-comments mr-2"></i>Ask AI Tutor
            </a>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:w-80 space-y-6">
        <!-- Lesson Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Lesson Info</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Subject</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= h($lesson['subject']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Difficulty</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= h(ucfirst($lesson['difficulty'])) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Length</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= h(LESSON_LENGTHS[$lesson['length']] ?? $lesson['length']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="font-medium text-gray-900 dark:text-white"><?= format_date($lesson['created_at']) ?></dd>
                </div>
            </dl>
        </div>
        
        <!-- Related Lessons -->
        <?php if (!empty($relatedLessons)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Related Lessons</h3>
            <div class="space-y-3">
                <?php foreach ($relatedLessons as $related): ?>
                <a href="lesson-view.php?id=<?= $related['id'] ?>" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <p class="font-medium text-gray-900 dark:text-white text-sm"><?= h($related['topic']) ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= h($related['subject']) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl p-6 text-white">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <a href="lessons.php" class="block px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-book-open mr-2"></i>Browse Lessons
                </a>
                <a href="quiz.php" class="block px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-question-circle mr-2"></i>Take a Quiz
                </a>
                <a href="chat.php" class="block px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-comments mr-2"></i>Chat with AI
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const lessonId = <?= $lessonId ?>;
const lessonContent = <?= json_encode($lesson['content'] ?? '') ?>;
let saveTimeout;

// Render lesson content
document.addEventListener('DOMContentLoaded', function() {
    const contentDiv = document.getElementById('lesson-content');
    
    // Render markdown
    if (typeof marked !== 'undefined') {
        contentDiv.innerHTML = marked.parse(lessonContent);
    } else {
        contentDiv.innerHTML = lessonContent.replace(/\n/g, '<br>');
    }
    
    // Render math
    renderMath();
    
    // Generate table of contents
    generateTOC();
    
    // Track reading progress
    trackProgress();
});

function generateTOC() {
    const content = document.getElementById('lesson-content');
    const toc = document.getElementById('table-of-contents');
    const headings = content.querySelectorAll('h1, h2, h3');
    
    if (headings.length === 0) {
        document.getElementById('toc-container').style.display = 'none';
        return;
    }
    
    let tocHtml = '';
    headings.forEach((heading, index) => {
        const id = 'heading-' + index;
        heading.id = id;
        
        const level = parseInt(heading.tagName[1]);
        const indent = (level - 1) * 16;
        
        tocHtml += `
            <a href="#${id}" class="block text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition" style="padding-left: ${indent}px">
                ${heading.textContent}
            </a>
        `;
    });
    
    toc.innerHTML = tocHtml;
}

function trackProgress() {
    const content = document.getElementById('lesson-content');
    let maxScroll = 0;
    
    function updateProgress() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = Math.min(100, Math.round((scrollTop / docHeight) * 100));
        
        if (scrollPercent > maxScroll) {
            maxScroll = scrollPercent;
            
            document.getElementById('progress-bar').style.width = maxScroll + '%';
            document.getElementById('progress-text').textContent = maxScroll + '%';
            
            // Save progress (debounced)
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                saveProgress(maxScroll);
            }, 1000);
        }
    }
    
    window.addEventListener('scroll', updateProgress);
}

async function saveProgress(progress) {
    await apiRequest('api/lessons.php?action=update', {
        id: lessonId,
        progress: progress,
        csrf_token: csrfToken
    });
}

async function toggleFavorite() {
    const result = await apiRequest('api/lessons.php?action=favorite', {
        id: lessonId,
        csrf_token: csrfToken
    });
    
    if (result.success) {
        const btn = document.getElementById('favorite-btn');
        const icon = btn.querySelector('i');
        
        if (result.data.favorite) {
            icon.className = 'fas fa-star text-xl text-yellow-500';
            showToast('Added to favorites', 'success');
        } else {
            icon.className = 'far fa-star text-xl text-gray-400';
            showToast('Removed from favorites', 'info');
        }
    }
}

async function toggleComplete() {
    const result = await apiRequest('api/lessons.php?action=complete', {
        id: lessonId,
        csrf_token: csrfToken
    });
    
    if (result.success) {
        if (result.data.completed) {
            document.getElementById('progress-bar').style.width = '100%';
            document.getElementById('progress-text').textContent = '100%';
            showToast('🎉 Lesson completed! +25 XP', 'success');
        } else {
            showToast('Lesson marked as incomplete', 'info');
        }
        location.reload();
    }
}

// Auto-save notes
const notesTextarea = document.getElementById('notes-textarea');
const notesStatus = document.getElementById('notes-status');

notesTextarea.addEventListener('input', debounce(async function() {
    notesStatus.textContent = 'Saving...';
    
    const result = await apiRequest('api/lessons.php?action=note', {
        id: lessonId,
        notes: notesTextarea.value,
        csrf_token: csrfToken
    });
    
    if (result.success) {
        notesStatus.textContent = 'Saved';
        setTimeout(() => {
            notesStatus.textContent = '';
        }, 2000);
    } else {
        notesStatus.textContent = 'Failed to save';
    }
}, 1000));

function shareLesson() {
    const url = window.location.href;
    copyToClipboard(url);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
