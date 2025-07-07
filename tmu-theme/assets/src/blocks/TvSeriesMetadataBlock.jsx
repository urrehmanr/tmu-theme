/**
 * TV Series Metadata Block Component
 * 
 * React component for TV series metadata block editor interface
 * with TMDB integration and comprehensive TV series data fields.
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
 * Register TV Series Metadata Block
 */
registerBlockType('tmu/tv-series-metadata', {
    title: __('TV Series Metadata', 'tmu-theme'),
    description: __('Comprehensive TV series metadata management with TMDB integration', 'tmu-theme'),
    icon: 'video-alt2',
    category: 'tmu-blocks',
    keywords: [
        __('tv', 'tmu-theme'),
        __('series', 'tmu-theme'),
        __('metadata', 'tmu-theme'),
        __('tmdb', 'tmu-theme'),
    ],
    supports: {
        html: false,
        multiple: false,
    },
    attributes: {
        tmdb_id: { type: 'number' },
        imdb_id: { type: 'string' },
        name: { type: 'string' },
        original_name: { type: 'string' },
        tagline: { type: 'string' },
        overview: { type: 'string' },
        first_air_date: { type: 'string' },
        last_air_date: { type: 'string' },
        status: { 
            type: 'string',
            default: 'Ended'
        },
        type: { type: 'string' },
        homepage: { type: 'string' },
        in_production: { 
            type: 'boolean',
            default: false
        },
        number_of_episodes: { type: 'number' },
        number_of_seasons: { type: 'number' },
        original_language: { type: 'string' },
        poster_path: { type: 'string' },
        backdrop_path: { type: 'string' },
        tmdb_vote_average: { type: 'number' },
        tmdb_vote_count: { type: 'number' },
        tmdb_popularity: { type: 'number' },
        adult: { 
            type: 'boolean',
            default: false
        },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [notice, setNotice] = useState(null);
        
        const fetchTmdbData = async (tmdbId) => {
            if (!tmdbId) {
                setNotice({
                    type: 'error',
                    message: __('Please enter a valid TMDB ID', 'tmu-theme')
                });
                return;
            }
            
            setIsLoading(true);
            setNotice(null);
            
            try {
                const response = await fetch(`/wp-json/tmu/v1/tmdb/tv/${tmdbId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Auto-populate attributes from TMDB data
                const mappedData = {
                    tmdb_id: data.id,
                    imdb_id: data.external_ids?.imdb_id,
                    name: data.name,
                    original_name: data.original_name,
                    tagline: data.tagline,
                    overview: data.overview,
                    first_air_date: data.first_air_date,
                    last_air_date: data.last_air_date,
                    status: data.status,
                    type: data.type,
                    homepage: data.homepage,
                    in_production: data.in_production,
                    number_of_episodes: data.number_of_episodes,
                    number_of_seasons: data.number_of_seasons,
                    original_language: data.original_language,
                    poster_path: data.poster_path,
                    backdrop_path: data.backdrop_path,
                    tmdb_vote_average: data.vote_average,
                    tmdb_vote_count: data.vote_count,
                    tmdb_popularity: data.popularity,
                    adult: data.adult,
                };
                
                setAttributes(mappedData);
                setNotice({
                    type: 'success',
                    message: __('TV series data fetched successfully from TMDB', 'tmu-theme')
                });
                
            } catch (error) {
                console.error('Error fetching TMDB data:', error);
                setNotice({
                    type: 'error',
                    message: __('Failed to fetch TV series data from TMDB. Please check the ID and try again.', 'tmu-theme')
                });
            } finally {
                setIsLoading(false);
            }
        };
        
        const statusOptions = [
            { label: __('Returning Series', 'tmu-theme'), value: 'Returning Series' },
            { label: __('Planned', 'tmu-theme'), value: 'Planned' },
            { label: __('In Production', 'tmu-theme'), value: 'In Production' },
            { label: __('Ended', 'tmu-theme'), value: 'Ended' },
            { label: __('Canceled', 'tmu-theme'), value: 'Canceled' },
            { label: __('Pilot', 'tmu-theme'), value: 'Pilot' },
        ];
        
        const typeOptions = [
            { label: __('Scripted', 'tmu-theme'), value: 'Scripted' },
            { label: __('Reality', 'tmu-theme'), value: 'Reality' },
            { label: __('Documentary', 'tmu-theme'), value: 'Documentary' },
            { label: __('News', 'tmu-theme'), value: 'News' },
            { label: __('Talk Show', 'tmu-theme'), value: 'Talk Show' },
            { label: __('Miniseries', 'tmu-theme'), value: 'Miniseries' },
        ];
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('TMDB Integration', 'tmu-theme')} initialOpen={true}>
                        <NumberControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id || ''}
                            onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || 0 })}
                            help={__('Enter the TMDB ID to auto-fetch TV series data', 'tmu-theme')}
                        />
                        <Button
                            variant="primary"
                            onClick={() => fetchTmdbData(attributes.tmdb_id)}
                            disabled={!attributes.tmdb_id || isLoading}
                            isBusy={isLoading}
                        >
                            {isLoading 
                                ? __('Fetching...', 'tmu-theme') 
                                : __('Fetch TMDB Data', 'tmu-theme')
                            }
                        </Button>
                        {notice && (
                            <Notice
                                status={notice.type}
                                isDismissible={true}
                                onRemove={() => setNotice(null)}
                            >
                                {notice.message}
                            </Notice>
                        )}
                    </PanelBody>
                    
                    <PanelBody title={__('Basic Information', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Name', 'tmu-theme')}
                            value={attributes.name || ''}
                            onChange={(value) => setAttributes({ name: value })}
                        />
                        <TextControl
                            label={__('Original Name', 'tmu-theme')}
                            value={attributes.original_name || ''}
                            onChange={(value) => setAttributes({ original_name: value })}
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
                        <TextControl
                            label={__('Original Language', 'tmu-theme')}
                            value={attributes.original_language || ''}
                            onChange={(value) => setAttributes({ original_language: value })}
                            help={__('Language code (e.g., en, es, fr)', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Air Dates & Status', 'tmu-theme')} initialOpen={false}>
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
                        <SelectControl
                            label={__('Type', 'tmu-theme')}
                            value={attributes.type || 'Scripted'}
                            options={typeOptions}
                            onChange={(value) => setAttributes({ type: value })}
                        />
                        <ToggleControl
                            label={__('In Production', 'tmu-theme')}
                            checked={attributes.in_production || false}
                            onChange={(value) => setAttributes({ in_production: value })}
                            help={__('Is the series currently in production?', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Episodes & Seasons', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Number of Seasons', 'tmu-theme')}
                            value={attributes.number_of_seasons || ''}
                            onChange={(value) => setAttributes({ number_of_seasons: parseInt(value) || 0 })}
                            min={0}
                        />
                        <NumberControl
                            label={__('Number of Episodes', 'tmu-theme')}
                            value={attributes.number_of_episodes || ''}
                            onChange={(value) => setAttributes({ number_of_episodes: parseInt(value) || 0 })}
                            min={0}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Ratings & Popularity', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('TMDB Vote Average', 'tmu-theme')}
                            value={attributes.tmdb_vote_average || ''}
                            onChange={(value) => setAttributes({ tmdb_vote_average: parseFloat(value) || 0 })}
                            step={0.1}
                            min={0}
                            max={10}
                        />
                        <NumberControl
                            label={__('TMDB Vote Count', 'tmu-theme')}
                            value={attributes.tmdb_vote_count || ''}
                            onChange={(value) => setAttributes({ tmdb_vote_count: parseInt(value) || 0 })}
                            min={0}
                        />
                        <NumberControl
                            label={__('TMDB Popularity', 'tmu-theme')}
                            value={attributes.tmdb_popularity || ''}
                            onChange={(value) => setAttributes({ tmdb_popularity: parseFloat(value) || 0 })}
                            step={0.1}
                            min={0}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Media & Links', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('IMDB ID', 'tmu-theme')}
                            value={attributes.imdb_id || ''}
                            onChange={(value) => setAttributes({ imdb_id: value })}
                            help={__('Format: tt1234567', 'tmu-theme')}
                        />
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
                            help={__('TMDB poster path (e.g., /path/to/poster.jpg)', 'tmu-theme')}
                        />
                        <TextControl
                            label={__('Backdrop Path', 'tmu-theme')}
                            value={attributes.backdrop_path || ''}
                            onChange={(value) => setAttributes({ backdrop_path: value })}
                            help={__('TMDB backdrop path (e.g., /path/to/backdrop.jpg)', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Content Flags', 'tmu-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Adult Content', 'tmu-theme')}
                            checked={attributes.adult || false}
                            onChange={(value) => setAttributes({ adult: value })}
                            help={__('Mark if this series contains adult content', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-tv-series-metadata-block bg-white border border-gray-200 rounded-lg p-6">
                    {attributes.name ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex items-start gap-4 mb-4">
                                {attributes.poster_path && (
                                    <img 
                                        src={`https://image.tmdb.org/t/p/w200${attributes.poster_path}`}
                                        alt={attributes.name}
                                        className="w-24 h-36 object-cover rounded-md shadow-md"
                                    />
                                )}
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">
                                        {attributes.name}
                                        {attributes.first_air_date && (
                                            <span className="text-gray-500 font-normal ml-2">
                                                ({new Date(attributes.first_air_date).getFullYear()})
                                            </span>
                                        )}
                                    </h3>
                                    {attributes.original_name && attributes.original_name !== attributes.name && (
                                        <p className="text-gray-600 italic mb-2">
                                            {__('Original Name:', 'tmu-theme')} {attributes.original_name}
                                        </p>
                                    )}
                                    {attributes.tagline && (
                                        <p className="text-gray-700 italic mb-3">"{attributes.tagline}"</p>
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
                                {attributes.number_of_seasons && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Seasons', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.number_of_seasons}</div>
                                    </div>
                                )}
                                {attributes.number_of_episodes && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Episodes', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.number_of_episodes}</div>
                                    </div>
                                )}
                                {attributes.status && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Status', 'tmu-theme')}</div>
                                        <div className="font-semibold">{attributes.status}</div>
                                    </div>
                                )}
                            </div>
                            
                            {attributes.tmdb_vote_average && (
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                                    <div className="text-center p-3 bg-blue-50 rounded">
                                        <div className="text-sm text-blue-600">{__('TMDB Rating', 'tmu-theme')}</div>
                                        <div className="font-semibold text-blue-900">
                                            {attributes.tmdb_vote_average}/10
                                        </div>
                                    </div>
                                    {attributes.tmdb_vote_count && (
                                        <div className="text-center p-3 bg-blue-50 rounded">
                                            <div className="text-sm text-blue-600">{__('Votes', 'tmu-theme')}</div>
                                            <div className="font-semibold text-blue-900">
                                                {attributes.tmdb_vote_count.toLocaleString()}
                                            </div>
                                        </div>
                                    )}
                                    {attributes.tmdb_popularity && (
                                        <div className="text-center p-3 bg-blue-50 rounded">
                                            <div className="text-sm text-blue-600">{__('Popularity', 'tmu-theme')}</div>
                                            <div className="font-semibold text-blue-900">
                                                {Math.round(attributes.tmdb_popularity)}
                                            </div>
                                        </div>
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
                                {attributes.type && (
                                    <span className="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                                        {attributes.type}
                                    </span>
                                )}
                                {attributes.original_language && (
                                    <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                        {attributes.original_language.toUpperCase()}
                                    </span>
                                )}
                                {attributes.in_production && (
                                    <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                        {__('In Production', 'tmu-theme')}
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
                            icon="video-alt2"
                            label={__('TV Series Metadata', 'tmu-theme')}
                            instructions={__('Configure TV series metadata in the block settings panel or fetch data using TMDB ID.', 'tmu-theme')}
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