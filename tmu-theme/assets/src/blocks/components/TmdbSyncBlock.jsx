/**
 * TMDB Sync Block
 * 
 * Simple React component for TMDB data synchronization.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl,
    SelectControl,
    Button,
    Placeholder,
    Spinner
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const TmdbSyncBlock = {
    title: __('TMDB Sync', 'tmu-theme'),
    icon: 'update',
    category: 'tmu-blocks',
    description: __('Synchronize data with The Movie Database (TMDB)', 'tmu-theme'),
    keywords: [__('tmdb', 'tmu-theme'), __('sync', 'tmu-theme'), __('data', 'tmu-theme')],
    attributes: {
        tmdb_id: { type: 'string', default: '' },
        content_type: { type: 'string', default: 'movie' },
        sync_status: { type: 'string', default: 'ready' },
        last_sync: { type: 'string', default: '' },
        auto_sync: { type: 'boolean', default: false },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [syncMessage, setSyncMessage] = useState('');
        
        const handleSync = async () => {
            if (!attributes.tmdb_id) {
                setSyncMessage(__('Please enter a TMDB ID first.', 'tmu-theme'));
                return;
            }
            
            setIsLoading(true);
            setSyncMessage(__('Syncing with TMDB...', 'tmu-theme'));
            
            try {
                const response = await fetch(`/wp-json/tmu/v1/tmdb/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.tmuBlocks?.nonce || '',
                    },
                    body: JSON.stringify({
                        tmdb_id: attributes.tmdb_id,
                        content_type: attributes.content_type,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    setSyncMessage(__('Successfully synced with TMDB!', 'tmu-theme'));
                    setAttributes({
                        sync_status: 'synced',
                        last_sync: new Date().toISOString(),
                    });
                } else {
                    setSyncMessage(__('Error syncing with TMDB: ', 'tmu-theme') + (data.message || __('Unknown error', 'tmu-theme')));
                    setAttributes({ sync_status: 'error' });
                }
            } catch (error) {
                setSyncMessage(__('Network error. Please try again.', 'tmu-theme'));
                setAttributes({ sync_status: 'error' });
            } finally {
                setIsLoading(false);
            }
        };
        
        const getSyncStatusBadge = () => {
            const statusColors = {
                ready: 'bg-gray-100 text-gray-800',
                syncing: 'bg-blue-100 text-blue-800',
                synced: 'bg-green-100 text-green-800',
                error: 'bg-red-100 text-red-800',
            };
            
            return (
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[attributes.sync_status]}`}>
                    {attributes.sync_status.toUpperCase()}
                </span>
            );
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('TMDB Settings', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Content Type', 'tmu-theme')}
                            value={attributes.content_type}
                            options={[
                                { label: __('Movie', 'tmu-theme'), value: 'movie' },
                                { label: __('TV Show', 'tmu-theme'), value: 'tv' },
                                { label: __('Person', 'tmu-theme'), value: 'person' },
                            ]}
                            onChange={(value) => setAttributes({ content_type: value })}
                        />
                        
                        <TextControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id}
                            onChange={(value) => setAttributes({ tmdb_id: value })}
                            placeholder={__('Enter TMDB ID...', 'tmu-theme')}
                            help={__('The numeric ID from TMDB URL', 'tmu-theme')}
                        />
                        
                        {attributes.last_sync && (
                            <div className="sync-info text-sm text-gray-600 mt-2">
                                <strong>{__('Last Sync:', 'tmu-theme')}</strong> {new Date(attributes.last_sync).toLocaleString()}
                            </div>
                        )}
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-tmdb-sync-block">
                    <div className="tmdb-sync-interface bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                        <div className="sync-header mb-4">
                            <div className="flex items-center justify-between mb-2">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    {__('TMDB Synchronization', 'tmu-theme')}
                                </h3>
                                {getSyncStatusBadge()}
                            </div>
                            <p className="text-sm text-gray-600">
                                {__('Sync data from The Movie Database to populate metadata fields automatically.', 'tmu-theme')}
                            </p>
                        </div>
                        
                        <div className="sync-controls space-y-4">
                            <div className="flex items-center space-x-3">
                                <TextControl
                                    label={__('TMDB ID', 'tmu-theme')}
                                    value={attributes.tmdb_id}
                                    onChange={(value) => setAttributes({ tmdb_id: value })}
                                    placeholder={__('e.g., 550 for Fight Club', 'tmu-theme')}
                                    className="flex-1"
                                />
                                <SelectControl
                                    label={__('Type', 'tmu-theme')}
                                    value={attributes.content_type}
                                    options={[
                                        { label: __('Movie', 'tmu-theme'), value: 'movie' },
                                        { label: __('TV', 'tmu-theme'), value: 'tv' },
                                        { label: __('Person', 'tmu-theme'), value: 'person' },
                                    ]}
                                    onChange={(value) => setAttributes({ content_type: value })}
                                />
                            </div>
                            
                            <div className="sync-actions">
                                <Button
                                    isPrimary
                                    onClick={handleSync}
                                    disabled={!attributes.tmdb_id || isLoading}
                                    className="mr-2"
                                >
                                    {isLoading ? (
                                        <>
                                            <Spinner />
                                            {__('Syncing...', 'tmu-theme')}
                                        </>
                                    ) : (
                                        __('Sync from TMDB', 'tmu-theme')
                                    )}
                                </Button>
                                
                                <Button
                                    isSecondary
                                    onClick={() => {
                                        setSyncMessage('');
                                        setAttributes({ sync_status: 'ready' });
                                    }}
                                    disabled={isLoading}
                                >
                                    {__('Reset', 'tmu-theme')}
                                </Button>
                            </div>
                            
                            {syncMessage && (
                                <div className={`sync-message p-3 rounded text-sm ${
                                    attributes.sync_status === 'synced' ? 'bg-green-50 text-green-700 border border-green-200' :
                                    attributes.sync_status === 'error' ? 'bg-red-50 text-red-700 border border-red-200' :
                                    'bg-blue-50 text-blue-700 border border-blue-200'
                                }`}>
                                    {syncMessage}
                                </div>
                            )}
                            
                            {attributes.last_sync && (
                                <div className="sync-info text-xs text-gray-500 border-t pt-3">
                                    {__('Last synchronized:', 'tmu-theme')} {new Date(attributes.last_sync).toLocaleString()}
                                </div>
                            )}
                        </div>
                        
                        <div className="sync-features mt-6 pt-4 border-t border-gray-200">
                            <h4 className="text-sm font-medium text-gray-900 mb-2">{__('What gets synced:', 'tmu-theme')}</h4>
                            <ul className="text-xs text-gray-600 space-y-1">
                                <li>• {__('Title, overview, and release information', 'tmu-theme')}</li>
                                <li>• {__('Cast and crew details', 'tmu-theme')}</li>
                                <li>• {__('Ratings, popularity, and vote counts', 'tmu-theme')}</li>
                                <li>• {__('Poster and backdrop images', 'tmu-theme')}</li>
                                <li>• {__('Genres, production companies, and countries', 'tmu-theme')}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-tmdb-sync">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

export default TmdbSyncBlock;