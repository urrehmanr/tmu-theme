/**
 * Movie Metadata Block Component
 * 
 * React component for movie metadata block editor interface
 * with TMDB integration and comprehensive movie data fields.
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
 * Register Movie Metadata Block
 */
registerBlockType('tmu/movie-metadata', {
    title: __('Movie Metadata', 'tmu-theme'),
    description: __('Comprehensive movie metadata management with TMDB integration', 'tmu-theme'),
    icon: 'video-alt3',
    category: 'tmu-blocks',
    keywords: [
        __('movie', 'tmu-theme'),
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
        title: { type: 'string' },
        original_title: { type: 'string' },
        tagline: { type: 'string' },
        overview: { type: 'string' },
        runtime: { type: 'number' },
        release_date: { type: 'string' },
        status: { 
            type: 'string',
            default: 'Released'
        },
        budget: { type: 'number' },
        revenue: { type: 'number' },
        homepage: { type: 'string' },
        poster_path: { type: 'string' },
        backdrop_path: { type: 'string' },
        tmdb_vote_average: { type: 'number' },
        tmdb_vote_count: { type: 'number' },
        tmdb_popularity: { type: 'number' },
        adult: { 
            type: 'boolean',
            default: false
        },
        video: { 
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
                const response = await fetch(`/wp-json/tmu/v1/tmdb/movie/${tmdbId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Auto-populate attributes from TMDB data
                const mappedData = {
                    tmdb_id: data.id,
                    imdb_id: data.imdb_id,
                    title: data.title,
                    original_title: data.original_title,
                    tagline: data.tagline,
                    overview: data.overview,
                    runtime: data.runtime,
                    release_date: data.release_date,
                    status: data.status,
                    budget: data.budget,
                    revenue: data.revenue,
                    homepage: data.homepage,
                    poster_path: data.poster_path,
                    backdrop_path: data.backdrop_path,
                    tmdb_vote_average: data.vote_average,
                    tmdb_vote_count: data.vote_count,
                    tmdb_popularity: data.popularity,
                    adult: data.adult,
                    video: data.video,
                };
                
                setAttributes(mappedData);
                setNotice({
                    type: 'success',
                    message: __('Movie data fetched successfully from TMDB', 'tmu-theme')
                });
                
            } catch (error) {
                console.error('Error fetching TMDB data:', error);
                setNotice({
                    type: 'error',
                    message: __('Failed to fetch movie data from TMDB. Please check the ID and try again.', 'tmu-theme')
                });
            } finally {
                setIsLoading(false);
            }
        };
        
        const statusOptions = [
            { label: __('Released', 'tmu-theme'), value: 'Released' },
            { label: __('In Production', 'tmu-theme'), value: 'In Production' },
            { label: __('Post Production', 'tmu-theme'), value: 'Post Production' },
            { label: __('Planned', 'tmu-theme'), value: 'Planned' },
            { label: __('Canceled', 'tmu-theme'), value: 'Canceled' },
            { label: __('Rumored', 'tmu-theme'), value: 'Rumored' },
        ];
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('TMDB Integration', 'tmu-theme')} initialOpen={true}>
                        <NumberControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id || ''}
                            onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || 0 })}
                            help={__('Enter the TMDB ID to auto-fetch movie data', 'tmu-theme')}
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
                    
                    <PanelBody title={__('Release Information', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Release Date', 'tmu-theme')}
                            type="date"
                            value={attributes.release_date || ''}
                            onChange={(value) => setAttributes({ release_date: value })}
                        />
                        <SelectControl
                            label={__('Status', 'tmu-theme')}
                            value={attributes.status || 'Released'}
                            options={statusOptions}
                            onChange={(value) => setAttributes({ status: value })}
                        />
                        <NumberControl
                            label={__('Runtime (minutes)', 'tmu-theme')}
                            value={attributes.runtime || ''}
                            onChange={(value) => setAttributes({ runtime: parseInt(value) || 0 })}
                            min={0}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Financial Information', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Budget ($)', 'tmu-theme')}
                            value={attributes.budget || ''}
                            onChange={(value) => setAttributes({ budget: parseInt(value) || 0 })}
                            min={0}
                        />
                        <NumberControl
                            label={__('Revenue ($)', 'tmu-theme')}
                            value={attributes.revenue || ''}
                            onChange={(value) => setAttributes({ revenue: parseInt(value) || 0 })}
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
                            help={__('Mark if this movie contains adult content', 'tmu-theme')}
                        />
                        <ToggleControl
                            label={__('Has Video', 'tmu-theme')}
                            checked={attributes.video || false}
                            onChange={(value) => setAttributes({ video: value })}
                            help={__('Mark if trailers/videos are available', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-movie-metadata-block bg-white border border-gray-200 rounded-lg p-6">
                    {attributes.title ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex items-start gap-4 mb-4">
                                {attributes.poster_path && (
                                    <img 
                                        src={`https://image.tmdb.org/t/p/w200${attributes.poster_path}`}
                                        alt={attributes.title}
                                        className="w-24 h-36 object-cover rounded-md shadow-md"
                                    />
                                )}
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">
                                        {attributes.title}
                                        {attributes.release_date && (
                                            <span className="text-gray-500 font-normal ml-2">
                                                ({new Date(attributes.release_date).getFullYear()})
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
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                {attributes.release_date && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Release Date', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {new Date(attributes.release_date).toLocaleDateString()}
                                        </div>
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
                                {attributes.tmdb_vote_average && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('TMDB Rating', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {attributes.tmdb_vote_average}/10
                                        </div>
                                    </div>
                                )}
                            </div>
                            
                            {attributes.overview && (
                                <div className="mb-4">
                                    <h4 className="font-semibold text-gray-900 mb-2">{__('Overview', 'tmu-theme')}</h4>
                                    <p className="text-gray-700 leading-relaxed">{attributes.overview}</p>
                                </div>
                            )}
                            
                            {(attributes.budget || attributes.revenue) && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {attributes.budget && (
                                        <div className="p-3 bg-blue-50 rounded">
                                            <div className="text-sm text-blue-600">{__('Budget', 'tmu-theme')}</div>
                                            <div className="font-semibold text-blue-900">
                                                ${attributes.budget.toLocaleString()}
                                            </div>
                                        </div>
                                    )}
                                    {attributes.revenue && (
                                        <div className="p-3 bg-green-50 rounded">
                                            <div className="text-sm text-green-600">{__('Revenue', 'tmu-theme')}</div>
                                            <div className="font-semibold text-green-900">
                                                ${attributes.revenue.toLocaleString()}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="video-alt3"
                            label={__('Movie Metadata', 'tmu-theme')}
                            instructions={__('Configure movie metadata in the block settings panel or fetch data using TMDB ID.', 'tmu-theme')}
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