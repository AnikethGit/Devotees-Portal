// js/script.js - Global JavaScript Functions

// Form validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    return cleaned.length === 10 && /^[6-9]/.test(cleaned);
}

// Confirmation dialogs
function confirmAction(message) {
    return confirm(message);
}

// Show/hide elements
function show(elementId) {
    document.getElementById(elementId).style.display = 'block';
}

function hide(elementId) {
    document.getElementById(elementId).style.display = 'none';
}

function toggle(elementId) {
    const element = document.getElementById(elementId);
    element.style.display = element.style.display === 'none' ? 'block' : 'none';
}

// Alert wrapper
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => alertDiv.remove(), 5000);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// AJAX request helper
function makeRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    return fetch(url, options)
        .then(response => response.json())
        .catch(error => console.error('Error:', error));
}

// File upload validation
function validateFileUpload(file, maxSize = 52428800) {
    const allowedTypes = ['application/pdf', 'application/vnd.ms-excel', 
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if (!allowedTypes.includes(file.type)) {
        showAlert('Only PDF and Office documents are allowed', 'error');
        return false;
    }
    
    if (file.size > maxSize) {
        showAlert('File size exceeds 50MB limit', 'error');
        return false;
    }
    
    return true;
}

// Export to CSV
function exportToCSV(filename, data) {
    let csv = '';
    
    // Headers
    if (data.headers) {
        csv += data.headers.join(',') + '\n';
    }
    
    // Data rows
    if (data.rows) {
        data.rows.forEach(row => {
            csv += row.map(cell => `"${cell}"`).join(',') + '\n';
        });
    }
    
    // Create blob and download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    a.remove();
}

// Print page
function printPage() {
    window.print();
}

// Session timeout warning
let sessionTimeoutTimer;
let warningTimeout;

function resetSessionTimer() {
    clearTimeout(sessionTimeoutTimer);
    clearTimeout(warningTimeout);
    
    // 25 minutes until warning
    warningTimeout = setTimeout(() => {
        if (confirm('Your session will expire in 5 minutes. Continue working?')) {
            resetSessionTimer();
        }
    }, 25 * 60 * 1000);
    
    // 30 minutes until logout
    sessionTimeoutTimer = setTimeout(() => {
        alert('Your session has expired. Please log in again.');
        window.location.href = '/devotees-portal/login.php';
    }, 30 * 60 * 1000);
}

// Initialize session timer on page load
document.addEventListener('DOMContentLoaded', () => {
    resetSessionTimer();
    
    // Reset timer on user activity
    document.addEventListener('click', resetSessionTimer);
    document.addEventListener('keypress', resetSessionTimer);
});

// Smooth scroll
function smoothScroll(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// Add to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
    }).catch(err => {
        showAlert('Failed to copy', 'error');
    });
}

// Form reset
function resetForm(formId) {
    document.getElementById(formId).reset();
}

// Disable form submission button
function disableFormButton(formId) {
    const form = document.getElementById(formId);
    if (form) {
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Processing...';
        }
    }
}

// Mobile menu toggle
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
}

// Responsive table
function makeTableResponsive(tableId) {
    const table = document.getElementById(tableId);
    if (table && window.innerWidth < 768) {
        table.classList.add('responsive-table');
    }
}

// Add event listeners
window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) {
        const responsiveTables = document.querySelectorAll('.responsive-table');
        responsiveTables.forEach(table => table.classList.remove('responsive-table'));
    }
});

// Image enlarge on click
document.querySelectorAll('.rules-image-box img').forEach(img => {
    img.style.cursor = 'pointer';
    img.addEventListener('click', function() {
        window.open(this.src, '_blank');
    });
});