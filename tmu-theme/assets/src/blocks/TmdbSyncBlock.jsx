/**
 * TMDB Sync Block Component
 * 
 * React component for TMDB synchronization block interface
 * with data fetching and synchronization controls.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    NumberControl,
    SelectControl,
    Button,
    Placeholder,
    Notice,
    Spinner,
    ProgressBar
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Register TMDB Sync Block
 */
registerBlockType('tmu/tmdb-sync', {
    title: __('TMDB Sync', 'tmu-theme'),
    description: __('TMDB data synchronization and import tools', 'tmu-theme'),
    icon: 'update',
    category: 'tmu-blocks',
    keywords: [
        __('tmdb', 'tmu-theme'),
        __('sync', 'tmu-theme'),
        __('import', 'tmu-theme'),
        __('data', 'tmu-theme'),
    ],
    supports: {
        html: false,
        multiple: false,
    },
    attributes: {
        sync_type: { 
            type: 'string',
            default: 'single'
        },
        tmdb_id: { type: 'number' },
        content_type: { 
            type: 'string',
            default: 'movie'
        },
        auto_sync: { 
            type: 'boolean',
            default: false
        },
        last_sync: { type: 'string' },
        sync_status: { type: 'string' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [notice, setNotice] = useState(null);
        const [syncProgress, setSyncProgress] = useState(0);
        const [syncData, setSyncData] = useState(null);
        
        const syncTypeOptions = [
            { label: __('Single Item', 'tmu-theme'), value: 'single' },
            { label: __('Bulk Import', 'tmu-theme'), value: 'bulk' },
            { label: __('Update Existing', 'tmu-theme'), value: 'update' },
        ];
        
        const contentTypeOptions = [
            { label: __('Movie', 'tmu-theme'), value: 'movie' },
            { label: __('TV Series', 'tmu-theme'), value: 'tv' },
            { label: __('Person', 'tmu-theme'), value: 'person' },
        ];
        
        const syncSingleItem = async () => {
            if (!attributes.tmdb_id || !attributes.content_type) {
                setNotice({
                    type: 'error',
                    message: __('Please enter a TMDB ID and select content type', 'tmu-theme')
                });
                return;
            }
            
            setIsLoading(true);
            setNotice(null);
            setSyncProgress(0);
            
            try {
                // Simulate progress
                const progressInterval = setInterval(() => {
                    setSyncProgress(prev => Math.min(prev + 10, 90));
                }, 200);
                
                const response = await fetch(`/wp-json/tmu/v1/tmdb/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.wpApiSettings?.nonce || '',
                    },
                    body: JSON.stringify({
                        tmdb_id: attributes.tmdb_id,
                        content_type: attributes.content_type,
                        sync_type: attributes.sync_type,
                    }),
                });
                
                clearInterval(progressInterval);
                setSyncProgress(100);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                setSyncData(data);
                
                setAttributes({
                    last_sync: new Date().toISOString(),
                    sync_status: 'success'
                });
                
                setNotice({
                    type: 'success',
                    message: __('Data synchronized successfully from TMDB', 'tmu-theme')
                });
                
            } catch (error) {
                console.error('Error syncing TMDB data:', error);
                setNotice({
                    type: 'error',
                    message: __('Failed to sync data from TMDB. Please try again.', 'tmu-theme')
                });
                setAttributes({
                    sync_status: 'error'
                });
            } finally {
                setIsLoading(false);
                setTimeout(() => setSyncProgress(0), 2000);
            }
        };
        
        const bulkImport = async () => {
            setIsLoading(true);
            setNotice(null);
            setSyncProgress(0);
            
            try {
                const response = await fetch(`/wp-json/tmu/v1/tmdb/bulk-import`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.wpApiSettings?.nonce || '',
                    },
                    body: JSON.stringify({
                        content_type: attributes.content_type,
                        limit: 20, // Default limit
                    }),
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                setSyncData(data);
                
                setNotice({
                    type: 'success',
                    message: __('Bulk import completed successfully', 'tmu-theme')
                });
                
            } catch (error) {
                console.error('Error with bulk import:', error);
                setNotice({
                    type: 'error',
                    message: __('Bulk import failed. Please try again.', 'tmu-theme')
                });
            } finally {
                setIsLoading(false);
            }
        };
        
        const updateExisting = async () => {
            setIsLoading(true);
            setNotice(null);
            
            try {
                const response = await fetch(`/wp-json/tmu/v1/tmdb/update-existing`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.wpApiSettings?.nonce || '',
                    },
                    body: JSON.stringify({
                        content_type: attributes.content_type,
                    }),
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                setSyncData(data);
                
                setNotice({
                    type: 'success',
                    message: __('Existing data updated successfully', 'tmu-theme')
                });
                
            } catch (error) {
                console.error('Error updating existing data:', error);
                setNotice({
                    type: 'error',
                    message: __('Failed to update existing data. Please try again.', 'tmu-theme')
                });
            } finally {
                setIsLoading(false);
            }
        };
        
        const getStatusColor = (status) => {
            switch (status) {
                case 'success': return 'text-green-600';
                case 'error': return 'text-red-600';
                case 'pending': return 'text-yellow-600';
                default: return 'text-gray-600';
            }
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Sync Configuration', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Sync Type', 'tmu-theme')}
                            value={attributes.sync_type || 'single'}
                            options={syncTypeOptions}
                            onChange={(value) => setAttributes({ sync_type: value })}
                        />
                        <SelectControl
                            label={__('Content Type', 'tmu-theme')}
                            value={attributes.content_type || 'movie'}
                            options={contentTypeOptions}
                            onChange={(value) => setAttributes({ content_type: value })}
                        />
                        {attributes.sync_type === 'single' && (
                            <NumberControl
                                label={__('TMDB ID', 'tmu-theme')}
                                value={attributes.tmdb_id || ''}
                                onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || 0 })}
                                help={__('Enter the TMDB ID to sync', 'tmu-theme')}
                            />
                        )}
                    </PanelBody>
                    
                    <PanelBody title={__('Sync Actions', 'tmu-theme')} initialOpen={false}>
                        {attributes.sync_type === 'single' && (
                            <Button
                                variant="primary"
                                onClick={syncSingleItem}
                                disabled={!attributes.tmdb_id || isLoading}
                                isBusy={isLoading}
                            >
                                {isLoading 
                                    ? __('Syncing...', 'tmu-theme') 
                                    : __('Sync Single Item', 'tmu-theme')
                                }
                            </Button>
                        )}
                        {attributes.sync_type === 'bulk' && (
                            <Button
                                variant="primary"
                                onClick={bulkImport}
                                disabled={isLoading}
                                isBusy={isLoading}
                            >
                                {isLoading 
                                    ? __('Importing...', 'tmu-theme') 
                                    : __('Start Bulk Import', 'tmu-theme')
                                }
                            </Button>
                        )}
                        {attributes.sync_type === 'update' && (
                            <Button
                                variant="primary"
                                onClick={updateExisting}
                                disabled={isLoading}
                                isBusy={isLoading}
                            >
                                {isLoading 
                                    ? __('Updating...', 'tmu-theme') 
                                    : __('Update Existing Data', 'tmu-theme')
                                }
                            </Button>
                        )}
                    </PanelBody>
                    
                    {attributes.last_sync && (
                        <PanelBody title={__('Sync Status', 'tmu-theme')} initialOpen={false}>
                            <p>
                                <strong>{__('Last Sync:', 'tmu-theme')}</strong><br />
                                {new Date(attributes.last_sync).toLocaleString()}
                            </p>
                            {attributes.sync_status && (
                                <p className={getStatusColor(attributes.sync_status)}>
                                    <strong>{__('Status:', 'tmu-theme')}</strong> {attributes.sync_status}
                                </p>
                            )}
                        </PanelBody>
                    )}
                </InspectorControls>
                
                <div className="tmu-tmdb-sync-block bg-white border border-gray-200 rounded-lg p-6">
                    <div className="text-center">
                        <div className="mb-4">
                            <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                                <svg className="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <h3 className="text-xl font-bold text-gray-900 mb-2">
                                {__('TMDB Synchronization', 'tmu-theme')}
                            </h3>
                            <p className="text-gray-600 mb-4">
                                {attributes.sync_type === 'single' && __('Sync a single item from TMDB', 'tmu-theme')}
                                {attributes.sync_type === 'bulk' && __('Import multiple items from TMDB', 'tmu-theme')}
                                {attributes.sync_type === 'update' && __('Update existing data with latest TMDB information', 'tmu-theme')}
                            </p>
                        </div>
                        
                        {notice && (
                            <Notice
                                status={notice.type}
                                isDismissible={true}
                                onRemove={() => setNotice(null)}
                                className="mb-4"
                            >
                                {notice.message}
                            </Notice>
                        )}
                        
                        {syncProgress > 0 && (
                            <div className="mb-4">
                                <div className="text-sm text-gray-600 mb-2">
                                    {__('Sync Progress:', 'tmu-theme')} {syncProgress}%
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                        style={{ width: `${syncProgress}%` }}
                                    ></div>
                                </div>
                            </div>
                        )}
                        
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div className="p-4 bg-gray-50 rounded-lg">
                                <div className="text-lg font-semibold text-gray-900">
                                    {attributes.sync_type || 'single'}
                                </div>
                                <div className="text-sm text-gray-600">{__('Sync Type', 'tmu-theme')}</div>
                            </div>
                            <div className="p-4 bg-gray-50 rounded-lg">
                                <div className="text-lg font-semibold text-gray-900">
                                    {attributes.content_type || 'movie'}
                                </div>
                                <div className="text-sm text-gray-600">{__('Content Type', 'tmu-theme')}</div>
                            </div>
                            {attributes.tmdb_id && (
                                <div className="p-4 bg-gray-50 rounded-lg">
                                    <div className="text-lg font-semibold text-gray-900">
                                        {attributes.tmdb_id}
                                    </div>
                                    <div className="text-sm text-gray-600">{__('TMDB ID', 'tmu-theme')}</div>
                                </div>
                            )}
                        </div>
                        
                        {syncData && (
                            <div className="mt-4 p-4 bg-green-50 rounded-lg">
                                <h4 className="font-semibold text-green-900 mb-2">
                                    {__('Sync Results', 'tmu-theme')}
                                </h4>
                                <div className="text-sm text-green-800">
                                    {syncData.imported && (
                                        <p>{__('Imported:', 'tmu-theme')} {syncData.imported} {__('items', 'tmu-theme')}</p>
                                    )}
                                    {syncData.updated && (
                                        <p>{__('Updated:', 'tmu-theme')} {syncData.updated} {__('items', 'tmu-theme')}</p>
                                    )}
                                    {syncData.errors && (
                                        <p className="text-red-600">
                                            {__('Errors:', 'tmu-theme')} {syncData.errors} {__('items', 'tmu-theme')}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}
                        
                        {isLoading && (
                            <div className="flex items-center justify-center mt-4">
                                <Spinner />
                                <span className="ml-2 text-gray-600">
                                    {__('Processing...', 'tmu-theme')}
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </>
        );
    },
    
    save: () => {
        // Server-side rendering will handle the frontend display
        return null;
    },
});