// Import Alpine.js for interactivity
import Alpine from 'alpinejs';

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// Main theme functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme components
    initializeSearch();
    initializeFilters();
    initializeLoadMore();
    initializeRating();
    initializeLazyLoading();
});

// Search functionality
function initializeSearch() {
    const searchForm = document.querySelector('.tmu-search-form');
    if (!searchForm) return;
    
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = this.querySelector('input[name="s"]').value;
        if (query.length < 2) return;
        
        // Implement AJAX search
        performSearch(query);
    });
}

// Filter functionality
function initializeFilters() {
    const filterDropdowns = document.querySelectorAll('.filter-dropdown');
    filterDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            applyFilters();
        });
    });
}

// Load more functionality
function initializeLoadMore() {
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (!loadMoreBtn) return;
    
    loadMoreBtn.addEventListener('click', function() {
        const page = parseInt(this.dataset.page) + 1;
        loadMoreContent(page);
    });
}

// Rating functionality
function initializeRating() {
    const ratingStars = document.querySelectorAll('.rating-interactive');
    ratingStars.forEach(rating => {
        rating.addEventListener('click', function(e) {
            if (e.target.classList.contains('star')) {
                const value = parseInt(e.target.dataset.value);
                submitRating(this.dataset.postId, value);
            }
        });
    });
}

// Lazy loading for images
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

// AJAX functions
function performSearch(query) {
    fetch(`${tmu_ajax.ajaxurl}?action=tmu_search&s=${encodeURIComponent(query)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nonce=${tmu_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSearchResults(data.data);
        }
    })
    .catch(error => console.error('Search error:', error));
}

function applyFilters() {
    const filters = {};
    document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
        if (dropdown.value) {
            filters[dropdown.name] = dropdown.value;
        }
    });
    
    const params = new URLSearchParams(filters);
    window.location.search = params.toString();
}

function loadMoreContent(page) {
    const loadMoreBtn = document.querySelector('.load-more-btn');
    loadMoreBtn.textContent = 'Loading...';
    loadMoreBtn.disabled = true;
    
    fetch(`${tmu_ajax.ajaxurl}?action=tmu_load_more&page=${page}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nonce=${tmu_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appendContent(data.data.content);
            loadMoreBtn.dataset.page = page;
            loadMoreBtn.textContent = 'Load More';
            loadMoreBtn.disabled = false;
            
            if (!data.data.has_more) {
                loadMoreBtn.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Load more error:', error);
        loadMoreBtn.textContent = 'Load More';
        loadMoreBtn.disabled = false;
    });
}

function submitRating(postId, rating) {
    fetch(`${tmu_ajax.ajaxurl}?action=tmu_submit_rating`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&rating=${rating}&nonce=${tmu_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateRatingDisplay(postId, data.data);
        }
    })
    .catch(error => console.error('Rating error:', error));
}

// Helper functions
function updateSearchResults(results) {
    const container = document.querySelector('.search-results');
    container.innerHTML = results;
}

function appendContent(content) {
    const container = document.querySelector('.content-grid');
    container.insertAdjacentHTML('beforeend', content);
}

function updateRatingDisplay(postId, ratingData) {
    const ratingElement = document.querySelector(`[data-post-id="${postId}"] .rating-display`);
    if (ratingElement) {
        ratingElement.innerHTML = ratingData.html;
    }
}