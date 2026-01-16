<?php
/**
 * ACT AI Tutor - Lessons Page
 * 
 * Generate and view lessons.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Lessons';
$breadcrumbs = [['title' => 'Lessons']];
$userId = auth_user_id();

// Get user's lessons
$lessons = db_read_user($userId, 'lessons');
$lessons = array_reverse($lessons); // Most recent first

// Get filter parameters
$filterSubject = input('subject');
$filterDifficulty = input('difficulty');
$filterCompleted = input('completed');
$search = input('search');
$sort = input('sort', 'string', 'newest');
$page = input('page', 'int', 1);

// Apply filters
if ($filterSubject) {
    $lessons = array_filter($lessons, fn($l) => $l['subject'] === $filterSubject);
}
if ($filterDifficulty) {
    $lessons = array_filter($lessons, fn($l) => $l['difficulty'] === $filterDifficulty);
}
if ($filterCompleted !== null && $filterCompleted !== '') {
    $completed = $filterCompleted === '1';
    $lessons = array_filter($lessons, fn($l) => ($l['completed'] ?? false) === $completed);
}
if ($search) {
    $lessons = search_items($lessons, $search, ['topic', 'subject', 'content']);
}

// Apply sorting
switch ($sort) {
    case 'oldest':
        $lessons = sort_items(array_values($lessons), 'created_at', 'asc');
        break;
    case 'title':
        $lessons = sort_items(array_values($lessons), 'topic', 'asc');
        break;
    default:
        $lessons = sort_items(array_values($lessons), 'created_at', 'desc');
}

// Paginate
$paginated = paginate(array_values($lessons), $page, 12);

include __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lessons</h1>
    <button onclick="showGenerateModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
        <i class="fas fa-plus mr-2"></i>
        Generate Lesson
    </button>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search lessons..."
                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
        </div>
        <select name="subject" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            <option value="">All Subjects</option>
            <?php foreach (array_keys(ACT_TOPICS) as $subject): ?>
            <option value="<?= h($subject) ?>" <?= $filterSubject === $subject ? 'selected' : '' ?>><?= h($subject) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="difficulty" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            <option value="">All Difficulties</option>
            <?php foreach (DIFFICULTY_LEVELS as $key => $label): ?>
            <option value="<?= h($key) ?>" <?= $filterDifficulty === $key ? 'selected' : '' ?>><?= h(ucfirst($key)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="completed" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            <option value="">All Status</option>
            <option value="1" <?= $filterCompleted === '1' ? 'selected' : '' ?>>Completed</option>
            <option value="0" <?= $filterCompleted === '0' ? 'selected' : '' ?>>In Progress</option>
        </select>
        <select name="sort" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>By Title</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            <i class="fas fa-search mr-1"></i> Search
        </button>
    </form>
</div>

<!-- Lessons Grid -->
<?php if (empty($paginated['items'])): ?>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
    <i class="fas fa-book-open text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Lessons Found</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-6">
        <?php if ($search || $filterSubject || $filterDifficulty): ?>
        Try adjusting your filters or search terms.
        <?php else: ?>
        Generate your first lesson to start learning!
        <?php endif; ?>
    </p>
    <button onclick="showGenerateModal()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
        <i class="fas fa-plus mr-2"></i> Generate Your First Lesson
    </button>
</div>
<?php else: ?>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <?php foreach ($paginated['items'] as $lesson): ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition overflow-hidden group">
        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-sm font-medium rounded-full">
                    <?= h($lesson['subject']) ?>
                </span>
                <div class="flex items-center space-x-2">
                    <?php if ($lesson['favorite'] ?? false): ?>
                    <i class="fas fa-star text-yellow-500"></i>
                    <?php endif; ?>
                    <?php if ($lesson['completed'] ?? false): ?>
                    <i class="fas fa-check-circle text-green-500"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-primary-600 transition">
                <?= h($lesson['topic']) ?>
            </h3>
            
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">
                <?= h(truncate($lesson['content'] ?? '', 100)) ?>
            </p>
            
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center">
                    <i class="fas fa-signal mr-1"></i>
                    <?= h(ucfirst($lesson['difficulty'])) ?>
                </span>
                <span><?= time_ago($lesson['created_at']) ?></span>
            </div>
        </div>
        
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t dark:border-gray-700">
            <div class="flex items-center justify-between">
                <a href="lesson-view.php?id=<?= $lesson['id'] ?>" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                    View Lesson →
                </a>
                <div class="flex items-center space-x-2">
                    <button onclick="toggleFavorite(<?= $lesson['id'] ?>)" class="p-2 text-gray-400 hover:text-yellow-500 transition">
                        <i class="<?= ($lesson['favorite'] ?? false) ? 'fas' : 'far' ?> fa-star"></i>
                    </button>
                    <button onclick="deleteLesson(<?= $lesson['id'] ?>)" class="p-2 text-gray-400 hover:text-red-500 transition">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($paginated['total_pages'] > 1): ?>
<div class="flex justify-center space-x-2">
    <?php if ($paginated['has_prev']): ?>
    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&subject=<?= urlencode($filterSubject) ?>&difficulty=<?= urlencode($filterDifficulty) ?>&sort=<?= urlencode($sort) ?>" 
       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
        <i class="fas fa-chevron-left"></i>
    </a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($paginated['total_pages'], $page + 2); $i++): ?>
    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&subject=<?= urlencode($filterSubject) ?>&difficulty=<?= urlencode($filterDifficulty) ?>&sort=<?= urlencode($sort) ?>" 
       class="px-4 py-2 <?= $i === $page ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' ?> rounded-lg transition">
        <?= $i ?>
    </a>
    <?php endfor; ?>
    
    <?php if ($paginated['has_next']): ?>
    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&subject=<?= urlencode($filterSubject) ?>&difficulty=<?= urlencode($filterDifficulty) ?>&sort=<?= urlencode($sort) ?>" 
       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
        <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Generate Lesson Modal -->
<script>
const topics = <?= json_encode(ACT_TOPICS) ?>;
const models = <?= json_encode(AI_MODELS) ?>;
const difficulties = <?= json_encode(DIFFICULTY_LEVELS) ?>;
const lengths = <?= json_encode(LESSON_LENGTHS) ?>;

function showGenerateModal() {
    let topicOptions = '';
    
    const modalContent = `
        <form id="generate-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                <select name="subject" id="subject-select" required onchange="updateTopics()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    <option value="">Select a subject</option>
                    ${Object.keys(topics).map(s => `<option value="${s}">${s}</option>`).join('')}
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Topic</label>
                <select name="topic" id="topic-select"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    <option value="">Select a topic or enter custom below</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Or Enter Custom Topic</label>
                <input type="text" name="custom_topic" id="custom-topic" placeholder="Enter your own topic..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Difficulty</label>
                    <select name="difficulty" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        ${Object.entries(difficulties).map(([k, v]) => `<option value="${k}">${k.charAt(0).toUpperCase() + k.slice(1)}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Length</label>
                    <select name="length" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        ${Object.entries(lengths).map(([k, v]) => `<option value="${k}" ${k === 'medium' ? 'selected' : ''}>${v}</option>`).join('')}
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Focus Areas</label>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="focus[]" value="Concepts" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Concepts</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="focus[]" value="Examples" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Examples</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="focus[]" value="Practice Problems" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Practice</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="focus[]" value="Strategies" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Strategies</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">AI Model</label>
                <select name="model"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    ${Object.entries(models).map(([k, v]) => `<option value="${k}" ${k === 'deepseek/deepseek-v3.2:thinking' ? 'selected' : ''}>${v}</option>`).join('')}
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    <i class="fas fa-magic mr-2"></i> Generate Lesson
                </button>
            </div>
        </form>
    `;
    
    showModal(modalContent, { title: 'Generate New Lesson', size: 'lg' });
    
    // Attach form handler
    document.getElementById('generate-form').addEventListener('submit', handleGenerate);
}

function updateTopics() {
    const subject = document.getElementById('subject-select').value;
    const topicSelect = document.getElementById('topic-select');
    
    topicSelect.innerHTML = '<option value="">Select a topic or enter custom below</option>';
    
    if (subject && topics[subject]) {
        Object.entries(topics[subject]).forEach(([topic, desc]) => {
            const option = document.createElement('option');
            option.value = topic;
            option.textContent = topic;
            option.title = desc;
            topicSelect.appendChild(option);
        });
    }
}

async function handleGenerate(e) {
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
    
    // Get focus areas
    const focusAreas = formData.getAll('focus[]');
    
    setLoading(submitBtn, true);
    
    // Show progress modal
    showModal(`
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Generating Your Lesson</h3>
            <p class="text-gray-500 dark:text-gray-400">This may take 30-60 seconds...</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Topic: ${topic}</p>
        </div>
    `, { closeable: false });
    
    try {
        const result = await apiRequest('api/lessons.php?action=generate', {
            subject: subject,
            topic: topic,
            difficulty: formData.get('difficulty'),
            length: formData.get('length'),
            focus: focusAreas,
            model: formData.get('model'),
            csrf_token: csrfToken
        });
        
        if (result.success) {
            showToast('Lesson generated successfully!', 'success');
            window.location.href = 'lesson-view.php?id=' + result.data.id;
        } else {
            closeModal();
            showToast(result.message || 'Failed to generate lesson', 'error');
        }
    } catch (error) {
        closeModal();
        showToast('An error occurred. Please try again.', 'error');
    }
}

async function toggleFavorite(id) {
    const result = await apiRequest('api/lessons.php?action=favorite', {
        id: id,
        csrf_token: csrfToken
    });
    
    if (result.success) {
        location.reload();
    } else {
        showToast(result.message || 'Failed to update', 'error');
    }
}

function deleteLesson(id) {
    confirmAction('Are you sure you want to delete this lesson?', async () => {
        const result = await apiRequest('api/lessons.php?action=delete', {
            id: id,
            csrf_token: csrfToken
        });
        
        if (result.success) {
            showToast('Lesson deleted', 'success');
            location.reload();
        } else {
            showToast(result.message || 'Failed to delete', 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
