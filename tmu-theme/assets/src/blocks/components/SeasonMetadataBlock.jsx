/**
 * Season Metadata Block
 * 
 * React component for season metadata block editor interface.
 * Handles season-specific data fields for TV shows.
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
    Spinner
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const SeasonMetadataBlock = {
    title: __('Season Metadata', 'tmu-theme'),
    icon: 'playlist-video',
    category: 'tmu-blocks',
    description: __('Season metadata management for TV shows', 'tmu-theme'),
    keywords: [__('season', 'tmu-theme'), __('tv', 'tmu-theme'), __('metadata', 'tmu-theme')],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
    },
    attributes: {
        tv_series: { type: 'number', default: 0 },
        season_number: { type: 'number', default: 1 },
        name: { type: 'string', default: '' },
        overview: { type: 'string', default: '' },
        air_date: { type: 'string', default: '' },
        episode_count: { type: 'number', default: 0 },
        poster_path: { type: 'string', default: '' },
        vote_average: { type: 'number', default: 0 },
        vote_count: { type: 'number', default: 0 },
        tmdb_id: { type: 'number', default: 0 },
        production_status: { type: 'string', default: 'aired' },
        season_type: { type: 'string', default: 'regular' },
        synopsis: { type: 'string', default: '' },
        cast_changes: { type: 'array', default: [] },
        awards: { type: 'array', default: [] },
        behind_scenes: { type: 'string', default: '' },
        themes: { type: 'array', default: [] },
        critical_reception: { type: 'string', default: '' },
        broadcast_info: { type: 'object', default: {} },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [tvShowOptions, setTvShowOptions] = useState([]);
        const [validationErrors, setValidationErrors] = useState({});
        
        // Load TV show posts for selection
        const tvShows = useSelect((select) => {
            return select('core').getEntityRecords('postType', 'tv', {
                per_page: -1,
                status: 'publish',
            });
        }, []);
        
        useEffect(() => {
            if (tvShows) {
                const options = [
                    { label: __('Select TV Series', 'tmu-theme'), value: 0 },
                    ...tvShows.map(show => ({
                        label: show.title.rendered,
                        value: show.id
                    }))
                ];
                setTvShowOptions(options);
            }
        }, [tvShows]);
        
        // Validation function
        const validateFields = () => {
            const errors = {};
            
            if (!attributes.name.trim()) {
                errors.name = __('Season name is required', 'tmu-theme');
            }
            
            if (attributes.season_number < 1) {
                errors.season_number = __('Season number must be positive', 'tmu-theme');
            }
            
            if (attributes.episode_count < 0) {
                errors.episode_count = __('Episode count cannot be negative', 'tmu-theme');
            }
            
            if (attributes.vote_average < 0 || attributes.vote_average > 10) {
                errors.vote_average = __('Vote average must be between 0 and 10', 'tmu-theme');
            }
            
            setValidationErrors(errors);
            return Object.keys(errors).length === 0;
        };
        
        // Auto-save validation
        useEffect(() => {
            validateFields();
        }, [attributes]);
        
        const fetchTmdbData = async () => {
            if (!attributes.tmdb_id || !attributes.tv_series) return;
            
            setIsLoading(true);
            try {
                const response = await fetch(`/wp-json/tmu/v1/tmdb/tv/${attributes.tv_series}/season/${attributes.season_number}`);
                const data = await response.json();
                
                if (data.success && data.data) {
                    const seasonData = data.data;
                    setAttributes({
                        name: seasonData.name || attributes.name,
                        overview: seasonData.overview || attributes.overview,
                        air_date: seasonData.air_date || attributes.air_date,
                        episode_count: seasonData.episode_count || attributes.episode_count,
                        poster_path: seasonData.poster_path || attributes.poster_path,
                        vote_average: seasonData.vote_average || attributes.vote_average,
                        vote_count: seasonData.vote_count || attributes.vote_count,
                    });
                }
            } catch (error) {
                console.error('Error fetching TMDB data:', error);
            } finally {
                setIsLoading(false);
            }
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Season Information', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('TV Series', 'tmu-theme')}
                            value={attributes.tv_series}
                            options={tvShowOptions}
                            onChange={(value) => setAttributes({ tv_series: parseInt(value) })}
                            help={__('Select the TV series this season belongs to', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Season Number', 'tmu-theme')}
                            value={attributes.season_number}
                            onChange={(value) => setAttributes({ season_number: parseInt(value) || 1 })}
                            min={1}
                            help={validationErrors.season_number}
                        />
                        
                        <TextControl
                            label={__('Season Name', 'tmu-theme')}
                            value={attributes.name}
                            onChange={(value) => setAttributes({ name: value })}
                            placeholder={__('Season 1, Season 2, etc.', 'tmu-theme')}
                            help={validationErrors.name}
                        />
                        
                        <SelectControl
                            label={__('Season Type', 'tmu-theme')}
                            value={attributes.season_type}
                            options={[
                                { label: __('Regular Season', 'tmu-theme'), value: 'regular' },
                                { label: __('Mini Series', 'tmu-theme'), value: 'mini_series' },
                                { label: __('Special Season', 'tmu-theme'), value: 'special' },
                                { label: __('Limited Series', 'tmu-theme'), value: 'limited' },
                                { label: __('Anthology', 'tmu-theme'), value: 'anthology' },
                            ]}
                            onChange={(value) => setAttributes({ season_type: value })}
                        />
                        
                        <SelectControl
                            label={__('Production Status', 'tmu-theme')}
                            value={attributes.production_status}
                            options={[
                                { label: __('Aired', 'tmu-theme'), value: 'aired' },
                                { label: __('In Production', 'tmu-theme'), value: 'in_production' },
                                { label: __('Post Production', 'tmu-theme'), value: 'post_production' },
                                { label: __('Announced', 'tmu-theme'), value: 'announced' },
                                { label: __('Cancelled', 'tmu-theme'), value: 'cancelled' },
                            ]}
                            onChange={(value) => setAttributes({ production_status: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Season Details', 'tmu-theme')} initialOpen={false}>
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            placeholder={__('Brief season description...', 'tmu-theme')}
                            rows={4}
                        />
                        
                        <TextareaControl
                            label={__('Synopsis', 'tmu-theme')}
                            value={attributes.synopsis}
                            onChange={(value) => setAttributes({ synopsis: value })}
                            placeholder={__('Detailed season synopsis...', 'tmu-theme')}
                            rows={6}
                        />
                        
                        <NumberControl
                            label={__('Episode Count', 'tmu-theme')}
                            value={attributes.episode_count}
                            onChange={(value) => setAttributes({ episode_count: parseInt(value) || 0 })}
                            min={0}
                            help={validationErrors.episode_count}
                        />
                        
                        <TextControl
                            label={__('Air Date', 'tmu-theme')}
                            type="date"
                            value={attributes.air_date}
                            onChange={(value) => setAttributes({ air_date: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('TMDB Integration', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id}
                            onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || 0 })}
                            help={__('TMDB season ID for data synchronization', 'tmu-theme')}
                        />
                        
                        <Button
                            isPrimary
                            isLarge
                            onClick={fetchTmdbData}
                            disabled={!attributes.tmdb_id || !attributes.tv_series || isLoading}
                        >
                            {isLoading ? (
                                <>
                                    <Spinner />
                                    {__('Fetching...', 'tmu-theme')}
                                </>
                            ) : (
                                __('Fetch TMDB Data', 'tmu-theme')
                            )}
                        </Button>
                    </PanelBody>
                    
                    <PanelBody title={__('Ratings & Reviews', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Vote Average', 'tmu-theme')}
                            value={attributes.vote_average}
                            onChange={(value) => setAttributes({ vote_average: parseFloat(value) || 0 })}
                            step={0.1}
                            min={0}
                            max={10}
                            help={validationErrors.vote_average}
                        />
                        
                        <NumberControl
                            label={__('Vote Count', 'tmu-theme')}
                            value={attributes.vote_count}
                            onChange={(value) => setAttributes({ vote_count: parseInt(value) || 0 })}
                            min={0}
                        />
                        
                        <TextareaControl
                            label={__('Critical Reception', 'tmu-theme')}
                            value={attributes.critical_reception}
                            onChange={(value) => setAttributes({ critical_reception: value })}
                            placeholder={__('Critical reception and reviews summary...', 'tmu-theme')}
                            rows={4}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Media & Visuals', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Poster Path', 'tmu-theme')}
                            value={attributes.poster_path}
                            onChange={(value) => setAttributes({ poster_path: value })}
                            placeholder={__('Season poster image URL or path', 'tmu-theme')}
                        />
                        
                        {attributes.poster_path && (
                            <div className="poster-preview mt-2">
                                <img 
                                    src={attributes.poster_path.startsWith('/') ? `https://image.tmdb.org/t/p/w300${attributes.poster_path}` : attributes.poster_path}
                                    alt={attributes.name}
                                    style={{ maxWidth: '150px', height: 'auto' }}
                                />
                            </div>
                        )}
                    </PanelBody>
                    
                    <PanelBody title={__('Additional Information', 'tmu-theme')} initialOpen={false}>
                        <TextareaControl
                            label={__('Behind the Scenes', 'tmu-theme')}
                            value={attributes.behind_scenes}
                            onChange={(value) => setAttributes({ behind_scenes: value })}
                            placeholder={__('Behind the scenes information...', 'tmu-theme')}
                            rows={4}
                        />
                        
                        <TextControl
                            label={__('Themes (JSON)', 'tmu-theme')}
                            value={JSON.stringify(attributes.themes)}
                            onChange={(value) => {
                                try {
                                    const parsed = JSON.parse(value);
                                    setAttributes({ themes: Array.isArray(parsed) ? parsed : [] });
                                } catch (e) {
                                    // Invalid JSON, ignore
                                }
                            }}
                            placeholder={__('["Theme 1", "Theme 2"]', 'tmu-theme')}
                            help={__('Enter themes as JSON array', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Awards (JSON)', 'tmu-theme')}
                            value={JSON.stringify(attributes.awards)}
                            onChange={(value) => {
                                try {
                                    const parsed = JSON.parse(value);
                                    setAttributes({ awards: Array.isArray(parsed) ? parsed : [] });
                                } catch (e) {
                                    // Invalid JSON, ignore
                                }
                            }}
                            placeholder={__('[{"name": "Award Name", "year": 2023}]', 'tmu-theme')}
                            help={__('Enter awards as JSON array', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-season-metadata-block">
                    {attributes.name ? (
                        <div className="tmu-season-preview bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <div className="season-header mb-4">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="season-badge bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                        {__('Season', 'tmu-theme')} {attributes.season_number}
                                    </span>
                                    <span className={`status-badge text-xs font-medium px-2.5 py-0.5 rounded ${
                                        attributes.production_status === 'aired' ? 'bg-green-100 text-green-800' :
                                        attributes.production_status === 'in_production' ? 'bg-yellow-100 text-yellow-800' :
                                        attributes.production_status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                        'bg-gray-100 text-gray-800'
                                    }`}>
                                        {attributes.production_status.replace('_', ' ').toUpperCase()}
                                    </span>
                                </div>
                                <h3 className="season-title text-xl font-bold text-gray-900 mb-2">{attributes.name}</h3>
                            </div>
                            
                            <div className="season-content flex gap-4">
                                {attributes.poster_path && (
                                    <div className="season-poster flex-shrink-0">
                                        <img 
                                            src={attributes.poster_path.startsWith('/') ? `https://image.tmdb.org/t/p/w200${attributes.poster_path}` : attributes.poster_path}
                                            alt={attributes.name}
                                            className="w-24 h-36 object-cover rounded-lg shadow-md"
                                        />
                                    </div>
                                )}
                                
                                <div className="season-details flex-1">
                                    <div className="season-meta grid grid-cols-2 gap-4 mb-4 text-sm">
                                        {attributes.episode_count > 0 && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Episodes:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{attributes.episode_count}</span>
                                            </div>
                                        )}
                                        {attributes.air_date && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Air Date:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{new Date(attributes.air_date).toLocaleDateString()}</span>
                                            </div>
                                        )}
                                        {attributes.season_type !== 'regular' && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Type:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{attributes.season_type.replace('_', ' ')}</span>
                                            </div>
                                        )}
                                        {attributes.vote_average > 0 && (
                                            <div className="meta-item">
                                                <span className="meta-label font-medium text-gray-600 block">{__('Rating:', 'tmu-theme')}</span>
                                                <span className="meta-value text-gray-900">{attributes.vote_average}/10</span>
                                            </div>
                                        )}
                                    </div>
                                    
                                    {attributes.overview && (
                                        <div className="season-overview">
                                            <h4 className="font-medium text-gray-700 mb-2">{__('Overview:', 'tmu-theme')}</h4>
                                            <p className="text-gray-600 text-sm leading-relaxed">{attributes.overview}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            {Object.keys(validationErrors).length > 0 && (
                                <div className="validation-errors mt-4 p-3 bg-red-50 border border-red-200 rounded">
                                    <div className="text-red-700 text-sm font-medium mb-1">{__('Please fix the following errors:', 'tmu-theme')}</div>
                                    <ul className="text-red-600 text-xs list-disc list-inside">
                                        {Object.values(validationErrors).map((error, index) => (
                                            <li key={index}>{error}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="playlist-video"
                            label={__('Season Metadata', 'tmu-theme')}
                            instructions={__('Configure season metadata in the block settings panel.', 'tmu-theme')}
                        />
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-season-metadata">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

export default SeasonMetadataBlock;