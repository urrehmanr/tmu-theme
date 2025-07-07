/**
 * Movie Metadata Block Component
 * 
 * Comprehensive React component for movie metadata management with TMDB integration,
 * real-time validation, and complete movie information handling.
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
 * Movie Metadata Block Component
 */
const MovieMetadataBlock = {
    title: __('Movie Metadata', 'tmu-theme'),
    icon: 'video-alt3',
    category: 'tmu-blocks',
    description: __('Comprehensive movie metadata management with TMDB integration', 'tmu-theme'),
    keywords: [
        __('movie', 'tmu-theme'),
        __('film', 'tmu-theme'),
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
        title: { type: 'string', default: '' },
        original_title: { type: 'string', default: '' },
        tagline: { type: 'string', default: '' },
        overview: { type: 'string', default: '' },
        
        // Release Information
        release_date: { type: 'string', default: '' },
        status: { type: 'string', default: 'Released' },
        runtime: { type: 'number', default: null },
        
        // Financial Information
        budget: { type: 'number', default: null },
        revenue: { type: 'number', default: null },
        
        // Certification & Production
        certification: { type: 'string', default: '' },
        streaming_platforms: { type: 'string', default: '' },
        star_cast: { type: 'string', default: '' },
        
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
        video: { type: 'boolean', default: false },
        
        // Collection Information
        belongs_to_collection: { type: 'object', default: null },
        
        // Production Information
        production_companies: { type: 'array', default: [] },
        production_countries: { type: 'array', default: [] },
        spoken_languages: { type: 'array', default: [] },
        
        // Extended TMDB Data
        credits: { type: 'object', default: null },
        external_ids: { type: 'object', default: null },
        images: { type: 'object', default: null },
        videos: { type: 'object', default: null },
        reviews: { type: 'object', default: null },
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
            className: 'tmu-movie-metadata-block'
        });

        // Debounced TMDB fetch to avoid excessive API calls
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
         * Fetch movie data from TMDB API
         */
        const fetchTmdbData = async (tmdbId) => {
            if (!tmdbId || isLoading) return;

            setIsLoading(true);
            setError(null);

            try {
                const response = await apiFetch({
                    path: `/tmu/v1/tmdb/movie/${tmdbId}`,
                    method: 'GET'
                });

                if (response.success && response.data) {
                    setTmdbData(response.data);
                    setLastFetchedId(tmdbId);
                    populateAttributesFromTmdb(response.data);
                    
                    // Update last sync time
                    setAttributes({
                        last_tmdb_sync: new Date().toISOString()
                    });
                } else {
                    setError(__('Movie not found in TMDB database.', 'tmu-theme'));
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
            if (data.title && !attributes.title) updates.title = data.title;
            if (data.original_title) updates.original_title = data.original_title;
            if (data.tagline) updates.tagline = data.tagline;
            if (data.overview) updates.overview = data.overview;

            // Release Information
            if (data.release_date) updates.release_date = data.release_date;
            if (data.status) updates.status = data.status;
            if (data.runtime) updates.runtime = data.runtime;

            // Financial Information
            if (data.budget) updates.budget = data.budget;
            if (data.revenue) updates.revenue = data.revenue;

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
            if (typeof data.video === 'boolean') updates.video = data.video;

            // External IDs
            if (data.external_ids?.imdb_id) updates.imdb_id = data.external_ids.imdb_id;

            // Complex Data
            if (data.belongs_to_collection) updates.belongs_to_collection = data.belongs_to_collection;
            if (data.production_companies) updates.production_companies = data.production_companies;
            if (data.production_countries) updates.production_countries = data.production_countries;
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
                setLastFetchedId(null); // Force refresh
                fetchTmdbData(attributes.tmdb_id);
            }
        };

        /**
         * Format runtime for display
         */
        const formatRuntime = (minutes) => {
            if (!minutes) return '';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
        };

        /**
         * Format currency for display
         */
        const formatCurrency = (amount) => {
            if (!amount) return '';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0
            }).format(amount);
        };

        /**
         * Get TMDB image URL
         */
        const getTmdbImageUrl = (path, size = 'w300') => {
            return path ? `https://image.tmdb.org/t/p/${size}${path}` : '';
        };

        const statusOptions = [
            { label: __('Released', 'tmu-theme'), value: 'Released' },
            { label: __('In Production', 'tmu-theme'), value: 'In Production' },
            { label: __('Post Production', 'tmu-theme'), value: 'Post Production' },
            { label: __('Planned', 'tmu-theme'), value: 'Planned' },
            { label: __('Canceled', 'tmu-theme'), value: 'Canceled' }
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
                            help={__('Enter the TMDB movie ID to fetch metadata automatically.', 'tmu-theme')}
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
                            label={__('Title', 'tmu-theme')}
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                            help={__('Movie title as displayed on the site.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Original Title', 'tmu-theme')}
                            value={attributes.original_title}
                            onChange={(value) => setAttributes({ original_title: value })}
                            help={__('Original movie title (if different from display title).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Tagline', 'tmu-theme')}
                            value={attributes.tagline}
                            onChange={(value) => setAttributes({ tagline: value })}
                            help={__('Movie tagline or slogan.', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            rows={4}
                            help={__('Movie plot summary or description.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Release Information Panel */}
                    <PanelBody
                        title={__('Release Information', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <BaseControl
                            label={__('Release Date', 'tmu-theme')}
                            help={__('Movie release date.', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.release_date}
                                onChange={(e) => setAttributes({ release_date: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <SelectControl
                            label={__('Status', 'tmu-theme')}
                            value={attributes.status}
                            options={statusOptions}
                            onChange={(value) => setAttributes({ status: value })}
                            help={__('Movie production/release status.', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Runtime (minutes)', 'tmu-theme')}
                            value={attributes.runtime || ''}
                            onChange={(value) => setAttributes({ runtime: parseInt(value) || null })}
                            help={__('Movie duration in minutes.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Financial Information Panel */}
                    <PanelBody
                        title={__('Financial Information', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('Budget ($)', 'tmu-theme')}
                            value={attributes.budget || ''}
                            onChange={(value) => setAttributes({ budget: parseInt(value) || null })}
                            help={__('Movie production budget in USD.', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Revenue ($)', 'tmu-theme')}
                            value={attributes.revenue || ''}
                            onChange={(value) => setAttributes({ revenue: parseInt(value) || null })}
                            help={__('Movie box office revenue in USD.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Production Details Panel */}
                    <PanelBody
                        title={__('Production Details', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextControl
                            label={__('Certification', 'tmu-theme')}
                            value={attributes.certification}
                            onChange={(value) => setAttributes({ certification: value })}
                            help={__('Movie rating/certification (e.g., PG-13, R).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Streaming Platforms', 'tmu-theme')}
                            value={attributes.streaming_platforms}
                            onChange={(value) => setAttributes({ streaming_platforms: value })}
                            help={__('Available streaming platforms (comma-separated).', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Star Cast', 'tmu-theme')}
                            value={attributes.star_cast}
                            onChange={(value) => setAttributes({ star_cast: value })}
                            rows={3}
                            help={__('Main cast members (comma-separated).', 'tmu-theme')}
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
                            help={__('IMDB movie ID (e.g., tt1234567).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Homepage', 'tmu-theme')}
                            type="url"
                            value={attributes.homepage}
                            onChange={(value) => setAttributes({ homepage: value })}
                            help={__('Official movie website URL.', 'tmu-theme')}
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
                            label={__('Has Video', 'tmu-theme')}
                            checked={attributes.video}
                            onChange={(value) => setAttributes({ video: value })}
                            help={__('Mark if video content is available.', 'tmu-theme')}
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
                    {attributes.title ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex gap-4">
                                {attributes.poster_path && (
                                    <div className="flex-shrink-0">
                                        <img 
                                            src={getTmdbImageUrl(attributes.poster_path, 'w185')}
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
                                        {attributes.release_date && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Release:</span>
                                                <span className="ml-1">{new Date(attributes.release_date).getFullYear()}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.runtime && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Runtime:</span>
                                                <span className="ml-1">{formatRuntime(attributes.runtime)}</span>
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
                                    </div>
                                    
                                    {attributes.budget && (
                                        <div className="mt-2 text-sm">
                                            <span className="font-semibold text-gray-700">Budget:</span>
                                            <span className="ml-1">{formatCurrency(attributes.budget)}</span>
                                        </div>
                                    )}
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
                            icon="video-alt3"
                            label={__('Movie Metadata', 'tmu-theme')}
                            instructions={__('Configure movie metadata in the block settings panel. Start by entering a TMDB ID to automatically fetch movie information.', 'tmu-theme')}
                            className="tmu-placeholder"
                        />
                    )}
                </div>
            </>
        );
    },

    save: () => {
        // Server-side rendering will handle the frontend display
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

export default MovieMetadataBlock;