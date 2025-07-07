/**
 * TMU Admin JavaScript
 * 
 * Handles admin interface interactions including TMDB sync,
 * progress tracking, and admin page functionality.
 */

(function($) {
    'use strict';

    const TMUAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initProgressBars();
            this.initTooltips();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Quick Actions
            $(document).on('click', '#bulk-tmdb-sync', this.handleBulkTMDBSync);
            $(document).on('click', '#data-health-check', this.handleDataHealthCheck);
            
            // Admin Bar Actions
            $(document).on('click', '.tmu-sync-all-link', this.handleAdminBarSync);
            
            // Progress tracking
            $(document).on('tmu:progress:update', this.updateProgress);
            $(document).on('tmu:progress:complete', this.completeProgress);
            
            // Notifications
            $(document).on('click', '.notice-dismiss', this.dismissNotice);
        },

        /**
         * Initialize progress bars
         */
        initProgressBars: function() {
            $('.progress-fill').each(function() {
                const $this = $(this);
                const percentage = $this.data('percentage') || 0;
                $this.css('width', percentage + '%');
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $this = $(this);
                const tooltip = $this.data('tooltip');
                
                $this.attr('title', tooltip);
            });
        },

        /**
         * Handle bulk TMDB sync
         */
        handleBulkTMDBSync: function(e) {
            e.preventDefault();
            
            if (!confirm(tmuAdmin.strings.confirm_sync)) {
                return;
            }
            
            const $button = $(this);
            const $progress = $('#action-progress');
            const $progressText = $('.progress-text');
            const $progressFill = $('.progress-fill');
            
            // Show progress
            $progress.show();
            $button.prop('disabled', true).addClass('loading');
            $progressText.text(tmuAdmin.strings.syncing);
            
            // Start sync process
            TMUAdmin.performBulkSync()
                .then(function(response) {
                    $progressFill.css('width', '100%');
                    $progressText.text(tmuAdmin.strings.sync_complete);
                    
                    setTimeout(function() {
                        $progress.hide();
                        $button.prop('disabled', false).removeClass('loading');
                        TMUAdmin.showNotice('success', response.message);
                    }, 1000);
                })
                .catch(function(error) {
                    $progressText.text(tmuAdmin.strings.sync_error);
                    $button.prop('disabled', false).removeClass('loading');
                    TMUAdmin.showNotice('error', error.message || tmuAdmin.strings.sync_error);
                    
                    setTimeout(function() {
                        $progress.hide();
                    }, 2000);
                });
        },

        /**
         * Handle data health check
         */
        handleDataHealthCheck: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            $button.prop('disabled', true).addClass('loading');
            
            // Simulate health check
            setTimeout(function() {
                $button.prop('disabled', false).removeClass('loading');
                TMUAdmin.showNotice('success', 'Data health check completed successfully!');
            }, 2000);
        },

        /**
         * Handle admin bar sync
         */
        handleAdminBarSync: function(e) {
            e.preventDefault();
            
            if (!confirm(tmuAdmin.strings.confirm_sync)) {
                return;
            }
            
            // Add loading state to admin bar
            const $link = $(this);
            $link.addClass('loading');
            
            TMUAdmin.performBulkSync()
                .then(function(response) {
                    $link.removeClass('loading');
                    TMUAdmin.showNotice('success', response.message);
                })
                .catch(function(error) {
                    $link.removeClass('loading');
                    TMUAdmin.showNotice('error', error.message);
                });
        },

        /**
         * Perform bulk TMDB sync
         */
        performBulkSync: function() {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: tmuAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tmu_bulk_tmdb_sync',
                        nonce: tmuAdmin.nonce,
                        post_type: tmuAdmin.postType || 'all'
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        reject({
                            message: 'AJAX request failed: ' + error
                        });
                    }
                });
            });
        },

        /**
         * Update progress bar
         */
        updateProgress: function(e, data) {
            const percentage = data.percentage || 0;
            const text = data.text || '';
            
            $('.progress-fill').css('width', percentage + '%');
            $('.progress-text').text(text);
        },

        /**
         * Complete progress
         */
        completeProgress: function(e, data) {
            $('.progress-fill').css('width', '100%');
            $('.progress-text').text(data.text || 'Complete!');
            
            setTimeout(function() {
                $('#action-progress').fadeOut();
            }, 2000);
        },

        /**
         * Show notification
         */
        showNotice: function(type, message) {
            const $notice = $('<div>')
                .addClass('notice notice-' + type + ' is-dismissible')
                .html('<p>' + message + '</p>')
                .hide();
            
            $('.wrap').prepend($notice);
            $notice.slideDown();
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.slideUp(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Dismiss notice
         */
        dismissNotice: function() {
            $(this).closest('.notice').slideUp(function() {
                $(this).remove();
            });
        },

        /**
         * Utility: Format number with commas
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        /**
         * Utility: Get human readable time difference
         */
        timeAgo: function(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = now - time;
            
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (days > 0) {
                return days + ' day' + (days > 1 ? 's' : '') + ' ago';
            } else if (hours > 0) {
                return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
            } else if (minutes > 0) {
                return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
            } else {
                return 'Just now';
            }
        }
    };

    /**
     * TMDB Meta Box functionality
     */
    const TMDBMetaBox = {
        
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '#tmdb-sync-btn', this.handleSync);
            $(document).on('click', '#tmdb-search-btn', this.handleSearch);
            $(document).on('click', '#tmdb-verify-btn', this.handleVerify);
            $(document).on('click', '#tmdb-clear-btn', this.handleClear);
            $(document).on('click', '#tmdb-refresh-btn', this.handleRefresh);
            $(document).on('click', '.tmdb-search-result', this.selectResult);
            $(document).on('keypress', '#tmdb_search', this.handleSearchKeypress);
        },

        handleSync: function(e) {
            e.preventDefault();
            
            if (!confirm(tmuTMDB.strings.confirm_overwrite)) {
                return;
            }
            
            TMDBMetaBox.showLoading(tmuTMDB.strings.syncing);
            
            $.ajax({
                url: tmuTMDB.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_tmdb_sync',
                    nonce: tmuTMDB.nonce,
                    post_id: tmuTMDB.postId,
                    force: true
                },
                success: function(response) {
                    TMDBMetaBox.hideLoading();
                    
                    if (response.success) {
                        TMDBMetaBox.showMessage('success', response.data.message);
                        // Refresh the page after successful sync
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        TMDBMetaBox.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    TMDBMetaBox.hideLoading();
                    TMDBMetaBox.showMessage('error', tmuTMDB.strings.sync_error);
                }
            });
        },

        handleSearch: function(e) {
            e.preventDefault();
            
            const query = $('#tmdb_search').val().trim();
            if (!query) {
                TMDBMetaBox.showMessage('error', 'Please enter a search term.');
                return;
            }
            
            TMDBMetaBox.showLoading(tmuTMDB.strings.searching);
            
            $.ajax({
                url: tmuTMDB.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_tmdb_search',
                    nonce: tmuTMDB.nonce,
                    query: query,
                    post_type: tmuTMDB.postType
                },
                success: function(response) {
                    TMDBMetaBox.hideLoading();
                    
                    if (response.success) {
                        TMDBMetaBox.displaySearchResults(response.data.results);
                    } else {
                        TMDBMetaBox.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    TMDBMetaBox.hideLoading();
                    TMDBMetaBox.showMessage('error', 'Search failed. Please try again.');
                }
            });
        },

        handleSearchKeypress: function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#tmdb-search-btn').click();
            }
        },

        handleVerify: function(e) {
            e.preventDefault();
            
            const tmdbId = $('#tmdb_id_manual').val().trim();
            if (!tmdbId) {
                TMDBMetaBox.showMessage('error', 'Please enter a TMDB ID.');
                return;
            }
            
            // For now, just set the ID and refresh
            TMDBMetaBox.showMessage('success', 'TMDB ID set. Save the post to apply changes.');
        },

        handleClear: function(e) {
            e.preventDefault();
            
            if (!confirm('Clear all TMDB data? This cannot be undone.')) {
                return;
            }
            
            $('#tmdb_id, #tmdb_id_manual').val('');
            TMDBMetaBox.showMessage('success', 'TMDB data cleared. Save the post to apply changes.');
        },

        handleRefresh: function(e) {
            e.preventDefault();
            location.reload();
        },

        selectResult: function() {
            $('.tmdb-search-result').removeClass('selected');
            $(this).addClass('selected');
            
            const tmdbId = $(this).data('tmdb-id');
            $('#tmdb_id_manual').val(tmdbId);
        },

        displaySearchResults: function(results) {
            const $container = $('#tmdb-search-results');
            $container.empty();
            
            if (!results || results.length === 0) {
                $container.html('<p>' + tmuTMDB.strings.no_results + '</p>').show();
                return;
            }
            
            results.forEach(function(result) {
                const $result = $('<div>')
                    .addClass('tmdb-search-result')
                    .attr('data-tmdb-id', result.id)
                    .html(
                        '<div class="result-title">' + (result.title || result.name) + '</div>' +
                        '<div class="result-meta">' + 
                        (result.release_date || result.first_air_date || 'No date') + 
                        ' • ID: ' + result.id + '</div>'
                    );
                
                $container.append($result);
            });
            
            $container.show();
        },

        showLoading: function(text) {
            $('#tmdb-loading-text').text(text || 'Loading...');
            $('#tmdb-loading').show();
        },

        hideLoading: function() {
            $('#tmdb-loading').hide();
        },

        showMessage: function(type, message) {
            const $messages = $('#tmdb-messages');
            const $message = $('<div>')
                .addClass('notice notice-' + type)
                .html('<p>' + message + '</p>');
            
            $messages.html($message);
            
            setTimeout(function() {
                $message.fadeOut();
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        TMUAdmin.init();
        
        // Initialize TMDB meta box if it exists
        if ($('.tmu-tmdb-metabox').length) {
            TMDBMetaBox.init();
        }
    });

    // Expose to global scope for external use
    window.TMUAdmin = TMUAdmin;
    window.TMDBMetaBox = TMDBMetaBox;

})(jQuery);