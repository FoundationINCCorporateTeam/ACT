<?php
/**
 * ACT AI Tutor - Chat Page
 * 
 * AI chat tutor interface.
 */

require_once __DIR__ . '/config.php';
require_auth();
auth_update_activity();

$pageTitle = 'AI Tutor';
$breadcrumbs = [['title' => 'AI Tutor']];
$userId = auth_user_id();
$user = auth_user();

// Get user's chat conversations
$chats = db_read_user($userId, 'chats');
$chats = array_reverse($chats);

// Get topic from URL if provided
$preTopic = input('topic');

// Get current conversation
$currentChatId = input('chat', 'int');
$currentChat = null;

if ($currentChatId) {
    $currentChat = db_get_user_item($userId, 'chats', $currentChatId);
}

// Get user's weak areas for context
$quizzes = db_read_user($userId, 'quizzes');
$weakAreas = [];

if (!empty($quizzes)) {
    $topicScores = [];
    
    foreach ($quizzes as $quiz) {
        if (!isset($quiz['completed']) || !$quiz['completed']) continue;
        
        $topic = $quiz['topic'];
        if (!isset($topicScores[$topic])) {
            $topicScores[$topic] = ['total' => 0, 'sum' => 0];
        }
        $topicScores[$topic]['total']++;
        $topicScores[$topic]['sum'] += $quiz['score'] ?? 0;
    }
    
    foreach ($topicScores as $topic => $data) {
        $avg = $data['total'] > 0 ? $data['sum'] / $data['total'] : 0;
        if ($avg < 70) {
            $weakAreas[] = $topic;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="flex h-[calc(100vh-200px)] bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
    <!-- Sidebar - Conversations -->
    <div class="w-64 border-r dark:border-gray-700 flex flex-col hidden md:flex">
        <div class="p-4 border-b dark:border-gray-700">
            <button onclick="newConversation()" class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i>New Chat
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            <?php if (empty($chats)): ?>
            <div class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                <i class="fas fa-comments text-3xl mb-2"></i>
                <p>No conversations yet</p>
            </div>
            <?php else: ?>
            <div class="py-2">
                <?php foreach ($chats as $chat): ?>
                <a href="chat.php?chat=<?= $chat['id'] ?>" 
                   class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition <?= $currentChatId === $chat['id'] ? 'bg-primary-50 dark:bg-primary-900/30 border-l-4 border-primary-500' : '' ?>">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        <?= h($chat['title'] ?? 'New Conversation') ?>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <?= time_ago($chat['updated_at'] ?? $chat['created_at']) ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="p-4 border-t dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">AI Model</p>
            <select id="model-select" onchange="updateModel()"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <?php foreach (AI_MODELS as $key => $name): ?>
                <option value="<?= h($key) ?>" <?= $key === DEFAULT_AI_MODEL ? 'selected' : '' ?>><?= h($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
        <!-- Chat Header -->
        <div class="px-6 py-4 border-b dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <button onclick="toggleSidebar()" class="md:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot text-white"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white" id="chat-title">
                        <?= h($currentChat['title'] ?? 'ACT AI Tutor') ?>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                        Online • Ready to help
                    </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <?php if ($currentChat): ?>
                <button onclick="renameChat()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500" title="Rename">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="exportChat()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500" title="Export">
                    <i class="fas fa-download"></i>
                </button>
                <button onclick="clearChat()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500" title="Clear">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-6" id="messages-container">
            <?php if ($currentChat && !empty($currentChat['messages'])): ?>
                <!-- Existing messages will be rendered by JavaScript -->
            <?php else: ?>
            <!-- Welcome Screen -->
            <div id="welcome-screen" class="h-full flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-robot text-3xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Hi, <?= h($user['name']) ?>!</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md">
                    I'm your ACT AI Tutor. Ask me anything about the ACT, and I'll help you prepare for success!
                </p>
                
                <!-- Quick Prompts -->
                <div class="grid grid-cols-2 gap-4 max-w-lg">
                    <button onclick="sendQuickPrompt('Explain the difference between restrictive and nonrestrictive clauses in English grammar')" 
                            class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-left">
                        <i class="fas fa-book text-primary-500 mb-2"></i>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">English Grammar</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Clauses and punctuation</p>
                    </button>
                    <button onclick="sendQuickPrompt('Walk me through solving a quadratic equation using the quadratic formula')" 
                            class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-left">
                        <i class="fas fa-calculator text-green-500 mb-2"></i>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Math Help</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Quadratic equations</p>
                    </button>
                    <button onclick="sendQuickPrompt('What strategies should I use for the ACT Reading section?')" 
                            class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-left">
                        <i class="fas fa-book-reader text-purple-500 mb-2"></i>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Reading Strategies</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Time management tips</p>
                    </button>
                    <button onclick="sendQuickPrompt('How do I analyze data representation questions in ACT Science?')" 
                            class="p-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-left">
                        <i class="fas fa-flask text-yellow-500 mb-2"></i>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Science Section</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Data interpretation</p>
                    </button>
                </div>
                
                <?php if (!empty($weakAreas)): ?>
                <div class="mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl max-w-lg">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Based on your quiz results, you might want to focus on: <strong><?= h(implode(', ', array_slice($weakAreas, 0, 3))) ?></strong>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Typing Indicator -->
        <div id="typing-indicator" class="hidden px-6 py-2">
            <div class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <span class="text-sm">AI is thinking...</span>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="p-4 border-t dark:border-gray-700">
            <form id="chat-form" class="flex items-end space-x-4">
                <div class="flex-1 relative">
                    <textarea id="message-input" 
                              placeholder="Ask me anything about the ACT..."
                              rows="1"
                              class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 resize-none"
                              onkeydown="handleKeyDown(event)"><?= h($preTopic ? "I need help with: $preTopic" : '') ?></textarea>
                    <button type="button" onclick="toggleVoiceInput()" 
                            class="absolute right-3 bottom-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            title="Voice input">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>
                <button type="submit" id="send-button"
                        class="px-6 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition flex items-center">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let currentChatId = <?= json_encode($currentChatId) ?>;
let messages = <?= json_encode($currentChat['messages'] ?? []) ?>;
let selectedModel = document.getElementById('model-select')?.value || '<?= DEFAULT_AI_MODEL ?>';
const weakAreas = <?= json_encode($weakAreas) ?>;
const userTargetScore = <?= json_encode($user['target_score']) ?>;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (messages.length > 0) {
        renderMessages();
        scrollToBottom();
    }
    
    autoResizeTextarea();
    
    // Focus on pre-filled topic
    <?php if ($preTopic): ?>
    setTimeout(() => document.getElementById('message-input').focus(), 500);
    <?php endif; ?>
});

function renderMessages() {
    const container = document.getElementById('messages-container');
    const welcomeScreen = document.getElementById('welcome-screen');
    
    if (welcomeScreen) {
        welcomeScreen.remove();
    }
    
    container.innerHTML = messages.map((msg, index) => {
        const isUser = msg.role === 'user';
        const content = isUser ? escapeHtml(msg.content) : renderMarkdown(msg.content);
        
        return `
            <div class="flex ${isUser ? 'justify-end' : 'justify-start'} mb-4">
                <div class="max-w-3xl ${isUser ? 'order-2' : 'order-1'}">
                    <div class="${isUser 
                        ? 'bg-primary-600 text-white rounded-2xl rounded-br-sm' 
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-2xl rounded-bl-sm'} 
                        px-4 py-3 shadow-sm">
                        <div class="markdown-content ${isUser ? 'text-white' : ''}">${content}</div>
                    </div>
                    <div class="flex items-center ${isUser ? 'justify-end' : 'justify-start'} mt-1 space-x-2">
                        <span class="text-xs text-gray-400">${formatTime(msg.timestamp)}</span>
                        ${!isUser ? `
                            <button onclick="copyMessage(${index})" class="text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button onclick="regenerateMessage(${index})" class="text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fas fa-redo"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="${isUser ? 'order-1 mr-3' : 'order-2 ml-3'}">
                    ${isUser 
                        ? `<div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-sm font-medium">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                           </div>`
                        : `<div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-accent-500 flex items-center justify-center">
                            <i class="fas fa-robot text-white text-sm"></i>
                           </div>`
                    }
                </div>
            </div>
        `;
    }).join('');
    
    // Render math
    renderMath();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    container.scrollTop = container.scrollHeight;
}

function autoResizeTextarea() {
    const textarea = document.getElementById('message-input');
    
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });
}

function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        document.getElementById('chat-form').dispatchEvent(new Event('submit'));
    }
}

document.getElementById('chat-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    await sendMessage(message);
});

async function sendMessage(message) {
    const input = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    
    // Add user message
    const userMessage = {
        role: 'user',
        content: message,
        timestamp: new Date().toISOString()
    };
    
    messages.push(userMessage);
    
    // Clear input
    input.value = '';
    input.style.height = 'auto';
    
    // Remove welcome screen and render
    renderMessages();
    scrollToBottom();
    
    // Show typing indicator
    document.getElementById('typing-indicator').classList.remove('hidden');
    sendButton.disabled = true;
    scrollToBottom();
    
    try {
        const result = await apiRequest('api/chat.php?action=send', {
            chat_id: currentChatId,
            message: message,
            model: selectedModel,
            context: {
                weak_areas: weakAreas,
                target_score: userTargetScore
            },
            csrf_token: csrfToken
        });
        
        document.getElementById('typing-indicator').classList.add('hidden');
        sendButton.disabled = false;
        
        if (result.success) {
            // Update chat ID if new
            if (result.data.chat_id) {
                currentChatId = result.data.chat_id;
                
                // Update title
                if (result.data.title) {
                    document.getElementById('chat-title').textContent = result.data.title;
                }
            }
            
            // Add AI response
            const aiMessage = {
                role: 'assistant',
                content: result.data.response,
                timestamp: new Date().toISOString()
            };
            
            messages.push(aiMessage);
            renderMessages();
            scrollToBottom();
        } else {
            showToast(result.message || 'Failed to get response', 'error');
        }
    } catch (error) {
        document.getElementById('typing-indicator').classList.add('hidden');
        sendButton.disabled = false;
        showToast('Failed to send message', 'error');
    }
}

function sendQuickPrompt(prompt) {
    sendMessage(prompt);
}

async function newConversation() {
    window.location.href = 'chat.php';
}

function updateModel() {
    selectedModel = document.getElementById('model-select').value;
}

async function copyMessage(index) {
    const content = messages[index].content;
    await copyToClipboard(content);
}

async function regenerateMessage(index) {
    if (index === 0 || messages[index - 1].role !== 'user') return;
    
    // Get the user message before this AI response
    const userMessage = messages[index - 1].content;
    
    // Remove the AI response
    messages.splice(index, 1);
    renderMessages();
    
    // Resend the user message
    await sendMessage(userMessage);
}

async function renameChat() {
    const newTitle = prompt('Enter new conversation title:', document.getElementById('chat-title').textContent);
    
    if (newTitle && newTitle.trim()) {
        const result = await apiRequest('api/chat.php?action=rename', {
            chat_id: currentChatId,
            title: newTitle.trim(),
            csrf_token: csrfToken
        });
        
        if (result.success) {
            document.getElementById('chat-title').textContent = newTitle.trim();
            showToast('Conversation renamed', 'success');
        }
    }
}

function exportChat() {
    const text = messages.map(m => {
        const role = m.role === 'user' ? 'You' : 'AI Tutor';
        return `${role}:\n${m.content}\n`;
    }).join('\n---\n\n');
    
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'chat-export.txt';
    a.click();
    URL.revokeObjectURL(url);
}

async function clearChat() {
    if (confirm('Are you sure you want to delete this conversation?')) {
        const result = await apiRequest('api/chat.php?action=delete', {
            chat_id: currentChatId,
            csrf_token: csrfToken
        });
        
        if (result.success) {
            window.location.href = 'chat.php';
        }
    }
}

function toggleVoiceInput() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        showToast('Voice input not supported in this browser', 'error');
        return;
    }
    
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    
    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        document.getElementById('message-input').value += transcript;
    };
    
    recognition.onerror = function(event) {
        showToast('Voice recognition error: ' + event.error, 'error');
    };
    
    recognition.start();
    showToast('Listening...', 'info', 2000);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
