<?php
/**
 * Search form partial template
 * 
 * @package TMU
 */
?>

<form role="search" 
      method="get" 
      class="relative" 
      action="<?php echo esc_url(home_url('/')); ?>"
      x-data="searchForm">
    
    <div class="relative">
        <input type="search" 
               name="s" 
               value="<?php echo get_search_query(); ?>" 
               placeholder="Search movies, TV shows, people..."
               class="w-full px-4 py-2 pl-10 pr-12 text-gray-900 bg-white rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-20 transition-all duration-200"
               autocomplete="off"
               x-model="query"
               @input.debounce.300ms="search()"
               @focus="showSuggestions = true"
               @keydown.escape="showSuggestions = false"
               @keydown.arrow-down.prevent="navigateDown()"
               @keydown.arrow-up.prevent="navigateUp()"
               @keydown.enter.prevent="selectCurrent()">
        
        <!-- Search Icon -->
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        
        <!-- Clear Button -->
        <button type="button" 
                x-show="query.length > 0"
                @click="clearSearch()"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <!-- Search Suggestions Dropdown -->
    <div x-show="showSuggestions && suggestions.length > 0"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showSuggestions = false"
         class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto">
        
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
            <a :href="suggestion.url"
               :class="{'bg-gray-100': index === selectedIndex}"
               class="flex items-center p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 transition-colors duration-200"
               @mouseenter="selectedIndex = index"
               @click="selectSuggestion(suggestion)">
                
                <!-- Suggestion Image -->
                <div class="w-12 h-16 mr-3 bg-gray-200 rounded overflow-hidden flex-shrink-0">
                    <img x-show="suggestion.image" 
                         :src="suggestion.image" 
                         :alt="suggestion.title"
                         class="w-full h-full object-cover"
                         loading="lazy">
                    <div x-show="!suggestion.image" 
                         class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Suggestion Content -->
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 truncate" x-text="suggestion.title"></h4>
                    <p class="text-xs text-gray-500 mt-1" x-text="suggestion.type"></p>
                    <p x-show="suggestion.year" 
                       class="text-xs text-gray-400" 
                       x-text="suggestion.year"></p>
                </div>
                
                <!-- Suggestion Rating -->
                <div x-show="suggestion.rating" 
                     class="flex items-center ml-2">
                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <span class="text-xs text-gray-600 ml-1" x-text="suggestion.rating"></span>
                </div>
            </a>
        </template>
        
        <!-- View All Results -->
        <div x-show="query.length > 2" 
             class="p-3 border-t border-gray-200 bg-gray-50">
            <button type="submit"
                    class="w-full text-left text-sm text-blue-600 hover:text-blue-800 font-medium">
                View all results for "<span x-text="query"></span>"
            </button>
        </div>
    </div>
    
    <!-- Loading Indicator -->
    <div x-show="isLoading"
         class="absolute right-3 top-1/2 transform -translate-y-1/2">
        <svg class="animate-spin w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
</form>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('searchForm', () => ({
        query: '',
        suggestions: [],
        showSuggestions: false,
        selectedIndex: -1,
        isLoading: false,
        
        async search() {
            if (this.query.length < 2) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }
            
            this.isLoading = true;
            
            try {
                const response = await fetch(`${window.location.origin}/wp-json/tmu/v1/search?s=${encodeURIComponent(this.query)}&per_page=8`);
                
                if (!response.ok) {
                    throw new Error('Search request failed');
                }
                
                const data = await response.json();
                this.suggestions = this.formatSuggestions(data);
                this.showSuggestions = true;
                this.selectedIndex = -1;
                
            } catch (error) {
                console.error('Search error:', error);
                this.suggestions = [];
            } finally {
                this.isLoading = false;
            }
        },
        
        formatSuggestions(data) {
            return data.map(item => ({
                id: item.id,
                title: item.title,
                url: item.url,
                type: this.getTypeLabel(item.type),
                year: item.year || '',
                rating: item.rating ? parseFloat(item.rating).toFixed(1) : '',
                image: item.image || ''
            }));
        },
        
        getTypeLabel(type) {
            const labels = {
                'movie': 'Movie',
                'tv': 'TV Show',
                'drama': 'Drama',
                'people': 'Person'
            };
            return labels[type] || type;
        },
        
        navigateDown() {
            if (this.selectedIndex < this.suggestions.length - 1) {
                this.selectedIndex++;
            }
        },
        
        navigateUp() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
        },
        
        selectCurrent() {
            if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
                this.selectSuggestion(this.suggestions[this.selectedIndex]);
            } else {
                // Submit the form for general search
                this.$el.submit();
            }
        },
        
        selectSuggestion(suggestion) {
            window.location.href = suggestion.url;
        },
        
        clearSearch() {
            this.query = '';
            this.suggestions = [];
            this.showSuggestions = false;
            this.selectedIndex = -1;
        }
    }));
});
</script>