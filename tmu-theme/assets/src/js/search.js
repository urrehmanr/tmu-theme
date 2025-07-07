(function($) {
    'use strict';
    
    class TMUSearch {
        constructor() {
            this.searchForm = $('.tmu-search-form');
            this.searchInput = $('.tmu-search-input');
            this.filtersContainer = $('.tmu-search-filters');
            this.resultsContainer = $('.tmu-search-results');
            this.facetsContainer = $('.tmu-search-facets');
            this.paginationContainer = $('.tmu-search-pagination');
            this.sortContainer = $('.tmu-search-sort');
            this.loadingSpinner = $('.tmu-loading-spinner');
            
            this.currentQuery = '';
            this.currentFilters = {};
            this.currentPage = 1;
            this.currentSort = 'relevance';
            this.isLoading = false;
            this.searchTimeout = null;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initAutocomplete();
            this.initUrlParams();
            this.initFilterSliders();
        }
        
        bindEvents() {
            // Search form submission
            this.searchForm.on('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
            
            // Real-time search (debounced)
            this.searchInput.on('input', () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    const query = this.searchInput.val().trim();
                    if (query.length >= 2 || query.length === 0) {
                        this.performSearch();
                    }
                }, 500);
            });
            
            // Filter changes
            $(document).on('change', '.tmu-filter-checkbox', () => {
                this.updateFilters();
                this.performSearch();
            });
            
            // Sort order changes
            $(document).on('change', '.tmu-sort-select', (e) => {
                this.currentSort = $(e.target).val();
                this.performSearch();
            });
            
            // Load more results
            $(document).on('click', '.tmu-load-more', () => {
                this.loadMoreResults();
            });
            
            // Clear filters
            $(document).on('click', '.tmu-clear-filters', () => {
                this.clearFilters();
            });
            
            // Clear individual filter
            $(document).on('click', '.tmu-clear-filter', (e) => {
                const filterType = $(e.target).data('filter-type');
                const filterValue = $(e.target).data('filter-value');
                this.clearIndividualFilter(filterType, filterValue);
            });
            
            // Range slider changes
            $(document).on('input change', '.tmu-year-slider, .tmu-rating-slider', () => {
                this.updateRangeFilters();
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.performSearch();
                }, 800);
            });
        }
        
        initAutocomplete() {
            if (typeof $.fn.autocomplete === 'undefined') {
                return; // jQuery UI not loaded
            }
            
            this.searchInput.autocomplete({
                source: (request, response) => {
                    $.ajax({
                        url: tmuSearch.ajax_url,
                        data: {
                            action: 'tmu_get_suggestions',
                            q: request.term
                        },
                        success: (data) => {
                            if (data.success) {
                                const suggestions = this.formatSuggestions(data.data);
                                response(suggestions);
                            }
                        },
                        error: () => {
                            response([]);
                        }
                    });
                },
                minLength: 2,
                delay: 300,
                select: (event, ui) => {
                    if (ui.item.url) {
                        window.location.href = ui.item.url;
                        return false;
                    }
                },
                open: () => {
                    $('.ui-autocomplete').addClass('tmu-autocomplete-menu');
                }
            }).autocomplete('instance')._renderItem = function(ul, item) {
                return $('<li>').append(this.formatSuggestionItem(item)).appendTo(ul);
            };
        }
        
        formatSuggestions(data) {
            const suggestions = [];
            
            // Add posts
            if (data.posts) {
                data.posts.forEach(post => {
                    suggestions.push({
                        label: `${post.title}${post.year ? ` (${post.year})` : ''} - ${post.type}`,
                        value: post.title,
                        url: post.url,
                        type: 'post',
                        poster: post.poster,
                        rating: post.rating
                    });
                });
            }
            
            // Add terms
            if (data.terms) {
                data.terms.forEach(term => {
                    suggestions.push({
                        label: `${term.name} - ${term.taxonomy} (${term.count})`,
                        value: term.name,
                        url: term.url,
                        type: 'term'
                    });
                });
            }
            
            // Add people
            if (data.people) {
                data.people.forEach(person => {
                    suggestions.push({
                        label: `${person.name}${person.known_for ? ` - ${person.known_for}` : ''} - Person`,
                        value: person.name,
                        url: person.url,
                        type: 'person',
                        profile: person.profile
                    });
                });
            }
            
            return suggestions.slice(0, 10);
        }
        
        formatSuggestionItem(item) {
            let html = '<div class="tmu-suggestion-item flex items-center p-2">';
            
            if (item.poster || item.profile) {
                html += `<img src="${item.poster || item.profile}" alt="${item.label}" class="w-8 h-8 rounded mr-2 object-cover">`;
            }
            
            html += `<div class="flex-1">`;
            html += `<div class="text-sm font-medium">${item.label}</div>`;
            
            if (item.rating) {
                html += `<div class="text-xs text-gray-500">⭐ ${item.rating}</div>`;
            }
            
            html += `</div></div>`;
            
            return html;
        }
        
        performSearch(resetPage = true) {
            if (this.isLoading) return;
            
            this.isLoading = true;
            
            if (resetPage) {
                this.currentPage = 1;
            }
            
            this.currentQuery = this.searchInput.val().trim();
            this.updateFilters();
            
            const searchData = {
                action: 'tmu_search',
                nonce: tmuSearch.nonce,
                query: this.currentQuery,
                filters: this.currentFilters,
                page: this.currentPage,
                per_page: 20,
                orderby: this.currentSort
            };
            
            // Show loading state
            this.showLoading();
            
            $.ajax({
                url: tmuSearch.ajax_url,
                type: 'POST',
                data: searchData,
                success: (response) => {
                    if (response.success) {
                        this.displayResults(response.data, resetPage);
                        this.updateUrl();
                        this.updateResultsInfo(response.data);
                    } else {
                        this.showError(response.data?.message || tmuSearch.strings.error);
                    }
                },
                error: () => {
                    this.showError(tmuSearch.strings.error);
                },
                complete: () => {
                    this.isLoading = false;
                    this.hideLoading();
                }
            });
        }
        
        updateFilters() {
            this.currentFilters = {};
            
            $('.tmu-filter-checkbox:checked').each((index, checkbox) => {
                const $checkbox = $(checkbox);
                const filterType = $checkbox.data('filter-type');
                const filterValue = $checkbox.val();
                
                if (!this.currentFilters[filterType]) {
                    this.currentFilters[filterType] = [];
                }
                
                this.currentFilters[filterType].push(filterValue);
            });
            
            // Add range filters
            this.updateRangeFilters();
        }
        
        updateRangeFilters() {
            // Year range filter
            const yearMin = $('.tmu-year-min').val();
            const yearMax = $('.tmu-year-max').val();
            if (yearMin && yearMax && (yearMin !== '1900' || yearMax !== new Date().getFullYear().toString())) {
                this.currentFilters.year_range = [yearMin, yearMax];
            }
            
            // Rating filter
            const rating = $('.tmu-rating-slider').val();
            if (rating && rating > 0) {
                this.currentFilters.rating_min = rating;
            }
            
            // Update displays
            $('.tmu-year-min-display').text(yearMin);
            $('.tmu-year-max-display').text(yearMax);
            $('.tmu-rating-value').text(parseFloat(rating).toFixed(1));
        }
        
        displayResults(data, resetPage = true) {
            // Update results
            if (resetPage) {
                this.resultsContainer.html(data.html);
            } else {
                // Append for "load more"
                this.resultsContainer.find('.tmu-search-results-grid').append(
                    $(data.html).find('.tmu-search-results-grid').html()
                );
            }
            
            // Update facets
            if (data.facets) {
                this.facetsContainer.html(data.facets);
            }
            
            // Update pagination
            if (data.pagination) {
                this.paginationContainer.html(data.pagination);
            }
            
            // Show/hide results info
            this.updateResultsInfo(data);
        }
        
        updateResultsInfo(data) {
            $('.tmu-results-count').text(data.total || 0);
            $('.tmu-execution-time').text((data.execution_time || 0).toFixed(3));
            
            // Update active filters display
            this.updateActiveFiltersDisplay();
        }
        
        updateActiveFiltersDisplay() {
            const activeFiltersContainer = $('.tmu-active-filters');
            activeFiltersContainer.empty();
            
            let hasActiveFilters = false;
            
            Object.keys(this.currentFilters).forEach(filterType => {
                const values = this.currentFilters[filterType];
                if (values && values.length > 0) {
                    hasActiveFilters = true;
                    
                    values.forEach(value => {
                        const filterTag = $(`
                            <span class="tmu-active-filter inline-flex items-center px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-800 mr-2 mb-2">
                                ${this.formatFilterLabel(filterType, value)}
                                <button type="button" class="tmu-clear-filter ml-2 text-blue-600 hover:text-blue-800" 
                                        data-filter-type="${filterType}" data-filter-value="${value}">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        `);
                        
                        activeFiltersContainer.append(filterTag);
                    });
                }
            });
            
            if (hasActiveFilters) {
                $('.tmu-active-filters-section').show();
            } else {
                $('.tmu-active-filters-section').hide();
            }
        }
        
        formatFilterLabel(filterType, value) {
            const labels = {
                post_type: {
                    movie: 'Movies',
                    tv: 'TV Shows',
                    drama: 'Dramas',
                    people: 'People'
                }
            };
            
            if (labels[filterType] && labels[filterType][value]) {
                return labels[filterType][value];
            }
            
            return value;
        }
        
        loadMoreResults() {
            this.currentPage++;
            this.performSearch(false);
        }
        
        clearFilters() {
            $('.tmu-filter-checkbox').prop('checked', false);
            $('.tmu-year-min').val('1900');
            $('.tmu-year-max').val(new Date().getFullYear());
            $('.tmu-rating-slider').val('0');
            this.currentFilters = {};
            this.performSearch();
        }
        
        clearIndividualFilter(filterType, filterValue) {
            if (this.currentFilters[filterType]) {
                this.currentFilters[filterType] = this.currentFilters[filterType].filter(v => v !== filterValue);
                
                if (this.currentFilters[filterType].length === 0) {
                    delete this.currentFilters[filterType];
                }
                
                // Uncheck the corresponding checkbox
                $(`.tmu-filter-checkbox[data-filter-type="${filterType}"][value="${filterValue}"]`).prop('checked', false);
                
                this.performSearch();
            }
        }
        
        showLoading() {
            this.resultsContainer.addClass('opacity-50 pointer-events-none');
            this.loadingSpinner.show();
            
            // Show skeleton loading
            if (!$('.tmu-skeleton-loader').length) {
                const skeletonHtml = this.generateSkeletonLoader();
                this.resultsContainer.append(skeletonHtml);
            }
        }
        
        hideLoading() {
            this.resultsContainer.removeClass('opacity-50 pointer-events-none');
            this.loadingSpinner.hide();
            $('.tmu-skeleton-loader').remove();
        }
        
        generateSkeletonLoader() {
            let html = '<div class="tmu-skeleton-loader grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 mt-6">';
            
            for (let i = 0; i < 10; i++) {
                html += `
                    <div class="animate-pulse">
                        <div class="bg-gray-300 h-64 rounded-lg mb-4"></div>
                        <div class="bg-gray-300 h-4 rounded mb-2"></div>
                        <div class="bg-gray-300 h-3 rounded w-3/4"></div>
                    </div>
                `;
            }
            
            html += '</div>';
            return html;
        }
        
        showError(message) {
            this.resultsContainer.html(`
                <div class="tmu-error text-center py-12">
                    <div class="max-w-md mx-auto">
                        <svg class="w-16 h-16 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Error</h3>
                        <p class="text-gray-600">${message}</p>
                    </div>
                </div>
            `);
        }
        
        updateUrl() {
            const params = new URLSearchParams();
            
            if (this.currentQuery) {
                params.set('s', this.currentQuery);
            }
            
            if (this.currentSort && this.currentSort !== 'relevance') {
                params.set('sort', this.currentSort);
            }
            
            Object.keys(this.currentFilters).forEach(filterType => {
                this.currentFilters[filterType].forEach(value => {
                    params.append(`filter[${filterType}]`, value);
                });
            });
            
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', newUrl);
            }
        }
        
        initUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Set search query
            const query = urlParams.get('s');
            if (query) {
                this.searchInput.val(query);
                this.currentQuery = query;
            }
            
            // Set sort
            const sort = urlParams.get('sort');
            if (sort) {
                this.currentSort = sort;
                $('.tmu-sort-select').val(sort);
            }
            
            // Set filters
            urlParams.forEach((value, key) => {
                const filterMatch = key.match(/^filter\[(.+)\]$/);
                if (filterMatch) {
                    const filterType = filterMatch[1];
                    $(`.tmu-filter-checkbox[data-filter-type="${filterType}"][value="${value}"]`).prop('checked', true);
                }
            });
            
            // Perform initial search if there are parameters
            if (query || Object.keys(this.currentFilters).length > 0) {
                this.updateFilters();
                this.performSearch();
            }
        }
        
        initFilterSliders() {
            // Initialize year range sliders
            const currentYear = new Date().getFullYear();
            $('.tmu-year-min').attr('max', currentYear).val('1900');
            $('.tmu-year-max').attr('max', currentYear).val(currentYear);
            
            // Update displays
            $('.tmu-year-min-display').text('1900');
            $('.tmu-year-max-display').text(currentYear);
            $('.tmu-rating-value').text('0.0');
        }
    }
    
    // Initialize search when document is ready
    $(document).ready(() => {
        if ($('.tmu-search-form').length || $('.tmu-search-container').length) {
            window.tmuSearchInstance = new TMUSearch();
        }
    });
    
})(jQuery);