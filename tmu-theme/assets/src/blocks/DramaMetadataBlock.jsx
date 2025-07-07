/**
 * Drama Metadata Block Component
 * 
 * React component for drama metadata block editor interface
 * with comprehensive drama data fields.
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
 * Register Drama Metadata Block
 */
registerBlockType('tmu/drama-metadata', {
    title: __('Drama Metadata', 'tmu-theme'),
    description: __('Comprehensive drama metadata management', 'tmu-theme'),
    icon: 'heart',
    category: 'tmu-blocks',
    keywords: [
        __('drama', 'tmu-theme'),
        __('metadata', 'tmu-theme'),
        __('korean', 'tmu-theme'),
        __('asian', 'tmu-theme'),
    ],
    supports: {
        html: false,
        multiple: false,
    },
    attributes: {
        title: { type: 'string' },
        original_title: { type: 'string' },
        tagline: { type: 'string' },
        overview: { type: 'string' },
        first_air_date: { type: 'string' },
        last_air_date: { type: 'string' },
        status: { 
            type: 'string',
            default: 'Ended'
        },
        episode_count: { type: 'number' },
        runtime: { type: 'number' },
        original_language: { type: 'string' },
        country_of_origin: { type: 'string' },
        poster_path: { type: 'string' },
        backdrop_path: { type: 'string' },
        rating: { type: 'number' },
        vote_count: { type: 'number' },
        popularity: { type: 'number' },
        homepage: { type: 'string' },
        channel: { type: 'string' },
        director: { type: 'string' },
        writer: { type: 'string' },
        production_company: { type: 'string' },
        adult: { 
            type: 'boolean',
            default: false
        },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [notice, setNotice] = useState(null);
        
        const statusOptions = [
            { label: __('Airing', 'tmu-theme'), value: 'Airing' },
            { label: __('Ended', 'tmu-theme'), value: 'Ended' },
            { label: __('Planned', 'tmu-theme'), value: 'Planned' },
            { label: __('In Production', 'tmu-theme'), value: 'In Production' },
            { label: __('Canceled', 'tmu-theme'), value: 'Canceled' },
        ];
        
        const languageOptions = [
            { label: __('Korean', 'tmu-theme'), value: 'ko' },
            { label: __('Japanese', 'tmu-theme'), value: 'ja' },
            { label: __('Chinese', 'tmu-theme'), value: 'zh' },
            { label: __('Thai', 'tmu-theme'), value: 'th' },
            { label: __('English', 'tmu-theme'), value: 'en' },
            { label: __('Other', 'tmu-theme'), value: 'other' },
        ];
        
        const countryOptions = [
            { label: __('South Korea', 'tmu-theme'), value: 'KR' },
            { label: __('Japan', 'tmu-theme'), value: 'JP' },
            { label: __('China', 'tmu-theme'), value: 'CN' },
            { label: __('Taiwan', 'tmu-theme'), value: 'TW' },
            { label: __('Thailand', 'tmu-theme'), value: 'TH' },
            { label: __('Other', 'tmu-theme'), value: 'other' },
        ];
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Basic Information', 'tmu-theme')} initialOpen={true}>
                        <TextControl
                            label={__('Title', 'tmu-theme')}
                            value={attributes.title || ''}
                            onChange={(value) => setAttributes({ title: value })}
                        />
                        <TextControl
                            label={__('Original Title', 'tmu-theme')}
                            value={attributes.original_title || ''}
                            onChange={(value) => setAttributes({ original_title: value })}
                        />
                        <TextControl
                            label={__('Tagline', 'tmu-theme')}
                            value={attributes.tagline || ''}
                            onChange={(value) => setAttributes({ tagline: value })}
                        />
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview || ''}
                            onChange={(value) => setAttributes({ overview: value })}
                            rows={4}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Broadcast Information', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('First Air Date', 'tmu-theme')}
                            type="date"
                            value={attributes.first_air_date || ''}
                            onChange={(value) => setAttributes({ first_air_date: value })}
                        />
                        <TextControl
                            label={__('Last Air Date', 'tmu-theme')}
                            type="date"
                            value={attributes.last_air_date || ''}
                            onChange={(value) => setAttributes({ last_air_date: value })}
                        />
                        <SelectControl
                            label={__('Status', 'tmu-theme')}
                            value={attributes.status || 'Ended'}
                            options={statusOptions}
                            onChange={(value) => setAttributes({ status: value })}
                        />
                        <TextControl
                            label={__('Channel/Network', 'tmu-theme')}
                            value={attributes.channel || ''}
                            onChange={(value) => setAttributes({ channel: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Episode Information', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Total Episodes', 'tmu-theme')}
                            value={attributes.episode_count || ''}
                            onChange={(value) => setAttributes({ episode_count: parseInt(value) || 0 })}
                            min={0}
                        />
                        <NumberControl
                            label={__('Episode Runtime (minutes)', 'tmu-theme')}
                            value={attributes.runtime || ''}
                            onChange={(value) => setAttributes({ runtime: parseInt(value) || 0 })}
                            min={0}
                            help={__('Average runtime per episode', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Origin & Language', 'tmu-theme')} initialOpen={false}>
                        <SelectControl
                            label={__('Original Language', 'tmu-theme')}
                            value={attributes.original_language || 'ko'}
                            options={languageOptions}
                            onChange={(value) => setAttributes({ original_language: value })}
                        />
                        <SelectControl
                            label={__('Country of Origin', 'tmu-theme')}
                            value={attributes.country_of_origin || 'KR'}
                            options={countryOptions}
                            onChange={(value) => setAttributes({ country_of_origin: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Production Details', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Director', 'tmu-theme')}
                            value={attributes.director || ''}
                            onChange={(value) => setAttributes({ director: value })}
                        />
                        <TextControl
                            label={__('Writer/Screenwriter', 'tmu-theme')}
                            value={attributes.writer || ''}
                            onChange={(value) => setAttributes({ writer: value })}
                        />
                        <TextControl
                            label={__('Production Company', 'tmu-theme')}
                            value={attributes.production_company || ''}
                            onChange={(value) => setAttributes({ production_company: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Ratings & Popularity', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Rating', 'tmu-theme')}
                            value={attributes.rating || ''}
                            onChange={(value) => setAttributes({ rating: parseFloat(value) || 0 })}
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
                        <NumberControl
                            label={__('Popularity Score', 'tmu-theme')}
                            value={attributes.popularity || ''}
                            onChange={(value) => setAttributes({ popularity: parseFloat(value) || 0 })}
                            step={0.1}
                            min={0}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Media & Links', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Homepage', 'tmu-theme')}
                            type="url"
                            value={attributes.homepage || ''}
                            onChange={(value) => setAttributes({ homepage: value })}
                        />
                        <TextControl
                            label={__('Poster Path', 'tmu-theme')}
                            value={attributes.poster_path || ''}
                            onChange={(value) => setAttributes({ poster_path: value })}
                        />
                        <TextControl
                            label={__('Backdrop Path', 'tmu-theme')}
                            value={attributes.backdrop_path || ''}
                            onChange={(value) => setAttributes({ backdrop_path: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Content Flags', 'tmu-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Adult Content', 'tmu-theme')}
                            checked={attributes.adult || false}
                            onChange={(value) => setAttributes({ adult: value })}
                            help={__('Mark if this drama contains adult content', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-drama-metadata-block bg-white border border-gray-200 rounded-lg p-6">
                    {attributes.title ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex items-start gap-4 mb-4">
                                {attributes.poster_path && (
                                    <img 
                                        src={attributes.poster_path}
                                        alt={attributes.title}
                                        className="w-24 h-36 object-cover rounded-md shadow-md"
                                    />
                                )}
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">
                                        {attributes.title}
                                        {attributes.first_air_date && (
                                            <span className="text-gray-500 font-normal ml-2">
                                                ({new Date(attributes.first_air_date).getFullYear()})
                                            </span>
                                        )}
                                    </h3>
                                    {attributes.original_title && attributes.original_title !== attributes.title && (
                                        <p className="text-gray-600 italic mb-2">
                                            {__('Original Title:', 'tmu-theme')} {attributes.original_title}
                                        </p>
                                    )}
                                    {attributes.tagline && (
                                        <p className="text-gray-700 italic mb-3">"{attributes.tagline}"</p>
                                    )}
                                    {attributes.channel && (
                                        <p className="text-sm text-gray-600 mb-2">
                                            <span className="font-medium">{__('Channel:', 'tmu-theme')}</span> {attributes.channel}
                                        </p>
                                    )}
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                {attributes.first_air_date && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('First Aired', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {new Date(attributes.first_air_date).toLocaleDateString()}
                                        </div>
                                    </div>
                                )}
                                {attributes.episode_count && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Episodes', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.episode_count}</div>
                                    </div>
                                )}
                                {attributes.runtime && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Runtime', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.runtime} {__('min', 'tmu-theme')}</div>
                                    </div>
                                )}
                                {attributes.status && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Status', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.status}</div>
                                    </div>
                                )}
                            </div>
                            
                            {attributes.rating && (
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                                    <div className="text-center p-3 bg-pink-50 rounded">
                                        <div className="text-sm text-pink-600">{__('Rating', 'tmu-theme')}</div>
                                        <div className="font-semibold text-pink-900">
                                            {attributes.rating}/10
                                        </div>
                                    </div>
                                    {attributes.vote_count && (
                                        <div className="text-center p-3 bg-pink-50 rounded">
                                            <div className="text-sm text-pink-600">{__('Votes', 'tmu-theme')}</div>
                                            <div className="font-semibold text-pink-900">
                                                {attributes.vote_count.toLocaleString()}
                                            </div>
                                        </div>
                                    )}
                                    {attributes.popularity && (
                                        <div className="text-center p-3 bg-pink-50 rounded">
                                            <div className="text-sm text-pink-600">{__('Popularity', 'tmu-theme')}</div>
                                            <div className="font-semibold text-pink-900">
                                                {Math.round(attributes.popularity)}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                            
                            {(attributes.director || attributes.writer || attributes.production_company) && (
                                <div className="mb-4 p-4 bg-purple-50 rounded-lg">
                                    <h4 className="font-semibold text-purple-900 mb-2">{__('Production', 'tmu-theme')}</h4>
                                    {attributes.director && (
                                        <p className="text-sm text-purple-800 mb-1">
                                            <span className="font-medium">{__('Director:', 'tmu-theme')}</span> {attributes.director}
                                        </p>
                                    )}
                                    {attributes.writer && (
                                        <p className="text-sm text-purple-800 mb-1">
                                            <span className="font-medium">{__('Writer:', 'tmu-theme')}</span> {attributes.writer}
                                        </p>
                                    )}
                                    {attributes.production_company && (
                                        <p className="text-sm text-purple-800">
                                            <span className="font-medium">{__('Production:', 'tmu-theme')}</span> {attributes.production_company}
                                        </p>
                                    )}
                                </div>
                            )}
                            
                            {attributes.overview && (
                                <div className="mb-4">
                                    <h4 className="font-semibold text-gray-900 mb-2">{__('Overview', 'tmu-theme')}</h4>
                                    <p className="text-gray-700 leading-relaxed">{attributes.overview}</p>
                                </div>
                            )}
                            
                            <div className="flex flex-wrap gap-2">
                                {attributes.original_language && (
                                    <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                        {attributes.original_language.toUpperCase()}
                                    </span>
                                )}
                                {attributes.country_of_origin && (
                                    <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                        {attributes.country_of_origin}
                                    </span>
                                )}
                                {attributes.adult && (
                                    <span className="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                        {__('Adult Content', 'tmu-theme')}
                                    </span>
                                )}
                            </div>
                        </div>
                    ) : (
                        <Placeholder
                            icon="heart"
                            label={__('Drama Metadata', 'tmu-theme')}
                            instructions={__('Configure drama metadata in the block settings panel.', 'tmu-theme')}
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