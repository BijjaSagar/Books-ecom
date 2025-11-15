/**
 * Optimized Bookstore JavaScript - Critical Fixes Applied
 * Version: 2.0
 * Last Updated: 2025-11-10
 *
 * CRITICAL FIXES INCLUDED:
 * 1. ✅ Removed page reload on resize
 * 2. ✅ Smart carousel recalculation
 * 3. ✅ Proper event delegation
 * 4. ✅ debounce/throttle for performance
 * 5. ✅ Accessibility improvements
 * 6. ✅ Mobile optimization
 * 7. ✅ Error handling improvements
 */

// ============================================
// CONFIGURATION & UTILITIES
// ============================================

// Get base path from header
const ASSET_PATH = window.ASSET_PATH || '/Books-ecom';
const BASE_URL = window.BASE_URL || window.location.origin;

/**
 * Debounce helper - prevents excessive function calls
 * @param {Function} func - Function to debounce
 * @param {Number} wait - Delay in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait = 300) {
    let timeout = null;

    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * Throttle helper - limits function call frequency
 * @param {Function} func - Function to throttle
 * @param {Number} limit - Minimum time between calls
 * @returns {Function} Throttled function
 */
function throttle(func, limit = 100) {
    let inThrottle = false;

    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Safe query selector with null checking
 * @param {String} selector - CSS selector
 * @returns {Element|null} Element or null
 */
function safeQuery(selector) {
    try {
        return document.querySelector(selector);
    } catch (e) {
        console.warn('Invalid selector:', selector);
        return null;
    }
}

/**
 * Show notification with error handling
 * @param {String} message - Message to display
 * @param {String} type - 'success', 'error', 'warning', 'info'
 */
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = safeQuery('.notification-container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => alertDiv.remove(), 5000);
}

// ============================================
// FIX #1: SMART CAROUSEL WITHOUT PAGE RELOAD
// ============================================

const CarouselManager = (() => {
    let currentIndex = 0;
    let itemsToShow = 4;
    let items = [];
    let autoScrollTimer = null;

    const getItemsToShow = () => {
        if (window.innerWidth < 480) return 1;
        if (window.innerWidth < 768) return 2;
        if (window.innerWidth < 1024) return 3;
        return 4;
    };

    const updateCarousel = () => {
        const carousel = safeQuery('.carousel-container');
        if (!carousel || items.length === 0) return;

        // Clamp index to valid range
        currentIndex = Math.min(currentIndex, Math.max(0, items.length - itemsToShow));

        // Use transform for smooth GPU-accelerated animation
        const translateX = -(currentIndex * (100 / itemsToShow));
        carousel.style.transform = `translateX(${translateX}%)`;
    };

    const init = () => {
        const carousel = safeQuery('.carousel-container');
        if (!carousel) return;

        items = Array.from(carousel.querySelectorAll('.carousel-item'));
        itemsToShow = getItemsToShow();
        updateCarousel();

        // FIX: Use resize without page reload
        window.addEventListener('resize', debounce(() => {
            const newItemsToShow = getItemsToShow();

            if (newItemsToShow !== itemsToShow) {
                itemsToShow = newItemsToShow;
                // Recalculate position without reload
                currentIndex = Math.min(currentIndex, Math.max(0, items.length - itemsToShow));
                updateCarousel();
            }
        }, 200), { passive: true });

        // Navigation buttons
        const prevBtn = safeQuery('.carousel-prev');
        const nextBtn = safeQuery('.carousel-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                currentIndex = Math.max(0, currentIndex - 1);
                updateCarousel();
                resetAutoScroll();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                currentIndex = Math.min(items.length - itemsToShow, currentIndex + 1);
                updateCarousel();
                resetAutoScroll();
            });
        }

        // Auto-scroll with pause on interaction
        startAutoScroll();
    };

    const startAutoScroll = () => {
        autoScrollTimer = setInterval(() => {
            if (currentIndex < items.length - itemsToShow) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateCarousel();
        }, 5000);
    };

    const resetAutoScroll = () => {
        clearInterval(autoScrollTimer);
        startAutoScroll();
    };

    return { init, getItemsToShow };
})();

// ============================================
// FIX #2: IMPROVED STICKY HEADER (Consolidated)
// ============================================

const StickyHeader = (() => {
    let isSticky = false;
    let lastScrollTop = 0;
    let header = null;
    const scrollThreshold = 100;

    const makeSticky = () => {
        if (!isSticky && header) {
            header.classList.add('sticky-top');
            document.body.style.paddingTop = header.offsetHeight + 'px';
            isSticky = true;
        }
    };

    const removeSticky = () => {
        if (isSticky && header) {
            header.classList.remove('sticky-top');
            document.body.style.paddingTop = '0';
            isSticky = false;
        }
    };

    const handleScroll = throttle(() => {
        const currentScroll = window.pageYOffset;

        if (currentScroll > scrollThreshold) {
            makeSticky();
        } else {
            removeSticky();
        }

        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    }, 50);

    const init = () => {
        header = safeQuery('.navbar');
        if (!header) return;

        // Use passive event listener for better performance
        window.addEventListener('scroll', handleScroll, { passive: true });
    };

    return { init };
})();

// ============================================
// FIX #3: IMPROVED SEARCH WITH PROPER DEBOUNCE
// ============================================

const SearchManager = (() => {
    let searchTimeout = null;

    const fetchSearchSuggestions = async (query) => {
        if (query.length < 2) return;

        try {
            const response = await fetch(`${ASSET_PATH}/ajax_search.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `query=${encodeURIComponent(query)}`,
                signal: AbortSignal.timeout(5000) // 5 second timeout
            });

            if (!response.ok) throw new Error('Search failed');

            const data = await response.json();
            displaySearchSuggestions(data);
        } catch (error) {
            console.error('Search error:', error);
        }
    };

    const displaySearchSuggestions = (data) => {
        const suggestions = safeQuery('#searchSuggestions');
        if (!suggestions) return;

        suggestions.innerHTML = '';

        if (!data || data.length === 0) {
            suggestions.innerHTML = '<div class="p-3">No results found</div>';
            return;
        }

        data.slice(0, 5).forEach(item => {
            const div = document.createElement('div');
            div.className = 'suggestion-item p-2 cursor-pointer';
            // Use textContent to prevent XSS (not innerHTML)
            div.textContent = `${item.title} by ${item.author}`;
            div.addEventListener('click', () => {
                const search = safeQuery('#mainSearchInput');
                if (search) search.value = item.title;
                suggestions.innerHTML = '';
            });
            suggestions.appendChild(div);
        });

        suggestions.style.display = 'block';
    };

    const init = () => {
        const searchInput = safeQuery('#mainSearchInput');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                const suggestions = safeQuery('#searchSuggestions');
                if (suggestions) suggestions.innerHTML = '';
                return;
            }

            // FIXED: Proper debounce (wait 300ms before searching)
            searchTimeout = setTimeout(() => {
                fetchSearchSuggestions(query);
            }, 300);
        });

        // Close suggestions on escape
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const suggestions = safeQuery('#searchSuggestions');
                if (suggestions) suggestions.innerHTML = '';
            }
        });
    };

    return { init };
})();

// ============================================
// FIX #4: IMPROVED FORM VALIDATION
// ============================================

const FormValidator = (() => {
    const patterns = {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone: /^[\d\s\-\+\(\)]{10,}$/,
        card: /^\d{13,19}$/,
        cvv: /^\d{3,4}$/,
        zip: /^\d{5,6}$/
    };

    const validateField = (field) => {
        const type = field.dataset.validate;
        if (!type || !patterns[type]) return true;

        const value = field.value.trim();
        if (!value) return false;

        return patterns[type].test(value);
    };

    const addFieldValidation = (form) => {
        if (!form) return;

        // Real-time validation
        form.querySelectorAll('[data-validate]').forEach(field => {
            field.addEventListener('blur', () => {
                const isValid = validateField(field);
                field.classList.toggle('is-invalid', !isValid);
            });

            field.addEventListener('input', () => {
                if (field.classList.contains('is-invalid')) {
                    const isValid = validateField(field);
                    field.classList.toggle('is-invalid', !isValid);
                }
            });
        });
    };

    const init = () => {
        document.querySelectorAll('form').forEach(addFieldValidation);
    };

    return { init, validateField };
})();

// ============================================
// FIX #5: IMPROVED CART OPERATIONS
// ============================================

const CartManager = (() => {
    const addToCart = async (productId, quantity = 1) => {
        try {
            // Validate inputs
            if (!productId || quantity < 1) {
                showNotification('Invalid product or quantity', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch(`${ASSET_PATH}/ajax/add_to_cart.php`, {
                method: 'POST',
                body: formData,
                signal: AbortSignal.timeout(5000)
            });

            if (!response.ok) throw new Error('Failed to add to cart');

            const data = await response.json();

            if (data.success) {
                showNotification('Added to cart!', 'success');
                updateCartCount();
            } else {
                showNotification(data.message || 'Error adding to cart', 'error');
            }
        } catch (error) {
            console.error('Cart error:', error);
            showNotification('Error adding to cart. Please try again.', 'error');
        }
    };

    const updateCartCount = async () => {
        try {
            const response = await fetch(`${ASSET_PATH}/ajax/get_cart_count.php`);
            if (!response.ok) return;

            const data = await response.json();
            const cartBadge = safeQuery('.cart-count');

            if (cartBadge) {
                cartBadge.textContent = data.count || 0;
            }
        } catch (error) {
            console.warn('Could not update cart count:', error);
        }
    };

    const init = () => {
        // Event delegation for add to cart buttons
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-add-to-cart]');
            if (btn) {
                const productId = btn.dataset.productId;
                const quantity = parseInt(btn.dataset.quantity || 1);
                addToCart(productId, quantity);
            }
        });
    };

    // Make globally available
    window.addToCart = addToCart;
    window.updateCartCount = updateCartCount;

    return { init };
})();

// ============================================
// FIX #6: ACCESSIBILITY IMPROVEMENTS
// ============================================

const A11yManager = (() => {
    const init = () => {
        // Add keyboard navigation to icon-only buttons
        document.querySelectorAll('a[aria-label], button[aria-label]').forEach(el => {
            if (!el.hasAttribute('tabindex')) {
                el.setAttribute('tabindex', '0');
            }
        });

        // Announce navigation changes for screen readers
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = safeQuery(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    target.focus();
                }
            });
        });
    };

    return { init };
})();

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Initialize all managers
    CarouselManager.init();
    StickyHeader.init();
    SearchManager.init();
    FormValidator.init();
    CartManager.init();
    A11yManager.init();

    // Log initialization
    console.log('Bookstore app initialized successfully');
});

// ============================================
// ERROR HANDLING
// ============================================

window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    // Don't break the page, just log it
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
});

// ============================================
// UTILITIES FOR DEBUGGING
// ============================================

// Make available in console for debugging
window.DEBUG = {
    getAssetPath: () => ASSET_PATH,
    getCartCount: () => CartManager.updateCartCount(),
    validateForm: (selector) => {
        const form = safeQuery(selector);
        if (form) FormValidator.init();
    }
};
