// Import admin CSS
import '../css/admin.css';

// Admin functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin components
    initializeTMDBSync();
    initializeBulkActions();
    initializeMetaBoxes();
    initializeImageUpload();
    initializeFormValidation();
    initializeAjaxForms();
});

// TMDB Synchronization functionality
function initializeTMDBSync() {
    const syncButtons = document.querySelectorAll('.tmdb-sync-button');
    syncButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const postId = this.dataset.postId;
            const tmdbId = this.dataset.tmdbId;
            
            if (!tmdbId) {
                alert(tmu_admin.strings.tmdb_id_required);
                return;
            }
            
            syncWithTMDB(postId, tmdbId, this);
        });
    });
}

// Bulk actions functionality
function initializeBulkActions() {
    const bulkActionSelect = document.querySelector('#bulk-action-selector-top');
    const bulkActionButton = document.querySelector('#doaction');
    
    if (bulkActionSelect && bulkActionButton) {
        bulkActionButton.addEventListener('click', function(e) {
            const action = bulkActionSelect.value;
            const checkedItems = document.querySelectorAll('input[name="post[]"]:checked');
            
            if (action === 'tmdb_sync' && checkedItems.length > 0) {
                e.preventDefault();
                bulkSyncWithTMDB(Array.from(checkedItems).map(item => item.value));
            }
        });
    }
}

// Meta boxes functionality
function initializeMetaBoxes() {
    // TMDB ID input validation
    const tmdbIdInputs = document.querySelectorAll('input[name="tmdb_id"]');
    tmdbIdInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && !isValidTMDBId(value)) {
                this.classList.add('error');
                showNotification('Invalid TMDB ID format', 'error');
            } else {
                this.classList.remove('error');
            }
        });
    });
    
    // Cast/Crew dynamic fields
    initializeCastCrewFields();
    
    // Release date picker
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            validateDate(this);
        });
    });
}

// Cast/Crew dynamic fields
function initializeCastCrewFields() {
    const addCastButton = document.querySelector('#add-cast-member');
    const addCrewButton = document.querySelector('#add-crew-member');
    
    if (addCastButton) {
        addCastButton.addEventListener('click', function() {
            addCastCrewField('cast');
        });
    }
    
    if (addCrewButton) {
        addCrewButton.addEventListener('click', function() {
            addCastCrewField('crew');
        });
    }
    
    // Remove buttons for existing fields
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-cast-crew')) {
            e.preventDefault();
            e.target.closest('.cast-crew-field').remove();
        }
    });
}

// Image upload functionality
function initializeImageUpload() {
    const uploadButtons = document.querySelectorAll('.image-upload-button');
    uploadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetInput = document.querySelector(this.dataset.target);
            const previewContainer = document.querySelector(this.dataset.preview);
            
            openMediaUploader(targetInput, previewContainer);
        });
    });
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// AJAX forms
function initializeAjaxForms() {
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAjaxForm(this);
        });
    });
}

// TMDB Sync functions
function syncWithTMDB(postId, tmdbId, button) {
    const originalText = button.textContent;
    const spinner = button.querySelector('.loading-spinner');
    
    button.disabled = true;
    button.textContent = tmu_admin.strings.loading;
    
    if (spinner) {
        spinner.style.display = 'inline-block';
    }
    
    fetch(`${tmu_admin.ajaxurl}?action=tmdb_sync_post`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&tmdb_id=${tmdbId}&nonce=${tmu_admin.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(tmu_admin.strings.sync_success, 'success');
            // Refresh the page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.data || tmu_admin.strings.sync_error, 'error');
        }
    })
    .catch(error => {
        console.error('TMDB sync error:', error);
        showNotification(tmu_admin.strings.sync_error, 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = originalText;
        
        if (spinner) {
            spinner.style.display = 'none';
        }
    });
}

function bulkSyncWithTMDB(postIds) {
    if (!confirm(`Sync ${postIds.length} items with TMDB?`)) {
        return;
    }
    
    const progressBar = createProgressBar();
    document.body.appendChild(progressBar);
    
    let completed = 0;
    const total = postIds.length;
    
    const syncPromises = postIds.map(postId => {
        return fetch(`${tmu_admin.ajaxurl}?action=tmdb_sync_post`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}&nonce=${tmu_admin.nonce}`
        })
        .then(response => response.json())
        .then(data => {
            completed++;
            updateProgressBar(progressBar, (completed / total) * 100);
            return data;
        });
    });
    
    Promise.all(syncPromises)
        .then(results => {
            const successful = results.filter(r => r.success).length;
            showNotification(`Successfully synced ${successful} of ${total} items`, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        })
        .catch(error => {
            console.error('Bulk sync error:', error);
            showNotification('Error during bulk sync', 'error');
        })
        .finally(() => {
            document.body.removeChild(progressBar);
        });
}

// Helper functions
function addCastCrewField(type) {
    const container = document.querySelector(`#${type}-fields-container`);
    const template = document.querySelector(`#${type}-field-template`);
    
    if (container && template) {
        const newField = template.content.cloneNode(true);
        container.appendChild(newField);
    }
}

function isValidTMDBId(id) {
    return /^\d+$/.test(id);
}

function validateDate(input) {
    const date = new Date(input.value);
    const now = new Date();
    
    if (date > now) {
        input.classList.add('future-date');
    } else {
        input.classList.remove('future-date');
    }
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

function submitAjaxForm(form) {
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    const originalText = submitButton.textContent;
    
    submitButton.disabled = true;
    submitButton.textContent = tmu_admin.strings.loading;
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Form submitted successfully', 'success');
            form.reset();
        } else {
            showNotification(data.data || 'Form submission failed', 'error');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showNotification('Form submission failed', 'error');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
}

function openMediaUploader(targetInput, previewContainer) {
    if (typeof wp !== 'undefined' && wp.media) {
        const mediaUploader = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            targetInput.value = attachment.url;
            
            if (previewContainer) {
                previewContainer.innerHTML = `<img src="${attachment.url}" alt="Preview" style="max-width: 200px;">`;
            }
        });
        
        mediaUploader.open();
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notice notice-${type} is-dismissible`;
    notification.innerHTML = `<p>${message}</p>`;
    
    const notices = document.querySelector('.wp-header-end');
    if (notices) {
        notices.parentNode.insertBefore(notification, notices);
    }
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

function createProgressBar() {
    const progressBar = document.createElement('div');
    progressBar.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 300px;
        height: 20px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        z-index: 10000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    
    const progress = document.createElement('div');
    progress.style.cssText = `
        width: 0%;
        height: 100%;
        background: #007cba;
        transition: width 0.3s ease;
    `;
    
    progressBar.appendChild(progress);
    return progressBar;
}

function updateProgressBar(progressBar, percentage) {
    const progress = progressBar.querySelector('div');
    progress.style.width = `${percentage}%`;
}