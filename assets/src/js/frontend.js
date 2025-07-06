/**
 * TMU Theme Frontend JavaScript
 *
 * @package TMU
 * @version 1.0.0
 */

import Alpine from 'alpinejs';

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize rating system
    initializeRating();
    
    // Initialize lazy loading
    initializeLazyLoading();
    
    // Initialize smooth scrolling
    initializeSmoothScrolling();
    
    // Initialize accessibility features
    initializeAccessibility();
    
    console.log('TMU Theme initialized');
});

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchForms = document.querySelectorAll('.search-form');
    
    searchForms.forEach(form => {
        const input = form.querySelector('input[type="search"]');
        if (input) {
            // Add debounced search suggestions
            let searchTimeout;
            input.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    handleSearchSuggestions(e.target.value);
                }, 300);
            });
        }
    });
}

/**
 * Handle search suggestions
 */
function handleSearchSuggestions(query) {
    if (query.length < 3) return;
    
    // AJAX search implementation would go here
    console.log('Search query:', query);
}

/**
 * Initialize rating system
 */
function initializeRating() {
    const ratingElements = document.querySelectorAll('.tmu-rating');
    
    ratingElements.forEach(element => {
        const stars = element.querySelectorAll('.rating-star');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                handleRating(element, index + 1);
            });
            
            star.addEventListener('mouseenter', function() {
                highlightStars(stars, index + 1);
            });
        });
        
        element.addEventListener('mouseleave', function() {
            resetStars(stars);
        });
    });
}

/**
 * Handle rating submission
 */
function handleRating(element, rating) {
    const postId = element.dataset.postId;
    
    if (!postId) return;
    
    // AJAX rating submission would go here
    console.log('Rating submitted:', rating, 'for post:', postId);
}

/**
 * Highlight stars up to rating
 */
function highlightStars(stars, rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('highlighted');
        } else {
            star.classList.remove('highlighted');
        }
    });
}

/**
 * Reset stars to original state
 */
function resetStars(stars) {
    stars.forEach(star => {
        star.classList.remove('highlighted');
    });
}

/**
 * Initialize lazy loading for images
 */
function initializeLazyLoading() {
    if ('loading' in HTMLImageElement.prototype) {
        // Browser supports native lazy loading
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.src = img.dataset.src || img.src;
        });
    } else {
        // Fallback for older browsers
        import('lazysizes').then(() => {
            console.log('Lazysizes loaded');
        });
    }
}

/**
 * Initialize smooth scrolling
 */
function initializeSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            const target = document.querySelector(href);
            
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Initialize accessibility features
 */
function initializeAccessibility() {
    // Skip link functionality
    const skipLink = document.querySelector('.skip-link');
    if (skipLink) {
        skipLink.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.focus();
            }
        });
    }
    
    // Keyboard navigation for mobile menu
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
}

/**
 * Utility function for AJAX requests
 */
function ajaxRequest(url, data = {}, method = 'POST') {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.tmuData?.restNonce || ''
        },
        body: method === 'POST' ? JSON.stringify(data) : null
    })
    .then(response => response.json())
    .catch(error => {
        console.error('AJAX Error:', error);
        throw error;
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `tmu-notification tmu-notification-${type} fixed top-4 right-4 bg-white border-l-4 p-4 shadow-lg z-50 max-w-sm`;
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">${message}</p>
            </div>
            <button class="ml-auto text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Export for global access
window.TMU = {
    showNotification,
    ajaxRequest
};