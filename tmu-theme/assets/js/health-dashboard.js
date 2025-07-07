class TMUHealthDashboard {
    constructor() {
        this.charts = {};
        this.updateInterval = 30000; // 30 seconds
        this.init();
    }
    
    init() {
        this.setupCharts();
        this.loadInitialData();
        this.startAutoRefresh();
    }
    
    setupCharts() {
        // Performance chart
        const performanceCtx = document.getElementById('performance-chart').getContext('2d');
        this.charts.performance = new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Response Time (ms)',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Response Time (ms)'
                        }
                    }
                }
            }
        });
        
        // Error rate chart
        const errorCtx = document.getElementById('error-chart').getContext('2d');
        this.charts.errors = new Chart(errorCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Errors per Hour',
                    data: [],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Error Count'
                        }
                    }
                }
            }
        });
    }
    
    loadInitialData() {
        this.updateHealthCards();
        this.updateCharts();
        this.updateRecentEvents();
    }
    
    updateHealthCards() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_health_check',
                nonce: tmu_admin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderHealthCards(response.data);
                }
            },
            error: (xhr, status, error) => {
                console.error('Health check failed:', error);
            }
        });
    }
    
    renderHealthCards(data) {
        // Database health
        const dbCard = document.getElementById('database-health');
        const dbStatus = dbCard.querySelector('.health-status');
        dbStatus.className = `health-status ${data.database.status}`;
        dbStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.database.status}</span>
            <span class="status-details">${data.database.response_time.toFixed(3)}s</span>
        `;
        
        // Cache health
        const cacheCard = document.getElementById('cache-health');
        const cacheStatus = cacheCard.querySelector('.health-status');
        cacheStatus.className = `health-status ${data.cache.status}`;
        cacheStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.cache.status}</span>
            <span class="status-details">${data.cache.response_time.toFixed(3)}s</span>
        `;
        
        // API health
        const apiCard = document.getElementById('api-health');
        const apiStatus = apiCard.querySelector('.health-status');
        apiStatus.className = `health-status ${data.api.status}`;
        apiStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.api.status}</span>
            <span class="status-details">${data.api.response_time ? data.api.response_time.toFixed(3) + 's' : ''}</span>
        `;
        
        // Performance health
        const perfCard = document.getElementById('performance-health');
        const perfStatus = perfCard.querySelector('.health-status');
        perfStatus.className = `health-status ${data.performance.status}`;
        perfStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.performance.status}</span>
            <span class="status-details">Avg: ${data.performance.avg_response_time ? data.performance.avg_response_time.toFixed(3) + 's' : 'N/A'}</span>
        `;
    }
    
    updateCharts() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_performance_data',
                nonce: tmu_admin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderCharts(response.data);
                }
            }
        });
    }
    
    renderCharts(data) {
        // Update performance chart
        if (data.performance) {
            this.charts.performance.data.labels = data.performance.labels;
            this.charts.performance.data.datasets[0].data = data.performance.data;
            this.charts.performance.update();
        }
        
        // Update error chart
        if (data.errors) {
            this.charts.errors.data.labels = data.errors.labels;
            this.charts.errors.data.datasets[0].data = data.errors.data;
            this.charts.errors.update();
        }
    }
    
    updateRecentEvents() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_recent_events',
                nonce: tmu_admin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderRecentEvents(response.data);
                }
            }
        });
    }
    
    renderRecentEvents(events) {
        const container = document.getElementById('recent-events');
        container.innerHTML = events.map(event => `
            <div class="event-item ${event.type}">
                <span class="event-time">${new Date(event.timestamp).toLocaleString()}</span>
                <span class="event-type">${event.type}</span>
                <span class="event-message">${event.message}</span>
            </div>
        `).join('');
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.updateHealthCards();
            this.updateCharts();
            this.updateRecentEvents();
        }, this.updateInterval);
    }
}

// Initialize dashboard
window.TMUHealthDashboard = new TMUHealthDashboard();