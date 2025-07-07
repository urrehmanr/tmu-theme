/**
 * Season Metadata Block Component
 * 
 * React component for season metadata block editor interface
 * with comprehensive season data fields.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    NumberControl,
    SelectControl,
    Button,
    Placeholder,
    Notice
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Register Season Metadata Block
 */
registerBlockType('tmu/season-metadata', {
    title: __('Season Metadata', 'tmu-theme'),
    description: __('Season metadata management for TV series', 'tmu-theme'),
    icon: 'calendar-alt',
    category: 'tmu-blocks',
    keywords: [
        __('season', 'tmu-theme'),
        __('tv', 'tmu-theme'),
        __('series', 'tmu-theme'),
        __('metadata', 'tmu-theme'),
    ],
    supports: {
        html: false,
        multiple: false,
    },
    attributes: {
        tv_series_id: { type: 'number' },
        season_number: { type: 'number' },
        name: { type: 'string' },
        overview: { type: 'string' },
        air_date: { type: 'string' },
        episode_count: { type: 'number' },
        poster_path: { type: 'string' },
        vote_average: { type: 'number' },
        vote_count: { type: 'number' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [notice, setNotice] = useState(null);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Season Information', 'tmu-theme')} initialOpen={true}>
                        <NumberControl
                            label={__('TV Series ID', 'tmu-theme')}
                            value={attributes.tv_series_id || ''}
                            onChange={(value) => setAttributes({ tv_series_id: parseInt(value) || 0 })}
                            help={__('Post ID of the TV series this season belongs to', 'tmu-theme')}
                        />
                        <NumberControl
                            label={__('Season Number', 'tmu-theme')}
                            value={attributes.season_number || ''}
                            onChange={(value) => setAttributes({ season_number: parseInt(value) || 0 })}
                            min={0}
                        />
                        <TextControl
                            label={__('Season Name', 'tmu-theme')}
                            value={attributes.name || ''}
                            onChange={(value) => setAttributes({ name: value })}
                            placeholder={__('e.g., Season 1, Specials', 'tmu-theme')}
                        />
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview || ''}
                            onChange={(value) => setAttributes({ overview: value })}
                            rows={4}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Air Date & Episodes', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Air Date', 'tmu-theme')}
                            type="date"
                            value={attributes.air_date || ''}
                            onChange={(value) => setAttributes({ air_date: value })}
                        />
                        <NumberControl
                            label={__('Episode Count', 'tmu-theme')}
                            value={attributes.episode_count || ''}
                            onChange={(value) => setAttributes({ episode_count: parseInt(value) || 0 })}
                            min={0}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Ratings', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Vote Average', 'tmu-theme')}
                            value={attributes.vote_average || ''}
                            onChange={(value) => setAttributes({ vote_average: parseFloat(value) || 0 })}
                            step={0.1}
                            min={0}
                            max={10}
                        />
                        <NumberControl
                            label={__('Vote Count', 'tmu-theme')}
                            value={attributes.vote_count || ''}
                            onChange={(value) => setAttributes({ vote_count: parseInt(value) || 0 })}
                            min={0}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Media', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Poster Path', 'tmu-theme')}
                            value={attributes.poster_path || ''}
                            onChange={(value) => setAttributes({ poster_path: value })}
                            help={__('Season poster image path', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-season-metadata-block bg-white border border-gray-200 rounded-lg p-6">
                    {attributes.name || attributes.season_number ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex items-start gap-4 mb-4">
                                {attributes.poster_path && (
                                    <img 
                                        src={attributes.poster_path}
                                        alt={attributes.name}
                                        className="w-20 h-30 object-cover rounded-md shadow-md"
                                    />
                                )}
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">
                                        {attributes.name || `${__('Season', 'tmu-theme')} ${attributes.season_number}`}
                                        {attributes.air_date && (
                                            <span className="text-gray-500 font-normal ml-2">
                                                ({new Date(attributes.air_date).getFullYear()})
                                            </span>
                                        )}
                                    </h3>
                                    {attributes.season_number && (
                                        <p className="text-gray-600 mb-2">
                                            {__('Season Number:', 'tmu-theme')} {attributes.season_number}
                                        </p>
                                    )}
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                                {attributes.air_date && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Air Date', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {new Date(attributes.air_date).toLocaleDateString()}
                                        </div>
                                    </div>
                                )}
                                {attributes.episode_count && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Episodes', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.episode_count}</div>
                                    </div>
                                )}
                                {attributes.vote_average && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Rating', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.vote_average}/10</div>
                                    </div>
                                )}
                            </div>
                            
                            {attributes.overview && (
                                <div className="mb-4">
                                    <h4 className="font-semibold text-gray-900 mb-2">{__('Overview', 'tmu-theme')}</h4>
                                    <p className="text-gray-700 leading-relaxed">{attributes.overview}</p>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="calendar-alt"
                            label={__('Season Metadata', 'tmu-theme')}
                            instructions={__('Configure season metadata in the block settings panel.', 'tmu-theme')}
                        />
                    )}
                </div>
            </>
        );
    },
    
    save: () => {
        // Server-side rendering will handle the frontend display
        return null;
    },
});