<?php
/**
 * ACT AI Tutor - Practice Test Page
 * 
 * Full-length ACT practice tests.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Practice Tests';
$breadcrumbs = [['title' => 'Practice Tests']];
$userId = auth_user_id();

// Get user's practice tests
$tests = db_read_user($userId, 'tests');
$tests = array_reverse($tests);

// Check for test in progress
$inProgressTest = null;
foreach ($tests as $test) {
    if (isset($test['in_progress']) && $test['in_progress'] === true) {
        $inProgressTest = $test;
        break;
    }
}

// Calculate stats
$completedTests = array_filter($tests, fn($t) => isset($t['completed']) && $t['completed']);
$avgComposite = 0;
if (!empty($completedTests)) {
    $scores = array_map(fn($t) => $t['composite_score'] ?? 0, $completedTests);
    $avgComposite = round(array_sum($scores) / count($scores));
}

include __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Practice Tests</h1>
    <button onclick="showGenerateModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
        <i class="fas fa-plus mr-2"></i>
        New Practice Test
    </button>
</div>

<?php if ($inProgressTest): ?>
<!-- Resume Test Banner -->
<div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-clock mr-2"></i>Test In Progress
            </h3>
            <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                You have an unfinished practice test. Section: <strong><?= h(ucfirst($inProgressTest['current_section'] ?? 'English')) ?></strong>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="practice-test.php?resume=<?= $inProgressTest['id'] ?>" 
               class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                Resume Test
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats Overview -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-primary-600"><?= count($completedTests) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Tests Completed</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-green-600"><?= $avgComposite ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Average Score</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <?php
        $bestScore = !empty($completedTests) ? max(array_map(fn($t) => $t['composite_score'] ?? 0, $completedTests)) : 0;
        ?>
        <p class="text-3xl font-bold text-purple-600"><?= $bestScore ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Best Score</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <?php
        $totalTime = array_sum(array_map(fn($t) => $t['total_time'] ?? 0, $completedTests));
        ?>
        <p class="text-3xl font-bold text-orange-600"><?= format_duration($totalTime) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Time</p>
    </div>
</div>

<!-- ACT Sections Info -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ACT Test Format</h2>
    <div class="grid md:grid-cols-4 gap-4">
        <?php foreach (ACT_SECTIONS as $key => $section): ?>
        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <h3 class="font-semibold text-gray-900 dark:text-white"><?= h($section['name']) ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <?= $section['questions'] ?> questions • <?= $section['minutes'] ?> minutes
            </p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Test History -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="p-6 border-b dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Test History</h2>
    </div>
    
    <?php if (empty($completedTests)): ?>
    <div class="p-12 text-center">
        <i class="fas fa-file-alt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Practice Tests Yet</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Take a full-length practice test to simulate the real ACT experience.</p>
        <button onclick="showGenerateModal()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
            <i class="fas fa-plus mr-2"></i>Start Your First Practice Test
        </button>
    </div>
    <?php else: ?>
    <div class="divide-y dark:divide-gray-700">
        <?php foreach ($completedTests as $test): ?>
        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl flex items-center justify-center">
                            <span class="text-2xl font-bold text-white"><?= $test['composite_score'] ?? 0 ?></span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                Practice Test #<?= $test['id'] ?>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= format_date($test['completed_at'] ?? $test['created_at']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Section Scores -->
                    <div class="hidden md:flex space-x-4">
                        <?php foreach (['english', 'math', 'reading', 'science'] as $section): ?>
                        <?php if (isset($test['section_scores'][$section])): ?>
                        <div class="text-center">
                            <p class="text-lg font-bold text-gray-900 dark:text-white"><?= $test['section_scores'][$section] ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= ucfirst($section) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="test-results.php?id=<?= $test['id'] ?>" 
                       class="px-4 py-2 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-800 transition">
                        View Results
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
const sections = <?= json_encode(ACT_SECTIONS) ?>;
const models = <?= json_encode(AI_MODELS) ?>;

function showGenerateModal() {
    const modalContent = `
        <form id="generate-test-form" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Select Sections</label>
                <div class="space-y-2">
                    ${Object.entries(sections).map(([key, section]) => `
                        <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                            <input type="checkbox" name="sections[]" value="${key}" checked
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-3">
                                <span class="font-medium text-gray-900 dark:text-white">${section.name}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">${section.questions} questions • ${section.minutes} min</span>
                            </span>
                        </label>
                    `).join('')}
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">AI Model</label>
                <select name="model"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    ${Object.entries(models).map(([k, v]) => `<option value="${k}" ${k === 'deepseek/deepseek-v3.2:thinking' ? 'selected' : ''}>${v}</option>`).join('')}
                </select>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-lg">
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    <i class="fas fa-info-circle mr-1"></i>
                    Generating a full practice test may take 2-5 minutes. Please be patient.
                </p>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    <i class="fas fa-play mr-2"></i>Start Practice Test
                </button>
            </div>
        </form>
    `;
    
    showModal(modalContent, { title: 'New Practice Test', size: 'lg' });
    
    document.getElementById('generate-test-form').addEventListener('submit', handleGenerateTest);
}

async function handleGenerateTest(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const selectedSections = formData.getAll('sections[]');
    
    if (selectedSections.length === 0) {
        showToast('Please select at least one section', 'error');
        return;
    }
    
    // Show progress modal
    showModal(`
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Generating Practice Test</h3>
            <p class="text-gray-500 dark:text-gray-400">This may take 2-5 minutes...</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2" id="generation-status">Preparing questions...</p>
        </div>
    `, { closeable: false });
    
    try {
        const result = await apiRequest('api/tests.php?action=generate', {
            sections: selectedSections,
            model: formData.get('model'),
            csrf_token: csrfToken
        });
        
        if (result.success) {
            window.location.href = 'practice-test.php?resume=' + result.data.id;
        } else {
            closeModal();
            showToast(result.message || 'Failed to generate test', 'error');
        }
    } catch (error) {
        closeModal();
        showToast('An error occurred. Please try again.', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
