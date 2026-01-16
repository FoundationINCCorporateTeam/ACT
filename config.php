<?php
/**
 * ACT AI Tutor - Configuration File
 * 
 * This file contains all configuration settings for the application.
 * In production, sensitive values should be loaded from environment variables.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set PHP configuration for AI requests
ini_set('max_execution_time', 300);
ini_set('default_socket_timeout', 300);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/data/error.log');

// Application settings
define('APP_NAME', 'ACT AI Tutor');
define('APP_VERSION', '1.0.0');
define('APP_URL', '');

// Paths
define('ROOT_PATH', __DIR__);
define('DATA_PATH', __DIR__ . '/data');
define('UPLOADS_PATH', __DIR__ . '/uploads');

// AI API Configuration
define('AI_API_ENDPOINT', 'https://nano-gpt.com/api/v1/chat/completions');
define('AI_API_KEY', getenv('NANO_GPT_API_KEY') ?: 'sk-nano-d1f7d1c4-79a9-456d-8f8b-a3e7962693fc');
define('AI_REQUEST_TIMEOUT', 300);

// Available AI Models
define('AI_MODELS', [
    'deepseek/deepseek-v3.2:thinking' => 'DeepSeek V3.2 (Recommended)',
    'zai-org/glm-4.7:thinking' => 'GLM 4.7 Thinking',
    'Meta-Llama-3-1-405B-Instruct-FP8' => 'Llama 3.1 Large',
    'llama-4-maverick' => 'Llama 4 Maverick',
    'llama-3.3-70b' => 'Llama 3.3 (70B)',
    'minimax/minimax-m2.1' => 'MiniMax M2.1',
    'mistralai/mistral-large-3-675b-instruct-2512' => 'Mistral Large 3',
    'mistral-small-31' => 'Mistral Small 3.1 (24B)',
    'glm-4.5-air' => 'GLM 4.5 Air',
    'gpt-oss-120b' => 'GPT OSS 120B',
    'gpt-oss-20b' => 'GPT OSS 20B',
    'mimo-v2-flash-thinking' => 'Xiaomi MIMO V2 Flash Thinking',
    'moonshotai/kimi-k2-thinking' => 'Kimi K2 Thinking'
]);

// Default AI Model
define('DEFAULT_AI_MODEL', 'deepseek/deepseek-v3.2:thinking');

// ACT Topics by Subject
define('ACT_TOPICS', [
    'English' => [
        'Grammar and Usage' => 'Grammar rules, subject-verb agreement, pronoun usage',
        'Punctuation' => 'Commas, semicolons, colons, apostrophes, dashes',
        'Sentence Structure' => 'Sentence fragments, run-ons, parallelism, modifiers',
        'Strategy' => 'Main idea, author\'s purpose, audience awareness',
        'Organization' => 'Transitions, paragraph structure, logical order',
        'Style' => 'Word choice, tone, conciseness, clarity'
    ],
    'Math' => [
        'Pre-Algebra' => 'Basic operations, fractions, decimals, percentages, ratios',
        'Elementary Algebra' => 'Linear equations, inequalities, absolute value, exponents',
        'Intermediate Algebra' => 'Quadratic equations, systems of equations, functions, matrices',
        'Coordinate Geometry' => 'Graphs, slopes, midpoints, distance, conic sections',
        'Plane Geometry' => 'Angles, triangles, circles, quadrilaterals, area, perimeter',
        'Trigonometry' => 'Trig ratios, identities, graphs, equations, unit circle',
        'Statistics and Probability' => 'Mean, median, mode, probability, combinations, permutations'
    ],
    'Reading' => [
        'Main Ideas' => 'Central themes, thesis statements, primary arguments',
        'Details and Evidence' => 'Supporting details, textual evidence, facts vs opinions',
        'Inference and Interpretation' => 'Drawing conclusions, implied meanings, author\'s intent',
        'Vocabulary in Context' => 'Word meanings, connotation, context clues',
        'Literary Narrative' => 'Fiction, memoir, personal essays, narrative techniques',
        'Social Science' => 'Psychology, sociology, economics, political science',
        'Humanities' => 'Art, music, philosophy, architecture, dance',
        'Natural Science' => 'Biology, chemistry, physics, earth science'
    ],
    'Science' => [
        'Data Representation' => 'Reading graphs, tables, charts, interpreting data',
        'Research Summaries' => 'Experimental design, variables, controls, conclusions',
        'Conflicting Viewpoints' => 'Comparing theories, evaluating evidence, scientific debate',
        'Biology' => 'Cell biology, genetics, evolution, ecology, anatomy',
        'Chemistry' => 'Atomic structure, reactions, solutions, organic chemistry',
        'Physics' => 'Motion, forces, energy, waves, electricity, magnetism',
        'Earth Science' => 'Geology, meteorology, astronomy, environmental science'
    ],
    'Writing' => [
        'Ideas and Analysis' => 'Generating ideas, analyzing prompts, thesis development',
        'Development and Support' => 'Evidence, examples, reasoning, elaboration',
        'Organization' => 'Introduction, body, conclusion, transitions, structure',
        'Language Use' => 'Vocabulary, sentence variety, grammar, style, tone'
    ]
]);

// Difficulty Levels
define('DIFFICULTY_LEVELS', [
    'beginner' => 'Beginner - Basic concepts and simple problems',
    'intermediate' => 'Intermediate - Standard ACT difficulty',
    'advanced' => 'Advanced - Challenging ACT problems',
    'expert' => 'Expert - Beyond ACT, competition-level'
]);

// Lesson Lengths
define('LESSON_LENGTHS', [
    'short' => 'Short (5 min)',
    'medium' => 'Medium (15 min)',
    'long' => 'Long (30 min)'
]);

// Quiz Question Counts
define('QUIZ_QUESTION_COUNTS', [5, 10, 15, 20, 25, 30]);

// ACT Test Sections
define('ACT_SECTIONS', [
    'english' => ['name' => 'English', 'questions' => 75, 'minutes' => 45],
    'math' => ['name' => 'Mathematics', 'questions' => 60, 'minutes' => 60],
    'reading' => ['name' => 'Reading', 'questions' => 40, 'minutes' => 35],
    'science' => ['name' => 'Science', 'questions' => 40, 'minutes' => 35]
]);

// XP and Level System
define('XP_ACTIONS', [
    'lesson_completed' => 50,
    'quiz_completed' => 100,
    'quiz_perfect' => 200,
    'practice_test' => 500,
    'essay_submitted' => 150,
    'flashcard_deck' => 75,
    'study_streak' => 25,
    'chat_session' => 10
]);

define('LEVEL_THRESHOLDS', [
    1 => 0,
    2 => 100,
    3 => 300,
    4 => 600,
    5 => 1000,
    6 => 1500,
    7 => 2200,
    8 => 3000,
    9 => 4000,
    10 => 5500,
    11 => 7500,
    12 => 10000,
    13 => 13000,
    14 => 17000,
    15 => 22000
]);

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 86400); // 24 hours
define('REMEMBER_ME_LIFETIME', 2592000); // 30 days

// Include required files
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ai.php';
require_once __DIR__ . '/includes/helpers.php';
