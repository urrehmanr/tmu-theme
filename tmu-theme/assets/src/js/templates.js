/**
 * Template Interactions JavaScript
 * 
 * JavaScript functionality for frontend templates
 * 
 * @package TMU
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Template JavaScript Class
    class TMUTemplates {
        constructor() {
            this.init();
        }
        
        init() {
            this.initTabs();
            this.initFilters();
            this.initModals();
            this.initLoadMore();
            this.initQuickView();
            this.initWatchlist();
            this.initMobileMenu();
            this.initLazyLoad();
            this.initSearch();
        }
        
        // Tab functionality for single templates
        initTabs() {
            $('.tmu-tab-button').on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const tabId = $this.data('tab');
                const $tabContainer = $this.closest('.tmu-content-tabs');
                
                // Update active tab button
                $tabContainer.find('.tmu-tab-button').removeClass('active border-blue-600 text-blue-600')
                            .addClass('border-transparent text-gray-500');
                $this.addClass('active border-blue-600 text-blue-600')
                     .removeClass('border-transparent text-gray-500');
                
                // Update active tab content
                $tabContainer.find('.tmu-tab-pane').removeClass('active').addClass('hidden');
                $tabContainer.find('#' + tabId).addClass('active').removeClass('hidden');
                
                // Trigger custom event
                $(document).trigger('tmu:tab:changed', [tabId]);
            });
            
            // Media tabs for movie media section
            $('.tmu-media-tab').on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const mediaType = $this.data('media');
                const $container = $this.closest('.tmu-movie-media');
                
                // Update active media tab
                $container.find('.tmu-media-tab').removeClass('active border-blue-600 text-blue-600')
                          .addClass('border-transparent text-gray-500');
                $this.addClass('active border-blue-600 text-blue-600')
                     .removeClass('border-transparent text-gray-500');
                
                // Update active media section
                $container.find('.tmu-media-section').addClass('hidden').removeClass('active');
                $container.find('#' + mediaType).removeClass('hidden').addClass('active');
            });
        }
        
        // Archive filter functionality
        initFilters() {
            // Filter dropdown changes
            $('#archiveFilters select').on('change', function() {
                this.applyFilters();
                this.updateActiveFilters();
            }.bind(this));
            
            // Clear all filters
            $('#clearFilters').on('click', function(e) {
                e.preventDefault();
                $('#archiveFilters')[0].reset();
                this.applyFilters();
                this.updateActiveFilters();
            }.bind(this));
            
            // Search filters for search page
            $('.tmu-filter-btn').on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const filter = $this.data('filter');
                
                $('.tmu-filter-btn').removeClass('active');
                $this.addClass('active');
                
                if (filter === 'all') {
                    $('.tmu-search-section').show();
                } else {
                    $('.tmu-search-section').hide();
                    $('.tmu-search-section[data-type="' + filter + '"]').show();
                }
            });
        }
        
        applyFilters() {
            const $form = $('#archiveFilters');
            if (!$form.length) return;
            
            const formData = new FormData($form[0]);
            const params = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value && value !== '') {
                    params.append(key, value);
                }
            }
            
            const url = new URL(window.location);
            url.search = params.toString();
            window.location.href = url.toString();
        }
        
        updateActiveFilters() {
            const $form = $('#archiveFilters');
            const $activeFiltersDiv = $('#activeFilters');
            const $container = $('#activeFiltersContainer');
            
            if (!$form.length || !$activeFiltersDiv.length) return;
            
            const formData = new FormData($form[0]);
            const activeFilters = [];
            
            for (let [key, value] of formData.entries()) {
                if (value && value !== '' && key !== 'post_type') {
                    const $select = $form.find(`[name="${key}"]`);
                    const $option = $select.find(`option[value="${value}"]`);
                    if ($option.length) {
                        activeFilters.push({
                            key: key,
                            value: value,
                            label: $option.text().trim()
                        });
                    }
                }
            }
            
            if (activeFilters.length > 0) {
                $activeFiltersDiv.removeClass('hidden');
                $container.html(activeFilters.map(filter => 
                    `<span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                        ${filter.label}
                        <button type="button" class="ml-2 text-blue-600 hover:text-blue-800 remove-filter" data-key="${filter.key}">
                            ×
                        </button>
                    </span>`
                ).join(''));
            } else {
                $activeFiltersDiv.addClass('hidden');
            }
        }
        
        // Modal functionality
        initModals() {
            // Video modal
            window.openVideoModal = function(videoId) {
                const $modal = $('#videoModal');
                const $iframe = $('#videoFrame');
                $iframe.attr('src', `https://www.youtube.com/embed/${videoId}?autoplay=1`);
                $modal.removeClass('hidden');
                $('body').addClass('overflow-hidden');
            };
            
            window.closeVideoModal = function() {
                const $modal = $('#videoModal');
                const $iframe = $('#videoFrame');
                $iframe.attr('src', '');
                $modal.addClass('hidden');
                $('body').removeClass('overflow-hidden');
            };
            
            // Image modal
            window.openImageModal = function(type, index) {
                const $modal = $('#imageModal');
                const $img = $('#modalImage');
                
                // This would be populated from actual data
                const imageData = window.tmuImageData || {};
                
                if (imageData[type] && imageData[type][index]) {
                    const imagePath = imageData[type][index].file_path;
                    $img.attr('src', tmu_ajax.image_base_url + imagePath);
                    $modal.removeClass('hidden');
                    $('body').addClass('overflow-hidden');
                }
            };
            
            window.closeImageModal = function() {
                const $modal = $('#imageModal');
                $modal.addClass('hidden');
                $('body').removeClass('overflow-hidden');
            };
            
            // Close modals on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.closeVideoModal();
                    window.closeImageModal();
                }
            });
            
            // Close modals on background click
            $('.tmu-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).addClass('hidden');
                    $('body').removeClass('overflow-hidden');
                }
            });
        }
        
        // Load more functionality
        initLoadMore() {
            $('.tmu-load-more').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const type = $button.data('type');
                const page = parseInt($button.data('page') || 1) + 1;
                
                $button.prop('disabled', true).text('Loading...');
                
                $.ajax({
                    url: tmu_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'tmu_load_more',
                        post_type: type,
                        page: page,
                        nonce: tmu_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const $grid = $button.closest('.tmu-search-section').find('.tmu-grid');
                            $grid.append(response.data.html);
                            
                            if (response.data.has_more) {
                                $button.data('page', page).prop('disabled', false).text('Load More');
                            } else {
                                $button.hide();
                            }
                        } else {
                            alert('Error loading more content');
                        }
                    },
                    error: function() {
                        alert('Error loading more content');
                        $button.prop('disabled', false).text('Load More');
                    }
                });
            });
        }
        
        // Quick view functionality
        initQuickView() {
            $(document).on('click', '.tmu-quick-view', function(e) {
                e.preventDefault();
                
                const postId = $(this).data('post-id');
                
                $.ajax({
                    url: tmu_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'tmu_quick_view',
                        post_id: postId,
                        nonce: tmu_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const $modal = $('<div class="tmu-modal fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">')
                                .html(response.data.html)
                                .appendTo('body');
                            
                            $modal.fadeIn(300);
                            $('body').addClass('overflow-hidden');
                            
                            // Close modal
                            $modal.on('click', '.tmu-modal-close, .tmu-modal-overlay', function() {
                                $modal.fadeOut(300, function() {
                                    $modal.remove();
                                    $('body').removeClass('overflow-hidden');
                                });
                            });
                        }
                    }
                });
            });
        }
        
        // Watchlist functionality
        initWatchlist() {
            $(document).on('click', '.tmu-add-watchlist', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const postId = $button.data('post-id');
                const $icon = $button.find('i');
                
                $.ajax({
                    url: tmu_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'tmu_toggle_watchlist',
                        post_id: postId,
                        nonce: tmu_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.added) {
                                $icon.removeClass('fa-plus').addClass('fa-check');
                                $button.addClass('added');
                            } else {
                                $icon.removeClass('fa-check').addClass('fa-plus');
                                $button.removeClass('added');
                            }
                        }
                    }
                });
            });
        }
        
        // Mobile menu toggle
        initMobileMenu() {
            $('.tmu-mobile-menu-toggle').on('click', function(e) {
                e.preventDefault();
                
                $(this).toggleClass('active');
                $('.tmu-navigation').toggleClass('active');
                $('body').toggleClass('menu-open');
            });
        }
        
        // Lazy loading for images
        initLazyLoad() {
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
        
        // Search functionality
        initSearch() {
            let searchTimeout;
            
            $('.tmu-search-input').on('input', function() {
                const $input = $(this);
                const query = $input.val().trim();
                const $suggestions = $input.siblings('.tmu-search-suggestions');
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    $suggestions.hide();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: tmu_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'tmu_search_suggestions',
                            query: query,
                            nonce: tmu_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data.suggestions.length) {
                                $suggestions.html(response.data.html).show();
                            } else {
                                $suggestions.hide();
                            }
                        }
                    });
                }, 300);
            });
            
            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.tmu-search-form').length) {
                    $('.tmu-search-suggestions').hide();
                }
            });
        }
    }
    
    // Smooth scrolling for anchor links
    function initSmoothScroll() {
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });
    }
    
    // Remove filter functionality
    $(document).on('click', '.remove-filter', function(e) {
        e.preventDefault();
        
        const filterKey = $(this).data('key');
        const $select = $(`[name="${filterKey}"]`);
        if ($select.length) {
            $select.val('');
            $select.trigger('change');
        }
    });
    
    // Show more cast functionality
    $(document).on('click', '.tmu-show-more-cast', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $hiddenCast = $('.tmu-hidden-cast');
        
        $hiddenCast.removeClass('hidden');
        $button.hide();
    });
    
    // Initialize everything when document is ready
    $(document).ready(function() {
        new TMUTemplates();
        initSmoothScroll();
        
        // Trigger custom event
        $(document).trigger('tmu:templates:loaded');
    });
    
})(jQuery);