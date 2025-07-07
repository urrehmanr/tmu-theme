/**
 * TMU Keyboard Navigation
 * 
 * Comprehensive keyboard navigation system for the TMU theme.
 * Implements WCAG 2.1 AA keyboard accessibility standards.
 * 
 * @version 1.0.0
 */

class TMUKeyboardNavigation {
    constructor() {
        this.focusableElements = 'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])';
        this.settings = window.tmu_keyboard_nav?.settings || {};
        this.strings = window.tmu_keyboard_nav?.strings || {};
        this.init();
    }
    
    init() {
        this.setupKeyboardHandlers();
        this.setupFocusManagement();
        this.setupSkipLinks();
        this.setupAriaLiveRegions();
        this.setupModalHandling();
        this.setupDropdownHandling();
        this.initializeAccessibilityFeatures();
    }
    
    /**
     * Set up main keyboard event handlers
     */
    setupKeyboardHandlers() {
        document.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'Tab':
                    this.handleTabNavigation(e);
                    break;
                case 'Enter':
                case ' ':
                    this.handleActivation(e);
                    break;
                case 'Escape':
                    this.handleEscape(e);
                    break;
                case 'ArrowUp':
                case 'ArrowDown':
                case 'ArrowLeft':
                case 'ArrowRight':
                    this.handleArrowNavigation(e);
                    break;
                case 'Home':
                case 'End':
                    this.handleHomeEnd(e);
                    break;
                case 'PageUp':
                case 'PageDown':
                    this.handlePageNavigation(e);
                    break;
            }
        });
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.altKey) {
                this.handleKeyboardShortcuts(e);
            }
        });
    }
    
    /**
     * Handle Tab navigation
     */
    handleTabNavigation(e) {
        const focusableElements = document.querySelectorAll(this.focusableElements);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        // Check if we're in a modal or specific container
        const modal = document.querySelector('.tmu-modal.active');
        if (modal) {
            this.trapFocus(modal, e);
            return;
        }
        
        // Handle main page tab cycling
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
            }
        } else {
            if (document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
            }
        }
        
        // Add visual focus indicator
        this.addFocusIndicator(e.target);
    }
    
    /**
     * Handle Enter/Space activation
     */
    handleActivation(e) {
        const target = e.target;
        
        // Handle card activation
        if (target.classList.contains('tmu-card') || target.closest('.tmu-card')) {
            const card = target.closest('.tmu-card') || target;
            const link = card.querySelector('a');
            
            if (link) {
                this.announceToScreenReader('Navigating to ' + (link.textContent || link.getAttribute('aria-label')));
                link.click();
                e.preventDefault();
            }
        }
        
        // Handle filter button activation
        if (target.classList.contains('tmu-filter-button')) {
            this.handleFilterActivation(target, e);
        }
        
        // Handle dropdown toggle
        if (target.classList.contains('tmu-dropdown-trigger')) {
            this.toggleDropdown(target);
            e.preventDefault();
        }
        
        // Handle custom button elements
        if (target.hasAttribute('role') && target.getAttribute('role') === 'button') {
            target.click();
            e.preventDefault();
        }
    }
    
    /**
     * Handle Escape key
     */
    handleEscape(e) {
        // Close modals
        const modal = document.querySelector('.tmu-modal.active');
        if (modal) {
            this.closeModal(modal);
            e.preventDefault();
            return;
        }
        
        // Close dropdowns
        const dropdown = document.querySelector('.tmu-dropdown.open');
        if (dropdown) {
            this.closeDropdown(dropdown);
            e.preventDefault();
            return;
        }
        
        // Close search overlay
        const searchOverlay = document.querySelector('.tmu-search-overlay.active');
        if (searchOverlay) {
            this.closeSearchOverlay(searchOverlay);
            e.preventDefault();
            return;
        }
        
        // Remove any active states
        this.clearActiveStates();
    }
    
    /**
     * Handle arrow key navigation
     */
    handleArrowNavigation(e) {
        const grid = e.target.closest('.tmu-content-grid');
        if (!grid) return;
        
        const cards = Array.from(grid.querySelectorAll('.tmu-card[tabindex="0"], .tmu-card[tabindex]:not([tabindex="-1"])'));
        const currentIndex = cards.indexOf(e.target.closest('.tmu-card'));
        
        if (currentIndex === -1) return;
        
        let newIndex;
        const columns = this.getGridColumns(grid);
        
        switch (e.key) {
            case 'ArrowUp':
                newIndex = currentIndex - columns;
                break;
            case 'ArrowDown':
                newIndex = currentIndex + columns;
                break;
            case 'ArrowLeft':
                newIndex = currentIndex - 1;
                break;
            case 'ArrowRight':
                newIndex = currentIndex + 1;
                break;
        }
        
        if (newIndex >= 0 && newIndex < cards.length) {
            cards[newIndex].focus();
            this.announceToScreenReader(`Item ${newIndex + 1} of ${cards.length}`);
            e.preventDefault();
        }
    }
    
    /**
     * Handle Home/End keys
     */
    handleHomeEnd(e) {
        const grid = e.target.closest('.tmu-content-grid');
        if (!grid) return;
        
        const cards = Array.from(grid.querySelectorAll('.tmu-card[tabindex="0"], .tmu-card[tabindex]:not([tabindex="-1"])'));
        
        if (e.key === 'Home' && cards.length > 0) {
            cards[0].focus();
            this.announceToScreenReader('First item');
            e.preventDefault();
        } else if (e.key === 'End' && cards.length > 0) {
            cards[cards.length - 1].focus();
            this.announceToScreenReader('Last item');
            e.preventDefault();
        }
    }
    
    /**
     * Handle Page Up/Down navigation
     */
    handlePageNavigation(e) {
        const grid = e.target.closest('.tmu-content-grid');
        if (!grid) return;
        
        const cards = Array.from(grid.querySelectorAll('.tmu-card[tabindex="0"], .tmu-card[tabindex]:not([tabindex="-1"])'));
        const currentIndex = cards.indexOf(e.target.closest('.tmu-card'));
        const columns = this.getGridColumns(grid);
        const rowsPerPage = 3; // Show 3 rows per page navigation
        
        let newIndex;
        if (e.key === 'PageUp') {
            newIndex = Math.max(0, currentIndex - (columns * rowsPerPage));
        } else {
            newIndex = Math.min(cards.length - 1, currentIndex + (columns * rowsPerPage));
        }
        
        if (newIndex !== currentIndex) {
            cards[newIndex].focus();
            this.announceToScreenReader(`Page navigation: item ${newIndex + 1} of ${cards.length}`);
            e.preventDefault();
        }
    }
    
    /**
     * Handle keyboard shortcuts
     */
    handleKeyboardShortcuts(e) {
        const shortcuts = {
            '1': () => this.skipToTarget('#main-content'),
            '2': () => this.skipToTarget('#site-navigation'),
            '3': () => this.skipToTarget('#site-search'),
            '4': () => this.skipToTarget('#site-footer'),
            'm': () => this.toggleMainMenu(),
            's': () => this.focusSearch()
        };
        
        const handler = shortcuts[e.key.toLowerCase()];
        if (handler) {
            handler();
            e.preventDefault();
        }
    }
    
    /**
     * Set up focus management
     */
    setupFocusManagement() {
        // Add focus indicators
        document.addEventListener('focusin', (e) => {
            this.addFocusIndicator(e.target);
        });
        
        document.addEventListener('focusout', (e) => {
            this.removeFocusIndicator(e.target);
        });
        
        // Handle mouse vs keyboard focus
        document.addEventListener('mousedown', () => {
            document.body.classList.add('using-mouse');
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.remove('using-mouse');
            }
        });
    }
    
    /**
     * Set up skip links
     */
    setupSkipLinks() {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'tmu-skip-link';
        skipLink.textContent = this.strings.skip_to_content || 'Skip to main content';
        
        document.body.insertBefore(skipLink, document.body.firstChild);
        
        skipLink.addEventListener('click', (e) => {
            e.preventDefault();
            this.skipToTarget('#main-content');
        });
        
        // Add additional skip links
        const additionalSkipLinks = [
            { href: '#site-navigation', text: this.strings.skip_to_navigation || 'Skip to navigation' },
            { href: '#site-search', text: 'Skip to search' },
            { href: '#site-footer', text: 'Skip to footer' }
        ];
        
        const skipContainer = document.createElement('div');
        skipContainer.className = 'tmu-skip-links';
        skipContainer.appendChild(skipLink);
        
        additionalSkipLinks.forEach(link => {
            const skipLinkEl = document.createElement('a');
            skipLinkEl.href = link.href;
            skipLinkEl.className = 'tmu-skip-link';
            skipLinkEl.textContent = link.text;
            skipLinkEl.addEventListener('click', (e) => {
                e.preventDefault();
                this.skipToTarget(link.href);
            });
            skipContainer.appendChild(skipLinkEl);
        });
        
        document.body.insertBefore(skipContainer, document.body.firstChild);
    }
    
    /**
     * Set up ARIA live regions
     */
    setupAriaLiveRegions() {
        // Create live regions if they don't exist
        if (!document.getElementById('tmu-live-region')) {
            const liveRegion = document.createElement('div');
            liveRegion.id = 'tmu-live-region';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }
        
        if (!document.getElementById('tmu-live-region-assertive')) {
            const liveRegionAssertive = document.createElement('div');
            liveRegionAssertive.id = 'tmu-live-region-assertive';
            liveRegionAssertive.setAttribute('aria-live', 'assertive');
            liveRegionAssertive.setAttribute('aria-atomic', 'true');
            liveRegionAssertive.className = 'sr-only';
            document.body.appendChild(liveRegionAssertive);
        }
    }
    
    /**
     * Set up modal handling
     */
    setupModalHandling() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const modal = document.querySelector('.tmu-modal.active');
                if (modal) {
                    this.trapFocus(modal, e);
                }
            }
        });
    }
    
    /**
     * Set up dropdown handling
     */
    setupDropdownHandling() {
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.tmu-dropdown')) {
                this.closeAllDropdowns();
            }
        });
    }
    
    /**
     * Initialize accessibility features
     */
    initializeAccessibilityFeatures() {
        // Make cards focusable
        document.querySelectorAll('.tmu-card').forEach(card => {
            if (!card.hasAttribute('tabindex')) {
                card.setAttribute('tabindex', '0');
            }
            if (!card.hasAttribute('role')) {
                card.setAttribute('role', 'article');
            }
        });
        
        // Enhance form accessibility
        this.enhanceFormAccessibility();
        
        // Add keyboard hints
        this.addKeyboardHints();
    }
    
    /**
     * Trap focus within element
     */
    trapFocus(element, e) {
        const focusableElements = element.querySelectorAll(this.focusableElements);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
            }
        } else {
            if (document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
            }
        }
    }
    
    /**
     * Get grid columns count
     */
    getGridColumns(grid) {
        const style = window.getComputedStyle(grid);
        const columns = style.gridTemplateColumns.split(' ').length;
        return columns || 1;
    }
    
    /**
     * Skip to target element
     */
    skipToTarget(selector) {
        const target = document.querySelector(selector);
        if (target) {
            target.focus();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            this.announceToScreenReader(`Skipped to ${target.getAttribute('aria-label') || target.id || 'target section'}`);
        }
    }
    
    /**
     * Add focus indicator
     */
    addFocusIndicator(element) {
        element.classList.add('tmu-keyboard-focus');
    }
    
    /**
     * Remove focus indicator
     */
    removeFocusIndicator(element) {
        element.classList.remove('tmu-keyboard-focus');
    }
    
    /**
     * Announce to screen reader
     */
    announceToScreenReader(message, assertive = false) {
        const regionId = assertive ? 'tmu-live-region-assertive' : 'tmu-live-region';
        const liveRegion = document.getElementById(regionId);
        
        if (liveRegion) {
            liveRegion.textContent = '';
            setTimeout(() => {
                liveRegion.textContent = message;
            }, 100);
        }
    }
    
    /**
     * Close modal
     */
    closeModal(modal) {
        modal.classList.remove('active');
        const trigger = document.querySelector(`[data-modal="${modal.id}"]`);
        if (trigger) {
            trigger.focus();
        }
        this.announceToScreenReader('Modal closed');
    }
    
    /**
     * Close dropdown
     */
    closeDropdown(dropdown) {
        dropdown.classList.remove('open');
        const trigger = dropdown.querySelector('.tmu-dropdown-trigger');
        if (trigger) {
            trigger.focus();
            trigger.setAttribute('aria-expanded', 'false');
        }
        this.announceToScreenReader('Dropdown closed');
    }
    
    /**
     * Close all dropdowns
     */
    closeAllDropdowns() {
        document.querySelectorAll('.tmu-dropdown.open').forEach(dropdown => {
            this.closeDropdown(dropdown);
        });
    }
    
    /**
     * Toggle dropdown
     */
    toggleDropdown(trigger) {
        const dropdown = trigger.closest('.tmu-dropdown');
        const isOpen = dropdown.classList.contains('open');
        
        // Close other dropdowns first
        this.closeAllDropdowns();
        
        if (!isOpen) {
            dropdown.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            
            // Focus first item in dropdown
            const firstItem = dropdown.querySelector('a, button, [tabindex="0"]');
            if (firstItem) {
                firstItem.focus();
            }
            
            this.announceToScreenReader('Dropdown opened');
        }
    }
    
    /**
     * Toggle main menu
     */
    toggleMainMenu() {
        const menuToggle = document.querySelector('.menu-toggle, [aria-controls="site-navigation"]');
        if (menuToggle) {
            menuToggle.click();
        }
    }
    
    /**
     * Focus search
     */
    focusSearch() {
        const searchInput = document.querySelector('#site-search input, .search-field');
        if (searchInput) {
            searchInput.focus();
            this.announceToScreenReader('Search focused');
        }
    }
    
    /**
     * Handle filter activation
     */
    handleFilterActivation(button, e) {
        const isActive = button.classList.contains('active');
        button.classList.toggle('active');
        button.setAttribute('aria-pressed', !isActive);
        
        const filterType = button.dataset.filter || button.textContent;
        this.announceToScreenReader(`Filter ${filterType} ${!isActive ? 'activated' : 'deactivated'}`);
        
        // Trigger filter event
        const filterEvent = new CustomEvent('tmu-filter-changed', {
            detail: { button, active: !isActive }
        });
        document.dispatchEvent(filterEvent);
    }
    
    /**
     * Close search overlay
     */
    closeSearchOverlay(overlay) {
        overlay.classList.remove('active');
        const trigger = document.querySelector('[data-search-overlay]');
        if (trigger) {
            trigger.focus();
        }
        this.announceToScreenReader('Search overlay closed');
    }
    
    /**
     * Clear active states
     */
    clearActiveStates() {
        document.querySelectorAll('.tmu-active, .active').forEach(element => {
            if (!element.classList.contains('tmu-modal') && !element.classList.contains('tmu-dropdown')) {
                element.classList.remove('tmu-active', 'active');
            }
        });
    }
    
    /**
     * Enhance form accessibility
     */
    enhanceFormAccessibility() {
        // Add required indicators
        document.querySelectorAll('input[required], textarea[required], select[required]').forEach(field => {
            if (!field.hasAttribute('aria-required')) {
                field.setAttribute('aria-required', 'true');
            }
        });
        
        // Enhance error handling
        document.querySelectorAll('.error, .invalid').forEach(field => {
            field.setAttribute('aria-invalid', 'true');
        });
    }
    
    /**
     * Add keyboard hints
     */
    addKeyboardHints() {
        if (this.settings.enable_keyboard_shortcuts) {
            const hint = document.createElement('div');
            hint.className = 'tmu-keyboard-hint sr-only';
            hint.innerHTML = 'Keyboard shortcuts: Alt+1 (main content), Alt+2 (navigation), Alt+M (menu), Alt+S (search)';
            document.body.appendChild(hint);
        }
    }
}

// Initialize keyboard navigation
document.addEventListener('DOMContentLoaded', () => {
    new TMUKeyboardNavigation();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TMUKeyboardNavigation;
}