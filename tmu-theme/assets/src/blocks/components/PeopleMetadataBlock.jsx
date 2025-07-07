/**
 * People Metadata Block Component
 * 
 * Comprehensive React component for people/celebrity metadata management
 * with TMDB integration and biography information.
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

const PeopleMetadataBlock = {
    title: __('People Metadata', 'tmu-theme'),
    icon: 'admin-users',
    category: 'tmu-blocks',
    description: __('Comprehensive people/celebrity metadata management with TMDB integration', 'tmu-theme'),
    keywords: [
        __('people', 'tmu-theme'),
        __('person', 'tmu-theme'),
        __('celebrity', 'tmu-theme'),
        __('actor', 'tmu-theme'),
        __('director', 'tmu-theme'),
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
        known_for_department: { type: 'string', default: '' },
        gender: { type: 'number', default: 0 },
        birthday: { type: 'string', default: '' },
        deathday: { type: 'string', default: '' },
        place_of_birth: { type: 'string', default: '' },
        
        // Biography & Career
        biography: { type: 'string', default: '' },
        also_known_as: { type: 'array', default: [] },
        adult: { type: 'boolean', default: false },
        
        // Media & Links
        profile_path: { type: 'string', default: '' },
        homepage: { type: 'string', default: '' },
        
        // TMDB Data
        tmdb_popularity: { type: 'number', default: null },
        
        // Extended Information
        external_ids: { type: 'object', default: null },
        images: { type: 'object', default: null },
        movie_credits: { type: 'object', default: null },
        tv_credits: { type: 'object', default: null },
        combined_credits: { type: 'object', default: null },
        
        // Local Data
        featured: { type: 'boolean', default: false },
        trending: { type: 'boolean', default: false },
        last_tmdb_sync: { type: 'string', default: '' }
    },

    edit: ({ attributes, setAttributes, clientId }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [tmdbData, setTmdbData] = useState(null);
        const [error, setError] = useState(null);
        const [lastFetchedId, setLastFetchedId] = useState(null);

        const blockProps = useBlockProps({
            className: 'tmu-people-metadata-block'
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
         * Fetch person data from TMDB API
         */
        const fetchTmdbData = async (tmdbId) => {
            if (!tmdbId || isLoading) return;

            setIsLoading(true);
            setError(null);

            try {
                const response = await apiFetch({
                    path: `/tmu/v1/tmdb/person/${tmdbId}`,
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
                    setError(__('Person not found in TMDB database.', 'tmu-theme'));
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
            if (data.known_for_department) updates.known_for_department = data.known_for_department;
            if (typeof data.gender === 'number') updates.gender = data.gender;
            if (data.birthday) updates.birthday = data.birthday;
            if (data.deathday) updates.deathday = data.deathday;
            if (data.place_of_birth) updates.place_of_birth = data.place_of_birth;

            // Biography & Career
            if (data.biography) updates.biography = data.biography;
            if (data.also_known_as) updates.also_known_as = data.also_known_as;
            if (typeof data.adult === 'boolean') updates.adult = data.adult;

            // Media & Links
            if (data.profile_path) updates.profile_path = data.profile_path;
            if (data.homepage) updates.homepage = data.homepage;

            // TMDB Data
            if (data.popularity) updates.tmdb_popularity = data.popularity;

            // External IDs
            if (data.external_ids?.imdb_id) updates.imdb_id = data.external_ids.imdb_id;

            // Complex Data
            if (data.external_ids) updates.external_ids = data.external_ids;
            if (data.images) updates.images = data.images;
            if (data.movie_credits) updates.movie_credits = data.movie_credits;
            if (data.tv_credits) updates.tv_credits = data.tv_credits;
            if (data.combined_credits) updates.combined_credits = data.combined_credits;

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
         * Get TMDB image URL
         */
        const getTmdbImageUrl = (path, size = 'w300') => {
            return path ? `https://image.tmdb.org/t/p/${size}${path}` : '';
        };

        /**
         * Calculate age from birthday
         */
        const calculateAge = (birthday, deathday = null) => {
            if (!birthday) return '';
            
            const birth = new Date(birthday);
            const now = deathday ? new Date(deathday) : new Date();
            const age = Math.floor((now - birth) / (365.25 * 24 * 60 * 60 * 1000));
            
            return deathday ? `${age} (at time of death)` : `${age} years old`;
        };

        const genderOptions = [
            { label: __('Not specified', 'tmu-theme'), value: 0 },
            { label: __('Female', 'tmu-theme'), value: 1 },
            { label: __('Male', 'tmu-theme'), value: 2 },
            { label: __('Non-binary', 'tmu-theme'), value: 3 }
        ];

        const departmentOptions = [
            { label: __('Acting', 'tmu-theme'), value: 'Acting' },
            { label: __('Directing', 'tmu-theme'), value: 'Directing' },
            { label: __('Production', 'tmu-theme'), value: 'Production' },
            { label: __('Writing', 'tmu-theme'), value: 'Writing' },
            { label: __('Camera', 'tmu-theme'), value: 'Camera' },
            { label: __('Editing', 'tmu-theme'), value: 'Editing' },
            { label: __('Sound', 'tmu-theme'), value: 'Sound' },
            { label: __('Art', 'tmu-theme'), value: 'Art' },
            { label: __('Costume & Make-Up', 'tmu-theme'), value: 'Costume & Make-Up' },
            { label: __('Visual Effects', 'tmu-theme'), value: 'Visual Effects' },
            { label: __('Crew', 'tmu-theme'), value: 'Crew' }
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
                            help={__('Enter the TMDB person ID to fetch metadata automatically.', 'tmu-theme')}
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
                            label={__('Name', 'tmu-theme')}
                            value={attributes.name}
                            onChange={(value) => setAttributes({ name: value })}
                            help={__('Person name as displayed on the site.', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Known For Department', 'tmu-theme')}
                            value={attributes.known_for_department}
                            options={[{ label: __('Select Department', 'tmu-theme'), value: '' }, ...departmentOptions]}
                            onChange={(value) => setAttributes({ known_for_department: value })}
                            help={__('Primary department or profession.', 'tmu-theme')}
                        />
                        
                        <SelectControl
                            label={__('Gender', 'tmu-theme')}
                            value={attributes.gender}
                            options={genderOptions}
                            onChange={(value) => setAttributes({ gender: parseInt(value) })}
                            help={__('Person gender.', 'tmu-theme')}
                        />
                        
                        <BaseControl
                            label={__('Birthday', 'tmu-theme')}
                            help={__('Person birth date.', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.birthday}
                                onChange={(e) => setAttributes({ birthday: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <BaseControl
                            label={__('Death Date', 'tmu-theme')}
                            help={__('Person death date (if applicable).', 'tmu-theme')}
                        >
                            <input
                                type="date"
                                value={attributes.deathday}
                                onChange={(e) => setAttributes({ deathday: e.target.value })}
                                className="components-text-control__input"
                            />
                        </BaseControl>
                        
                        <TextControl
                            label={__('Place of Birth', 'tmu-theme')}
                            value={attributes.place_of_birth}
                            onChange={(value) => setAttributes({ place_of_birth: value })}
                            help={__('Person birthplace.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Biography & Career Panel */}
                    <PanelBody
                        title={__('Biography & Career', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <TextareaControl
                            label={__('Biography', 'tmu-theme')}
                            value={attributes.biography}
                            onChange={(value) => setAttributes({ biography: value })}
                            rows={6}
                            help={__('Person biography or career summary.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Also Known As', 'tmu-theme')}
                            value={Array.isArray(attributes.also_known_as) ? attributes.also_known_as.join(', ') : ''}
                            onChange={(value) => {
                                const names = value.split(',').map(v => v.trim()).filter(v => v);
                                setAttributes({ also_known_as: names });
                            }}
                            help={__('Alternative names (comma-separated).', 'tmu-theme')}
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
                            help={__('IMDB person ID (e.g., nm1234567).', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Homepage', 'tmu-theme')}
                            type="url"
                            value={attributes.homepage}
                            onChange={(value) => setAttributes({ homepage: value })}
                            help={__('Official website URL.', 'tmu-theme')}
                        />
                        
                        <TextControl
                            label={__('Profile Path', 'tmu-theme')}
                            value={attributes.profile_path}
                            onChange={(value) => setAttributes({ profile_path: value })}
                            help={__('TMDB profile image path.', 'tmu-theme')}
                        />
                    </PanelBody>

                    {/* Popularity Panel */}
                    <PanelBody
                        title={__('Popularity', 'tmu-theme')}
                        initialOpen={false}
                    >
                        <NumberControl
                            label={__('TMDB Popularity', 'tmu-theme')}
                            value={attributes.tmdb_popularity || ''}
                            onChange={(value) => setAttributes({ tmdb_popularity: parseFloat(value) || null })}
                            step={0.1}
                            help={__('TMDB popularity score.', 'tmu-theme')}
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
                            help={__('Mark if this person works in adult content.', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('Featured', 'tmu-theme')}
                            checked={attributes.featured}
                            onChange={(value) => setAttributes({ featured: value })}
                            help={__('Mark as featured person.', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('Trending', 'tmu-theme')}
                            checked={attributes.trending}
                            onChange={(value) => setAttributes({ trending: value })}
                            help={__('Mark as trending person.', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    {attributes.name ? (
                        <div className="tmu-metadata-preview">
                            <div className="flex gap-4">
                                {attributes.profile_path && (
                                    <div className="flex-shrink-0">
                                        <img 
                                            src={getTmdbImageUrl(attributes.profile_path, 'w185')}
                                            alt={attributes.name}
                                            className="w-24 h-auto rounded shadow-lg"
                                        />
                                    </div>
                                )}
                                
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold mb-2 text-gray-900">
                                        {attributes.name}
                                    </h3>
                                    
                                    {attributes.known_for_department && (
                                        <p className="text-sm text-blue-600 mb-2 font-medium">
                                            {attributes.known_for_department}
                                        </p>
                                    )}
                                    
                                    <div className="grid grid-cols-1 gap-2 text-sm">
                                        {attributes.birthday && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Born:</span>
                                                <span className="ml-1">
                                                    {new Date(attributes.birthday).toLocaleDateString()}
                                                    {calculateAge(attributes.birthday, attributes.deathday) && (
                                                        <span className="text-gray-600 ml-1">
                                                            ({calculateAge(attributes.birthday, attributes.deathday)})
                                                        </span>
                                                    )}
                                                </span>
                                            </div>
                                        )}
                                        
                                        {attributes.deathday && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Died:</span>
                                                <span className="ml-1">{new Date(attributes.deathday).toLocaleDateString()}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.place_of_birth && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Birthplace:</span>
                                                <span className="ml-1">{attributes.place_of_birth}</span>
                                            </div>
                                        )}
                                        
                                        {attributes.tmdb_popularity && (
                                            <div>
                                                <span className="font-semibold text-gray-700">Popularity:</span>
                                                <span className="ml-1">{attributes.tmdb_popularity.toFixed(1)}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                            
                            {attributes.biography && (
                                <div className="mt-4 p-3 bg-gray-50 rounded">
                                    <p className="text-sm text-gray-700 leading-relaxed">
                                        {attributes.biography.length > 300 
                                            ? attributes.biography.substring(0, 300) + '...' 
                                            : attributes.biography
                                        }
                                    </p>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="admin-users"
                            label={__('People Metadata', 'tmu-theme')}
                            instructions={__('Configure people metadata in the block settings panel. Start by entering a TMDB ID to automatically fetch person information.', 'tmu-theme')}
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

export default PeopleMetadataBlock;