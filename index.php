<?php
/**
 * ACT AI Tutor - Landing Page
 * 
 * Login and registration page for the application.
 */

require_once __DIR__ . '/config.php';

// Redirect to dashboard if already logged in
if (auth_check()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Welcome';
$error = '';
$success = '';
$activeTab = $_GET['tab'] ?? 'login';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $email = input('email', 'email');
        $password = input('password');
        $remember = input('remember', 'bool', false);
        
        $result = auth_login($email, $password, $remember);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
            $activeTab = 'login';
        }
    } elseif ($action === 'register') {
        $name = input('name');
        $email = input('email', 'email');
        $password = input('password');
        $confirmPassword = input('confirm_password');
        
        if ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
            $activeTab = 'register';
        } else {
            $result = auth_register($name, $email, $password);
            
            if ($result['success']) {
                // Auto-login after registration
                $loginResult = auth_login($email, $password);
                if ($loginResult['success']) {
                    header('Location: dashboard.php');
                    exit;
                }
                $success = 'Account created! Please log in.';
                $activeTab = 'login';
            } else {
                $error = $result['message'];
                $activeTab = 'register';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACT AI Tutor - Your Personal ACT Test Preparation Assistant</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-graduation-cap text-3xl text-primary-600"></i>
                    <span class="ml-3 text-2xl font-bold text-gray-900">ACT AI Tutor</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="#features" class="text-gray-600 hover:text-gray-900 hidden sm:block">Features</a>
                    <a href="#login" class="px-4 py-2 text-primary-600 font-medium hover:text-primary-700">Log In</a>
                    <a href="#login" onclick="document.querySelector('[data-tab=\"register\"]').click()" 
                       class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:flex lg:items-center lg:justify-between">
                <div class="lg:w-1/2">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight">
                        Ace Your ACT with AI-Powered Learning
                    </h1>
                    <p class="mt-4 text-xl text-blue-100">
                        Personalized lessons, adaptive quizzes, and intelligent tutoring to help you achieve your target score.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="#login" onclick="document.querySelector('[data-tab=\"register\"]').click()" 
                           class="px-8 py-3 bg-white text-primary-600 rounded-lg font-semibold text-center hover:bg-gray-100 transition">
                            Start Free Today
                        </a>
                        <a href="#features" class="px-8 py-3 border-2 border-white text-white rounded-lg font-semibold text-center hover:bg-white/10 transition">
                            Learn More
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block lg:w-1/2">
                    <div class="relative">
                        <div class="float-animation">
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 ml-12">
                                <div class="flex items-center space-x-4 mb-6">
                                    <div class="w-12 h-12 bg-accent-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-robot text-2xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold">AI Tutor</p>
                                        <p class="text-sm text-blue-200">Always ready to help</p>
                                    </div>
                                </div>
                                <p class="text-blue-100">
                                    "Let me explain this math concept with a step-by-step example..."
                                </p>
                            </div>
                        </div>
                        <div class="absolute -bottom-4 -left-4 bg-green-500 rounded-lg px-4 py-2 shadow-lg">
                            <i class="fas fa-chart-line mr-2"></i>
                            <span class="font-semibold">+5 points improvement!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-12 bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-primary-600">13+</div>
                    <div class="text-gray-600 mt-1">AI Models</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-primary-600">50+</div>
                    <div class="text-gray-600 mt-1">ACT Topics</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-primary-600">24/7</div>
                    <div class="text-gray-600 mt-1">AI Tutoring</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-primary-600">100%</div>
                    <div class="text-gray-600 mt-1">Personalized</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900">Everything You Need to Succeed</h2>
                <p class="mt-4 text-xl text-gray-600">Comprehensive tools designed to maximize your ACT score</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-book-open text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">AI-Generated Lessons</h3>
                    <p class="text-gray-600">
                        Personalized lessons on any ACT topic, generated instantly by advanced AI models.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-question-circle text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Adaptive Quizzes</h3>
                    <p class="text-gray-600">
                        Practice with quizzes that adapt to your skill level and provide detailed explanations.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-file-alt text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Practice Tests</h3>
                    <p class="text-gray-600">
                        Full-length ACT practice tests with realistic timing and composite scoring.
                    </p>
                </div>
                
                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-comments text-2xl text-pink-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">AI Chat Tutor</h3>
                    <p class="text-gray-600">
                        Get instant help from an AI tutor that understands your questions and weak areas.
                    </p>
                </div>
                
                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-calendar-alt text-2xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Study Plans</h3>
                    <p class="text-gray-600">
                        AI-generated study plans tailored to your schedule and target score.
                    </p>
                </div>
                
                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Progress Analytics</h3>
                    <p class="text-gray-600">
                        Track your improvement with detailed analytics and score projections.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Login/Register Section -->
    <section id="login" class="py-20 bg-white">
        <div class="max-w-md mx-auto px-4">
            <div class="text-center mb-8">
                <i class="fas fa-graduation-cap text-5xl text-primary-600 mb-4"></i>
                <h2 class="text-3xl font-bold text-gray-900">Get Started</h2>
                <p class="text-gray-600 mt-2">Create an account or log in to continue</p>
            </div>
            
            <!-- Error/Success Messages -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= h($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= h($success) ?>
            </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6">
                <button data-tab="login" class="flex-1 py-3 text-center font-medium border-b-2 transition <?= $activeTab === 'login' ? 'text-primary-600 border-primary-600' : 'text-gray-500 border-transparent hover:text-gray-700' ?>" onclick="switchTab('login')">
                    Log In
                </button>
                <button data-tab="register" class="flex-1 py-3 text-center font-medium border-b-2 transition <?= $activeTab === 'register' ? 'text-primary-600 border-primary-600' : 'text-gray-500 border-transparent hover:text-gray-700' ?>" onclick="switchTab('register')">
                    Sign Up
                </button>
            </div>
            
            <!-- Login Form -->
            <form id="login-form" method="POST" class="<?= $activeTab !== 'login' ? 'hidden' : '' ?>">
                <input type="hidden" name="action" value="login">
                
                <div class="mb-4">
                    <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="login-email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="you@example.com">
                </div>
                
                <div class="mb-4">
                    <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="login-password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="••••••••">
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-primary-600 hover:text-primary-700">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition">
                    Log In
                </button>
            </form>
            
            <!-- Register Form -->
            <form id="register-form" method="POST" class="<?= $activeTab !== 'register' ? 'hidden' : '' ?>">
                <input type="hidden" name="action" value="register">
                
                <div class="mb-4">
                    <label for="register-name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="register-name" name="name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="John Doe">
                </div>
                
                <div class="mb-4">
                    <label for="register-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="register-email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="you@example.com">
                </div>
                
                <div class="mb-4">
                    <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="register-password" name="password" required minlength="8"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="••••••••">
                    <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters</p>
                </div>
                
                <div class="mb-6">
                    <label for="register-confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="register-confirm" name="confirm_password" required minlength="8"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="••••••••">
                </div>
                
                <button type="submit" class="w-full py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition">
                    Create Account
                </button>
                
                <p class="mt-4 text-xs text-center text-gray-500">
                    By signing up, you agree to our Terms of Service and Privacy Policy.
                </p>
            </form>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <i class="fas fa-graduation-cap text-2xl text-primary-500"></i>
                    <span class="ml-2 text-xl font-bold text-white">ACT AI Tutor</span>
                </div>
                <p class="text-sm">© <?= date('Y') ?> ACT AI Tutor. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('[data-tab]').forEach(btn => {
                if (btn.dataset.tab === tab) {
                    btn.classList.add('text-primary-600', 'border-primary-600');
                    btn.classList.remove('text-gray-500', 'border-transparent');
                } else {
                    btn.classList.remove('text-primary-600', 'border-primary-600');
                    btn.classList.add('text-gray-500', 'border-transparent');
                }
            });
            
            // Show/hide forms
            document.getElementById('login-form').classList.toggle('hidden', tab !== 'login');
            document.getElementById('register-form').classList.toggle('hidden', tab !== 'register');
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    document.querySelector(href).scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
