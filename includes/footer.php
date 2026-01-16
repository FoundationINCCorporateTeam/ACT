    </main>
    
    <?php if (auth_check()): ?>
    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t dark:border-gray-700 mt-auto no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 mb-4 md:mb-0">
                    <i class="fas fa-graduation-cap text-2xl text-primary-600"></i>
                    <span class="text-gray-600 dark:text-gray-400">© <?= date('Y') ?> ACT AI Tutor. All rights reserved.</span>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="progress.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-chart-line mr-1"></i> Progress
                    </a>
                    <a href="settings.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-cog mr-1"></i> Settings
                    </a>
                    <a href="profile.php" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-user mr-1"></i> Profile
                    </a>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Modal Container -->
    <div id="modal-container"></div>
    
    <!-- Common JavaScript -->
    <script>
        // Initialize marked for markdown rendering
        if (typeof marked !== 'undefined') {
            marked.setOptions({
                breaks: true,
                gfm: true
            });
        }
        
        // Render markdown content
        function renderMarkdown(content) {
            if (typeof marked !== 'undefined') {
                return marked.parse(content);
            }
            return content;
        }
        
        // Re-render MathJax
        function renderMath() {
            if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                MathJax.typesetPromise();
            }
        }
        
        // Toast notification
        function showToast(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.className = `${bgColors[type]} text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-3 toast-enter max-w-sm`;
            toast.innerHTML = `
                <i class="fas ${icons[type]}"></i>
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('toast-enter');
                toast.classList.add('toast-exit');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
        
        // Modal functions
        function showModal(content, options = {}) {
            const container = document.getElementById('modal-container');
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 overflow-y-auto';
            modal.innerHTML = `
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="${options.closeable !== false ? 'closeModal()' : ''}"></div>
                    <div class="relative inline-block w-full max-w-${options.size || 'lg'} p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
                        ${options.title ? `<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">${options.title}</h3>` : ''}
                        <div class="modal-content">${content}</div>
                        ${options.closeable !== false ? `
                            <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            container.innerHTML = '';
            container.appendChild(modal);
            
            // Close on ESC
            if (options.closeable !== false) {
                document.addEventListener('keydown', function escHandler(e) {
                    if (e.key === 'Escape') {
                        closeModal();
                        document.removeEventListener('keydown', escHandler);
                    }
                });
            }
        }
        
        function closeModal() {
            document.getElementById('modal-container').innerHTML = '';
        }
        
        // Confirm dialog
        function confirmAction(message, onConfirm) {
            showModal(`
                <p class="text-gray-700 dark:text-gray-300 mb-6">${message}</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button onclick="closeModal(); (${onConfirm})()" class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Confirm
                    </button>
                </div>
            `, { size: 'sm' });
        }
        
        // Loading state helper
        function setLoading(element, loading = true) {
            if (loading) {
                element.disabled = true;
                element.dataset.originalText = element.innerHTML;
                element.innerHTML = '<div class="spinner mx-auto"></div>';
            } else {
                element.disabled = false;
                element.innerHTML = element.dataset.originalText || element.innerHTML;
            }
        }
        
        // API request helper
        async function apiRequest(url, data = null, method = 'POST') {
            try {
                const options = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                };
                
                if (data) {
                    options.body = JSON.stringify(data);
                }
                
                const response = await fetch(url, options);
                const result = await response.json();
                
                return result;
            } catch (error) {
                console.error('API request failed:', error);
                return { success: false, message: 'Request failed. Please try again.' };
            }
        }
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Dropdown toggles
        document.querySelectorAll('[id$="-dropdown"]').forEach(dropdown => {
            const button = dropdown.querySelector('button');
            const menu = dropdown.querySelector('div');
            
            button?.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
        });
        
        // Close dropdowns on outside click
        document.addEventListener('click', function() {
            document.querySelectorAll('[id$="-dropdown"] > div').forEach(menu => {
                menu.classList.add('hidden');
            });
        });
        
        // Format numbers with commas
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // Format time duration
        function formatDuration(seconds) {
            if (seconds < 60) return seconds + 's';
            if (seconds < 3600) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return mins + 'm ' + secs + 's';
            }
            const hours = Math.floor(seconds / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            return hours + 'h ' + mins + 'm';
        }
        
        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Auto-save to localStorage
        function autoSave(key, data) {
            try {
                localStorage.setItem(key, JSON.stringify(data));
            } catch (e) {
                console.warn('Auto-save failed:', e);
            }
        }
        
        function autoLoad(key) {
            try {
                const data = localStorage.getItem(key);
                return data ? JSON.parse(data) : null;
            } catch (e) {
                console.warn('Auto-load failed:', e);
                return null;
            }
        }
        
        function autoRemove(key) {
            try {
                localStorage.removeItem(key);
            } catch (e) {
                console.warn('Auto-remove failed:', e);
            }
        }
        
        // Copy to clipboard
        async function copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                showToast('Copied to clipboard!', 'success', 2000);
            } catch (e) {
                console.error('Copy failed:', e);
                showToast('Failed to copy', 'error');
            }
        }
        
        // Print page
        function printPage() {
            window.print();
        }
        
        // CSRF token
        const csrfToken = '<?= get_csrf_token() ?>';
    </script>
</body>
</html>
