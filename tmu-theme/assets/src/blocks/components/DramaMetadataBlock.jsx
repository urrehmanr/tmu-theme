/**
 * Drama Metadata Block Component
 * 
 * Comprehensive React component for drama metadata management with
 * drama-specific fields and channel integration.
 */

import { __ } from '@wordpress/i18n';
import { 
    InspectorControls, 
    useBlockProps 
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    NumberControl,
    SelectControl,
    ToggleControl,
    Button,
    Placeholder,
    BaseControl,
    __experimentalDivider as Divider
} from '@wordpress/components';
import { useState } from '@wordpress/element';

const DramaMetadataBlock = {
    title: __('Drama Metadata', 'tmu-theme'),
    icon: 'video-alt',
    category: 'tmu-blocks',
    description: __('Comprehensive drama metadata management', 'tmu-theme'),
    keywords: [
        __('drama', 'tmu-theme'),
        __('series', 'tmu-theme'),
        __('metadata', 'tmu-theme')
    ],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
        lock: false
    },
    attributes: {
        // Basic Information
        title: { type: 'string', default: '' },
        original_title: { type: 'string', default: '' },
        tagline: { type: 'string', default: '' },
        overview: { type: 'string', default: '' },
        
        // Drama Specific
        episodes: { type: 'number', default: null },
        air_date: { type: 'string', default: '' },
        end_date: { type: 'string', default: '' },
        status: { type: 'string', default: 'Completed' },
        drama_type: { type: 'string', default: 'Drama' },
        
        // Production Details
        production_company: { type: 'string', default: '' },
        channel: { type: 'string', default: '' },
        runtime: { type: 'number', default: null },
        broadcast_day: { type: 'string', default: '' },
        
        // Cast & Crew
        main_cast: { type: 'string', default: '' },
        director: { type: 'string', default: '' },
        writer: { type: 'string', default: '' },
        
        // Media & Links
        poster_url: { type: 'string', default: '' },
        trailer_url: { type: 'string', default: '' },
        official_site: { type: 'string', default: '' },
        
        // Ratings
        rating: { type: 'number', default: null },
        rating_count: { type: 'number', default: 0 },
        
        // Content Flags
        featured: { type: 'boolean', default: false },
        trending: { type: 'boolean', default: false },
        completed: { type: 'boolean', default: false }
    },

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            className: 'tmu-drama-metadata-block'
        });

        const formatRuntime = (minutes) => {
            if (!minutes) return '';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
        };

        const statusOptions = [
            { label: __('Completed', 'tmu-theme'), value: 'Completed' },
            { label: __('Ongoing', 'tmu-theme'), value: 'Ongoing' },
            { label: __('Upcoming', 'tmu-theme'), value: 'Upcoming' },
            { label: __('Cancelled', 'tmu-theme'), value: 'Cancelled' }
        ];

        const dramaTypeOptions = [
            { label: __('Drama', 'tmu-theme'), value: 'Drama' },
            { label: __('Romance', 'tmu-theme'), value: 'Romance' },
            { label: __('Comedy', 'tmu-theme'), value: 'Comedy' },
            { label: __('Thriller', 'tmu-theme'), value: 'Thriller' },
            { label: __('Historical', 'tmu-theme'), value: 'Historical' },
            { label: __('Fantasy', 'tmu-theme'), value: 'Fantasy' },
            { label: __('Medical', 'tmu-theme'), value: 'Medical' },
            { label: __('Legal', 'tmu-theme'), value: 'Legal' }
        ];

        const broadcastDayOptions = [
            { label: __('Monday', 'tmu-theme'), value: 'Monday' },
            { label: __('Tuesday', 'tmu-theme'), value: 'Tuesday' },
            { label: __('Wednesday', 'tmu-theme'), value: 'Wednesday' },
            { label: __('Thursday', 'tmu-theme'), value: 'Thursday' },
            { label: __('Friday', 'tmu-theme'), value: 'Friday' },
            { label: __('Saturday', 'tmu-theme'), value: 'Saturday' },
            { label: __('Sunday', 'tmu-theme'), value: 'Sunday' },
            { label: __('Daily', 'tmu-theme'), value: 'Daily' },
            { label: __('Weekend', 'tmu-theme'), value: 'Weekend' }
        ];

        return (
            <>
                <InspectorControls>
                    {/* Basic Information Panel */}
                    <PanelBody
                        title={__('Basic Information', 'tmu-theme')}
                        initialOpen={true}
                    >
                        <TextControl
                            label={__('Drama Title', 'tmu-theme')}
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                            help={__('Drama title as displayed on the site.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Original Title', 'tmu-theme')}
                            value={attributes.original_title}
                            onChange={(value) => setAttributes({ original_title: value })}
                            help={__('Original drama title (if different from display title).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Tagline', 'tmu-theme')}
                            value={attributes.tagline}
                            onChange={(value) => setAttributes({ tagline: value })}
                            help={__('Drama tagline or slogan.', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            rows={4}
                            help={__('Drama plot summary or description.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Drama Details Panel */}
                    <PanelBody
                        title={__('Drama Details', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('Episodes', 'tmu-theme')}
                            value={attributes.episodes || ''}
                            onChange={(value) => setAttributes({ episodes: parseInt(value) || null })}
                            help={__('Total number of episodes.', 'tmu-theme')}
                        />
                        
                        <BaseControl
                            label={__('Air Date', 'tmu-theme')}
                            help={__('Drama premiere date.', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.air_date}
                                onChange={(e) => setAttributes({ air_date: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <BaseControl
                            label={__('End Date', 'tmu-theme')}
                            help={__('Drama final episode date.', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.end_date}
                                onChange={(e) => setAttributes({ end_date: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <SelectControl
                            label={__('Status', 'tmu-theme')}
                            value={attributes.status}
                            options={statusOptions}
                            onChange={(value) => setAttributes({ status: value })}
                            help={__('Drama production/air status.', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Drama Type', 'tmu-theme')}
                            value={attributes.drama_type}
                            options={dramaTypeOptions}
                            onChange={(value) => setAttributes({ drama_type: value })}
                            help={__('Drama genre/type.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Production Details Panel */}
                    <PanelBody
                        title={__('Production Details', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextControl
                            label={__('Production Company', 'tmu-theme')}
                            value={attributes.production_company}
                            onChange={(value) => setAttributes({ production_company: value })}
                            help={__('Production company name.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Channel', 'tmu-theme')}
                            value={attributes.channel}
                            onChange={(value) => setAttributes({ channel: value })}
                            help={__('Broadcasting channel or network.', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Runtime (minutes)', 'tmu-theme')}
                            value={attributes.runtime || ''}
                            onChange={(value) => setAttributes({ runtime: parseInt(value) || null })}
                            help={__('Episode duration in minutes.', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Broadcast Day', 'tmu-theme')}
                            value={attributes.broadcast_day}
                            options={[{ label: __('Select Day', 'tmu-theme'), value: '' }, ...broadcastDayOptions]}
                            onChange={(value) => setAttributes({ broadcast_day: value })}
                            help={__('Day(s) of the week the drama airs.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Cast & Crew Panel */}
                    <PanelBody
                        title={__('Cast & Crew', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextareaControl
                            label={__('Main Cast', 'tmu-theme')}
                            value={attributes.main_cast}
                            onChange={(value) => setAttributes({ main_cast: value })}
                            rows={3}
                            help={__('Main cast members (one per line or comma-separated).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Director', 'tmu-theme')}
                            value={attributes.director}
                            onChange={(value) => setAttributes({ director: value })}
                            help={__('Drama director(s).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Writer', 'tmu-theme')}
                            value={attributes.writer}
                            onChange={(value) => setAttributes({ writer: value })}
                            help={__('Drama writer(s)/screenwriter(s).', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Media & Links Panel */}
                    <PanelBody
                        title={__('Media & Links', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextControl
                            label={__('Poster URL', 'tmu-theme')}
                            type="url"
                            value={attributes.poster_url}
                            onChange={(value) => setAttributes({ poster_url: value })}
                            help={__('Drama poster image URL.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Trailer URL', 'tmu-theme')}
                            type="url"
                            value={attributes.trailer_url}
                            onChange={(value) => setAttributes({ trailer_url: value })}
                            help={__('Drama trailer video URL.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Official Site', 'tmu-theme')}
                            type="url"
                            value={attributes.official_site}
                            onChange={(value) => setAttributes({ official_site: value })}
                            help={__('Official drama website URL.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Ratings Panel */}
                    <PanelBody
                        title={__('Ratings', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('Rating', 'tmu-theme')}
                            value={attributes.rating || ''}
                            onChange={(value) => setAttributes({ rating: parseFloat(value) || null })}
                            step={0.1}
                            min={0}
                            max={10}
                            help={__('Average rating (0-10).', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Rating Count', 'tmu-theme')}
                            value={attributes.rating_count || ''}
                            onChange={(value) => setAttributes({ rating_count: parseInt(value) || 0 })}
                            help={__('Number of ratings.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Content Flags Panel */}
                    <PanelBody
                        title={__('Content Flags', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <ToggleControl
                            label={__('Featured', 'tmu-theme')}
                            checked={attributes.featured}
                            onChange={(value) => setAttributes({ featured: value })}
                            help={__('Mark as featured content.', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('Trending', 'tmu-theme')}
                            checked={attributes.trending}
                            onChange={(value) => setAttributes({ trending: value })}
                            help={__('Mark as trending content.', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('Completed', 'tmu-theme')}
                            checked={attributes.completed}
                            onChange={(value) => setAttributes({ completed: value })}
                            help={__('Mark if drama is fully completed/aired.', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    {attributes.title ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex gap-4">
                                {attributes.poster_url && (
                                    <div className="flex-shrink-0">
                                        <img 
                                            src={attributes.poster_url}
                                            alt={attributes.title}
                                            className="w-24 h-auto rounded shadow-lg"
                                        />
                                    </div>
                                )}
                                
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold mb-2 text-gray-900">
                                        {attributes.title}
                                    </h3>
                                    
                                    {attributes.original_title && attributes.original_title !== attributes.title && (
                                        <p className="text-sm text-gray-600 italic mb-2">
                                            {attributes.original_title}
                                        </p>
                                    )}
                                    
                                    {attributes.tagline && (
                                        <p className="text-sm text-blue-600 mb-3 italic">
                                            "{attributes.tagline}"
                                        </p>
                                    )}
                                    
                                    <div className="grid grid-cols-2 gap-2 text-sm">
                                        {attributes.air_date && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Air Date:</span>
                                                <span className="ml-1">{new Date(attributes.air_date).getFullYear()}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.episodes && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Episodes:</span>
                                                <span className="ml-1">{attributes.episodes}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.status && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Status:</span>
                                                <span className="ml-1">{attributes.status}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.channel && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Channel:</span>
                                                <span className="ml-1">{attributes.channel}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.rating && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Rating:</span>
                                                <span className="ml-1">{attributes.rating}/10</span>
                                            </div>
                                        )}
                                        
                                        {attributes.runtime && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Runtime:</span>
                                                <span className="ml-1">{formatRuntime(attributes.runtime)}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                            
                            {attributes.overview && (
                                <div className="mt-4 p-3 bg-gray-50 rounded">
                                    <p className="text-sm text-gray-700 leading-relaxed">
                                        {attributes.overview.length > 200 
                                            ? attributes.overview.substring(0, 200) + '...' 
                                            : attributes.overview
                                        }
                                    </p>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="video-alt"
                            label={__('Drama Metadata', 'tmu-theme')}
                            instructions={__('Configure drama metadata in the block settings panel.', 'tmu-theme')}
                            className="tmu-placeholder"
                        />
                    )}
                </div>
            </>
        );
    },

    save: () => {
        return null;
    }
};

export default DramaMetadataBlock;