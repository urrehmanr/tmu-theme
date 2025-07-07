/**
 * People Metadata Block Component
 * 
 * React component for people metadata block editor interface
 * with comprehensive person data fields.
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
 * Register People Metadata Block
 */
registerBlockType('tmu/people-metadata', {
    title: __('People Metadata', 'tmu-theme'),
    description: __('Comprehensive person metadata management with TMDB integration', 'tmu-theme'),
    icon: 'admin-users',
    category: 'tmu-blocks',
    keywords: [
        __('people', 'tmu-theme'),
        __('person', 'tmu-theme'),
        __('actor', 'tmu-theme'),
        __('director', 'tmu-theme'),
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
        also_known_as: { type: 'array', default: [] },
        biography: { type: 'string' },
        birthday: { type: 'string' },
        deathday: { type: 'string' },
        gender: { 
            type: 'number',
            default: 0
        },
        place_of_birth: { type: 'string' },
        profile_path: { type: 'string' },
        homepage: { type: 'string' },
        tmdb_popularity: { type: 'number' },
        known_for_department: { type: 'string' },
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
                const response = await fetch(`/wp-json/tmu/v1/tmdb/person/${tmdbId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Auto-populate attributes from TMDB data
                const mappedData = {
                    tmdb_id: data.id,
                    imdb_id: data.imdb_id,
                    name: data.name,
                    also_known_as: data.also_known_as || [],
                    biography: data.biography,
                    birthday: data.birthday,
                    deathday: data.deathday,
                    gender: data.gender,
                    place_of_birth: data.place_of_birth,
                    profile_path: data.profile_path,
                    homepage: data.homepage,
                    tmdb_popularity: data.popularity,
                    known_for_department: data.known_for_department,
                    adult: data.adult,
                };
                
                setAttributes(mappedData);
                setNotice({
                    type: 'success',
                    message: __('Person data fetched successfully from TMDB', 'tmu-theme')
                });
                
            } catch (error) {
                console.error('Error fetching TMDB data:', error);
                setNotice({
                    type: 'error',
                    message: __('Failed to fetch person data from TMDB. Please check the ID and try again.', 'tmu-theme')
                });
            } finally {
                setIsLoading(false);
            }
        };
        
        const genderOptions = [
            { label: __('Not specified', 'tmu-theme'), value: 0 },
            { label: __('Female', 'tmu-theme'), value: 1 },
            { label: __('Male', 'tmu-theme'), value: 2 },
            { label: __('Non-binary', 'tmu-theme'), value: 3 },
        ];
        
        const departmentOptions = [
            { label: __('Acting', 'tmu-theme'), value: 'Acting' },
            { label: __('Directing', 'tmu-theme'), value: 'Directing' },
            { label: __('Writing', 'tmu-theme'), value: 'Writing' },
            { label: __('Production', 'tmu-theme'), value: 'Production' },
            { label: __('Camera', 'tmu-theme'), value: 'Camera' },
            { label: __('Editing', 'tmu-theme'), value: 'Editing' },
            { label: __('Sound', 'tmu-theme'), value: 'Sound' },
            { label: __('Art', 'tmu-theme'), value: 'Art' },
            { label: __('Costume & Make-Up', 'tmu-theme'), value: 'Costume & Make-Up' },
            { label: __('Visual Effects', 'tmu-theme'), value: 'Visual Effects' },
            { label: __('Crew', 'tmu-theme'), value: 'Crew' },
        ];
        
        const getGenderLabel = (gender) => {
            const option = genderOptions.find(opt => opt.value === gender);
            return option ? option.label : __('Not specified', 'tmu-theme');
        };
        
        const calculateAge = (birthday, deathday = null) => {
            if (!birthday) return null;
            const birthDate = new Date(birthday);
            const endDate = deathday ? new Date(deathday) : new Date();
            const age = endDate.getFullYear() - birthDate.getFullYear();
            const monthDiff = endDate.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && endDate.getDate() < birthDate.getDate())) {
                return age - 1;
            }
            return age;
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('TMDB Integration', 'tmu-theme')} initialOpen={true}>
                        <NumberControl
                            label={__('TMDB ID', 'tmu-theme')}
                            value={attributes.tmdb_id || ''}
                            onChange={(value) => setAttributes({ tmdb_id: parseInt(value) || 0 })}
                            help={__('Enter the TMDB ID to auto-fetch person data', 'tmu-theme')}
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
                            label={__('Also Known As', 'tmu-theme')}
                            value={Array.isArray(attributes.also_known_as) ? attributes.also_known_as.join(', ') : ''}
                            onChange={(value) => setAttributes({ 
                                also_known_as: value ? value.split(',').map(name => name.trim()) : []
                            })}
                            help={__('Separate multiple names with commas', 'tmu-theme')}
                        />
                        <TextareaControl
                            label={__('Biography', 'tmu-theme')}
                            value={attributes.biography || ''}
                            onChange={(value) => setAttributes({ biography: value })}
                            rows={6}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Personal Details', 'tmu-theme')} initialOpen={false}>
                        <TextControl
                            label={__('Birthday', 'tmu-theme')}
                            type="date"
                            value={attributes.birthday || ''}
                            onChange={(value) => setAttributes({ birthday: value })}
                        />
                        <TextControl
                            label={__('Date of Death', 'tmu-theme')}
                            type="date"
                            value={attributes.deathday || ''}
                            onChange={(value) => setAttributes({ deathday: value })}
                        />
                        <SelectControl
                            label={__('Gender', 'tmu-theme')}
                            value={attributes.gender || 0}
                            options={genderOptions}
                            onChange={(value) => setAttributes({ gender: parseInt(value) })}
                        />
                        <TextControl
                            label={__('Place of Birth', 'tmu-theme')}
                            value={attributes.place_of_birth || ''}
                            onChange={(value) => setAttributes({ place_of_birth: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Professional Details', 'tmu-theme')} initialOpen={false}>
                        <SelectControl
                            label={__('Known For Department', 'tmu-theme')}
                            value={attributes.known_for_department || 'Acting'}
                            options={departmentOptions}
                            onChange={(value) => setAttributes({ known_for_department: value })}
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
                            help={__('Format: nm1234567', 'tmu-theme')}
                        />
                        <TextControl
                            label={__('Homepage', 'tmu-theme')}
                            type="url"
                            value={attributes.homepage || ''}
                            onChange={(value) => setAttributes({ homepage: value })}
                        />
                        <TextControl
                            label={__('Profile Image Path', 'tmu-theme')}
                            value={attributes.profile_path || ''}
                            onChange={(value) => setAttributes({ profile_path: value })}
                            help={__('TMDB profile path (e.g., /path/to/profile.jpg)', 'tmu-theme')}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Content Flags', 'tmu-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Adult Content', 'tmu-theme')}
                            checked={attributes.adult || false}
                            onChange={(value) => setAttributes({ adult: value })}
                            help={__('Mark if this person works in adult content', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-people-metadata-block bg-white border border-gray-200 rounded-lg p-6">
                    {attributes.name ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex items-start gap-4 mb-4">
                                {attributes.profile_path && (
                                    <img 
                                        src={`https://image.tmdb.org/t/p/w200${attributes.profile_path}`}
                                        alt={attributes.name}
                                        className="w-24 h-32 object-cover rounded-md shadow-md"
                                    />
                                )}
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">
                                        {attributes.name}
                                    </h3>
                                    {attributes.also_known_as && attributes.also_known_as.length > 0 && (
                                        <p className="text-gray-600 italic mb-2">
                                            {__('Also known as:', 'tmu-theme')} {attributes.also_known_as.slice(0, 2).join(', ')}
                                            {attributes.also_known_as.length > 2 && '...'}
                                        </p>
                                    )}
                                    {attributes.known_for_department && (
                                        <p className="text-gray-700 font-medium mb-2">
                                            {attributes.known_for_department}
                                        </p>
                                    )}
                                    {attributes.place_of_birth && (
                                        <p className="text-gray-600 mb-2">
                                            <span className="font-medium">{__('Born:', 'tmu-theme')}</span> {attributes.place_of_birth}
                                        </p>
                                    )}
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                {attributes.birthday && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Birthday', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {new Date(attributes.birthday).toLocaleDateString()}
                                            {calculateAge(attributes.birthday, attributes.deathday) && (
                                                <div className="text-xs text-gray-500">
                                                    {calculateAge(attributes.birthday, attributes.deathday)} {__('years', 'tmu-theme')}
                                                    {attributes.deathday && ' ' + __('old', 'tmu-theme')}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                                {attributes.deathday && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Died', 'tmu-theme')}</div>
                                        <div className="font-semibold">
                                            {new Date(attributes.deathday).toLocaleDateString()}
                                        </div>
                                    </div>
                                )}
                                {attributes.gender > 0 && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Gender', 'tmu-theme')}</div>
                                        <div className="font-semibold">{getGenderLabel(attributes.gender)}</div>
                                    </div>
                                )}
                                {attributes.tmdb_popularity && (
                                    <div className="text-center p-3 bg-gray-50 rounded">
                                        <div className="text-sm text-gray-600">{__('Popularity', 'tmu-theme')}</div>
                                        <div className="font-semibold">{Math.round(attributes.tmdb_popularity)}</div>
                                    </div>
                                )}
                            </div>
                            
                            {attributes.biography && (
                                <div className="mb-4">
                                    <h4 className="font-semibold text-gray-900 mb-2">{__('Biography', 'tmu-theme')}</h4>
                                    <p className="text-gray-700 leading-relaxed">
                                        {attributes.biography.length > 300 
                                            ? attributes.biography.substring(0, 300) + '...'
                                            : attributes.biography
                                        }
                                    </p>
                                </div>
                            )}
                            
                            <div className="flex flex-wrap gap-2">
                                {attributes.known_for_department && (
                                    <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                        {attributes.known_for_department}
                                    </span>
                                )}
                                {attributes.adult && (
                                    <span className="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                                        {__('Adult Content', 'tmu-theme')}
                                    </span>
                                )}
                                {attributes.deathday && (
                                    <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                        {__('Deceased', 'tmu-theme')}
                                    </span>
                                )}
                            </div>
                        </div>
                    ) : (
                        <Placeholder
                            icon="admin-users"
                            label={__('People Metadata', 'tmu-theme')}
                            instructions={__('Configure person metadata in the block settings panel or fetch data using TMDB ID.', 'tmu-theme')}
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