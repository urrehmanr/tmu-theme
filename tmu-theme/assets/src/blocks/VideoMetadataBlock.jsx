/**
 * Video Metadata Block Component
 * 
 * React component for video metadata block editor interface
 * with comprehensive video data fields.
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
    ToggleControl,
    Button,
    Placeholder,
    Notice
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Register Video Metadata Block
 */
registerBlockType('tmu/video-metadata', {
    title: __('Video Metadata', 'tmu-theme'),
    description: __('Video content metadata management', 'tmu-theme'),
    icon: 'video-alt',
    category: 'tmu-blocks',
    keywords: [
        __('video', 'tmu-theme'),
        __('trailer', 'tmu-theme'),
        __('clip', 'tmu-theme'),
        __('metadata', 'tmu-theme'),
    ],
    supports: {
        html: false,
        multiple: true,
    },
    attributes: {
        video_title: { type: 'string' },
        video_type: { 
            type: 'string',
            default: 'Trailer'
        },
        video_key: { type: 'string' },
        video_site: { 
            type: 'string',
            default: 'YouTube'
        },
        video_quality: { type: 'string' },
        video_language: { type: 'string' },
        video_duration: { type: 'number' },
        official: { 
            type: 'boolean',
            default: true
        },
        published_date: { type: 'string' },
        description: { type: 'string' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [notice, setNotice] = useState(null);
        
        const videoTypeOptions = [
            { label: __('Trailer', 'tmu-theme'), value: 'Trailer' },
            { label: __('Teaser', 'tmu-theme'), value: 'Teaser' },
            { label: __('Clip', 'tmu-theme'), value: 'Clip' },
            { label: __('Behind the Scenes', 'tmu-theme'), value: 'Behind the Scenes' },
            { label: __('Bloopers', 'tmu-theme'), value: 'Bloopers' },
            { label: __('Featurette', 'tmu-theme'), value: 'Featurette' },
            { label: __('Opening Credits', 'tmu-theme'), value: 'Opening Credits' },
            { label: __('Recap', 'tmu-theme'), value: 'Recap' },
        ];
        
        const siteOptions = [
            { label: __('YouTube', 'tmu-theme'), value: 'YouTube' },
            { label: __('Vimeo', 'tmu-theme'), value: 'Vimeo' },
            { label: __('Dailymotion', 'tmu-theme'), value: 'Dailymotion' },
            { label: __('Other', 'tmu-theme'), value: 'Other' },
        ];
        
        const qualityOptions = [
            { label: __('4K (2160p)', 'tmu-theme'), value: '2160p' },
            { label: __('Full HD (1080p)', 'tmu-theme'), value: '1080p' },
            { label: __('HD (720p)', 'tmu-theme'), value: '720p' },
            { label: __('SD (480p)', 'tmu-theme'), value: '480p' },
            { label: __('SD (360p)', 'tmu-theme'), value: '360p' },
        ];
        
        const getVideoThumbnail = (site, key) => {
            if (site === 'YouTube' && key) {
                return `https://img.youtube.com/vi/${key}/maxresdefault.jpg`;
            }
            return null;
        };
        
        const getVideoUrl = (site, key) => {
            if (site === 'YouTube' && key) {
                return `https://www.youtube.com/watch?v=${key}`;
            } else if (site === 'Vimeo' && key) {
                return `https://vimeo.com/${key}`;
            }
            return null;
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Video Information', 'tmu-theme')} initialOpen={true}>
                        <TextControl
                            label={__('Video Title', 'tmu-theme')}
                            value={attributes.video_title || ''}
                            onChange={(value) => setAttributes({ video_title: value })}
                        />
                        <SelectControl
                            label={__('Video Type', 'tmu-theme')}
                            value={attributes.video_type || 'Trailer'}
                            options={videoTypeOptions}
                            onChange={(value) => setAttributes({ video_type: value })}
                        />
                        <TextareaControl
                            label={__('Description', 'tmu-theme')}
                            value={attributes.description || ''}
                            onChange={(value) => setAttributes({ description: value })}
                            rows={3}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Video Source', 'tmu-theme')} initialOpen={false}>
                        <SelectControl
                            label={__('Video Site', 'tmu-theme')}
                            value={attributes.video_site || 'YouTube'}
                            options={siteOptions}
                            onChange={(value) => setAttributes({ video_site: value })}
                        />
                        <TextControl
                            label={__('Video Key/ID', 'tmu-theme')}
                            value={attributes.video_key || ''}
                            onChange={(value) => setAttributes({ video_key: value })}
                            help={__('YouTube video ID, Vimeo ID, etc.', 'tmu-theme')}
                        />
                        <SelectControl
                            label={__('Video Quality', 'tmu-theme')}
                            value={attributes.video_quality || '1080p'}
                            options={qualityOptions}
                            onChange={(value) => setAttributes({ video_quality: value })}
                        />
                        <TextControl
                            label={__('Language', 'tmu-theme')}
                            value={attributes.video_language || ''}
                            onChange={(value) => setAttributes({ video_language: value })}
                            help={__('Language code (e.g., en, es, fr)', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Video Details', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Duration (seconds)', 'tmu-theme')}
                            value={attributes.video_duration || ''}
                            onChange={(value) => setAttributes({ video_duration: parseInt(value) || 0 })}
                            min={0}
                        />
                        <TextControl
                            label={__('Published Date', 'tmu-theme')}
                            type="date"
                            value={attributes.published_date || ''}
                            onChange={(value) => setAttributes({ published_date: value })}
                        />
                        <ToggleControl
                            label={__('Official Video', 'tmu-theme')}
                            checked={attributes.official || true}
                            onChange={(value) => setAttributes({ official: value })}
                            help={__('Mark if this is an official video from the studio/distributor', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-video-metadata-block bg-white border border-gray-200 rounded-lg p-6">
                    {attributes.video_title || attributes.video_key ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex items-start gap-4 mb-4">
                                {getVideoThumbnail(attributes.video_site, attributes.video_key) && (
                                    <div className="relative">
                                        <img 
                                            src={getVideoThumbnail(attributes.video_site, attributes.video_key)}
                                            alt={attributes.video_title}
                                            className="w-32 h-18 object-cover rounded-md shadow-md"
                                        />
                                        <div className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-md">
                                            <div className="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center">
                                                <svg className="w-4 h-4 text-white fill-current" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                )}
                                <div className="flex-1">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">
                                        {attributes.video_title || __('Untitled Video', 'tmu-theme')}
                                    </h3>
                                    <div className="flex flex-wrap gap-2 mb-2">
                                        <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                            {attributes.video_type || 'Trailer'}
                                        </span>
                                        <span className="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm">
                                            {attributes.video_site || 'YouTube'}
                                        </span>
                                        {attributes.video_quality && (
                                            <span className="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">
                                                {attributes.video_quality}
                                            </span>
                                        )}
                                        {attributes.official && (
                                            <span className="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-sm">
                                                {__('Official', 'tmu-theme')}
                                            </span>
                                        )}
                                    </div>
                                    {getVideoUrl(attributes.video_site, attributes.video_key) && (
                                        <a
                                            href={getVideoUrl(attributes.video_site, attributes.video_key)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-blue-600 hover:text-blue-800 text-sm"
                                        >
                                            {__('Watch Video', 'tmu-theme')} ↗
                                        </a>
                                    )}
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                {attributes.video_duration && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Duration', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {Math.floor(attributes.video_duration / 60)}:{(attributes.video_duration % 60).toString().padStart(2, '0')}
                                        </div>
                                    </div>
                                )}
                                {attributes.published_date && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Published', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {new Date(attributes.published_date).toLocaleDateString()}
                                        </div>
                                    </div>
                                )}
                                {attributes.video_language && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Language', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.video_language.toUpperCase()}</div>
                                    </div>
                                )}
                                {attributes.video_key && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Video ID', 'tmu-theme')}</div>
                                        <div className="font-semibold text-xs font-mono">
                                            {attributes.video_key.length > 8 
                                                ? attributes.video_key.substring(0, 8) + '...'
                                                : attributes.video_key
                                            }
                                        </div>
                                    </div>
                                )}
                            </div>
                            
                            {attributes.description && (
                                <div className="mb-4">
                                    <h4 className="font-semibold text-gray-900 mb-2">{__('Description', 'tmu-theme')}</h4>
                                    <p className="text-gray-700 leading-relaxed">{attributes.description}</p>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="video-alt"
                            label={__('Video Metadata', 'tmu-theme')}
                            instructions={__('Configure video metadata in the block settings panel.', 'tmu-theme')}
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