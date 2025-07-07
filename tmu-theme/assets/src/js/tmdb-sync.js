(function($) {
    'use strict';
    
    /**
     * TMDB Sync JavaScript
     * Handles frontend interactions for TMDB sync functionality
     */
    
    const TMDBSync = {
        
        /**
         * Initialize TMDB sync functionality
         */
        init: function() {
            this.bindEvents();
            this.initProgressModal();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Test API connection
            $('#test-api').on('click', this.testApiConnection.bind(this));
            
            // Bulk sync operations
            $('#bulk-sync').on('click', this.bulkSync.bind(this));
            $('#sync-movies').on('click', () => this.bulkSync('movie'));
            $('#sync-tv').on('click', () => this.bulkSync('tv'));
            $('#sync-people').on('click', () => this.bulkSync('people'));
            
            // Clear cache
            $('#clear-cache').on('click', this.clearCache.bind(this));
            
            // Test webhook
            $('#test-webhook').on('click', this.testWebhook.bind(this));
            
            // Single post sync buttons (if on edit pages)
            $('.tmu-sync-single').on('click', this.syncSingle.bind(this));
            
            // Quick view modal functionality
            $('.tmu-quick-view').on('click', this.quickView.bind(this));
            
            // Add to watchlist functionality
            $('.tmu-add-watchlist').on('click', this.addToWatchlist.bind(this));
            
            // Trailer modal
            $('.tmu-trailer-btn').on('click', this.showTrailer.bind(this));
            
            // Modal close functionality
            $(document).on('click', '.tmu-modal-close, .tmu-modal-overlay', this.closeModal.bind(this));
            
            // Escape key to close modals
            $(document).on('keyup', (e) => {
                if (e.keyCode === 27) { // ESC key
                    this.closeModal();
                }
            });
        },
        
        /**
         * Test API connection
         */
        testApiConnection: function() {
            const $button = $('#test-api');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text(tmuTmdbSettings.strings.testing);
            
            $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_test_tmdb_api',
                    nonce: tmuTmdbSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Bulk sync operation
         */
        bulkSync: function(postType = 'all') {
            if (!confirm(tmuTmdbSettings.strings.confirm_bulk_sync)) {
                return;
            }
            
            const $button = postType === 'all' ? $('#bulk-sync') : $(`#sync-${postType}`);
            const originalText = $button.text();
            
            $button.prop('disabled', true).text(tmuTmdbSettings.strings.syncing);
            
            this.showProgressModal();
            this.updateProgress(0, 'Initializing sync...');
            
            $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_bulk_sync_tmdb',
                    post_type: postType,
                    nonce: tmuTmdbSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateProgress(100, response.data.message);
                        this.showNotice('success', response.data.message);
                        setTimeout(() => this.closeModal(), 3000);
                    } else {
                        this.updateProgress(100, 'Sync failed: ' + response.data);
                        this.showNotice('error', response.data);
                    }
                },
                error: () => {
                    this.updateProgress(100, 'Network error occurred');
                    this.showNotice('error', 'Network error occurred');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Clear TMDB cache
         */
        clearCache: function() {
            if (!confirm(tmuTmdbSettings.strings.confirm_clear_cache)) {
                return;
            }
            
            const $button = $('#clear-cache');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_clear_tmdb_cache',
                    nonce: tmuTmdbSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Test webhook
         */
        testWebhook: function() {
            const $button = $('#test-webhook');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_test_webhook',
                    nonce: tmuTmdbSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Sync single post
         */
        syncSingle: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const postId = $button.data('post-id');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Syncing...');
            
            $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_sync_single_tmdb',
                    post_id: postId,
                    nonce: tmuTmdbSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        // Reload page to show updated data
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.showNotice('error', response.data);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Quick view modal
         */
        quickView: function(e) {
            e.preventDefault();
            
            const postId = $(e.currentTarget).data('post-id');
            
            $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_quick_view',
                    post_id: postId,
                    nonce: tmuTmdbSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showModal(response.data.html, 'tmu-quick-view-modal');
                    } else {
                        this.showNotice('error', 'Failed to load content');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred');
                }
            });
        },
        
        /**
         * Add to watchlist
         */
        addToWatchlist: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const postId = $button.data('post-id');
            
            $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_add_watchlist',
                    post_id: postId,
                    nonce: tmuTmdbSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $button.toggleClass('added');
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data);
                    }
                },
                error: () => {
                    this.showNotice('error', 'Network error occurred');
                }
            });
        },
        
        /**
         * Show trailer modal
         */
        showTrailer: function(e) {
            e.preventDefault();
            
            const videoKey = $(e.currentTarget).data('video-key');
            const videoSite = $(e.currentTarget).data('video-site') || 'YouTube';
            
            let embedUrl = '';
            if (videoSite === 'YouTube') {
                embedUrl = `https://www.youtube.com/embed/${videoKey}?autoplay=1`;
            } else if (videoSite === 'Vimeo') {
                embedUrl = `https://player.vimeo.com/video/${videoKey}?autoplay=1`;
            }
            
            if (embedUrl) {
                const modalHtml = `
                    <div class="tmu-video-container">
                        <iframe src="${embedUrl}" 
                                frameborder="0" 
                                allowfullscreen 
                                allow="autoplay; encrypted-media">
                        </iframe>
                    </div>
                `;
                
                this.showModal(modalHtml, 'tmu-video-modal');
            }
        },
        
        /**
         * Initialize progress modal
         */
        initProgressModal: function() {
            if ($('#tmu-progress-modal').length === 0) {
                const modalHtml = `
                    <div id="tmu-progress-modal" class="tmu-modal" style="display: none;">
                        <div class="tmu-modal-overlay"></div>
                        <div class="tmu-modal-content">
                            <div class="tmu-modal-header">
                                <h3>Sync Progress</h3>
                                <button class="tmu-modal-close">&times;</button>
                            </div>
                            <div class="tmu-modal-body">
                                <div class="tmu-progress-bar">
                                    <div class="tmu-progress-fill"></div>
                                </div>
                                <div class="tmu-progress-text">
                                    <span id="tmu-progress-status">Initializing...</span>
                                </div>
                                <div class="tmu-sync-log"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(modalHtml);
            }
        },
        
        /**
         * Show progress modal
         */
        showProgressModal: function() {
            $('#tmu-progress-modal').fadeIn(300);
            $('body').addClass('tmu-modal-open');
        },
        
        /**
         * Update progress
         */
        updateProgress: function(percentage, message) {
            $('.tmu-progress-fill').css('width', percentage + '%');
            $('#tmu-progress-status').text(message);
            
            if (percentage === 100) {
                $('.tmu-progress-bar').addClass('complete');
            }
        },
        
        /**
         * Show modal
         */
        showModal: function(content, className = '') {
            const modalHtml = `
                <div class="tmu-modal ${className}">
                    <div class="tmu-modal-overlay"></div>
                    <div class="tmu-modal-content">
                        <div class="tmu-modal-header">
                            <button class="tmu-modal-close">&times;</button>
                        </div>
                        <div class="tmu-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `;
            
            const $modal = $(modalHtml).appendTo('body');
            $modal.fadeIn(300);
            $('body').addClass('tmu-modal-open');
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            $('.tmu-modal').fadeOut(300, function() {
                $(this).remove();
            });
            $('body').removeClass('tmu-modal-open');
        },
        
        /**
         * Show notice
         */
        showNotice: function(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const notice = $(`
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual dismiss
            notice.on('click', '.notice-dismiss', function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },
        
        /**
         * AJAX helper with error handling
         */
        ajaxRequest: function(action, data = {}, callback = null) {
            const requestData = {
                action: action,
                nonce: tmuTmdbSettings.nonce,
                ...data
            };
            
            return $.ajax({
                url: tmuTmdbSettings.ajax_url,
                type: 'POST',
                data: requestData,
                success: (response) => {
                    if (callback) {
                        callback(response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Error:', error);
                    this.showNotice('error', 'Network error occurred');
                }
            });
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        TMDBSync.init();
    });
    
    /**
     * Expose TMDBSync to global scope for external use
     */
    window.TMDBSync = TMDBSync;
    
})(jQuery);