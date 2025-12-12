/**
 * HARAMAYA PHARMA - Main Application JavaScript
 * Global functions and utilities
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize tooltips
    initializeTooltips();
    
    // Auto-hide alerts after 5 seconds
    autoHideAlerts();
    
    // Confirm delete actions
    confirmDeleteActions();
    
    // Mobile sidebar toggle
    initializeMobileSidebar();
    
    // Form validation
    enhanceFormValidation();
});

/**
 * Initialize tooltips for elements with data-tooltip attribute
 */
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltips = document.querySelectorAll('.tooltip');
            tooltips.forEach(t => t.remove());
        });
    });
}

/**
 * Auto-hide alert messages
 */
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Confirm delete actions
 */
function confirmDeleteActions() {
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete') || 
                          'Are you sure you want to delete this item?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Mobile sidebar toggle
 */
function initializeMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.mobile-menu-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (toggleBtn && sidebar) {
        // Toggle sidebar
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = sidebar.classList.contains('mobile-open');
            
            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        
        function openSidebar() {
            sidebar.classList.add('mobile-open');
            if (overlay) {
                overlay.classList.add('active');
            }
            document.body.classList.add('sidebar-open');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSidebar() {
            sidebar.classList.remove('mobile-open');
            if (overlay) {
                overlay.classList.remove('active');
            }
            document.body.classList.remove('sidebar-open');
            document.body.style.overflow = '';
        }
        
        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                closeSidebar();
            });
        }
        
        // Close sidebar when clicking nav links on mobile
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
        
        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                closeSidebar();
            }
        });
    }
}

/**
 * Enhance form validation
 */
function enhanceFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    showFieldError(field, 'This field is required');
                } else {
                    field.classList.remove('error');
                    removeFieldError(field);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });
}

/**
 * Show field error message
 */
function showFieldError(field, message) {
    removeFieldError(field);
    
    const error = document.createElement('div');
    error.className = 'field-error';
    error.textContent = message;
    field.parentNode.appendChild(error);
}

/**
 * Remove field error message
 */
function removeFieldError(field) {
    const error = field.parentNode.querySelector('.field-error');
    if (error) {
        error.remove();
    }
}

/**
 * Format currency (Ethiopian Birr)
 */
function formatCurrency(amount) {
    return 'ETB ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Calculate days between dates
 */
function daysBetween(date1, date2) {
    const oneDay = 24 * 60 * 60 * 1000;
    const firstDate = new Date(date1);
    const secondDate = new Date(date2);
    return Math.round(Math.abs((firstDate - secondDate) / oneDay));
}

/**
 * Show loading spinner
 */
function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    element.appendChild(spinner);
}

/**
 * Hide loading spinner
 */
function hideLoading(element) {
    const spinner = element.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * AJAX helper function
 */
function ajax(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                callback(null, response);
            } catch (e) {
                callback('Invalid JSON response', null);
            }
        } else {
            callback('Request failed: ' + xhr.status, null);
        }
    };
    
    xhr.onerror = function() {
        callback('Network error', null);
    };
    
    xhr.send(JSON.stringify(data));
}

/**
 * Print function for receipts
 */
function printReceipt() {
    window.print();
}

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
