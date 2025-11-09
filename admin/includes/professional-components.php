<?php
/**
 * Professional Admin Component Library
 * Centralized components for consistent admin interface
 */

// Professional Alert Component
function renderProfessionalAlert($type, $message, $icon = '') {
    $icons = [
        'success' => '✅',
        'error' => '⚠️', 
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $displayIcon = $icon ?: ($icons[$type] ?? '📢');
    
    echo "<div class='alert-professional alert-{$type}-professional fade-in'>
        <span>{$displayIcon}</span>
        <span>{$message}</span>
    </div>";
}

// Professional CSS Injection
function injectProfessionalCSS() {
    echo "<link rel='stylesheet' href='assets/admin-professional.css'>";
}

// Utility function to format currency
function formatCurrency($amount, $symbol = '₹') {
    return $symbol . number_format($amount, 2);
}

// Utility function to format status badges
function formatStatusBadge($status, $labels = []) {
    $defaultLabels = [
        'active' => ['✅', 'Active', 'status-active'],
        'inactive' => ['❌', 'Inactive', 'status-inactive'],
        'pending' => ['🕰️', 'Pending', 'status-pending'],
        'processing' => ['⚙️', 'Processing', 'status-processing'],
        'completed' => ['✅', 'Completed', 'status-delivered'],
        'cancelled' => ['❌', 'Cancelled', 'status-cancelled']
    ];
    
    $config = $labels[$status] ?? $defaultLabels[$status] ?? ['📋', ucfirst($status), 'status-default'];
    return "<span class='status-badge-professional {$config[2]}'>
        {$config[0]} {$config[1]}
    </span>";
}

// Professional JavaScript Utilities
function renderProfessionalJavaScript() {
    echo "<script>
// Professional Modal Management
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

// Enhanced table animations
function animateTableRows() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
}

// Professional form enhancements
function enhanceForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type=\"submit\"]');
            if (submitBtn) {
                submitBtn.style.opacity = '0.7';
                submitBtn.style.pointerEvents = 'none';
                submitBtn.innerHTML = '<span>🔄</span> Processing...';
            }
        });
    });
}

// Initialize professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    animateTableRows();
    enhanceForms();
    
    // Close modals with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal-professional.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
    
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-professional')) {
            closeModal(e.target.id);
        }
    });
});
</script>";
}

?>