/**
 * Episode Metadata Block
 * 
 * Universal React component for episode management.
 * Handles both TV episodes and drama episodes with dynamic type switching.
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
    DateTimePicker,
    __experimentalNumberControl as ExperimentalNumberControl
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Universal Episode Metadata Block
 * 
 * Handles both TV episodes and drama episodes with intelligent type detection
 */
const EpisodeMetadataBlock = {
    title: __('Episode Metadata', 'tmu-theme'),
    icon: 'playlist-video',
    category: 'tmu-blocks',
    description: __('Universal episode metadata management for TV shows and drama series', 'tmu-theme'),
    keywords: [__('episode', 'tmu-theme'), __('tv', 'tmu-theme'), __('drama', 'tmu-theme'), __('series', 'tmu-theme'), __('season', 'tmu-theme')],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
    },
    attributes: {
        // Episode Type & Identification
        episode_type: { type: 'string', default: 'tv' }, // 'tv' or 'drama'
        parent_series_id: { type: 'number', default: 0 },
        season_number: { type: 'number', default: 1 },
        episode_number: { type: 'number', default: 1 },
        absolute_number: { type: 'number', default: 0 },
        
        // Basic Episode Information
        name: { type: 'string', default: '' },
        original_name: { type: 'string', default: '' },
        overview: { type: 'string', default: '' },
        air_date: { type: 'string', default: '' },
        runtime: { type: 'number', default: 0 },
        
        // Episode Classification
        episode_classification: { type: 'string', default: 'regular' }, // regular, special, pilot, finale, recap
        content_rating: { type: 'string', default: 'TV-14' },
        language: { type: 'string', default: 'en' },
        
        // Production Information
        production_code: { type: 'string', default: '' },
        directed_by: { type: 'array', default: [] },
        written_by: { type: 'array', default: [] },
        guest_stars: { type: 'array', default: [] },
        
        // Media & Assets
        still_path: { type: 'string', default: '' },
        video_url: { type: 'string', default: '' },
        trailer_url: { type: 'string', default: '' },
        
        // Ratings & Analytics
        vote_average: { type: 'number', default: 0 },
        vote_count: { type: 'number', default: 0 },
        popularity: { type: 'number', default: 0 },
        view_count: { type: 'number', default: 0 },
        
        // External IDs & Sync
        tmdb_id: { type: 'number', default: 0 },
        imdb_id: { type: 'string', default: '' },
        tvdb_id: { type: 'number', default: 0 },
        
        // Drama-specific fields
        channel: { type: 'string', default: '' },
        broadcast_network: { type: 'string', default: '' },
        original_broadcast_date: { type: 'string', default: '' },
        drama_special_features: { type: 'array', default: [] },
        
        // Advanced Settings
        is_finale: { type: 'boolean', default: false },
        is_premiere: { type: 'boolean', default: false },
        is_special: { type: 'boolean', default: false },
        is_recap: { type: 'boolean', default: false },
        has_post_credits: { type: 'boolean', default: false },
        
        // Accessibility & Localization
        subtitles_available: { type: 'array', default: [] },
        audio_languages: { type: 'array', default: [] },
        accessibility_features: { type: 'array', default: [] },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [parentSeries, setParentSeries] = useState([]);
        const [validationErrors, setValidationErrors] = useState({});
        const [isLoadingTMDB, setIsLoadingTMDB] = useState(false);
        
        // Get available parent series based on episode type
        const seriesData = useSelect((select) => {
            const postType = attributes.episode_type === 'tv' ? 'tv' : 'drama';
            return select('core').getEntityRecords('postType', postType, {
                per_page: -1,
                status: 'publish',
            });
        }, [attributes.episode_type]);
        
        useEffect(() => {
            if (seriesData) {
                const seriesOptions = [
                    { label: __('Select Series', 'tmu-theme'), value: 0 },
                    ...seriesData.map(series => ({
                        label: series.title.rendered,
                        value: series.id
                    }))
                ];
                setParentSeries(seriesOptions);
            }
        }, [seriesData]);
        
        // Validate episode data
        const validateEpisodeData = () => {
            const errors = {};
            
            if (!attributes.name.trim()) {
                errors.name = __('Episode name is required', 'tmu-theme');
            }
            
            if (!attributes.parent_series_id) {
                errors.parent_series_id = __('Parent series is required', 'tmu-theme');
            }
            
            if (attributes.season_number < 1) {
                errors.season_number = __('Season number must be at least 1', 'tmu-theme');
            }
            
            if (attributes.episode_number < 1) {
                errors.episode_number = __('Episode number must be at least 1', 'tmu-theme');
            }
            
            if (attributes.runtime < 0) {
                errors.runtime = __('Runtime cannot be negative', 'tmu-theme');
            }
            
            setValidationErrors(errors);
            return Object.keys(errors).length === 0;
        };
        
        // Sync with TMDB
        const syncWithTMDB = async () => {
            if (!attributes.tmdb_id || !attributes.parent_series_id) {
                alert(__('TMDB ID and parent series are required for sync', 'tmu-theme'));
                return;
            }
            
            setIsLoadingTMDB(true);
            try {
                const endpoint = attributes.episode_type === 'tv' 
                    ? `/wp-json/tmu/v1/tmdb/tv/${attributes.parent_series_id}/season/${attributes.season_number}/episode/${attributes.episode_number}`
                    : `/wp-json/tmu/v1/tmdb/episode/${attributes.tmdb_id}`;
                
                const response = await fetch(endpoint);
                const data = await response.json();
                
                if (data.success) {
                    // Auto-populate fields from TMDB
                    setAttributes({
                        name: data.name || attributes.name,
                        overview: data.overview || attributes.overview,
                        air_date: data.air_date || attributes.air_date,
                        runtime: data.runtime || attributes.runtime,
                        still_path: data.still_path || attributes.still_path,
                        vote_average: data.vote_average || attributes.vote_average,
                        vote_count: data.vote_count || attributes.vote_count,
                        guest_stars: data.guest_stars || attributes.guest_stars,
                        directed_by: data.crew?.filter(c => c.job === 'Director').map(d => d.name) || attributes.directed_by,
                        written_by: data.crew?.filter(c => c.department === 'Writing').map(w => w.name) || attributes.written_by,
                    });
                    
                    alert(__('Episode data synced successfully from TMDB!', 'tmu-theme'));
                } else {
                    alert(__('Failed to sync with TMDB: ', 'tmu-theme') + data.message);
                }
            } catch (error) {
                console.error('TMDB sync error:', error);
                alert(__('Error syncing with TMDB', 'tmu-theme'));
            } finally {
                setIsLoadingTMDB(false);
            }
        };
        
        // Add crew member
        const addCrewMember = (type) => {
            const newMember = prompt(__(`Enter ${type} name:`, 'tmu-theme'));
            if (newMember) {
                const currentList = attributes[type] || [];
                setAttributes({ [type]: [...currentList, newMember.trim()] });
            }
        };
        
        // Remove crew member
        const removeCrewMember = (type, index) => {
            const currentList = attributes[type] || [];
            const updatedList = currentList.filter((_, i) => i !== index);
            setAttributes({ [type]: updatedList });
        };
        
        // Generate episode title suggestion
        const generateEpisodeTitle = () => {
            if (attributes.season_number && attributes.episode_number) {
                const suggestion = `S${attributes.season_number.toString().padStart(2, '0')}E${attributes.episode_number.toString().padStart(2, '0')}`;
                return suggestion;
            }
            return '';
        };
        
        useEffect(() => {
            validateEpisodeData();
        }, [attributes.name, attributes.parent_series_id, attributes.season_number, attributes.episode_number, attributes.runtime]);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Episode Type & Series', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Episode Type', 'tmu-theme')}
                            value={attributes.episode_type}
                            options={[
                                { label: __('TV Show Episode', 'tmu-theme'), value: 'tv' },
                                { label: __('Drama Episode', 'tmu-theme'), value: 'drama' },
                            ]}
                            onChange={(value) => setAttributes({ 
                                episode_type: value,
                                parent_series_id: 0 // Reset series selection when type changes
                            })}
                        />
                        
                        <SelectControl
                            label={attributes.episode_type === 'tv' ? __('TV Series', 'tmu-theme') : __('Drama Series', 'tmu-theme')}
                            value={attributes.parent_series_id}
                            options={parentSeries}
                            onChange={(value) => setAttributes({ parent_series_id: parseInt(value) })}
                            className={validationErrors.parent_series_id ? 'has-error' : ''}
                        />
                        {validationErrors.parent_series_id && (
                            <p className="error-message">{validationErrors.parent_series_id}</p>
                        )}
                        
                        <div className="episode-numbering">
                            <NumberControl
                                label={__('Season Number', 'tmu-theme')}
                                value={attributes.season_number}
                                onChange={(value) => setAttributes({ season_number: parseInt(value) || 1 })}
                                min={1}
                                className={validationErrors.season_number ? 'has-error' : ''}
                            />
                            
                            <NumberControl
                                label={__('Episode Number', 'tmu-theme')}
                                value={attributes.episode_number}
                                onChange={(value) => setAttributes({ episode_number: parseInt(value) || 1 })}
                                min={1}
                                className={validationErrors.episode_number ? 'has-error' : ''}
                            />
                            
                            <NumberControl
                                label={__('Absolute Number', 'tmu-theme')}
                                value={attributes.absolute_number}
                                onChange={(value) => setAttributes({ absolute_number: parseInt(value) || 0 })}
                                min={0}
                                help={__('Overall episode number across all seasons', 'tmu-theme')}
                            />
                        </div>
                        
                        <div className="episode-title-suggestion">
                            <p><strong>{__('Suggested Format:', 'tmu-theme')}</strong> {generateEpisodeTitle()}</p>
                        </div>
                    </PanelBody>
                    
                    <PanelBody title={__('Episode Information', 'tmu-theme')} initialOpen={true}>
                        <TextControl
                            label={__('Episode Name', 'tmu-theme')}
                            value={attributes.name}
                            onChange={(value) => setAttributes({ name: value })}
                            placeholder={__('Enter episode title', 'tmu-theme')}
                            className={validationErrors.name ? 'has-error' : ''}
                        />
                        {validationErrors.name && (
                            <p className="error-message">{validationErrors.name}</p>
                        )}
                        
                        <TextControl
                            label={__('Original Name', 'tmu-theme')}
                            value={attributes.original_name}
                            onChange={(value) => setAttributes({ original_name: value })}
                            placeholder={__('Original language title', 'tmu-theme')}
                        />
                        
                        <TextareaControl
                            label={__('Episode Overview', 'tmu-theme')}
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            placeholder={__('Episode synopsis and description', 'tmu-theme')}
                            rows={4}
                        />
                        
                        <TextControl
                            label={__('Air Date', 'tmu-theme')}
                            type="date"
                            value={attributes.air_date}
                            onChange={(value) => setAttributes({ air_date: value })}
                        />
                        
                        <NumberControl
                            label={__('Runtime (minutes)', 'tmu-theme')}
                            value={attributes.runtime}
                            onChange={(value) => setAttributes({ runtime: parseInt(value) || 0 })}
                            min={0}
                            className={validationErrors.runtime ? 'has-error' : ''}
                        />
                        
                        <SelectControl
                            label={__('Episode Classification', 'tmu-theme')}
                            value={attributes.episode_classification}
                            options={[
                                { label: __('Regular Episode', 'tmu-theme'), value: 'regular' },
                                { label: __('Special Episode', 'tmu-theme'), value: 'special' },
                                { label: __('Pilot Episode', 'tmu-theme'), value: 'pilot' },
                                { label: __('Season Finale', 'tmu-theme'), value: 'finale' },
                                { label: __('Season Premiere', 'tmu-theme'), value: 'premiere' },
                                { label: __('Recap Episode', 'tmu-theme'), value: 'recap' },
                                { label: __('Clip Show', 'tmu-theme'), value: 'clip_show' },
                            ]}
                            onChange={(value) => setAttributes({ episode_classification: value })}
                        />
                        
                        <SelectControl
                            label={__('Content Rating', 'tmu-theme')}
                            value={attributes.content_rating}
                            options={[
                                { label: 'TV-Y', value: 'TV-Y' },
                                { label: 'TV-Y7', value: 'TV-Y7' },
                                { label: 'TV-G', value: 'TV-G' },
                                { label: 'TV-PG', value: 'TV-PG' },
                                { label: 'TV-14', value: 'TV-14' },
                                { label: 'TV-MA', value: 'TV-MA' },
                            ]}
                            onChange={(value) => setAttributes({ content_rating: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Cast & Crew', 'tmu-theme')} initialOpen={false}>
                        <div className="crew-section">
                            <h4>{__('Directors', 'tmu-theme')}</h4>
                            {attributes.directed_by?.map((director, index) => (
                                <div key={index} className="crew-member">
                                    <span>{director}</span>
                                    <Button
                                        isDestructive
                                        isSmall
                                        onClick={() => removeCrewMember('directed_by', index)}
                                    >
                                        {__('Remove', 'tmu-theme')}
                                    </Button>
                                </div>
                            ))}
                            <Button isPrimary isSmall onClick={() => addCrewMember('directed_by')}>
                                {__('Add Director', 'tmu-theme')}
                            </Button>
                        </div>
                        
                        <div className="crew-section">
                            <h4>{__('Writers', 'tmu-theme')}</h4>
                            {attributes.written_by?.map((writer, index) => (
                                <div key={index} className="crew-member">
                                    <span>{writer}</span>
                                    <Button
                                        isDestructive
                                        isSmall
                                        onClick={() => removeCrewMember('written_by', index)}
                                    >
                                        {__('Remove', 'tmu-theme')}
                                    </Button>
                                </div>
                            ))}
                            <Button isPrimary isSmall onClick={() => addCrewMember('written_by')}>
                                {__('Add Writer', 'tmu-theme')}
                            </Button>
                        </div>
                        
                        <div className="crew-section">
                            <h4>{__('Guest Stars', 'tmu-theme')}</h4>
                            {attributes.guest_stars?.map((guest, index) => (
                                <div key={index} className="crew-member">
                                    <span>{guest}</span>
                                    <Button
                                        isDestructive
                                        isSmall
                                        onClick={() => removeCrewMember('guest_stars', index)}
                                    >
                                        {__('Remove', 'tmu-theme')}
                                    </Button>
                                </div>
                            ))}
                            <Button isPrimary isSmall onClick={() => addCrewMember('guest_stars')}>
                                {__('Add Guest Star', 'tmu-theme')}
                            </Button>
                        </div>
                    </PanelBody>
                    
                    <PanelBody title={__('Media & Assets', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Episode Still Image URL', 'tmu-theme')}
                            type="url"
                            value={attributes.still_path}
                            onChange={(value) => setAttributes({ still_path: value })}
                            placeholder={__('https://example.com/episode-still.jpg', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Video URL', 'tmu-theme')}
                            type="url"
                            value={attributes.video_url}
                            onChange={(value) => setAttributes({ video_url: value })}
                            placeholder={__('https://example.com/episode-video.mp4', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Trailer URL', 'tmu-theme')}
                            type="url"
                            value={attributes.trailer_url}
                            onChange={(value) => setAttributes({ trailer_url: value })}
                            placeholder={__('https://example.com/episode-trailer.mp4', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Ratings & Analytics', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('Vote Average', 'tmu-theme')}
                            value={attributes.vote_average}
                            onChange={(value) => setAttributes({ vote_average: parseFloat(value) || 0 })}
                            min={0}
                            max={10}
                            step={0.1}
                        />
                        
                        <NumberControl
                            label={__('Vote Count', 'tmu-theme')}
                            value={attributes.vote_count}
                            onChange={(value) => setAttributes({ vote_count: parseInt(value) || 0 })}
                            min={0}
                        />
                        
                        <NumberControl
                            label={__('View Count', 'tmu-theme')}
                            value={attributes.view_count}
                            onChange={(value) => setAttributes({ view_count: parseInt(value) || 0 })}
                            min={0}
                        />
                        
                        <NumberControl
                            label={__('Popularity Score', 'tmu-theme')}
                            value={attributes.popularity}
                            onChange={(value) => setAttributes({ popularity: parseFloat(value) || 0 })}
                            min={0}
                            step={0.1}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('TMDB Integration', 'tmu-theme')} initialOpen={false}>
                        <NumberControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id}
                            onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || 0 })}
                            min={0}
                        />
                        
                        <TextControl
                            label={__('IMDB ID', 'tmu-theme')}
                            value={attributes.imdb_id}
                            onChange={(value) => setAttributes({ imdb_id: value })}
                            placeholder={__('tt1234567', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('TVDB ID', 'tmu-theme')}
                            value={attributes.tvdb_id}
                            onChange={(value) => setAttributes({ tvdb_id: parseInt(value) || 0 })}
                            min={0}
                        />
                        
                        <Button
                            isPrimary
                            onClick={syncWithTMDB}
                            disabled={!attributes.tmdb_id || isLoadingTMDB}
                        >
                            {isLoadingTMDB ? __('Syncing...', 'tmu-theme') : __('Sync with TMDB', 'tmu-theme')}
                        </Button>
                    </PanelBody>
                    
                    {attributes.episode_type === 'drama' && (
                        <PanelBody title={__('Drama Specific', 'tmu-theme')} initialOpen={false}>
                            <TextControl
                                label={__('Channel', 'tmu-theme')}
                                value={attributes.channel}
                                onChange={(value) => setAttributes({ channel: value })}
                                placeholder={__('Broadcasting channel', 'tmu-theme')}
                            />
                            
                            <TextControl
                                label={__('Broadcast Network', 'tmu-theme')}
                                value={attributes.broadcast_network}
                                onChange={(value) => setAttributes({ broadcast_network: value })}
                                placeholder={__('Original broadcast network', 'tmu-theme')}
                            />
                            
                            <TextControl
                                label={__('Original Broadcast Date', 'tmu-theme')}
                                type="datetime-local"
                                value={attributes.original_broadcast_date}
                                onChange={(value) => setAttributes({ original_broadcast_date: value })}
                            />
                        </PanelBody>
                    )}
                    
                    <PanelBody title={__('Episode Flags', 'tmu-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Season Premiere', 'tmu-theme')}
                            checked={attributes.is_premiere}
                            onChange={(value) => setAttributes({ is_premiere: value })}
                        />
                        
                        <ToggleControl
                            label={__('Season Finale', 'tmu-theme')}
                            checked={attributes.is_finale}
                            onChange={(value) => setAttributes({ is_finale: value })}
                        />
                        
                        <ToggleControl
                            label={__('Special Episode', 'tmu-theme')}
                            checked={attributes.is_special}
                            onChange={(value) => setAttributes({ is_special: value })}
                        />
                        
                        <ToggleControl
                            label={__('Recap Episode', 'tmu-theme')}
                            checked={attributes.is_recap}
                            onChange={(value) => setAttributes({ is_recap: value })}
                        />
                        
                        <ToggleControl
                            label={__('Has Post-Credits Scene', 'tmu-theme')}
                            checked={attributes.has_post_credits}
                            onChange={(value) => setAttributes({ has_post_credits: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-episode-metadata-block">
                    {attributes.name && attributes.parent_series_id ? (
                        <div className="episode-preview">
                            <div className="episode-header">
                                <div className="episode-numbering">
                                    <span className="episode-code">
                                        {generateEpisodeTitle()}
                                    </span>
                                    <span className="episode-type-badge">
                                        {attributes.episode_type.toUpperCase()}
                                    </span>
                                </div>
                                <h3>{attributes.name}</h3>
                                {attributes.original_name && attributes.original_name !== attributes.name && (
                                    <p className="original-name">({attributes.original_name})</p>
                                )}
                            </div>
                            
                            <div className="episode-details">
                                {attributes.still_path && (
                                    <img 
                                        src={attributes.still_path}
                                        alt={attributes.name}
                                        className="episode-still"
                                    />
                                )}
                                
                                <div className="episode-info">
                                    {attributes.air_date && (
                                        <p><strong>{__('Air Date:', 'tmu-theme')}</strong> {new Date(attributes.air_date).toLocaleDateString()}</p>
                                    )}
                                    {attributes.runtime > 0 && (
                                        <p><strong>{__('Runtime:', 'tmu-theme')}</strong> {attributes.runtime} {__('minutes', 'tmu-theme')}</p>
                                    )}
                                    {attributes.vote_average > 0 && (
                                        <p><strong>{__('Rating:', 'tmu-theme')}</strong> {attributes.vote_average}/10</p>
                                    )}
                                    {attributes.episode_classification !== 'regular' && (
                                        <p><strong>{__('Type:', 'tmu-theme')}</strong> {attributes.episode_classification}</p>
                                    )}
                                </div>
                                
                                {attributes.overview && (
                                    <div className="episode-overview">
                                        <p>{attributes.overview.substring(0, 200)}{attributes.overview.length > 200 ? '...' : ''}</p>
                                    </div>
                                )}
                                
                                <div className="episode-flags">
                                    {attributes.is_premiere && <span className="flag premiere">{__('PREMIERE', 'tmu-theme')}</span>}
                                    {attributes.is_finale && <span className="flag finale">{__('FINALE', 'tmu-theme')}</span>}
                                    {attributes.is_special && <span className="flag special">{__('SPECIAL', 'tmu-theme')}</span>}
                                    {attributes.is_recap && <span className="flag recap">{__('RECAP', 'tmu-theme')}</span>}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <Placeholder
                            icon="playlist-video"
                            label={__('Episode Metadata', 'tmu-theme')}
                            instructions={__('Configure episode information in the block settings panel.', 'tmu-theme')}
                        />
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-episode-metadata">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

// Register the universal episode block
registerBlockType('tmu/episode-metadata', EpisodeMetadataBlock);

export { EpisodeMetadataBlock };