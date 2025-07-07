/**
 * Drama Episode Metadata Block
 * 
 * React component for drama episode metadata block editor interface.
 * Handles drama episode-specific data fields with comprehensive form controls.
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
    Spinner
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const DramaEpisodeMetadataBlock = {
    title: __('Drama Episode Metadata', 'tmu-theme'),
    icon: 'video-alt2',
    category: 'tmu-blocks',
    description: __('Individual drama episode metadata management', 'tmu-theme'),
    keywords: [__('drama', 'tmu-theme'), __('episode', 'tmu-theme'), __('metadata', 'tmu-theme')],
    supports: {
        html: false,
        multiple: false,
        reusable: false,
    },
    attributes: {
        drama: { type: 'number', default: 0 },
        episode_number: { type: 'number', default: 1 },
        name: { type: 'string', default: '' },
        overview: { type: 'string', default: '' },
        air_date: { type: 'string', default: '' },
        episode_type: { type: 'string', default: 'standard' },
        runtime: { type: 'number', default: 0 },
        still_path: { type: 'string', default: '' },
        vote_average: { type: 'number', default: 0 },
        vote_count: { type: 'number', default: 0 },
        crew: { type: 'array', default: [] },
        guest_stars: { type: 'array', default: [] },
        special_features: { type: 'array', default: [] },
        drama_channel: { type: 'string', default: '' },
        original_air_date: { type: 'string', default: '' },
        episode_summary: { type: 'string', default: '' },
        rating: { type: 'string', default: 'TV-14' },
        language: { type: 'string', default: '' },
        subtitles_available: { type: 'boolean', default: false },
        behind_scenes: { type: 'string', default: '' },
        trivia: { type: 'array', default: [] },
        awards: { type: 'array', default: [] },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [dramaOptions, setDramaOptions] = useState([]);
        const [validationErrors, setValidationErrors] = useState({});
        
        // Load drama posts for selection
        const dramas = useSelect((select) => {
            return select('core').getEntityRecords('postType', 'drama', {
                per_page: -1,
                status: 'publish',
            });
        }, []);
        
        useEffect(() => {
            if (dramas) {
                const options = [
                    { label: __('Select Drama', 'tmu-theme'), value: 0 },
                    ...dramas.map(drama => ({
                        label: drama.title.rendered,
                        value: drama.id
                    }))
                ];
                setDramaOptions(options);
            }
        }, [dramas]);
        
        // Validation function
        const validateFields = () => {
            const errors = {};
            
            if (!attributes.name.trim()) {
                errors.name = __('Episode name is required', 'tmu-theme');
            }
            
            if (attributes.episode_number < 1) {
                errors.episode_number = __('Episode number must be positive', 'tmu-theme');
            }
            
            if (attributes.runtime < 0) {
                errors.runtime = __('Runtime cannot be negative', 'tmu-theme');
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
        
        const formatRuntime = (minutes) => {
            if (!minutes) return '';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Episode Information', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Drama Series', 'tmu-theme')}
                            value={attributes.drama}
                            options={dramaOptions}
                            onChange={(value) => setAttributes({ drama: parseInt(value) })}
                            help={__('Select the drama series this episode belongs to', 'tmu-theme')}
                        />
                        
                        <NumberControl
                            label={__('Episode Number', 'tmu-theme')}
                            value={attributes.episode_number}
                            onChange={(value) => setAttributes({ episode_number: parseInt(value) || 1 })}
                            min={1}
                            help={validationErrors.episode_number}
                        />
                        
                        <TextControl
                            label={__('Episode Name', 'tmu-theme')}
                            value={attributes.name}
                            onChange={(value) => setAttributes({ name: value })}
                            placeholder={__('Enter episode title', 'tmu-theme')}
                            help={validationErrors.name}
                        />
                        
                        <SelectControl
                            label={__('Episode Type', 'tmu-theme')}
                            value={attributes.episode_type}
                            options={[
                                { label: __('Standard', 'tmu-theme'), value: 'standard' },
                                { label: __('Special', 'tmu-theme'), value: 'special' },
                                { label: __('Finale', 'tmu-theme'), value: 'finale' },
                                { label: __('Premiere', 'tmu-theme'), value: 'premiere' },
                                { label: __('Recap', 'tmu-theme'), value: 'recap' },
                            ]}
                            onChange={(value) => setAttributes({ episode_type: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Episode Details', 'tmu-theme')} initialOpen={false}>
                        <TextareaControl
                            label={__('Overview', 'tmu-theme')}
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            placeholder={__('Brief episode description...', 'tmu-theme')}
                            rows={4}
                        />
                        
                        <TextareaControl
                            label={__('Episode Summary', 'tmu-theme')}
                            value={attributes.episode_summary}
                            onChange={(value) => setAttributes({ episode_summary: value })}
                            placeholder={__('Detailed episode summary...', 'tmu-theme')}
                            rows={6}
                        />
                        
                        <NumberControl
                            label={__('Runtime (minutes)', 'tmu-theme')}
                            value={attributes.runtime}
                            onChange={(value) => setAttributes({ runtime: parseInt(value) || 0 })}
                            min={0}
                            help={attributes.runtime ? formatRuntime(attributes.runtime) : validationErrors.runtime}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Air Date & Channel', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Air Date', 'tmu-theme')}
                            type="date"
                            value={attributes.air_date}
                            onChange={(value) => setAttributes({ air_date: value })}
                        />
                        
                        <TextControl
                            label={__('Original Air Date', 'tmu-theme')}
                            type="date"
                            value={attributes.original_air_date}
                            onChange={(value) => setAttributes({ original_air_date: value })}
                            help={__('Original broadcast date if different from air date', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Drama Channel', 'tmu-theme')}
                            value={attributes.drama_channel}
                            onChange={(value) => setAttributes({ drama_channel: value })}
                            placeholder={__('KBS, SBS, MBC, tvN, etc.', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Content Rating', 'tmu-theme')}
                            value={attributes.rating}
                            options={[
                                { label: 'All Ages', value: 'All' },
                                { label: 'TV-G', value: 'TV-G' },
                                { label: 'TV-PG', value: 'TV-PG' },
                                { label: 'TV-14', value: 'TV-14' },
                                { label: 'TV-MA', value: 'TV-MA' },
                                { label: '12+', value: '12+' },
                                { label: '15+', value: '15+' },
                                { label: '19+', value: '19+' },
                            ]}
                            onChange={(value) => setAttributes({ rating: value })}
                        />
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
                    </PanelBody>
                    
                    <PanelBody title={__('Media & Language', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Still Image Path', 'tmu-theme')}
                            value={attributes.still_path}
                            onChange={(value) => setAttributes({ still_path: value })}
                            placeholder={__('Episode still image URL or path', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Language', 'tmu-theme')}
                            value={attributes.language}
                            onChange={(value) => setAttributes({ language: value })}
                            placeholder={__('Korean, English, etc.', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('Subtitles Available', 'tmu-theme')}
                            checked={attributes.subtitles_available}
                            onChange={(value) => setAttributes({ subtitles_available: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Additional Content', 'tmu-theme')} initialOpen={false}>
                        <TextareaControl
                            label={__('Behind the Scenes', 'tmu-theme')}
                            value={attributes.behind_scenes}
                            onChange={(value) => setAttributes({ behind_scenes: value })}
                            placeholder={__('Behind the scenes information...', 'tmu-theme')}
                            rows={4}
                        />
                        
                        <TextControl
                            label={__('Trivia (JSON)', 'tmu-theme')}
                            value={JSON.stringify(attributes.trivia)}
                            onChange={(value) => {
                                try {
                                    const parsed = JSON.parse(value);
                                    setAttributes({ trivia: Array.isArray(parsed) ? parsed : [] });
                                } catch (e) {
                                    // Invalid JSON, ignore
                                }
                            }}
                            placeholder={__('["Trivia item 1", "Trivia item 2"]', 'tmu-theme')}
                            help={__('Enter trivia as JSON array', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-drama-episode-metadata-block">
                    {attributes.name ? (
                        <div className="tmu-episode-preview bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <div className="episode-header mb-4">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="episode-badge bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                        {__('Episode', 'tmu-theme')} {attributes.episode_number}
                                    </span>
                                    {attributes.episode_type !== 'standard' && (
                                        <span className={`type-badge text-xs font-medium px-2.5 py-0.5 rounded ${
                                            attributes.episode_type === 'special' ? 'bg-yellow-100 text-yellow-800' :
                                            attributes.episode_type === 'finale' ? 'bg-red-100 text-red-800' :
                                            attributes.episode_type === 'premiere' ? 'bg-green-100 text-green-800' :
                                            'bg-blue-100 text-blue-800'
                                        }`}>
                                            {attributes.episode_type.toUpperCase()}
                                        </span>
                                    )}
                                </div>
                                <h3 className="episode-title text-xl font-bold text-gray-900 mb-2">{attributes.name}</h3>
                            </div>
                            
                            {attributes.still_path && (
                                <div className="episode-still mb-4">
                                    <img 
                                        src={attributes.still_path}
                                        alt={attributes.name}
                                        className="w-full h-48 object-cover rounded-lg"
                                    />
                                </div>
                            )}
                            
                            <div className="episode-meta grid grid-cols-2 gap-4 mb-4 text-sm">
                                {attributes.air_date && (
                                    <div className="meta-item">
                                        <span className="meta-label font-medium text-gray-600 block">{__('Air Date:', 'tmu-theme')}</span>
                                        <span className="meta-value text-gray-900">{new Date(attributes.air_date).toLocaleDateString()}</span>
                                    </div>
                                )}
                                {attributes.runtime > 0 && (
                                    <div className="meta-item">
                                        <span className="meta-label font-medium text-gray-600 block">{__('Runtime:', 'tmu-theme')}</span>
                                        <span className="meta-value text-gray-900">{formatRuntime(attributes.runtime)}</span>
                                    </div>
                                )}
                                {attributes.drama_channel && (
                                    <div className="meta-item">
                                        <span className="meta-label font-medium text-gray-600 block">{__('Channel:', 'tmu-theme')}</span>
                                        <span className="meta-value text-gray-900">{attributes.drama_channel}</span>
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
                                <div className="episode-overview">
                                    <h4 className="font-medium text-gray-700 mb-2">{__('Overview:', 'tmu-theme')}</h4>
                                    <p className="text-gray-600 text-sm leading-relaxed">{attributes.overview}</p>
                                </div>
                            )}
                            
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
                            icon="video-alt2"
                            label={__('Drama Episode Metadata', 'tmu-theme')}
                            instructions={__('Configure drama episode metadata in the block settings panel.', 'tmu-theme')}
                        />
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-drama-episode-metadata">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

export default DramaEpisodeMetadataBlock;