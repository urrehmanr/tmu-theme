/**
 * TV Episode Metadata Block Component
 * 
 * Comprehensive React component for individual TV episode metadata management
 * with parent TV series integration and episode-specific fields.
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
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const TvEpisodeMetadataBlock = {
    title: __('TV Episode Metadata', 'tmu-theme'),
    icon: 'playlist-video',
    category: 'tmu-blocks',
    description: __('Individual TV episode metadata management', 'tmu-theme'),
    keywords: [
        __('episode', 'tmu-theme'),
        __('tv', 'tmu-theme'),
        __('series', 'tmu-theme'),
        __('season', 'tmu-theme'),
        __('metadata', 'tmu-theme')
    ],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
        lock: false
    },
    attributes: {
        // Parent Relationship
        tv_series: { type: 'number', default: null },
        season_number: { type: 'number', default: null },
        episode_number: { type: 'number', default: null },
        
        // Basic Information
        name: { type: 'string', default: '' },
        overview: { type: 'string', default: '' },
        air_date: { type: 'string', default: '' },
        episode_type: { type: 'string', default: 'standard' },
        
        // Episode Details
        runtime: { type: 'number', default: null },
        still_path: { type: 'string', default: '' },
        vote_average: { type: 'number', default: null },
        vote_count: { type: 'number', default: null },
        
        // Cast & Crew
        crew: { type: 'array', default: [] },
        guest_stars: { type: 'array', default: [] },
        
        // TMDB Integration
        tmdb_id: { type: 'number', default: null },
        production_code: { type: 'string', default: '' },
        
        // Local Data
        local_rating: { type: 'number', default: null },
        local_rating_count: { type: 'number', default: 0 },
        watch_count: { type: 'number', default: 0 },
        
        // Episode Status
        watched: { type: 'boolean', default: false },
        featured: { type: 'boolean', default: false }
    },

    edit: ({ attributes, setAttributes, clientId }) => {
        const [tvSeriesList, setTvSeriesList] = useState([]);
        const [isLoading, setIsLoading] = useState(false);

        const blockProps = useBlockProps({
            className: 'tmu-tv-episode-metadata-block'
        });

        // Load TV Series list for parent selection
        useEffect(() => {
            loadTvSeries();
        }, []);

        const loadTvSeries = async () => {
            try {
                const response = await apiFetch({
                    path: '/wp/v2/tv?_fields=id,title&per_page=100'
                });
                setTvSeriesList(response || []);
            } catch (error) {
                console.error('Error loading TV series:', error);
            }
        };

        const formatRuntime = (minutes) => {
            if (!minutes) return '';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
        };

        const getTmdbImageUrl = (path, size = 'w300') => {
            return path ? `https://image.tmdb.org/t/p/${size}${path}` : '';
        };

        const episodeTypeOptions = [
            { label: __('Standard', 'tmu-theme'), value: 'standard' },
            { label: __('Finale', 'tmu-theme'), value: 'finale' },
            { label: __('Premiere', 'tmu-theme'), value: 'premiere' },
            { label: __('Special', 'tmu-theme'), value: 'special' },
            { label: __('Mid-Season Finale', 'tmu-theme'), value: 'mid_season_finale' },
            { label: __('Mid-Season Premiere', 'tmu-theme'), value: 'mid_season_premiere' }
        ];

        const tvSeriesOptions = tvSeriesList.map(series => ({
            label: series.title.rendered,
            value: series.id
        }));

        return (
            <>
                <InspectorControls>
                    {/* Parent Series Panel */}
                    <PanelBody
                        title={__('Parent Series', 'tmu-theme')}
                        initialOpen={true}
                    >
                        <SelectControl
                            label={__('TV Series', 'tmu-theme')}
                            value={attributes.tv_series || ''}
                            options={[{ label: __('Select TV Series', 'tmu-theme'), value: '' }, ...tvSeriesOptions]}
                            onChange={(value) => setAttributes({ tv_series: parseInt(value) || null })}
                            help={__('Select the parent TV series for this episode.', 'tmu-theme')}
                        />
                        
                        <div className="flex gap-2">
                            <NumberControl
                                label={__('Season', 'tmu-theme')}
                                value={attributes.season_number || ''}
                                onChange={(value) => setAttributes({ season_number: parseInt(value) || null })}
                                help={__('Season number.', 'tmu-theme')}
                            />
                            
                            <NumberControl
                                label={__('Episode', 'tmu-theme')}
                                value={attributes.episode_number || ''}
                                onChange={(value) => setAttributes({ episode_number: parseInt(value) || null })}
                                help={__('Episode number.', 'tmu-theme')}
                            />
                        </div>
                    </PanelBody>

                    {/* Episode Information Panel */}
                    <PanelBody
                        title={__('Episode Information', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextControl
                            label={__('Episode Name', 'tmu-theme')}
                            value={attributes.name}
                            onChange={(value) => setAttributes({ name: value })}
                            help={__('Episode title.', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            rows={4}
                            help={__('Episode plot summary.', 'tmu-theme')}
                        />
                        
                        <BaseControl
                            label={__('Air Date', 'tmu-theme')}
                            help={__('Episode air date.', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.air_date}
                                onChange={(e) => setAttributes({ air_date: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <SelectControl
                            label={__('Episode Type', 'tmu-theme')}
                            value={attributes.episode_type}
                            options={episodeTypeOptions}
                            onChange={(value) => setAttributes({ episode_type: value })}
                            help={__('Type of episode (standard, finale, etc.).', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Runtime (minutes)', 'tmu-theme')}
                            value={attributes.runtime || ''}
                            onChange={(value) => setAttributes({ runtime: parseInt(value) || null })}
                            help={__('Episode duration in minutes.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* TMDB Integration Panel */}
                    <PanelBody
                        title={__('TMDB Integration', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id || ''}
                            onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || null })}
                            help={__('TMDB episode ID for data synchronization.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Production Code', 'tmu-theme')}
                            value={attributes.production_code}
                            onChange={(value) => setAttributes({ production_code: value })}
                            help={__('Episode production code.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Still Path', 'tmu-theme')}
                            value={attributes.still_path}
                            onChange={(value) => setAttributes({ still_path: value })}
                            help={__('TMDB episode still image path.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Cast & Crew Panel */}
                    <PanelBody
                        title={__('Cast & Crew', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextareaControl
                            label={__('Crew', 'tmu-theme')}
                            value={Array.isArray(attributes.crew) ? attributes.crew.map(c => `${c.name} (${c.job})`).join('\n') : ''}
                            onChange={(value) => {
                                const crew = value.split('\n').map(line => {
                                    const match = line.match(/^(.+?)\s*\((.+?)\)$/);
                                    return match ? { name: match[1].trim(), job: match[2].trim() } : { name: line.trim(), job: '' };
                                }).filter(c => c.name);
                                setAttributes({ crew });
                            }}
                            rows={4}
                            help={__('Episode crew members. Format: Name (Job), one per line.', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Guest Stars', 'tmu-theme')}
                            value={Array.isArray(attributes.guest_stars) ? attributes.guest_stars.map(g => `${g.name} (${g.character || 'Guest'})`).join('\n') : ''}
                            onChange={(value) => {
                                const guests = value.split('\n').map(line => {
                                    const match = line.match(/^(.+?)\s*\((.+?)\)$/);
                                    return match ? { name: match[1].trim(), character: match[2].trim() } : { name: line.trim(), character: 'Guest' };
                                }).filter(g => g.name);
                                setAttributes({ guest_stars: guests });
                            }}
                            rows={4}
                            help={__('Episode guest stars. Format: Name (Character), one per line.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Ratings Panel */}
                    <PanelBody
                        title={__('Ratings & Stats', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('TMDB Vote Average', 'tmu-theme')}
                            value={attributes.vote_average || ''}
                            onChange={(value) => setAttributes({ vote_average: parseFloat(value) || null })}
                            step={0.1}
                            min={0}
                            max={10}
                            help={__('TMDB rating (0-10).', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('TMDB Vote Count', 'tmu-theme')}
                            value={attributes.vote_count || ''}
                            onChange={(value) => setAttributes({ vote_count: parseInt(value) || null })}
                            help={__('Number of TMDB votes.', 'tmu-theme')}
                        />

                        <Divider />

                        <NumberControl
                            label={__('Local Rating', 'tmu-theme')}
                            value={attributes.local_rating || ''}
                            onChange={(value) => setAttributes({ local_rating: parseFloat(value) || null })}
                            step={0.1}
                            min={0}
                            max={10}
                            help={__('Local site rating (0-10).', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Local Rating Count', 'tmu-theme')}
                            value={attributes.local_rating_count || ''}
                            onChange={(value) => setAttributes({ local_rating_count: parseInt(value) || 0 })}
                            help={__('Number of local ratings.', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Watch Count', 'tmu-theme')}
                            value={attributes.watch_count || ''}
                            onChange={(value) => setAttributes({ watch_count: parseInt(value) || 0 })}
                            help={__('Number of times episode was watched.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Episode Status Panel */}
                    <PanelBody
                        title={__('Episode Status', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <ToggleControl
                            label={__('Watched', 'tmu-theme')}
                            checked={attributes.watched}
                            onChange={(value) => setAttributes({ watched: value })}
                            help={__('Mark if episode has been watched.', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('Featured', 'tmu-theme')}
                            checked={attributes.featured}
                            onChange={(value) => setAttributes({ featured: value })}
                            help={__('Mark as featured episode.', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    {attributes.name ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex gap-4">
                                {attributes.still_path && (
                                    <div className="flex-shrink-0">
                                        <img 
                                            src={getTmdbImageUrl(attributes.still_path, 'w300')}
                                            alt={attributes.name}
                                            className="w-32 h-auto rounded shadow-lg"
                                        />
                                    </div>
                                )}
                                
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-2">
                                        {attributes.season_number && attributes.episode_number && (
                                            <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                S{attributes.season_number.toString().padStart(2, '0')}E{attributes.episode_number.toString().padStart(2, '0')}
                                            </span>
                                        )}
                                        {attributes.episode_type !== 'standard' && (
                                            <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded capitalize">
                                                {attributes.episode_type.replace('_', ' ')}
                                            </span>
                                        )}
                                    </div>
                                    
                                    <h3 className="text-xl font-bold mb-2 text-gray-900">
                                        {attributes.name}
                                    </h3>
                                    
                                    <div className="grid grid-cols-2 gap-2 text-sm">
                                        {attributes.air_date && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Air Date:</span>
                                                <span className="ml-1">{new Date(attributes.air_date).toLocaleDateString()}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.runtime && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Runtime:</span>
                                                <span className="ml-1">{formatRuntime(attributes.runtime)}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.vote_average && (
                                            <div>
                                                <span className="font-semibold text-gray-700">TMDB Rating:</span>
                                                <span className="ml-1">{attributes.vote_average}/10</span>
                                            </div>
                                        )}
                                        
                                        {attributes.watch_count > 0 && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Views:</span>
                                                <span className="ml-1">{attributes.watch_count}</span>
                                            </div>
                                        )}
                                    </div>
                                    
                                    {attributes.guest_stars && attributes.guest_stars.length > 0 && (
                                        <div className="mt-2">
                                            <span className="text-xs font-semibold text-gray-700">Guest Stars: </span>
                                            <span className="text-xs text-gray-600">
                                                {attributes.guest_stars.slice(0, 3).map(g => g.name).join(', ')}
                                                {attributes.guest_stars.length > 3 && ` +${attributes.guest_stars.length - 3} more`}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            {attributes.overview && (
                                <div className="mt-4 p-3 bg-gray-50 rounded">
                                    <p className="text-sm text-gray-700 leading-relaxed">
                                        {attributes.overview.length > 250 
                                            ? attributes.overview.substring(0, 250) + '...' 
                                            : attributes.overview
                                        }
                                    </p>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="playlist-video"
                            label={__('TV Episode Metadata', 'tmu-theme')}
                            instructions={__('Configure TV episode metadata in the block settings panel. Start by selecting a parent TV series.', 'tmu-theme')}
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

export default TvEpisodeMetadataBlock;