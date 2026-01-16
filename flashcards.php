<?php
/**
 * ACT AI Tutor - Flashcards Page
 * 
 * Flashcard study system.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'Flashcards';
$breadcrumbs = [['title' => 'Flashcards']];
$userId = auth_user_id();

// Get user's flashcard decks
$flashcards = db_read_user($userId, 'flashcards');
$flashcards = array_reverse($flashcards);

// Check if should auto-generate from URL params
$autoGenerate = input('generate', 'bool');
$preTopic = input('topic');
$preSubject = input('subject');

include __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Flashcards</h1>
    <button onclick="showCreateModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
        <i class="fas fa-plus mr-2"></i>
        New Deck
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-primary-600"><?= count($flashcards) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Decks</p>
    </div>
    <?php
    $totalCards = array_sum(array_map(fn($d) => count($d['cards'] ?? []), $flashcards));
    $masteredCards = 0;
    foreach ($flashcards as $deck) {
        foreach ($deck['cards'] ?? [] as $card) {
            if (($card['mastery'] ?? 0) >= 3) $masteredCards++;
        }
    }
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-green-600"><?= $totalCards ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Cards</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-purple-600"><?= $masteredCards ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Mastered</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <p class="text-3xl font-bold text-orange-600"><?= $totalCards - $masteredCards ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400">To Review</p>
    </div>
</div>

<!-- Deck List -->
<?php if (empty($flashcards)): ?>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
    <i class="fas fa-layer-group text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No Flashcard Decks Yet</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-6">Create flashcard decks to help memorize key concepts.</p>
    <button onclick="showCreateModal()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
        <i class="fas fa-plus mr-2"></i>Create Your First Deck
    </button>
</div>
<?php else: ?>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($flashcards as $deck): ?>
    <?php
    $cardCount = count($deck['cards'] ?? []);
    $masteredCount = 0;
    foreach ($deck['cards'] ?? [] as $card) {
        if (($card['mastery'] ?? 0) >= 3) $masteredCount++;
    }
    $progress = $cardCount > 0 ? round(($masteredCount / $cardCount) * 100) : 0;
    ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition group">
        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <span class="px-3 py-1 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-sm font-medium rounded-full">
                    <?= h($deck['subject'] ?? 'General') ?>
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400"><?= $cardCount ?> cards</span>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-primary-600 transition">
                <?= h($deck['topic'] ?? 'Untitled Deck') ?>
            </h3>
            
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-500 dark:text-gray-400">Mastery</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300"><?= $progress ?>%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all" style="width: <?= $progress ?>%"></div>
                </div>
            </div>
            
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= $masteredCount ?>/<?= $cardCount ?> mastered • <?= time_ago($deck['updated_at'] ?? $deck['created_at']) ?>
            </p>
        </div>
        
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t dark:border-gray-700 flex items-center justify-between">
            <button onclick="studyDeck(<?= $deck['id'] ?>)" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                <i class="fas fa-play mr-1"></i>Study
            </button>
            <div class="flex items-center space-x-2">
                <button onclick="editDeck(<?= $deck['id'] ?>)" class="p-2 text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteDeck(<?= $deck['id'] ?>)" class="p-2 text-gray-400 hover:text-red-500 transition">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Study Modal -->
<div id="study-modal" class="fixed inset-0 z-50 hidden bg-gray-900/50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full">
            <div id="study-content">
                <!-- Study content will be injected here -->
            </div>
        </div>
    </div>
</div>

<script>
const topics = <?= json_encode(ACT_TOPICS) ?>;
const models = <?= json_encode(AI_MODELS) ?>;
const preSubject = <?= json_encode($preSubject) ?>;
const preTopic = <?= json_encode($preTopic) ?>;
const autoGenerate = <?= json_encode($autoGenerate) ?>;

let currentDeck = null;
let currentCardIndex = 0;
let isFlipped = false;

// Auto-generate if requested
if (autoGenerate && preTopic) {
    setTimeout(() => showCreateModal(), 500);
}

function showCreateModal() {
    const modalContent = `
        <form id="create-deck-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                <select name="subject" id="deck-subject" required onchange="updateDeckTopics()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">Select a subject</option>
                    ${Object.keys(topics).map(s => `<option value="${s}" ${preSubject === s ? 'selected' : ''}>${s}</option>`).join('')}
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Topic</label>
                <input type="text" name="topic" id="deck-topic" required placeholder="Enter topic" value="${preTopic || ''}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Generation Method</label>
                <div class="space-y-2">
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer">
                        <input type="radio" name="method" value="ai" checked class="text-primary-600">
                        <span class="ml-3">
                            <span class="font-medium text-gray-900 dark:text-white">AI Generated</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 block">Let AI create flashcards for the topic</span>
                        </span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer">
                        <input type="radio" name="method" value="manual" class="text-primary-600">
                        <span class="ml-3">
                            <span class="font-medium text-gray-900 dark:text-white">Manual</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 block">Create cards yourself</span>
                        </span>
                    </label>
                </div>
            </div>
            
            <div id="ai-options">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Number of Cards</label>
                <select name="card_count"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="10" selected>10 cards</option>
                    <option value="15">15 cards</option>
                    <option value="20">20 cards</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">Create Deck</button>
            </div>
        </form>
    `;
    
    showModal(modalContent, { title: 'Create Flashcard Deck' });
    
    document.getElementById('create-deck-form').addEventListener('submit', handleCreateDeck);
    
    if (preSubject) {
        setTimeout(() => updateDeckTopics(), 100);
    }
}

function updateDeckTopics() {
    // Could populate topic suggestions here
}

async function handleCreateDeck(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const method = formData.get('method');
    
    if (method === 'ai') {
        showModal(`
            <div class="text-center py-8">
                <div class="spinner mx-auto mb-4" style="width: 48px; height: 48px;"></div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Creating Flashcards</h3>
                <p class="text-gray-500 dark:text-gray-400">Generating ${formData.get('card_count')} cards...</p>
            </div>
        `, { closeable: false });
        
        const result = await apiRequest('api/flashcards.php?action=generate', {
            subject: formData.get('subject'),
            topic: formData.get('topic'),
            count: parseInt(formData.get('card_count')),
            csrf_token: csrfToken
        });
        
        if (result.success) {
            showToast('Flashcard deck created!', 'success');
            location.reload();
        } else {
            closeModal();
            showToast(result.message || 'Failed to create deck', 'error');
        }
    } else {
        // Manual creation - redirect to editor
        closeModal();
        showToast('Manual card creation coming soon', 'info');
    }
}

async function studyDeck(deckId) {
    const result = await apiRequest('api/flashcards.php?action=get&id=' + deckId);
    
    if (result.success) {
        currentDeck = result.data;
        currentCardIndex = 0;
        isFlipped = false;
        showStudyCard();
    } else {
        showToast('Failed to load deck', 'error');
    }
}

function showStudyCard() {
    const modal = document.getElementById('study-modal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    const card = currentDeck.cards[currentCardIndex];
    const total = currentDeck.cards.length;
    
    document.getElementById('study-content').innerHTML = `
        <div class="p-6 border-b dark:border-gray-700 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 dark:text-white">${currentDeck.topic}</h2>
            <span class="text-sm text-gray-500">${currentCardIndex + 1} / ${total}</span>
        </div>
        
        <div class="p-8">
            <div onclick="flipCard()" class="min-h-[200px] flex items-center justify-center cursor-pointer">
                <div class="text-center">
                    <p class="text-xl ${isFlipped ? 'text-green-600' : 'text-gray-900 dark:text-white'} markdown-content">
                        ${isFlipped ? card.back : card.front}
                    </p>
                    ${!isFlipped && card.hint ? `<p class="text-sm text-gray-400 mt-4">Hint: ${card.hint}</p>` : ''}
                </div>
            </div>
            
            <p class="text-center text-sm text-gray-400 mt-4">
                ${isFlipped ? '' : 'Click to reveal answer'}
            </p>
        </div>
        
        ${isFlipped ? `
            <div class="p-6 border-t dark:border-gray-700">
                <p class="text-center text-sm text-gray-500 mb-4">How well did you know this?</p>
                <div class="flex justify-center space-x-3">
                    <button onclick="rateCard(1)" class="px-6 py-2 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200">
                        <i class="fas fa-times mr-1"></i>Didn't Know
                    </button>
                    <button onclick="rateCard(2)" class="px-6 py-2 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200">
                        <i class="fas fa-minus mr-1"></i>Somewhat
                    </button>
                    <button onclick="rateCard(3)" class="px-6 py-2 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-200">
                        <i class="fas fa-check mr-1"></i>Knew It
                    </button>
                </div>
            </div>
        ` : ''}
        
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center">
            <button onclick="closeStudyModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times mr-1"></i>Exit
            </button>
            <div class="flex items-center space-x-2">
                ${currentCardIndex > 0 ? `
                    <button onclick="prevCard()" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded-lg">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                ` : ''}
                <button onclick="nextCard()" class="px-4 py-2 bg-primary-600 text-white rounded-lg">
                    ${currentCardIndex < total - 1 ? 'Next <i class="fas fa-chevron-right ml-1"></i>' : 'Finish'}
                </button>
            </div>
        </div>
    `;
    
    renderMath();
}

function flipCard() {
    isFlipped = !isFlipped;
    showStudyCard();
}

async function rateCard(rating) {
    // Update card mastery
    await apiRequest('api/flashcards.php?action=rate', {
        deck_id: currentDeck.id,
        card_index: currentCardIndex,
        rating: rating,
        csrf_token: csrfToken
    });
    
    nextCard();
}

function nextCard() {
    if (currentCardIndex < currentDeck.cards.length - 1) {
        currentCardIndex++;
        isFlipped = false;
        showStudyCard();
    } else {
        closeStudyModal();
        showToast('Study session complete!', 'success');
        location.reload();
    }
}

function prevCard() {
    if (currentCardIndex > 0) {
        currentCardIndex--;
        isFlipped = false;
        showStudyCard();
    }
}

function closeStudyModal() {
    document.getElementById('study-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function deleteDeck(deckId) {
    if (confirm('Are you sure you want to delete this deck?')) {
        const result = await apiRequest('api/flashcards.php?action=delete', {
            id: deckId,
            csrf_token: csrfToken
        });
        
        if (result.success) {
            showToast('Deck deleted', 'success');
            location.reload();
        } else {
            showToast('Failed to delete deck', 'error');
        }
    }
}

function editDeck(deckId) {
    showToast('Edit feature coming soon', 'info');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
