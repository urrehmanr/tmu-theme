/**
 * TMU Maintenance Admin Interface
 * 
 * Handles all frontend interactions for the maintenance dashboard.
 */

(function($) {
    'use strict';
    
    const MaintenanceAdmin = {
        
        init() {
            this.bindEvents();
            this.loadMaintenanceStatus();
            this.startStatusPolling();
        },
        
        bindEvents() {
            // Manual maintenance buttons
            $('#run-daily-maintenance').on('click', () => this.runMaintenance('daily'));
            $('#run-weekly-maintenance').on('click', () => this.runMaintenance('weekly'));
            $('#run-monthly-maintenance').on('click', () => this.runMaintenance('monthly'));
            
            // Maintenance mode toggle
            $('#toggle-maintenance-mode').on('click', () => this.toggleMaintenanceMode());
            
            // Backup actions
            $(document).on('click', '.create-backup-btn', () => this.createBackup());
            $(document).on('click', '.restore-backup-btn', (e) => {
                const backupId = $(e.target).data('backup-id');
                this.restoreBackup(backupId);
            });
            
            // Security scan
            $(document).on('click', '#run-security-scan', () => this.runSecurityScan());
            
            // Update check
            $(document).on('click', '#check-updates', () => this.checkForUpdates());
        },
        
        loadMaintenanceStatus() {
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_maintenance_status',
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStatusDisplay(response.data);
                    }
                },
                error: () => {
                    this.showNotice('Failed to load maintenance status', 'error');
                }
            });
        },
        
        updateStatusDisplay(status) {
            const statusHtml = `
                <div class="maintenance-status-grid">
                    <div class="status-card">
                        <h3>Maintenance Mode</h3>
                        <span class="status-indicator ${status.maintenance_mode ? 'active' : 'inactive'}">
                            ${status.maintenance_mode ? 'Enabled' : 'Disabled'}
                        </span>
                    </div>
                    <div class="status-card">
                        <h3>Last Maintenance</h3>
                        <p>${status.last_maintenance}</p>
                        <small>${status.last_run_time || 'Never'}</small>
                    </div>
                    <div class="status-card">
                        <h3>Success Rate</h3>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${status.success_rate}%"></div>
                        </div>
                        <span>${status.success_rate}%</span>
                    </div>
                </div>
                
                <div class="scheduled-tasks">
                    <h3>Scheduled Tasks</h3>
                    <ul>
                        <li>Daily: ${this.formatNextRun(status.scheduled_tasks.daily)}</li>
                        <li>Weekly: ${this.formatNextRun(status.scheduled_tasks.weekly)}</li>
                        <li>Monthly: ${this.formatNextRun(status.scheduled_tasks.monthly)}</li>
                    </ul>
                </div>
            `;
            
            $('#maintenance-status-display').html(statusHtml);
            
            // Update toggle button text
            $('#toggle-maintenance-mode').text(
                status.maintenance_mode ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode'
            );
        },
        
        runMaintenance(type) {
            const button = $(`#run-${type}-maintenance`);
            const originalText = button.text();
            
            button.prop('disabled', true).text('Running...');
            
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_run_maintenance',
                    type: type,
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(`${type} maintenance completed successfully`, 'success');
                        this.loadMaintenanceStatus();
                    } else {
                        this.showNotice(`Maintenance failed: ${response.data}`, 'error');
                    }
                },
                error: () => {
                    this.showNotice('Maintenance request failed', 'error');
                },
                complete: () => {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        toggleMaintenanceMode() {
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_toggle_maintenance_mode',
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        this.loadMaintenanceStatus();
                    } else {
                        this.showNotice(`Failed to toggle maintenance mode: ${response.data}`, 'error');
                    }
                },
                error: () => {
                    this.showNotice('Failed to toggle maintenance mode', 'error');
                }
            });
        },
        
        createBackup() {
            const options = {
                include_files: $('#backup-include-files').is(':checked'),
                include_database: $('#backup-include-database').is(':checked'),
                include_media: $('#backup-include-media').is(':checked')
            };
            
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_create_manual_backup',
                    ...options,
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('Backup created successfully', 'success');
                        this.loadBackupList();
                    } else {
                        this.showNotice(`Backup failed: ${response.data}`, 'error');
                    }
                },
                error: () => {
                    this.showNotice('Backup request failed', 'error');
                }
            });
        },
        
        restoreBackup(backupId) {
            if (!confirm('Are you sure you want to restore this backup? This will overwrite current data.')) {
                return;
            }
            
            const options = {
                restore_database: confirm('Restore database?'),
                restore_files: confirm('Restore files?'),
                restore_tmu_data: confirm('Restore TMU data?')
            };
            
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_restore_backup',
                    backup_id: backupId,
                    restore_options: options,
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('Backup restored successfully', 'success');
                        location.reload();
                    } else {
                        this.showNotice(`Restore failed: ${response.data}`, 'error');
                    }
                },
                error: () => {
                    this.showNotice('Restore request failed', 'error');
                }
            });
        },
        
        runSecurityScan() {
            const button = $('#run-security-scan');
            const originalText = button.text();
            
            button.prop('disabled', true).text('Scanning...');
            
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_security_scan',
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displaySecurityResults(response.data);
                        this.showNotice('Security scan completed', 'success');
                    } else {
                        this.showNotice(`Security scan failed: ${response.data}`, 'error');
                    }
                },
                error: () => {
                    this.showNotice('Security scan request failed', 'error');
                },
                complete: () => {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        displaySecurityResults(results) {
            const resultsHtml = `
                <div class="security-results">
                    <h3>Security Score: ${results.score}/100</h3>
                    <div class="security-score-bar">
                        <div class="score-fill" style="width: ${results.score}%"></div>
                    </div>
                    
                    <div class="security-sections">
                        ${this.formatSecuritySection('File Permissions', results.results.file_permissions)}
                        ${this.formatSecuritySection('Plugin Vulnerabilities', results.results.plugin_vulnerabilities)}
                        ${this.formatSecuritySection('User Security', results.results.user_security)}
                        ${this.formatSecuritySection('Configuration', results.results.configuration_security)}
                        ${this.formatSecuritySection('Malware Scan', results.results.malware_scan)}
                        ${this.formatSecuritySection('SSL Configuration', results.results.ssl_check)}
                    </div>
                    
                    <div class="recommendations">
                        <h4>Recommendations:</h4>
                        <ul>
                            ${results.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;
            
            $('#security-results-display').html(resultsHtml);
        },
        
        formatSecuritySection(title, data) {
            return `
                <div class="security-section">
                    <h4>${title}</h4>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                </div>
            `;
        },
        
        checkForUpdates() {
            const button = $('#check-updates');
            const originalText = button.text();
            
            button.prop('disabled', true).text('Checking...');
            
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_check_updates',
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.update_available) {
                            this.displayUpdateInfo(response.data);
                        } else {
                            this.showNotice('No updates available', 'info');
                        }
                    } else {
                        this.showNotice(`Update check failed: ${response.data}`, 'error');
                    }
                },
                error: () => {
                    this.showNotice('Update check request failed', 'error');
                },
                complete: () => {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        displayUpdateInfo(updateData) {
            const updateHtml = `
                <div class="update-available">
                    <h3>Update Available</h3>
                    <p>Current Version: ${updateData.current_version}</p>
                    <p>New Version: ${updateData.new_version}</p>
                    <p>Release Date: ${updateData.release_date}</p>
                    
                    <div class="changelog">
                        <h4>What's New:</h4>
                        <p>${updateData.changelog}</p>
                    </div>
                    
                    <button id="install-update" class="button button-primary">Install Update</button>
                </div>
            `;
            
            $('#update-info-display').html(updateHtml);
            
            $('#install-update').on('click', () => this.installUpdate());
        },
        
        installUpdate() {
            if (!confirm('Are you sure you want to install the update? A backup will be created automatically.')) {
                return;
            }
            
            const button = $('#install-update');
            button.prop('disabled', true).text('Installing...');
            
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_install_update',
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('Update installed successfully', 'success');
                        location.reload();
                    } else {
                        this.showNotice(`Update failed: ${response.data}`, 'error');
                    }
                },
                error: () => {
                    this.showNotice('Update installation failed', 'error');
                },
                complete: () => {
                    button.prop('disabled', false).text('Install Update');
                }
            });
        },
        
        loadBackupList() {
            $.ajax({
                url: tmuMaintenance.ajax_url,
                type: 'POST',
                data: {
                    action: 'tmu_list_backups',
                    nonce: tmuMaintenance.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayBackupList(response.data);
                    }
                }
            });
        },
        
        displayBackupList(backups) {
            const backupsHtml = Object.keys(backups).map(backupId => {
                const backup = backups[backupId];
                return `
                    <div class="backup-item">
                        <span class="backup-id">${backupId}</span>
                        <span class="backup-type">${backup.type}</span>
                        <span class="backup-date">${backup.created_at}</span>
                        <span class="backup-size">${this.formatFileSize(backup.size)}</span>
                        <button class="restore-backup-btn button" data-backup-id="${backupId}">Restore</button>
                        <button class="delete-backup-btn button" data-backup-id="${backupId}">Delete</button>
                    </div>
                `;
            }).join('');
            
            $('#backup-list-display').html(backupsHtml);
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        formatNextRun(timestamp) {
            if (!timestamp) return 'Not scheduled';
            const date = new Date(timestamp * 1000);
            return date.toLocaleString();
        },
        
        showNotice(message, type = 'info') {
            const notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                </div>
            `);
            
            $('.wrap h1').after(notice);
            
            setTimeout(() => {
                notice.fadeOut();
            }, 5000);
        },
        
        startStatusPolling() {
            // Refresh status every 30 seconds
            setInterval(() => {
                this.loadMaintenanceStatus();
            }, 30000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(() => {
        MaintenanceAdmin.init();
    });
    
})(jQuery);