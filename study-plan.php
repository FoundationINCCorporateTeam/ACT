<?php
/**
 * ACT AI Tutor - Study Plan Page
 * 
 * Generate and manage personalized study plans.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Study Plan';
$breadcrumbs = [['title' => 'Study Plan']];
$userId = auth_user_id();
$user = auth_user();

// Get user's study plans
$studyPlans = db_read_user($userId, 'study_plans');
$currentPlan = !empty($studyPlans) ? end($studyPlans) : null;

// Get all subjects and topics for selection
$allTopics = get_all_topics();

include __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Study Plan</h1>
    <button onclick="showGenerateModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
        <i class="fas fa-magic mr-2"></i>
        Generate New Plan
    </button>
</div>

<?php if (!$currentPlan): ?>
<!-- No Plan State -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
    <i class="fas fa-calendar-alt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Study Plan Yet</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
        Create a personalized study plan based on your goals, schedule, and areas that need improvement.
    </p>
    <button onclick="showGenerateModal()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
        <i class="fas fa-magic mr-2"></i>Generate Your Study Plan
    </button>
</div>
<?php else: ?>
<!-- Current Plan -->
<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Plan Overview -->
        <div class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-bold"><?= h($currentPlan['plan']['summary'] ?? 'Your Study Plan') ?></h2>
                    <p class="text-white/80 mt-1">Created on <?= format_date($currentPlan['created_at']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold"><?= $currentPlan['plan']['total_weeks'] ?? 0 ?></p>
                    <p class="text-white/80 text-sm">weeks</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="bg-white/20 rounded-lg p-3">
                    <p class="text-sm text-white/80">Target Score</p>
                    <p class="text-xl font-bold"><?= $currentPlan['target_score'] ?? $user['target_score'] ?? 'N/A' ?></p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <p class="text-sm text-white/80">Hours/Week</p>
                    <p class="text-xl font-bold"><?= $currentPlan['plan']['weekly_hours'] ?? 0 ?></p>
                </div>
                <div class="bg-white/20 rounded-lg p-3">
                    <p class="text-sm text-white/80">Test Date</p>
                    <p class="text-xl font-bold"><?= format_date($currentPlan['test_date'] ?? '', 'M j') ?></p>
                </div>
            </div>
        </div>
        
        <!-- Weekly Schedule -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-6 border-b dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Weekly Schedule</h2>
            </div>
            
            <div class="divide-y dark:divide-gray-700">
                <?php if (isset($currentPlan['plan']['weeks'])): ?>
                <?php foreach ($currentPlan['plan']['weeks'] as $week): ?>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4 cursor-pointer" onclick="toggleWeek(<?= $week['week'] ?>)">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mr-3">
                                <span class="font-bold text-primary-600"><?= $week['week'] ?></span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Week <?= $week['week'] ?>: <?= h($week['theme'] ?? '') ?></h3>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400" id="week-icon-<?= $week['week'] ?>"></i>
                    </div>
                    
                    <div id="week-content-<?= $week['week'] ?>" class="hidden space-y-3 ml-13">
                        <?php if (isset($week['days'])): ?>
                        <?php foreach ($week['days'] as $day): ?>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2"><?= h($day['day']) ?></h4>
                            <div class="space-y-2">
                                <?php if (isset($day['tasks'])): ?>
                                <?php foreach ($day['tasks'] as $task): ?>
                                <div class="flex items-center text-sm">
                                    <input type="checkbox" class="rounded border-gray-300 text-primary-600 mr-3">
                                    <span class="text-gray-600 dark:text-gray-400"><?= h($task['time']) ?></span>
                                    <span class="mx-2">•</span>
                                    <span class="text-gray-900 dark:text-white"><?= h($task['activity']) ?></span>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No weekly schedule available.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Milestones -->
        <?php if (isset($currentPlan['plan']['milestones'])): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Milestones</h3>
            <div class="space-y-4">
                <?php foreach ($currentPlan['plan']['milestones'] as $milestone): ?>
                <div class="flex items-start">
                    <div class="w-8 h-8 bg-accent-100 dark:bg-accent-900 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                        <i class="fas fa-flag text-accent-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Week <?= $milestone['week'] ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= h($milestone['goal']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recommendations -->
        <?php if (isset($currentPlan['plan']['recommendations'])): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recommendations</h3>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <?php foreach ($currentPlan['plan']['recommendations'] as $rec): ?>
                <li class="flex items-start">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                    <?= h($rec) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl p-6 text-white">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <a href="lessons.php" class="block px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-book mr-2"></i>Start Today's Lesson
                </a>
                <a href="quiz.php" class="block px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-question-circle mr-2"></i>Take a Practice Quiz
                </a>
                <button onclick="printPage()" class="w-full text-left px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <i class="fas fa-print mr-2"></i>Print Schedule
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const topics = <?= json_encode(ACT_TOPICS) ?>;
const models = <?= json_encode(AI_MODELS) ?>;

function showGenerateModal() {
    const weakOptions = Object.entries(topics).map(([subject, topicList]) => 
        Object.keys(topicList).map(topic => `<option value="${topic}">${subject}: ${topic}</option>`).join('')
    ).join('');
    
    const modalContent = `
        <form id="generate-plan-form" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current ACT Score</label>
                    <input type="number" name="current_score" min="1" max="36" placeholder="1-36 or leave blank"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Score *</label>
                    <input type="number" name="target_score" min="1" max="36" required value="<?= $user['target_score'] ?? '' ?>"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Date *</label>
                    <input type="date" name="test_date" required value="<?= $user['test_date'] ?? '' ?>"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hours Per Day</label>
                    <select name="hours_per_day" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="1">1 hour</option>
                        <option value="2" selected>2 hours</option>
                        <option value="3">3 hours</option>
                        <option value="4">4+ hours</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Days Available Per Week</label>
                <div class="flex flex-wrap gap-2">
                    ${['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(day => `
                        <label class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600">
                            <input type="checkbox" name="days[]" value="${day}" ${['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].includes(day) ? 'checked' : ''}
                                   class="rounded border-gray-300 text-primary-600 mr-2">
                            ${day}
                        </label>
                    `).join('')}
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weak Areas (select up to 5)</label>
                <select name="weak_areas[]" multiple class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" size="5">
                    ${weakOptions}
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Learning Style</label>
                <select name="learning_style" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="Visual">Visual - I learn best from diagrams and charts</option>
                    <option value="Reading" selected>Reading - I learn best from text</option>
                    <option value="Auditory">Auditory - I learn best from listening</option>
                    <option value="Kinesthetic">Kinesthetic - I learn best by doing</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    <i class="fas fa-magic mr-2"></i>Generate Plan
                </button>
            </div>
        </form>
    `;
    
    showModal(modalContent, { title: 'Create Your Study Plan', size: 'lg' });
    
    document.getElementById('generate-plan-form').addEventListener('submit', handleGeneratePlan);
}

async function handleGeneratePlan(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    showModal(`
        <div class="text-center py-8">
            <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Creating Your Study Plan</h3>
            <p class="text-gray-500 dark:text-gray-400">This may take a minute...</p>
        </div>
    `, { closeable: false });
    
    try {
        const result = await apiRequest('api/study-plan.php?action=generate', {
            current_score: formData.get('current_score'),
            target_score: formData.get('target_score'),
            test_date: formData.get('test_date'),
            hours_per_day: formData.get('hours_per_day'),
            days_per_week: formData.getAll('days[]'),
            weak_areas: formData.getAll('weak_areas[]'),
            learning_style: formData.get('learning_style'),
            csrf_token: csrfToken
        });
        
        if (result.success) {
            showToast('Study plan created!', 'success');
            location.reload();
        } else {
            closeModal();
            showToast(result.message || 'Failed to create plan', 'error');
        }
    } catch (error) {
        closeModal();
        showToast('An error occurred', 'error');
    }
}

function toggleWeek(week) {
    const content = document.getElementById('week-content-' + week);
    const icon = document.getElementById('week-icon-' + week);
    
    content.classList.toggle('hidden');
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-up');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
