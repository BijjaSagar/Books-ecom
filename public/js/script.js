// Enhanced Bookstore JavaScript - Modern Interactive Features

// Document ready and initialization
document.addEventListener('DOMContentLoaded', function() {
    initializeEnhancedFeatures();
    initializeSearch();
    initializeCarousels();
    initializeAnimations();
    initializeCounters();
    initializeTooltips();
    initializeWishlist();
    initializeCart();
});

// Enhanced Features Initialization
function initializeEnhancedFeatures() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Enhanced sticky header
    initializeStickyHeader();
    
    // Parallax effects
    initializeParallax();
    
    // Intersection Observer for animations
    initializeScrollAnimations();
}

// Enhanced Search Functionality
function initializeSearch() {
    const searchInput = document.getElementById('mainSearchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    let searchTimeout;

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        searchTimeout = setTimeout(() => {
            fetchSearchSuggestions(query);
        }, 300);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.length >= 2) {
            fetchSearchSuggestions(this.value);
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            hideSuggestions();
        }
    });

    function fetchSearchSuggestions(query) {
        fetch(`/bookshelf/ajax/search_suggestions.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displaySuggestions(data);
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }

    function displaySuggestions(suggestions) {
        if (!searchSuggestions) return;
        
        searchSuggestions.innerHTML = '';
        
        if (suggestions.length === 0) {
            searchSuggestions.innerHTML = '<div class="suggestion-item">No suggestions found</div>';
        } else {
            suggestions.forEach(item => {
                const suggestionElement = document.createElement('div');
                suggestionElement.className = 'suggestion-item';
                suggestionElement.innerHTML = `
                    <div class="d-flex align-items-center">
                        <img src="${item.image_url || '/bookshelf/public/images/placeholder.jpg'}" 
                             alt="${item.title}" class="suggestion-image me-3">
                        <div>
                            <div class="suggestion-title">${item.title}</div>
                            <div class="suggestion-author">by ${item.author}</div>
                            <div class="suggestion-price">$${item.price}</div>
                        </div>
                    </div>
                `;
                
                suggestionElement.addEventListener('click', () => {
                    window.location.href = `/bookshelf/product-details.php?id=${item.id}`;
                });
                
                searchSuggestions.appendChild(suggestionElement);
            });
        }
        
        searchSuggestions.style.display = 'block';
    }

    function hideSuggestions() {
        if (searchSuggestions) {
            searchSuggestions.style.display = 'none';
        }
    }
}

// Enhanced Carousel Functionality
function initializeCarousels() {
    const featuredCarousel = document.getElementById('featuredBooksCarousel');
    const prevBtn = document.getElementById('featuredPrev');
    const nextBtn = document.getElementById('featuredNext');
    
    if (!featuredCarousel) return;
    
    let currentIndex = 0;
    const items = featuredCarousel.querySelectorAll('.carousel-item');
    const itemsToShow = getItemsToShow();
    const maxIndex = Math.max(0, items.length - itemsToShow);
    
    function getItemsToShow() {
        if (window.innerWidth >= 1200) return 4;
        if (window.innerWidth >= 992) return 3;
        if (window.innerWidth >= 768) return 2;
        return 1;
    }
    
    function updateCarousel() {
        const translateX = -currentIndex * (100 / itemsToShow);
        featuredCarousel.style.transform = `translateX(${translateX}%)`;
        
        // Update button states
        if (prevBtn) prevBtn.disabled = currentIndex === 0;
        if (nextBtn) nextBtn.disabled = currentIndex >= maxIndex;
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                updateCarousel();
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (currentIndex < maxIndex) {
                currentIndex++;
                updateCarousel();
            }
        });
    }
    
    // Auto-scroll functionality
    let autoScrollInterval;
    
    function startAutoScroll() {
        autoScrollInterval = setInterval(() => {
            if (currentIndex < maxIndex) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateCarousel();
        }, 5000);
    }
    
    function stopAutoScroll() {
        clearInterval(autoScrollInterval);
    }
    
    // Start auto-scroll
    startAutoScroll();
    
    // Pause on hover
    featuredCarousel.addEventListener('mouseenter', stopAutoScroll);
    featuredCarousel.addEventListener('mouseleave', startAutoScroll);
    
    // Handle window resize
    window.addEventListener('resize', () => {
        const newItemsToShow = getItemsToShow();
        if (newItemsToShow !== itemsToShow) {
            location.reload(); // Simple solution for demo
        }
    });
    
    // Initialize carousel
    updateCarousel();
}

// Animation System
function initializeAnimations() {
    // Animate elements on scroll
    const animatedElements = document.querySelectorAll('[data-animate]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const animationType = element.dataset.animate;
                element.classList.add(`animate-${animationType}`);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '-50px'
    });
    
    animatedElements.forEach(element => {
        observer.observe(element);
    });
}

// Counter Animation
function initializeCounters() {
    const counters = document.querySelectorAll('[data-count]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.dataset.count);
                animateCounter(counter, target);
                observer.unobserve(counter);
            }
        });
    });
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
    
    function animateCounter(element, target) {
        let current = 0;
        const increment = target / 100;
        const duration = 2000; // 2 seconds
        const stepTime = duration / 100;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, stepTime);
    }
}

// Sticky Header Enhancement
function initializeStickyHeader() {
    const header = document.querySelector('.site-header');
    const nav = document.querySelector('.main-nav');
    
    if (!header || !nav) return;
    
    let lastScrollY = window.scrollY;
    let ticking = false;
    
    function updateHeader() {
        const scrollY = window.scrollY;
        
        if (scrollY > 100) {
            nav.classList.add('sticky');
            document.body.classList.add('nav-sticky');
            
            // Hide/show based on scroll direction
            if (scrollY > lastScrollY && scrollY > 200) {
                nav.style.transform = 'translateY(-100%)';
            } else {
                nav.style.transform = 'translateY(0)';
            }
        } else {
            nav.classList.remove('sticky');
            document.body.classList.remove('nav-sticky');
            nav.style.transform = 'translateY(0)';
        }
        
        lastScrollY = scrollY;
        ticking = false;
    }
    
    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    });
}

// Parallax Effects
function initializeParallax() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    if (parallaxElements.length === 0) return;
    
    let ticking = false;
    
    function updateParallax() {
        const scrollY = window.scrollY;
        
        parallaxElements.forEach(element => {
            const speed = parseFloat(element.dataset.parallax) || 0.5;
            const yPos = -(scrollY * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
        
        ticking = false;
    }
    
    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    });
}

// Scroll Animations
function initializeScrollAnimations() {
    const animationElements = document.querySelectorAll('.book-card-enhanced, .category-card-enhanced, .promo-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '-20px'
    });
    
    animationElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(element);
    });
}

// Tooltip Initialization
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
    
    function showTooltip(e) {
        const text = e.target.dataset.tooltip;
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
        
        e.target.tooltipElement = tooltip;
    }
    
    function hideTooltip(e) {
        if (e.target.tooltipElement) {
            e.target.tooltipElement.remove();
            e.target.tooltipElement = null;
        }
    }
}

// Wishlist Functionality
function initializeWishlist() {
    const wishlistButtons = document.querySelectorAll('.wishlist-btn');
    
    wishlistButtons.forEach(button => {
        button.addEventListener('click', handleWishlistClick);
    });
    
    function handleWishlistClick(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const productId = button.dataset.productId;
        
        if (!productId) return;
        
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-heart-fill"></i>';
        
        fetch('/bookshelf/ajax/add_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.add('active');
                showNotification('Added to wishlist!', 'success');
                updateWishlistCount();
            } else {
                showNotification(data.message || 'Error adding to wishlist', 'error');
            }
        })
        .catch(error => {
            console.error('Wishlist error:', error);
            showNotification('Error adding to wishlist', 'error');
        })
        .finally(() => {
            button.disabled = false;
        });
    }
}

// Enhanced Cart Functionality
function initializeCart() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', handleAddToCart);
    });
    
    function handleAddToCart(e) {
        e.preventDefault();
        const button = e.currentTarget;
        const productId = button.dataset.productId;
        const quantity = 1;
        
        if (!productId) return;
        
        // Add loading state
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Adding...';
        
        fetch('/bookshelf/ajax/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.innerHTML = '<i class="bi bi-check"></i> Added!';
                showNotification('Product added to cart!', 'success');
                updateCartCount();
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to add to cart');
            }
        })
        .catch(error => {
            console.error('Cart error:', error);
            showNotification(error.message, 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// Utility Functions
function updateCartCount() {
    fetch('/bookshelf/ajax/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartBadge = document.querySelector('.cart-count');
                if (cartBadge) {
                    cartBadge.textContent = data.count;
                    cartBadge.style.display = data.count > 0 ? 'inline' : 'none';
                }
            }
        })
        .catch(error => console.error('Cart count error:', error));
}

function updateWishlistCount() {
    fetch('/bookshelf/ajax/get_wishlist_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const wishlistBadge = document.querySelector('.wishlist-count');
                if (wishlistBadge) {
                    wishlistBadge.textContent = data.count;
                    wishlistBadge.style.display = data.count > 0 ? 'inline' : 'none';
                }
            }
        })
        .catch(error => console.error('Wishlist count error:', error));
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    // Manual close
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.remove();
    });
}

// Professional Quick View Functionality
function initializeQuickView() {
    const quickViewButtons = document.querySelectorAll('.quick-view-btn');
    
    quickViewButtons.forEach(button => {
        button.addEventListener('click', handleQuickView);
    });
    
    function handleQuickView(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const productId = e.currentTarget.dataset.productId;
        
        if (!productId) {
            console.error('Product ID not found');
            return;
        }
        
        loadQuickView(productId);
    }
    
    function loadQuickView(productId) {
        const modal = new bootstrap.Modal(document.getElementById('quickViewModal'), {
            backdrop: 'static',
            keyboard: true
        });
        const content = document.getElementById('quickViewContent');
        
        if (!modal || !content) {
            console.error('Quick view modal not found');
            return;
        }
        
        // Show enhanced loading state
        content.innerHTML = `
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mb-0">Loading product details...</p>
                </div>
            </div>
        `;
        
        modal.show();
        
        // Load product data with enhanced error handling
        fetch(`/bookshelf/ajax/get_product_quick_view.php?id=${productId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.html;
                    
                    // Add smooth fade-in animation
                    content.style.opacity = '0';
                    content.style.transition = 'opacity 0.3s ease-in-out';
                    setTimeout(() => {
                        content.style.opacity = '1';
                    }, 100);
                    
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger m-4 d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <strong>Error!</strong> ${data.message || 'Failed to load product details.'}
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Quick view error:', error);
                content.innerHTML = `
                    <div class="alert alert-danger m-4 d-flex align-items-center" role="alert">
                        <i class="bi bi-wifi-off me-2"></i>
                        <div>
                            <strong>Connection Error!</strong> Please check your internet connection and try again.
                        </div>
                    </div>
                `;
            });
    }
    
    // Make functions globally available
    window.loadQuickView = loadQuickView;
}

// Newsletter Form
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.getElementById('newsletterForm');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            button.disabled = true;
            button.textContent = 'Subscribing...';
            
            fetch('/bookshelf/ajax/newsletter_subscribe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Successfully subscribed to newsletter!', 'success');
                    this.reset();
                } else {
                    showNotification(data.message || 'Subscription failed', 'error');
                }
            })
            .catch(error => {
                console.error('Newsletter error:', error);
                showNotification('Subscription failed', 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = originalText;
            });
        });
    }
});

// Export functions for global access
window.bookstoreApp = {
    updateCartCount,
    updateWishlistCount,
    showNotification,
    initializeQuickView
};