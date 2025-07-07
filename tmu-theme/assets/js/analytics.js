class TMUAnalytics {
    constructor() {
        this.events = [];
        this.config = window.tmu_analytics || {};
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.trackPageView();
        this.startSessionTracking();
    }
    
    setupEventListeners() {
        // Track content interactions
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-track]');
            if (target) {
                this.trackEvent('click', {
                    element: target.tagName.toLowerCase(),
                    content_type: target.dataset.contentType || '',
                    content_id: target.dataset.contentId || '',
                    position: this.getElementPosition(target),
                    text: target.textContent.trim().substring(0, 100)
                });
            }
        });
        
        // Track video plays
        document.addEventListener('play', (e) => {
            if (e.target.tagName === 'VIDEO') {
                this.trackEvent('video_play', {
                    src: e.target.src,
                    duration: e.target.duration,
                    current_time: e.target.currentTime
                });
            }
        }, true);
        
        // Track search usage
        const searchForms = document.querySelectorAll('.tmu-search-form');
        searchForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const query = form.querySelector('input[type="search"]').value;
                this.trackEvent('search', {
                    query: query,
                    results_count: this.getSearchResultsCount()
                });
            });
        });
        
        // Track scroll depth
        this.trackScrollDepth();
        
        // Track time on page
        this.trackTimeOnPage();
    }
    
    trackEvent(eventType, eventData = {}) {
        const event = {
            event_type: eventType,
            event_data: eventData,
            url: window.location.href,
            referrer: document.referrer,
            user_agent: navigator.userAgent,
            screen_resolution: `${screen.width}x${screen.height}`,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            timestamp: new Date().toISOString()
        };
        
        this.events.push(event);
        this.sendEvent(event);
    }
    
    trackPageView() {
        this.trackEvent('page_view', {
            page_type: this.config.page_type,
            content_id: this.config.content_id,
            load_time: performance.timing.loadEventEnd - performance.timing.navigationStart
        });
    }
    
    trackScrollDepth() {
        let maxScroll = 0;
        const milestones = [25, 50, 75, 100];
        const trackedMilestones = new Set();
        
        window.addEventListener('scroll', () => {
            const scrollPercent = Math.round(
                (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
            );
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
                
                milestones.forEach(milestone => {
                    if (scrollPercent >= milestone && !trackedMilestones.has(milestone)) {
                        trackedMilestones.add(milestone);
                        this.trackEvent('scroll_depth', {
                            percentage: milestone,
                            max_scroll: maxScroll
                        });
                    }
                });
            }
        });
    }
    
    trackTimeOnPage() {
        const startTime = Date.now();
        
        const trackTime = () => {
            const timeOnPage = Math.round((Date.now() - startTime) / 1000);
            this.trackEvent('time_on_page', {
                seconds: timeOnPage,
                minutes: Math.round(timeOnPage / 60)
            });
        };
        
        // Track time intervals
        setTimeout(() => trackTime(), 30000); // 30 seconds
        setTimeout(() => trackTime(), 60000); // 1 minute
        setTimeout(() => trackTime(), 300000); // 5 minutes
        
        // Track on page unload
        window.addEventListener('beforeunload', trackTime);
    }
    
    startSessionTracking() {
        // Track session start
        if (!sessionStorage.getItem('tmu_session_started')) {
            this.trackEvent('session_start', {
                landing_page: window.location.href,
                referrer: document.referrer
            });
            sessionStorage.setItem('tmu_session_started', 'true');
        }
        
        // Track session end
        window.addEventListener('beforeunload', () => {
            this.trackEvent('session_end', {
                session_duration: Date.now() - parseInt(sessionStorage.getItem('tmu_session_start') || Date.now()),
                pages_viewed: parseInt(sessionStorage.getItem('tmu_pages_viewed') || 0) + 1
            });
        });
    }
    
    sendEvent(event) {
        fetch(this.config.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tmu_track_event',
                nonce: this.config.nonce,
                ...event
            })
        }).catch(error => {
            console.error('Analytics tracking error:', error);
        });
    }
    
    getElementPosition(element) {
        const rect = element.getBoundingClientRect();
        return {
            x: rect.left + window.scrollX,
            y: rect.top + window.scrollY,
            viewport_x: rect.left,
            viewport_y: rect.top
        };
    }
    
    getSearchResultsCount() {
        const resultsContainer = document.querySelector('.tmu-search-results');
        if (resultsContainer) {
            const results = resultsContainer.querySelectorAll('.tmu-search-result');
            return results.length;
        }
        return 0;
    }
}

// Initialize analytics when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.tmuAnalytics = new TMUAnalytics();
});