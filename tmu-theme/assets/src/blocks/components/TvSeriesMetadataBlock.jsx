/**
 * TV Series Metadata Block Component
 * 
 * Comprehensive React component for TV series metadata management with TMDB integration,
 * season/episode tracking, and network information.
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
    Spinner,
    Notice,
    BaseControl,
    __experimentalDivider as Divider
} from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * TV Series Metadata Block Component
 */
const TvSeriesMetadataBlock = {
    title: __('TV Series Metadata', 'tmu-theme'),
    icon: 'video-alt2',
    category: 'tmu-blocks',
    description: __('Comprehensive TV series metadata management with TMDB integration', 'tmu-theme'),
    keywords: [
        __('tv', 'tmu-theme'),
        __('series', 'tmu-theme'),
        __('show', 'tmu-theme'),
        __('metadata', 'tmu-theme'),
        __('tmdb', 'tmu-theme')
    ],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
        lock: false
    },
    attributes: {
        // TMDB Integration
        tmdb_id: { type: 'number', default: null },
        imdb_id: { type: 'string', default: '' },
        
        // Basic Information
        name: { type: 'string', default: '' },
        original_name: { type: 'string', default: '' },
        tagline: { type: 'string', default: '' },
        overview: { type: 'string', default: '' },
        
        // Air Dates & Status
        first_air_date: { type: 'string', default: '' },
        last_air_date: { type: 'string', default: '' },
        status: { type: 'string', default: 'Ended' },
        type: { type: 'string', default: 'Scripted' },
        in_production: { type: 'boolean', default: false },
        
        // Episode Information
        number_of_episodes: { type: 'number', default: null },
        number_of_seasons: { type: 'number', default: null },
        episode_run_time: { type: 'array', default: [] },
        
        // Language & Country
        languages: { type: 'array', default: [] },
        origin_country: { type: 'array', default: [] },
        original_language: { type: 'string', default: '' },
        
        // Media & Links
        homepage: { type: 'string', default: '' },
        poster_path: { type: 'string', default: '' },
        backdrop_path: { type: 'string', default: '' },
        
        // TMDB Ratings & Popularity
        tmdb_vote_average: { type: 'number', default: null },
        tmdb_vote_count: { type: 'number', default: null },
        tmdb_popularity: { type: 'number', default: null },
        
        // Content Flags
        adult: { type: 'boolean', default: false },
        
        // Production Information
        created_by: { type: 'array', default: [] },
        genres: { type: 'array', default: [] },
        networks: { type: 'array', default: [] },
        production_companies: { type: 'array', default: [] },
        production_countries: { type: 'array', default: [] },
        seasons: { type: 'array', default: [] },
        spoken_languages: { type: 'array', default: [] },
        
        // Extended TMDB Data
        credits: { type: 'object', default: null },
        external_ids: { type: 'object', default: null },
        images: { type: 'object', default: null },
        videos: { type: 'object', default: null },
        similar: { type: 'array', default: [] },
        recommendations: { type: 'array', default: [] },
        
        // Local Data
        local_rating: { type: 'number', default: null },
        local_rating_count: { type: 'number', default: 0 },
        watch_count: { type: 'number', default: 0 },
        last_tmdb_sync: { type: 'string', default: '' },
        
        // SEO & Display Options
        featured: { type: 'boolean', default: false },
        trending: { type: 'boolean', default: false }
    },

    edit: ({ attributes, setAttributes, clientId }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [tmdbData, setTmdbData] = useState(null);
        const [error, setError] = useState(null);
        const [lastFetchedId, setLastFetchedId] = useState(null);

        const blockProps = useBlockProps({
            className: 'tmu-tv-series-metadata-block'
        });

        // Debounced TMDB fetch
        const debouncedFetch = useCallback(
            debounce((tmdbId) => {
                if (tmdbId && tmdbId !== lastFetchedId) {
                    fetchTmdbData(tmdbId);
                }
            }, 1000),
            [lastFetchedId]
        );

        useEffect(() => {
            if (attributes.tmdb_id) {
                debouncedFetch(attributes.tmdb_id);
            }
        }, [attributes.tmdb_id, debouncedFetch]);

        /**
         * Fetch TV series data from TMDB API
         */
        const fetchTmdbData = async (tmdbId) => {
            if (!tmdbId || isLoading) return;

            setIsLoading(true);
            setError(null);

            try {
                const response = await apiFetch({
                    path: `/tmu/v1/tmdb/tv/${tmdbId}`,
                    method: 'GET'
                });

                if (response.success && response.data) {
                    setTmdbData(response.data);
                    setLastFetchedId(tmdbId);
                    populateAttributesFromTmdb(response.data);
                    
                    setAttributes({
                        last_tmdb_sync: new Date().toISOString()
                    });
                } else {
                    setError(__('TV series not found in TMDB database.', 'tmu-theme'));
                }
            } catch (err) {
                console.error('TMDB fetch error:', err);
                setError(__('Error fetching TMDB data. Please check your API key and try again.', 'tmu-theme'));
            } finally {
                setIsLoading(false);
            }
        };

        /**
         * Populate block attributes from TMDB data
         */
        const populateAttributesFromTmdb = (data) => {
            const updates = {};

            // Basic Information
            if (data.name && !attributes.name) updates.name = data.name;
            if (data.original_name) updates.original_name = data.original_name;
            if (data.tagline) updates.tagline = data.tagline;
            if (data.overview) updates.overview = data.overview;

            // Air Dates & Status
            if (data.first_air_date) updates.first_air_date = data.first_air_date;
            if (data.last_air_date) updates.last_air_date = data.last_air_date;
            if (data.status) updates.status = data.status;
            if (data.type) updates.type = data.type;
            if (typeof data.in_production === 'boolean') updates.in_production = data.in_production;

            // Episode Information
            if (data.number_of_episodes) updates.number_of_episodes = data.number_of_episodes;
            if (data.number_of_seasons) updates.number_of_seasons = data.number_of_seasons;
            if (data.episode_run_time) updates.episode_run_time = data.episode_run_time;

            // Language & Country
            if (data.languages) updates.languages = data.languages;
            if (data.origin_country) updates.origin_country = data.origin_country;
            if (data.original_language) updates.original_language = data.original_language;

            // Media & Links
            if (data.homepage) updates.homepage = data.homepage;
            if (data.poster_path) updates.poster_path = data.poster_path;
            if (data.backdrop_path) updates.backdrop_path = data.backdrop_path;

            // TMDB Data
            if (data.vote_average) updates.tmdb_vote_average = data.vote_average;
            if (data.vote_count) updates.tmdb_vote_count = data.vote_count;
            if (data.popularity) updates.tmdb_popularity = data.popularity;

            // Content Flags
            if (typeof data.adult === 'boolean') updates.adult = data.adult;

            // External IDs
            if (data.external_ids?.imdb_id) updates.imdb_id = data.external_ids.imdb_id;

            // Complex Data
            if (data.created_by) updates.created_by = data.created_by;
            if (data.genres) updates.genres = data.genres;
            if (data.networks) updates.networks = data.networks;
            if (data.production_companies) updates.production_companies = data.production_companies;
            if (data.production_countries) updates.production_countries = data.production_countries;
            if (data.seasons) updates.seasons = data.seasons;
            if (data.spoken_languages) updates.spoken_languages = data.spoken_languages;
            if (data.credits) updates.credits = data.credits;
            if (data.external_ids) updates.external_ids = data.external_ids;
            if (data.images) updates.images = data.images;
            if (data.videos) updates.videos = data.videos;

            setAttributes(updates);
        };

        /**
         * Manual TMDB sync trigger
         */
        const handleManualSync = () => {
            if (attributes.tmdb_id) {
                setLastFetchedId(null);
                fetchTmdbData(attributes.tmdb_id);
            }
        };

        /**
         * Format runtime array for display
         */
        const formatRuntime = (runtimeArray) => {
            if (!runtimeArray || !Array.isArray(runtimeArray) || runtimeArray.length === 0) return '';
            if (runtimeArray.length === 1) {
                const minutes = runtimeArray[0];
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
            }
            return runtimeArray.map(runtime => `${runtime}m`).join(', ');
        };

        /**
         * Get TMDB image URL
         */
        const getTmdbImageUrl = (path, size = 'w300') => {
            return path ? `https://image.tmdb.org/t/p/${size}${path}` : '';
        };

        const statusOptions = [
            { label: __('Returning Series', 'tmu-theme'), value: 'Returning Series' },
            { label: __('Ended', 'tmu-theme'), value: 'Ended' },
            { label: __('Canceled', 'tmu-theme'), value: 'Canceled' },
            { label: __('In Production', 'tmu-theme'), value: 'In Production' },
            { label: __('Planned', 'tmu-theme'), value: 'Planned' },
            { label: __('Pilot', 'tmu-theme'), value: 'Pilot' }
        ];

        const typeOptions = [
            { label: __('Scripted', 'tmu-theme'), value: 'Scripted' },
            { label: __('Reality', 'tmu-theme'), value: 'Reality' },
            { label: __('Documentary', 'tmu-theme'), value: 'Documentary' },
            { label: __('News', 'tmu-theme'), value: 'News' },
            { label: __('Talk Show', 'tmu-theme'), value: 'Talk Show' },
            { label: __('Miniseries', 'tmu-theme'), value: 'Miniseries' }
        ];

        return (
            <>
                <InspectorControls>
                    {/* TMDB Integration Panel */}
                    <PanelBody
                        title={__('TMDB Integration', 'tmu-theme')}
                        initialOpen={true}
                        className="tmu-panel-tmdb"
                    >
                        <NumberControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id || ''}
                            onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || null })}
                            help={__('Enter the TMDB TV series ID to fetch metadata automatically.', 'tmu-theme')}
                        />
                        
                        <div className="tmu-tmdb-actions">
                            <Button
                                isPrimary
                                isLarge
                                onClick={handleManualSync}
                                disabled={!attributes.tmdb_id || isLoading}
                                className="tmu-sync-button"
                            >
                                {isLoading ? (
                                    <>
                                        <Spinner />
                                        {__('Fetching...', 'tmu-theme')}
                                    </>
                                ) : (
                                    __('Sync TMDB Data', 'tmu-theme')
                                )}
                            </Button>
                        </div>

                        {error && (
                            <Notice status="error" isDismissible={false}>
                                {error}
                            </Notice>
                        )}

                        {attributes.last_tmdb_sync && (
                            <p className="tmu-last-sync">
                                {__('Last synced:', 'tmu-theme')} {new Date(attributes.last_tmdb_sync).toLocaleString()}
                            </p>
                        )}
                    </PanelBody>

                    {/* Basic Information Panel */}
                    <PanelBody
                        title={__('Basic Information', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextControl
                            label={__('Series Name', 'tmu-theme')}
                            value={attributes.name}
                            onChange={(value) => setAttributes({ name: value })}
                            help={__('TV series name as displayed on the site.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Original Name', 'tmu-theme')}
                            value={attributes.original_name}
                            onChange={(value) => setAttributes({ original_name: value })}
                            help={__('Original series name (if different from display name).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Tagline', 'tmu-theme')}
                            value={attributes.tagline}
                            onChange={(value) => setAttributes({ tagline: value })}
                            help={__('Series tagline or slogan.', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            rows={4}
                            help={__('Series plot summary or description.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Air Dates & Status Panel */}
                    <PanelBody
                        title={__('Air Dates & Status', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <BaseControl
                            label={__('First Air Date', 'tmu-theme')}
                            help={__('Series premiere date.', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.first_air_date}
                                onChange={(e) => setAttributes({ first_air_date: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <BaseControl
                            label={__('Last Air Date', 'tmu-theme')}
                            help={__('Most recent episode air date.', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.last_air_date}
                                onChange={(e) => setAttributes({ last_air_date: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <SelectControl
                            label={__('Status', 'tmu-theme')}
                            value={attributes.status}
                            options={statusOptions}
                            onChange={(value) => setAttributes({ status: value })}
                            help={__('Series production/air status.', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Type', 'tmu-theme')}
                            value={attributes.type}
                            options={typeOptions}
                            onChange={(value) => setAttributes({ type: value })}
                            help={__('Series type/format.', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('In Production', 'tmu-theme')}
                            checked={attributes.in_production}
                            onChange={(value) => setAttributes({ in_production: value })}
                            help={__('Mark if series is currently in production.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Episode Information Panel */}
                    <PanelBody
                        title={__('Episode Information', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('Number of Episodes', 'tmu-theme')}
                            value={attributes.number_of_episodes || ''}
                            onChange={(value) => setAttributes({ number_of_episodes: parseInt(value) || null })}
                            help={__('Total number of episodes.', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Number of Seasons', 'tmu-theme')}
                            value={attributes.number_of_seasons || ''}
                            onChange={(value) => setAttributes({ number_of_seasons: parseInt(value) || null })}
                            help={__('Total number of seasons.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Episode Runtime', 'tmu-theme')}
                            value={Array.isArray(attributes.episode_run_time) ? attributes.episode_run_time.join(', ') : ''}
                            onChange={(value) => {
                                const runtimes = value.split(',').map(v => parseInt(v.trim())).filter(v => !isNaN(v));
                                setAttributes({ episode_run_time: runtimes });
                            }}
                            help={__('Episode runtime in minutes (comma-separated for multiple values).', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Language & Country Panel */}
                    <PanelBody
                        title={__('Language & Country', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextControl
                            label={__('Original Language', 'tmu-theme')}
                            value={attributes.original_language}
                            onChange={(value) => setAttributes({ original_language: value })}
                            help={__('Original language code (e.g., en, es, fr).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Languages', 'tmu-theme')}
                            value={Array.isArray(attributes.languages) ? attributes.languages.join(', ') : ''}
                            onChange={(value) => {
                                const langs = value.split(',').map(v => v.trim()).filter(v => v);
                                setAttributes({ languages: langs });
                            }}
                            help={__('All languages (comma-separated).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Origin Countries', 'tmu-theme')}
                            value={Array.isArray(attributes.origin_country) ? attributes.origin_country.join(', ') : ''}
                            onChange={(value) => {
                                const countries = value.split(',').map(v => v.trim()).filter(v => v);
                                setAttributes({ origin_country: countries });
                            }}
                            help={__('Origin countries (comma-separated country codes).', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Ratings & Popularity Panel */}
                    <PanelBody
                        title={__('Ratings & Popularity', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('TMDB Vote Average', 'tmu-theme')}
                            value={attributes.tmdb_vote_average || ''}
                            onChange={(value) => setAttributes({ tmdb_vote_average: parseFloat(value) || null })}
                            step={0.1}
                            min={0}
                            max={10}
                            help={__('TMDB rating (0-10).', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('TMDB Vote Count', 'tmu-theme')}
                            value={attributes.tmdb_vote_count || ''}
                            onChange={(value) => setAttributes({ tmdb_vote_count: parseInt(value) || null })}
                            help={__('Number of TMDB votes.', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('TMDB Popularity', 'tmu-theme')}
                            value={attributes.tmdb_popularity || ''}
                            onChange={(value) => setAttributes({ tmdb_popularity: parseFloat(value) || null })}
                            step={0.1}
                            help={__('TMDB popularity score.', 'tmu-theme')}
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
                    </PanelBody>

                    {/* Media & Links Panel */}
                    <PanelBody
                        title={__('Media & Links', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextControl
                            label={__('IMDB ID', 'tmu-theme')}
                            value={attributes.imdb_id}
                            onChange={(value) => setAttributes({ imdb_id: value })}
                            help={__('IMDB series ID (e.g., tt1234567).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Homepage', 'tmu-theme')}
                            type="url"
                            value={attributes.homepage}
                            onChange={(value) => setAttributes({ homepage: value })}
                            help={__('Official series website URL.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Poster Path', 'tmu-theme')}
                            value={attributes.poster_path}
                            onChange={(value) => setAttributes({ poster_path: value })}
                            help={__('TMDB poster image path.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Backdrop Path', 'tmu-theme')}
                            value={attributes.backdrop_path}
                            onChange={(value) => setAttributes({ backdrop_path: value })}
                            help={__('TMDB backdrop image path.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Content Flags Panel */}
                    <PanelBody
                        title={__('Content Flags', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <ToggleControl
                            label={__('Adult Content', 'tmu-theme')}
                            checked={attributes.adult}
                            onChange={(value) => setAttributes({ adult: value })}
                            help={__('Mark if this is adult content.', 'tmu-theme')}
                        />
                        
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
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    {attributes.name ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex gap-4">
                                {attributes.poster_path && (
                                    <div className="flex-shrink-0">
                                        <img 
                                            src={getTmdbImageUrl(attributes.poster_path, 'w185')}
                                            alt={attributes.name}
                                            className="w-24 h-auto rounded shadow-lg"
                                        />
                                    </div>
                                )}
                                
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold mb-2 text-gray-900">
                                        {attributes.name}
                                    </h3>
                                    
                                    {attributes.original_name && attributes.original_name !== attributes.name && (
                                        <p className="text-sm text-gray-600 italic mb-2">
                                            {attributes.original_name}
                                        </p>
                                    )}
                                    
                                    {attributes.tagline && (
                                        <p className="text-sm text-blue-600 mb-3 italic">
                                            "{attributes.tagline}"
                                        </p>
                                    )}
                                    
                                    <div className="grid grid-cols-2 gap-2 text-sm">
                                        {attributes.first_air_date && (
                                            <div>
                                                <span className="font-semibold text-gray-700">First Aired:</span>
                                                <span className="ml-1">{new Date(attributes.first_air_date).getFullYear()}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.number_of_seasons && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Seasons:</span>
                                                <span className="ml-1">{attributes.number_of_seasons}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.number_of_episodes && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Episodes:</span>
                                                <span className="ml-1">{attributes.number_of_episodes}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.status && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Status:</span>
                                                <span className="ml-1">{attributes.status}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.tmdb_vote_average && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Rating:</span>
                                                <span className="ml-1">{attributes.tmdb_vote_average}/10</span>
                                            </div>
                                        )}
                                        
                                        {attributes.episode_run_time && attributes.episode_run_time.length > 0 && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Runtime:</span>
                                                <span className="ml-1">{formatRuntime(attributes.episode_run_time)}</span>
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
                            icon="video-alt2"
                            label={__('TV Series Metadata', 'tmu-theme')}
                            instructions={__('Configure TV series metadata in the block settings panel. Start by entering a TMDB ID to automatically fetch series information.', 'tmu-theme')}
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

/**
 * Simple debounce utility function
 */
function debounce(func, wait) {
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

export default TvSeriesMetadataBlock;