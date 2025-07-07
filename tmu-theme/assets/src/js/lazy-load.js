/**
 * TMU Lazy Loading System
 * Advanced lazy loading for images and content
 * 
 * @package TMU
 * @version 1.0.0
 */

class TMULazyLoader {
    constructor() {
        this.imageObserver = null;
        this.contentObserver = null;
        this.isSupported = 'IntersectionObserver' in window;
        this.loadedImages = new Set();
        this.loadingImages = new Set();
        
        // Configuration
        this.config = {
            rootMargin: '50px 0px',
            threshold: 0.01,
            enableWebP: this.supportsWebP(),
            placeholderClass: 'tmu-lazy-placeholder',
            loadingClass: 'tmu-lazy-loading',
            loadedClass: 'tmu-lazy-loaded',
            errorClass: 'tmu-lazy-error'
        };
        
        this.init();
    }
    
    /**
     * Initialize lazy loader
     */
    init() {
        if (this.isSupported) {
            this.setupImageObserver();
            this.setupContentObserver();
            this.setupBackgroundImageObserver();
        } else {
            // Fallback for older browsers
            this.loadAllImages();
        }
        
        // Load images on user interaction for faster perceived performance
        this.setupInteractionLoading();
        
        // Preload critical images
        this.preloadCriticalImages();
    }
    
    /**
     * Setup image observer
     */
    setupImageObserver() {
        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.imageObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: this.config.rootMargin,
            threshold: this.config.threshold
        });
        
        this.observeImages();
    }
    
    /**
     * Setup content observer for lazy content loading
     */
    setupContentObserver() {
        this.contentObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadContent(entry.target);
                    this.contentObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '100px 0px',
            threshold: this.config.threshold
        });
        
        this.observeContent();
    }
    
    /**
     * Setup background image observer
     */
    setupBackgroundImageObserver() {
        this.backgroundObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadBackgroundImage(entry.target);
                    this.backgroundObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: this.config.rootMargin,
            threshold: this.config.threshold
        });
        
        this.observeBackgroundImages();
    }
    
    /**
     * Observe images for lazy loading
     */
    observeImages() {
        const lazyImages = document.querySelectorAll('img[data-src], img.lazy-load');
        
        lazyImages.forEach(img => {
            if (!this.loadedImages.has(img)) {
                this.imageObserver.observe(img);
                img.classList.add(this.config.placeholderClass);
            }
        });
    }
    
    /**
     * Observe content for lazy loading
     */
    observeContent() {
        const lazyContent = document.querySelectorAll('[data-lazy-content]');
        
        lazyContent.forEach(element => {
            this.contentObserver.observe(element);
        });
    }
    
    /**
     * Observe background images
     */
    observeBackgroundImages() {
        const lazyBackgrounds = document.querySelectorAll('[data-bg-src]');
        
        lazyBackgrounds.forEach(element => {
            this.backgroundObserver.observe(element);
        });
    }
    
    /**
     * Load image
     */
    loadImage(img) {
        if (this.loadedImages.has(img) || this.loadingImages.has(img)) {
            return;
        }
        
        this.loadingImages.add(img);
        img.classList.add(this.config.loadingClass);
        
        const dataSrc = img.getAttribute('data-src');
        const dataSrcset = img.getAttribute('data-srcset');
        const dataSizes = img.getAttribute('data-sizes');
        
        if (!dataSrc) {
            this.handleImageError(img);
            return;
        }
        
        // Preload image
        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            this.handleImageSuccess(img, dataSrc, dataSrcset, dataSizes);
        };
        
        imageLoader.onerror = () => {
            this.handleImageError(img);
        };
        
        // Check for WebP support and serve appropriate format
        const imageSrc = this.getOptimizedImageSrc(dataSrc);
        imageLoader.src = imageSrc;
        
        // Set srcset if available
        if (dataSrcset) {
            imageLoader.srcset = this.getOptimizedSrcset(dataSrcset);
        }
    }
    
    /**
     * Handle successful image load
     */
    handleImageSuccess(img, src, srcset, sizes) {
        // Set the actual source
        img.src = this.getOptimizedImageSrc(src);
        
        if (srcset) {
            img.srcset = this.getOptimizedSrcset(srcset);
        }
        
        if (sizes) {
            img.sizes = sizes;
        }
        
        // Update classes
        img.classList.remove(this.config.placeholderClass, this.config.loadingClass);
        img.classList.add(this.config.loadedClass);
        
        // Remove data attributes
        img.removeAttribute('data-src');
        img.removeAttribute('data-srcset');
        img.removeAttribute('data-sizes');
        
        // Update tracking
        this.loadingImages.delete(img);
        this.loadedImages.add(img);
        
        // Trigger custom event
        img.dispatchEvent(new CustomEvent('tmu:imageLoaded', {
            bubbles: true,
            detail: { src: img.src }
        }));
        
        // Animate in
        this.animateImageIn(img);
    }
    
    /**
     * Handle image load error
     */
    handleImageError(img) {
        img.classList.remove(this.config.placeholderClass, this.config.loadingClass);
        img.classList.add(this.config.errorClass);
        
        // Set fallback image
        img.src = this.getFallbackImage();
        img.alt = 'Image failed to load';
        
        this.loadingImages.delete(img);
        
        // Trigger error event
        img.dispatchEvent(new CustomEvent('tmu:imageError', {
            bubbles: true,
            detail: { originalSrc: img.getAttribute('data-src') }
        }));
    }
    
    /**
     * Load content via AJAX
     */
    loadContent(element) {
        const contentUrl = element.getAttribute('data-lazy-content');
        if (!contentUrl) return;
        
        element.classList.add(this.config.loadingClass);
        element.innerHTML = this.getLoadingSpinner();
        
        fetch(contentUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            element.innerHTML = html;
            element.classList.remove(this.config.loadingClass);
            element.classList.add(this.config.loadedClass);
            element.removeAttribute('data-lazy-content');
            
            // Re-observe any new lazy images in the loaded content
            this.observeNewImages(element);
            
            // Trigger content loaded event
            element.dispatchEvent(new CustomEvent('tmu:contentLoaded', {
                bubbles: true,
                detail: { url: contentUrl }
            }));
        })
        .catch(error => {
            console.error('Error loading lazy content:', error);
            element.innerHTML = '<p>Failed to load content.</p>';
            element.classList.remove(this.config.loadingClass);
            element.classList.add(this.config.errorClass);
        });
    }
    
    /**
     * Load background image
     */
    loadBackgroundImage(element) {
        const bgSrc = element.getAttribute('data-bg-src');
        if (!bgSrc) return;
        
        const img = new Image();
        
        img.onload = () => {
            element.style.backgroundImage = `url(${this.getOptimizedImageSrc(bgSrc)})`;
            element.classList.add(this.config.loadedClass);
            element.removeAttribute('data-bg-src');
            
            // Animate in
            this.animateBackgroundIn(element);
        };
        
        img.onerror = () => {
            element.classList.add(this.config.errorClass);
        };
        
        img.src = this.getOptimizedImageSrc(bgSrc);
    }
    
    /**
     * Get optimized image source (WebP if supported)
     */
    getOptimizedImageSrc(src) {
        if (!this.config.enableWebP) {
            return src;
        }
        
        // Check if this is a local image that might have a WebP version
        if (src.includes(window.location.origin)) {
            const webpSrc = src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
            
            // In a real implementation, you'd check if the WebP version exists
            // For now, we'll assume it does if the original is JPEG/PNG
            if (/\.(jpg|jpeg|png)$/i.test(src)) {
                return webpSrc;
            }
        }
        
        return src;
    }
    
    /**
     * Get optimized srcset
     */
    getOptimizedSrcset(srcset) {
        if (!this.config.enableWebP) {
            return srcset;
        }
        
        return srcset.replace(/\.(jpg|jpeg|png)(\s+\d+w)/gi, '.webp$2');
    }
    
    /**
     * Setup interaction loading for better perceived performance
     */
    setupInteractionLoading() {
        const interactionEvents = ['touchstart', 'mouseover', 'keydown'];
        
        const loadOnInteraction = () => {
            // Load a few more images when user starts interacting
            const unloadedImages = document.querySelectorAll(`img.${this.config.placeholderClass}`);
            
            Array.from(unloadedImages).slice(0, 3).forEach(img => {
                this.loadImage(img);
            });
            
            // Remove listeners after first interaction
            interactionEvents.forEach(event => {
                document.removeEventListener(event, loadOnInteraction);
            });
        };
        
        interactionEvents.forEach(event => {
            document.addEventListener(event, loadOnInteraction, { passive: true });
        });
    }
    
    /**
     * Preload critical images above the fold
     */
    preloadCriticalImages() {
        const criticalImages = document.querySelectorAll('img[data-critical="true"]');
        
        criticalImages.forEach(img => {
            this.loadImage(img);
        });
    }
    
    /**
     * Observe new images added to DOM
     */
    observeNewImages(container) {
        const newImages = container.querySelectorAll('img[data-src]');
        
        newImages.forEach(img => {
            if (!this.loadedImages.has(img)) {
                this.imageObserver.observe(img);
                img.classList.add(this.config.placeholderClass);
            }
        });
    }
    
    /**
     * Animate image in
     */
    animateImageIn(img) {
        // Simple fade-in animation
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease-in-out';
        
        requestAnimationFrame(() => {
            img.style.opacity = '1';
        });
        
        // Clean up transition after animation
        setTimeout(() => {
            img.style.transition = '';
        }, 300);
    }
    
    /**
     * Animate background image in
     */
    animateBackgroundIn(element) {
        element.style.opacity = '0';
        element.style.transition = 'opacity 0.5s ease-in-out';
        
        requestAnimationFrame(() => {
            element.style.opacity = '1';
        });
        
        setTimeout(() => {
            element.style.transition = '';
        }, 500);
    }
    
    /**
     * Load all images (fallback for unsupported browsers)
     */
    loadAllImages() {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        lazyImages.forEach(img => {
            this.loadImage(img);
        });
        
        const lazyBackgrounds = document.querySelectorAll('[data-bg-src]');
        
        lazyBackgrounds.forEach(element => {
            this.loadBackgroundImage(element);
        });
    }
    
    /**
     * Check WebP support
     */
    supportsWebP() {
        return new Promise((resolve) => {
            const webP = new Image();
            webP.onload = webP.onerror = () => {
                resolve(webP.height === 2);
            };
            webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
        });
    }
    
    /**
     * Get fallback image
     */
    getFallbackImage() {
        return 'data:image/svg+xml;base64,' + btoa(`
            <svg xmlns="http://www.w3.org/2000/svg" width="300" height="200">
                <rect width="100%" height="100%" fill="#f0f0f0"/>
                <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="sans-serif" font-size="14">
                    Image not available
                </text>
            </svg>
        `);
    }
    
    /**
     * Get loading spinner
     */
    getLoadingSpinner() {
        return `
            <div class="tmu-loading-spinner">
                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="sr-only">Loading...</span>
            </div>
        `;
    }
    
    /**
     * Force load all remaining images
     */
    loadAllRemaining() {
        const remainingImages = document.querySelectorAll(`img.${this.config.placeholderClass}`);
        
        remainingImages.forEach(img => {
            this.loadImage(img);
        });
    }
    
    /**
     * Public API methods
     */
    
    /**
     * Refresh - observe new images
     */
    refresh() {
        if (this.isSupported) {
            this.observeImages();
            this.observeContent();
            this.observeBackgroundImages();
        } else {
            this.loadAllImages();
        }
    }
    
    /**
     * Force load specific image
     */
    loadSpecific(selector) {
        const element = document.querySelector(selector);
        if (element) {
            if (element.tagName === 'IMG') {
                this.loadImage(element);
            } else if (element.hasAttribute('data-bg-src')) {
                this.loadBackgroundImage(element);
            } else if (element.hasAttribute('data-lazy-content')) {
                this.loadContent(element);
            }
        }
    }
    
    /**
     * Get loading statistics
     */
    getStats() {
        const totalImages = document.querySelectorAll('img[data-src], img.lazy-load').length;
        const loadedImages = this.loadedImages.size;
        const loadingImages = this.loadingImages.size;
        
        return {
            total: totalImages,
            loaded: loadedImages,
            loading: loadingImages,
            remaining: totalImages - loadedImages - loadingImages,
            percentage: totalImages > 0 ? Math.round((loadedImages / totalImages) * 100) : 100
        };
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.TMULazyLoader = new TMULazyLoader();
    });
} else {
    window.TMULazyLoader = new TMULazyLoader();
}

// Make TMULazyLoader available globally
window.TMULazyLoader = window.TMULazyLoader || TMULazyLoader;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TMULazyLoader;
}

// jQuery plugin wrapper
if (typeof jQuery !== 'undefined') {
    jQuery.fn.tmuLazyLoad = function() {
        return this.each(function() {
            if (this.tagName === 'IMG' && window.TMULazyLoader) {
                window.TMULazyLoader.loadSpecific(this);
            }
        });
    };
}