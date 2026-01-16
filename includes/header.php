<?php
/**
 * ACT AI Tutor - Header Template
 * 
 * Common header used across all pages.
 */

// Get current user if logged in
$currentUser = auth_user();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" class="<?= ($currentUser['settings']['theme'] ?? 'light') === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'ACT AI Tutor') ?> - ACT AI Tutor</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        accent: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- MathJax -->
    <script>
        MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
    
    <!-- Marked.js for Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Loading spinner */
        .spinner {
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-left-color: #3b82f6;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Typing indicator */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px;
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #6b7280;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }
        
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        /* Toast animations */
        .toast-enter {
            animation: slideIn 0.3s ease-out;
        }
        
        .toast-exit {
            animation: slideOut 0.3s ease-in;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* Markdown content styling */
        .markdown-content h1 { font-size: 1.875rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 1rem; }
        .markdown-content h2 { font-size: 1.5rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.75rem; }
        .markdown-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-content p { margin-bottom: 1rem; line-height: 1.7; }
        .markdown-content ul, .markdown-content ol { margin-left: 1.5rem; margin-bottom: 1rem; }
        .markdown-content li { margin-bottom: 0.25rem; }
        .markdown-content code { background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 0.875rem; }
        .markdown-content pre { background: #1f2937; color: #e5e7eb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-bottom: 1rem; }
        .markdown-content pre code { background: transparent; padding: 0; color: inherit; }
        .markdown-content blockquote { border-left: 4px solid #3b82f6; padding-left: 1rem; margin: 1rem 0; color: #6b7280; }
        .markdown-content table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        .markdown-content th, .markdown-content td { border: 1px solid #e5e7eb; padding: 0.5rem; text-align: left; }
        .markdown-content th { background: #f9fafb; font-weight: 600; }
        
        /* Dark mode markdown */
        .dark .markdown-content code { background: #374151; }
        .dark .markdown-content th { background: #374151; }
        .dark .markdown-content th, .dark .markdown-content td { border-color: #4b5563; }
        
        /* Question option styling */
        .option-correct { background-color: #dcfce7 !important; border-color: #22c55e !important; }
        .option-incorrect { background-color: #fee2e2 !important; border-color: #ef4444 !important; }
        .option-flagged { border-color: #eab308 !important; border-width: 2px !important; }
        .option-eliminated { opacity: 0.5; text-decoration: line-through; }
        
        /* Sidebar transition */
        .sidebar-collapsed { transform: translateX(-100%); }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
        
        /* Focus styles for accessibility */
        :focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <?php if ($currentUser): ?>
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-40 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo and Main Nav -->
                <div class="flex items-center">
                    <!-- Mobile menu button -->
                    <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <!-- Logo -->
                    <a href="dashboard.php" class="flex items-center ml-2 lg:ml-0">
                        <i class="fas fa-graduation-cap text-2xl text-primary-600"></i>
                        <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white hidden sm:block">ACT AI Tutor</span>
                    </a>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden lg:flex lg:items-center lg:ml-8 lg:space-x-4">
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'dashboard' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-home mr-1"></i> Dashboard
                        </a>
                        <a href="lessons.php" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'lessons' || $currentPage === 'lesson-view' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-book mr-1"></i> Lessons
                        </a>
                        <a href="quiz.php" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'quiz' || $currentPage === 'quiz-results' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-question-circle mr-1"></i> Quizzes
                        </a>
                        <a href="practice-test.php" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'practice-test' || $currentPage === 'test-results' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-file-alt mr-1"></i> Practice Tests
                        </a>
                        <a href="chat.php" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'chat' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                            <i class="fas fa-comments mr-1"></i> AI Tutor
                        </a>
                        
                        <!-- More dropdown -->
                        <div class="relative" id="more-dropdown">
                            <button class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                <i class="fas fa-ellipsis-h mr-1"></i> More
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="hidden absolute left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50">
                                <a href="study-plan.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-calendar-alt mr-2"></i> Study Plan
                                </a>
                                <a href="essay.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-pen-fancy mr-2"></i> Essay Practice
                                </a>
                                <a href="flashcards.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-layer-group mr-2"></i> Flashcards
                                </a>
                                <a href="progress.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-chart-line mr-2"></i> Progress
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right side -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="hidden md:block relative">
                        <input type="text" id="global-search" placeholder="Search..." 
                               class="w-64 pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    </div>
                    
                    <!-- XP and Level -->
                    <div class="hidden sm:flex items-center space-x-2 px-3 py-1 bg-accent-100 dark:bg-accent-900 rounded-full">
                        <i class="fas fa-star text-accent-500"></i>
                        <span class="text-sm font-medium text-accent-700 dark:text-accent-300">
                            Lvl <?= $currentUser['level'] ?>
                        </span>
                        <span class="text-xs text-accent-600 dark:text-accent-400">
                            <?= number_format($currentUser['xp']) ?> XP
                        </span>
                    </div>
                    
                    <!-- Streak -->
                    <?php if ($currentUser['streak'] > 0): ?>
                    <div class="hidden sm:flex items-center space-x-1 px-3 py-1 bg-orange-100 dark:bg-orange-900 rounded-full">
                        <i class="fas fa-fire text-orange-500"></i>
                        <span class="text-sm font-medium text-orange-700 dark:text-orange-300">
                            <?= $currentUser['streak'] ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- User Menu -->
                    <div class="relative" id="user-dropdown">
                        <button class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                            <?php if ($currentUser['avatar']): ?>
                            <img src="<?= h($currentUser['avatar']) ?>" alt="Avatar" class="w-8 h-8 rounded-full">
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-medium">
                                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                        <div class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50">
                            <div class="px-4 py-3 border-b dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= h($currentUser['name']) ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= h($currentUser['email']) ?></p>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <div class="border-t dark:border-gray-700"></div>
                            <a href="api/auth.php?action=logout" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="hidden lg:hidden border-t dark:border-gray-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'dashboard' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                <a href="lessons.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'lessons' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-book mr-2"></i> Lessons
                </a>
                <a href="quiz.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'quiz' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-question-circle mr-2"></i> Quizzes
                </a>
                <a href="practice-test.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'practice-test' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-file-alt mr-2"></i> Practice Tests
                </a>
                <a href="chat.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'chat' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-comments mr-2"></i> AI Tutor
                </a>
                <a href="study-plan.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'study-plan' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-calendar-alt mr-2"></i> Study Plan
                </a>
                <a href="essay.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'essay' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-pen-fancy mr-2"></i> Essay Practice
                </a>
                <a href="flashcards.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'flashcards' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-layer-group mr-2"></i> Flashcards
                </a>
                <a href="progress.php" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'progress' ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-chart-line mr-2"></i> Progress
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Breadcrumbs -->
    <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
    <div class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex py-3" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="dashboard.php" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                    <li class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 text-xs mx-2"></i>
                        <?php if (isset($crumb['url'])): ?>
                        <a href="<?= h($crumb['url']) ?>" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <?= h($crumb['title']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium"><?= h($crumb['title']) ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Flash Messages -->
    <?php $flash = get_flash(); if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($flash['type'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') ?>">
            <div class="flex items-center">
                <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : ($flash['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle') ?> mr-2"></i>
                <span><?= h($flash['message']) ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="<?= $currentUser ? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6' : '' ?>">
