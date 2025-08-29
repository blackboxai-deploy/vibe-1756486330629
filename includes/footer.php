            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="js/script.js"></script>
    
    <!-- Additional page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline scripts -->
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?php echo $inline_scripts; ?>
        </script>
    <?php endif; ?>
    
    <!-- Footer Scripts -->
    <script>
        // Initialize page-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Show success messages
            <?php if (isset($_SESSION['success_message'])): ?>
                CMS.showAlert('<?php echo addslashes($_SESSION['success_message']); ?>', 'success');
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            // Show error messages
            <?php if (isset($_SESSION['error_message'])): ?>
                CMS.showAlert('<?php echo addslashes($_SESSION['error_message']); ?>', 'danger');
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            // Show info messages
            <?php if (isset($_SESSION['info_message'])): ?>
                CMS.showAlert('<?php echo addslashes($_SESSION['info_message']); ?>', 'info');
                <?php unset($_SESSION['info_message']); ?>
            <?php endif; ?>
            
            // Auto-focus first input field
            const firstInput = document.querySelector('input[type="text"], input[type="email"], select');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Add confirmation to delete buttons
            const deleteButtons = document.querySelectorAll('[data-action="delete"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
            
            // Initialize data tables if present
            const dataTables = document.querySelectorAll('.data-table');
            dataTables.forEach(table => {
                // Add search functionality
                const searchContainer = table.closest('.table-container');
                if (searchContainer) {
                    const searchInput = document.createElement('input');
                    searchInput.type = 'text';
                    searchInput.className = 'form-control table-search';
                    searchInput.placeholder = 'Search...';
                    searchInput.style.marginBottom = '15px';
                    searchInput.style.maxWidth = '300px';
                    
                    const tableHeader = searchContainer.querySelector('.table-header');
                    if (tableHeader) {
                        tableHeader.appendChild(searchInput);
                    }
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (alert.parentNode) {
                        alert.style.opacity = '0';
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alert.remove();
                            }
                        }, 300);
                    }
                });
            }, 5000);
        });
        
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
        });
        
        // Handle AJAX errors
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', e.reason);
        });
    </script>
</body>
</html>