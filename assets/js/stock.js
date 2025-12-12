/**
 * HARAMAYA PHARMA - Stock Management JavaScript
 * Real-time search and filtering
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Stock Search Functionality
    const searchInput = document.getElementById('stockSearch');
    const stockTable = document.getElementById('stockTable');
    
    if (searchInput && stockTable) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = stockTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            Array.from(rows).forEach(function(row) {
                const text = row.textContent.toLowerCase();
                
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Highlight expiring items
    highlightExpiringItems();
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
});

/**
 * Highlight rows based on expiry status
 */
function highlightExpiringItems() {
    const rows = document.querySelectorAll('#stockTable tbody tr');
    
    rows.forEach(function(row) {
        const badge = row.querySelector('.badge');
        
        if (badge) {
            if (badge.classList.contains('badge-danger')) {
                row.style.backgroundColor = '#fee2e2';
            } else if (badge.classList.contains('badge-warning')) {
                row.style.backgroundColor = '#fef3c7';
            }
        }
    });
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return 'ETB ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Calculate days until expiry
 */
function daysUntilExpiry(expiryDate) {
    const today = new Date();
    const expiry = new Date(expiryDate);
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}
