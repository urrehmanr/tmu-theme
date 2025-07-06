/**
 * TMU Admin Settings JavaScript
 * 
 * @package TMU
 * @version 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    const TMUAdmin = {
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initFormValidation();
        },
        
        bindEvents: function() {
            // Toggle switches
            $('.tmu-toggle input[type="checkbox"]').on('change', this.handleToggleChange);
            
            // API test button
            $('#test-api').on('click', this.handleApiTest);
            
            // Form submissions
            $('form[data-tmu-form]').on('submit', this.handleFormSubmit);
            
            // Reset buttons
            $('.tmu-reset-settings').on('click', this.handleResetSettings);
            
            // Export settings
            $('.tmu-export-settings').on('click', this.handleExportSettings);
            
            // Import settings
            $('.tmu-import-settings').on('click', this.handleImportSettings);
            
            // Validate settings
            $('.tmu-validate-settings').on('click', this.handleValidateSettings);
            
            // Auto-save on input change
            $('.tmu-auto-save').on('input change', this.handleAutoSave);
            
            // Tab navigation
            $('.tmu-tab-nav').on('click', 'a', this.handleTabNavigation);
            
            // Collapsible sections
            $('.tmu-collapsible-header').on('click', this.handleCollapsibleToggle);
        },
        
        initTooltips: function() {
            // Initialize tooltips if available
            if (typeof $.fn.tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }
        },
        
        initFormValidation: function() {
            // Real-time validation
            $('input[data-validate]').on('blur', function() {
                TMUAdmin.validateField($(this));
            });
        },
        
        handleToggleChange: function() {
            const $toggle = $(this);
            const setting = $toggle.data('setting');
            const value = $toggle.is(':checked') ? 'on' : 'off';
            
            if (!setting) {
                TMUAdmin.showNotice('Invalid setting configuration', 'error');
                return;
            }
            
            // Show loading state
            $toggle.closest('.tmu-setting-item').addClass('tmu-loading');
            
            // Make AJAX request
            $.ajax({
                url: tmuSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_update_settings',
                    setting: setting,
                    value: value,
                    nonce: tmuSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TMUAdmin.showNotice(response.data.message || 'Setting updated successfully', 'success');
                        
                        // Trigger custom event
                        $(document).trigger('tmu:settingUpdated', {
                            setting: setting,
                            value: value
                        });
                    } else {
                        TMUAdmin.showNotice(response.data.message || 'Failed to update setting', 'error');
                        // Revert toggle state
                        $toggle.prop('checked', !$toggle.is(':checked'));
                    }
                },
                error: function(xhr, status, error) {
                    TMUAdmin.showNotice('An error occurred: ' + error, 'error');
                    // Revert toggle state
                    $toggle.prop('checked', !$toggle.is(':checked'));
                },
                complete: function() {
                    $toggle.closest('.tmu-setting-item').removeClass('tmu-loading');
                }
            });
        },
        
        handleApiTest: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $result = $('#api-test-result');
            const apiKey = $('input[name="tmu_tmdb_api_key"]').val();
            
            if (!apiKey) {
                $result.html('<span class="error">Please enter an API key</span>');
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true).text('Testing...');
            $result.html('<span class="loading">Testing connection...</span>');
            
            $.ajax({
                url: tmuSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_test_api',
                    api_key: apiKey,
                    nonce: tmuSettings.apiNonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span class="success">✓ ' + response.data.message + '</span>');
                        
                        // Show additional API info if available
                        if (response.data.api_info) {
                            TMUAdmin.showApiInfo(response.data.api_info);
                        }
                    } else {
                        $result.html('<span class="error">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span class="error">✗ Connection failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },
        
        handleFormSubmit: function(e) {
            const $form = $(this);
            const formData = new FormData(this);
            
            // Show loading state
            $form.addClass('tmu-loading');
            
            // Add nonce if not present
            if (!formData.has('nonce')) {
                formData.append('nonce', tmuSettings.nonce);
            }
            
            // Submit via AJAX if data-ajax attribute is present
            if ($form.data('ajax')) {
                e.preventDefault();
                
                $.ajax({
                    url: $form.attr('action') || tmuSettings.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            TMUAdmin.showNotice(response.data.message || 'Settings saved successfully', 'success');
                        } else {
                            TMUAdmin.showNotice(response.data.message || 'Failed to save settings', 'error');
                        }
                    },
                    error: function() {
                        TMUAdmin.showNotice('An error occurred while saving settings', 'error');
                    },
                    complete: function() {
                        $form.removeClass('tmu-loading');
                    }
                });
            }
        },
        
        handleResetSettings: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const section = $button.data('section') || '';
            
            if (!confirm('Are you sure you want to reset settings to defaults? This action cannot be undone.')) {
                return;
            }
            
            $button.prop('disabled', true).text('Resetting...');
            
            $.ajax({
                url: tmuSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_reset_settings',
                    section: section,
                    nonce: tmuSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TMUAdmin.showNotice(response.data.message, 'success');
                        // Reload page to show updated settings
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        TMUAdmin.showNotice(response.data.message || 'Failed to reset settings', 'error');
                    }
                },
                error: function() {
                    TMUAdmin.showNotice('An error occurred while resetting settings', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Reset to Defaults');
                }
            });
        },
        
        handleExportSettings: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            
            $button.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: tmuSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_export_settings',
                    nonce: tmuSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        const dataStr = JSON.stringify(response.data.data, null, 2);
                        const dataBlob = new Blob([dataStr], {type: 'application/json'});
                        const url = URL.createObjectURL(dataBlob);
                        
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = response.data.filename || 'tmu-settings.json';
                        link.click();
                        
                        URL.revokeObjectURL(url);
                        TMUAdmin.showNotice('Settings exported successfully', 'success');
                    } else {
                        TMUAdmin.showNotice(response.data.message || 'Failed to export settings', 'error');
                    }
                },
                error: function() {
                    TMUAdmin.showNotice('An error occurred while exporting settings', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Export Settings');
                }
            });
        },
        
        handleImportSettings: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $fileInput = $('<input type="file" accept=".json">');
            
            $fileInput.on('change', function() {
                const file = this.files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = JSON.parse(e.target.result);
                        TMUAdmin.importSettingsData(data);
                    } catch (error) {
                        TMUAdmin.showNotice('Invalid JSON file', 'error');
                    }
                };
                reader.readAsText(file);
            });
            
            $fileInput.click();
        },
        
        importSettingsData: function(data) {
            $.ajax({
                url: tmuSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_import_settings',
                    data: JSON.stringify(data),
                    nonce: tmuSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        let message = response.data.message;
                        if (response.data.errors && response.data.errors.length > 0) {
                            message += '\n\nErrors:\n' + response.data.errors.join('\n');
                        }
                        TMUAdmin.showNotice(message, 'success');
                        
                        // Reload page to show updated settings
                        setTimeout(function() {
                            window.location.reload();
                        }, 3000);
                    } else {
                        TMUAdmin.showNotice(response.data.message || 'Failed to import settings', 'error');
                    }
                },
                error: function() {
                    TMUAdmin.showNotice('An error occurred while importing settings', 'error');
                }
            });
        },
        
        handleValidateSettings: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            
            $button.prop('disabled', true).text('Validating...');
            
            $.ajax({
                url: tmuSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_validate_settings',
                    nonce: tmuSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.valid) {
                            TMUAdmin.showNotice(response.data.message, 'success');
                        } else {
                            let message = response.data.message + '\n\nErrors:\n' + response.data.errors.join('\n');
                            TMUAdmin.showNotice(message, 'warning');
                        }
                    } else {
                        TMUAdmin.showNotice(response.data.message || 'Validation failed', 'error');
                    }
                },
                error: function() {
                    TMUAdmin.showNotice('An error occurred during validation', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Validate Settings');
                }
            });
        },
        
        handleAutoSave: function() {
            const $field = $(this);
            const setting = $field.attr('name');
            const value = $field.val();
            
            // Debounce auto-save
            clearTimeout($field.data('autoSaveTimeout'));
            $field.data('autoSaveTimeout', setTimeout(function() {
                TMUAdmin.saveField(setting, value);
            }, 2000));
        },
        
        handleTabNavigation: function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const target = $link.attr('href');
            
            // Update active tab
            $link.closest('.tmu-tab-nav').find('a').removeClass('active');
            $link.addClass('active');
            
            // Show target content
            $('.tmu-tab-content').hide();
            $(target).show();
            
            // Update URL hash
            window.location.hash = target;
        },
        
        handleCollapsibleToggle: function() {
            const $header = $(this);
            const $content = $header.next('.tmu-collapsible-content');
            
            $content.slideToggle();
            $header.toggleClass('expanded');
        },
        
        saveField: function(setting, value) {
            $.ajax({
                url: tmuSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tmu_update_setting',
                    setting: setting,
                    value: value,
                    nonce: tmuSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TMUAdmin.showFieldSaved(setting);
                    }
                }
            });
        },
        
        validateField: function($field) {
            const value = $field.val();
            const validationType = $field.data('validate');
            let isValid = true;
            let message = '';
            
            switch (validationType) {
                case 'email':
                    isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    message = isValid ? '' : 'Please enter a valid email address';
                    break;
                    
                case 'url':
                    isValid = /^https?:\/\/.+/.test(value);
                    message = isValid ? '' : 'Please enter a valid URL';
                    break;
                    
                case 'number':
                    isValid = !isNaN(value) && value !== '';
                    message = isValid ? '' : 'Please enter a valid number';
                    break;
                    
                case 'required':
                    isValid = value.trim() !== '';
                    message = isValid ? '' : 'This field is required';
                    break;
            }
            
            TMUAdmin.showFieldValidation($field, isValid, message);
            return isValid;
        },
        
        showFieldValidation: function($field, isValid, message) {
            const $container = $field.closest('.tmu-form-group');
            $container.find('.tmu-form-error').remove();
            
            if (!isValid && message) {
                $container.append('<div class="tmu-form-error">' + message + '</div>');
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }
        },
        
        showFieldSaved: function(setting) {
            const $field = $('[name="' + setting + '"]');
            const $indicator = $('<span class="tmu-saved-indicator">✓ Saved</span>');
            
            $field.after($indicator);
            setTimeout(function() {
                $indicator.fadeOut(function() {
                    $indicator.remove();
                });
            }, 2000);
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            const $notice = $('<div class="tmu-status-message tmu-status-' + type + '">' + message + '</div>');
            
            // Insert notice at the top of the page
            $('.wrap h1').after($notice);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
            
            // Allow manual dismissal
            $notice.on('click', function() {
                $(this).fadeOut(function() {
                    $(this).remove();
                });
            });
        },
        
        showApiInfo: function(apiInfo) {
            if (!apiInfo.base_url) return;
            
            const info = [
                'API Information:',
                '• Base URL: ' + apiInfo.base_url,
                '• Poster Sizes: ' + (apiInfo.poster_sizes || []).join(', '),
                '• Backdrop Sizes: ' + (apiInfo.backdrop_sizes || []).join(', ')
            ].join('\n');
            
            const $info = $('<div class="tmu-api-info"><pre>' + info + '</pre></div>');
            $('#api-test-result').after($info);
            
            setTimeout(function() {
                $info.fadeOut(function() {
                    $info.remove();
                });
            }, 10000);
        },
        
        // Utility methods
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Initialize admin functionality
    TMUAdmin.init();
    
    // Handle hash-based tab navigation on page load
    if (window.location.hash) {
        const $target = $(window.location.hash);
        if ($target.length) {
            $('.tmu-tab-nav a[href="' + window.location.hash + '"]').click();
        }
    }
    
    // Expose TMUAdmin globally for extensibility
    window.TMUAdmin = TMUAdmin;
    
    // Custom events
    $(document).on('tmu:settingUpdated', function(event, data) {
        console.log('Setting updated:', data);
        // Add any global setting update handlers here
    });
});