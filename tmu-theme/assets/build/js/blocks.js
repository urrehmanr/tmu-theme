/**
 * TMU Theme - Frontend Block Scripts
 * 
 * This file contains all frontend JavaScript functionality for blocks
 */

(function($) {
    'use strict';
    
    /**
     * Initialize all block functionality
     */
    function initBlocks() {
        initMovieMetadataBlocks();
        initTvSeriesMetadataBlocks();
        initDramaMetadataBlocks();
        initPeopleMetadataBlocks();
        initTaxonomyBlocks();
        initSearchBlocks();
    }
    
    /**
     * Initialize movie metadata blocks
     */
    function initMovieMetadataBlocks() {
        $('.tmu-movie-metadata').each(function() {
            const $block = $(this);
            
            // Initialize tabs if they exist
            if ($block.find('.tmu-tabs').length) {
                $block.find('.tmu-tab-link').on('click', function(e) {
                    e.preventDefault();
                    const target = $(this).data('tab');
                    
                    // Hide all tabs and show the selected one
                    $block.find('.tmu-tab-content').hide();
                    $block.find('.tmu-tab-content[data-tab="' + target + '"]').show();
                    
                    // Update active tab
                    $block.find('.tmu-tab-link').removeClass('active');
                    $(this).addClass('active');
                });
                
                // Show first tab by default
                $block.find('.tmu-tab-link').first().trigger('click');
            }
            
            // Initialize trailers
            $block.find('.tmu-trailer-trigger').on('click', function(e) {
                e.preventDefault();
                const videoId = $(this).data('video-id');
                const provider = $(this).data('provider') || 'youtube';
                
                if (videoId) {
                    openVideoModal(videoId, provider);
                }
            });
        });
    }
    
    /**
     * Initialize TV series metadata blocks
     */
    function initTvSeriesMetadataBlocks() {
        $('.tmu-tv-series-metadata').each(function() {
            const $block = $(this);
            
            // Season selector
            $block.find('.tmu-season-selector').on('change', function() {
                const seasonId = $(this).val();
                
                // Hide all seasons and show the selected one
                $block.find('.tmu-season-content').hide();
                $block.find('.tmu-season-content[data-season="' + seasonId + '"]').show();
            });
            
            // Initialize with first season selected
            $block.find('.tmu-season-selector').trigger('change');
        });
    }
    
    /**
     * Initialize drama metadata blocks
     */
    function initDramaMetadataBlocks() {
        $('.tmu-drama-metadata').each(function() {
            const $block = $(this);
            
            // Episode pagination
            $block.find('.tmu-episode-pagination a').on('click', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                
                // Hide all pages and show the selected one
                $block.find('.tmu-episode-page').hide();
                $block.find('.tmu-episode-page[data-page="' + page + '"]').show();
                
                // Update active page
                $block.find('.tmu-episode-pagination a').removeClass('active');
                $(this).addClass('active');
            });
            
            // Initialize with first page selected
            $block.find('.tmu-episode-pagination a').first().trigger('click');
        });
    }
    
    /**
     * Initialize people metadata blocks
     */
    function initPeopleMetadataBlocks() {
        $('.tmu-people-metadata').each(function() {
            const $block = $(this);
            
            // Credits tabs
            $block.find('.tmu-credits-tab').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('credits');
                
                // Hide all credits and show the selected one
                $block.find('.tmu-credits-content').hide();
                $block.find('.tmu-credits-content[data-credits="' + target + '"]').show();
                
                // Update active tab
                $block.find('.tmu-credits-tab').removeClass('active');
                $(this).addClass('active');
            });
            
            // Initialize with first credits tab selected
            $block.find('.tmu-credits-tab').first().trigger('click');
        });
    }
    
    /**
     * Initialize taxonomy blocks
     */
    function initTaxonomyBlocks() {
        // Taxonomy image blocks
        $('.tmu-taxonomy-image').each(function() {
            $(this).find('img').on('error', function() {
                $(this).attr('src', tmu_blocks.placeholder_image);
            });
        });
        
        // Taxonomy FAQs blocks
        $('.tmu-taxonomy-faqs').each(function() {
            $(this).find('.tmu-faq-question').on('click', function() {
                $(this).toggleClass('active');
                $(this).next('.tmu-faq-answer').slideToggle();
            });
        });
    }
    
    /**
     * Initialize search blocks
     */
    function initSearchBlocks() {
        $('.tmu-search-form').each(function() {
            const $form = $(this);
            
            $form.on('submit', function(e) {
                const searchType = $form.find('.tmu-search-type').val();
                if (searchType !== 'all') {
                    // Modify form action based on search type
                    $form.attr('action', tmu_blocks.search_urls[searchType]);
                }
            });
        });
    }
    
    /**
     * Open video modal
     * 
     * @param {string} videoId Video ID
     * @param {string} provider Video provider (youtube, vimeo, etc.)
     */
    function openVideoModal(videoId, provider) {
        let embedUrl = '';
        
        // Get embed URL based on provider
        switch (provider) {
            case 'youtube':
                embedUrl = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
                break;
            case 'vimeo':
                embedUrl = 'https://player.vimeo.com/video/' + videoId + '?autoplay=1';
                break;
            default:
                embedUrl = videoId; // Direct URL
                break;
        }
        
        // Create modal HTML
        const modalHtml = `
            <div class="tmu-video-modal">
                <div class="tmu-video-modal-content">
                    <span class="tmu-video-modal-close">&times;</span>
                    <iframe src="${embedUrl}" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                </div>
            </div>
        `;
        
        // Append modal to body
        $('body').append(modalHtml);
        
        // Close modal on click
        $('.tmu-video-modal-close, .tmu-video-modal').on('click', function() {
            $('.tmu-video-modal').remove();
        });
        
        // Prevent modal content click from closing modal
        $('.tmu-video-modal-content').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Close modal on escape key
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape') {
                $('.tmu-video-modal').remove();
            }
        });
    }
    
    // Initialize blocks when DOM is ready
    $(document).ready(function() {
        initBlocks();
    });
    
})(jQuery);