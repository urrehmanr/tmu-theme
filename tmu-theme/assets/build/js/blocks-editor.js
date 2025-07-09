/**
 * TMU Theme - Block Editor Scripts
 * 
 * This file contains all Gutenberg editor JavaScript functionality for blocks
 */

(function(wp) {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, MediaUpload, RichText } = wp.blockEditor;
    const { PanelBody, TextControl, SelectControl, ToggleControl, Button, Placeholder } = wp.components;
    const { Fragment } = wp.element;
    const { __ } = wp.i18n;
    
    /**
     * Register all custom blocks
     */
    function registerBlocks() {
        // Movie Metadata Block
        registerBlockType('tmu/movie-metadata', {
            title: __('Movie Metadata', 'tmu'),
            icon: 'video-alt2',
            category: 'tmu-blocks',
            attributes: {
                movieId: {
                    type: 'number',
                    default: 0
                },
                showPoster: {
                    type: 'boolean',
                    default: true
                },
                showTitle: {
                    type: 'boolean',
                    default: true
                },
                showRating: {
                    type: 'boolean',
                    default: true
                },
                showMetadata: {
                    type: 'boolean',
                    default: true
                },
                layout: {
                    type: 'string',
                    default: 'standard'
                }
            },
            edit: function(props) {
                const { attributes, setAttributes } = props;
                const { movieId, showPoster, showTitle, showRating, showMetadata, layout } = attributes;
                
                return (
                    <Fragment>
                        <InspectorControls>
                            <PanelBody title={__('Movie Settings', 'tmu')}>
                                <TextControl
                                    label={__('Movie ID', 'tmu')}
                                    value={movieId}
                                    onChange={(value) => setAttributes({ movieId: parseInt(value) || 0 })}
                                    type="number"
                                />
                                <ToggleControl
                                    label={__('Show Poster', 'tmu')}
                                    checked={showPoster}
                                    onChange={() => setAttributes({ showPoster: !showPoster })}
                                />
                                <ToggleControl
                                    label={__('Show Title', 'tmu')}
                                    checked={showTitle}
                                    onChange={() => setAttributes({ showTitle: !showTitle })}
                                />
                                <ToggleControl
                                    label={__('Show Rating', 'tmu')}
                                    checked={showRating}
                                    onChange={() => setAttributes({ showRating: !showRating })}
                                />
                                <ToggleControl
                                    label={__('Show Metadata', 'tmu')}
                                    checked={showMetadata}
                                    onChange={() => setAttributes({ showMetadata: !showMetadata })}
                                />
                                <SelectControl
                                    label={__('Layout', 'tmu')}
                                    value={layout}
                                    options={[
                                        { label: __('Standard', 'tmu'), value: 'standard' },
                                        { label: __('Compact', 'tmu'), value: 'compact' },
                                        { label: __('Full Width', 'tmu'), value: 'full-width' }
                                    ]}
                                    onChange={(value) => setAttributes({ layout: value })}
                                />
                            </PanelBody>
                        </InspectorControls>
                        
                        <div className={`tmu-block-editor tmu-movie-metadata-editor layout-${layout}`}>
                            <Placeholder
                                icon="video-alt2"
                                label={__('Movie Metadata', 'tmu')}
                                instructions={__('Select a movie to display its metadata.', 'tmu')}
                            >
                                <TextControl
                                    label={__('Movie ID', 'tmu')}
                                    value={movieId}
                                    onChange={(value) => setAttributes({ movieId: parseInt(value) || 0 })}
                                    type="number"
                                />
                                <div className="tmu-block-options">
                                    <ToggleControl
                                        label={__('Show Poster', 'tmu')}
                                        checked={showPoster}
                                        onChange={() => setAttributes({ showPoster: !showPoster })}
                                    />
                                    <ToggleControl
                                        label={__('Show Title', 'tmu')}
                                        checked={showTitle}
                                        onChange={() => setAttributes({ showTitle: !showTitle })}
                                    />
                                </div>
                            </Placeholder>
                        </div>
                    </Fragment>
                );
            },
            save: function() {
                // Dynamic block, render via PHP
                return null;
            }
        });
        
        // TV Series Metadata Block
        registerBlockType('tmu/tv-series-metadata', {
            title: __('TV Series Metadata', 'tmu'),
            icon: 'desktop',
            category: 'tmu-blocks',
            attributes: {
                seriesId: {
                    type: 'number',
                    default: 0
                },
                showPoster: {
                    type: 'boolean',
                    default: true
                },
                showTitle: {
                    type: 'boolean',
                    default: true
                },
                showSeasons: {
                    type: 'boolean',
                    default: true
                },
                layout: {
                    type: 'string',
                    default: 'standard'
                }
            },
            edit: function(props) {
                const { attributes, setAttributes } = props;
                const { seriesId, showPoster, showTitle, showSeasons, layout } = attributes;
                
                return (
                    <Fragment>
                        <InspectorControls>
                            <PanelBody title={__('TV Series Settings', 'tmu')}>
                                <TextControl
                                    label={__('Series ID', 'tmu')}
                                    value={seriesId}
                                    onChange={(value) => setAttributes({ seriesId: parseInt(value) || 0 })}
                                    type="number"
                                />
                                <ToggleControl
                                    label={__('Show Poster', 'tmu')}
                                    checked={showPoster}
                                    onChange={() => setAttributes({ showPoster: !showPoster })}
                                />
                                <ToggleControl
                                    label={__('Show Title', 'tmu')}
                                    checked={showTitle}
                                    onChange={() => setAttributes({ showTitle: !showTitle })}
                                />
                                <ToggleControl
                                    label={__('Show Seasons', 'tmu')}
                                    checked={showSeasons}
                                    onChange={() => setAttributes({ showSeasons: !showSeasons })}
                                />
                                <SelectControl
                                    label={__('Layout', 'tmu')}
                                    value={layout}
                                    options={[
                                        { label: __('Standard', 'tmu'), value: 'standard' },
                                        { label: __('Compact', 'tmu'), value: 'compact' },
                                        { label: __('Full Width', 'tmu'), value: 'full-width' }
                                    ]}
                                    onChange={(value) => setAttributes({ layout: value })}
                                />
                            </PanelBody>
                        </InspectorControls>
                        
                        <div className={`tmu-block-editor tmu-tv-series-metadata-editor layout-${layout}`}>
                            <Placeholder
                                icon="desktop"
                                label={__('TV Series Metadata', 'tmu')}
                                instructions={__('Select a TV series to display its metadata.', 'tmu')}
                            >
                                <TextControl
                                    label={__('Series ID', 'tmu')}
                                    value={seriesId}
                                    onChange={(value) => setAttributes({ seriesId: parseInt(value) || 0 })}
                                    type="number"
                                />
                            </Placeholder>
                        </div>
                    </Fragment>
                );
            },
            save: function() {
                // Dynamic block, render via PHP
                return null;
            }
        });
        
        // Drama Metadata Block
        registerBlockType('tmu/drama-metadata', {
            title: __('Drama Metadata', 'tmu'),
            icon: 'format-video',
            category: 'tmu-blocks',
            attributes: {
                dramaId: {
                    type: 'number',
                    default: 0
                },
                showPoster: {
                    type: 'boolean',
                    default: true
                },
                showTitle: {
                    type: 'boolean',
                    default: true
                },
                showEpisodes: {
                    type: 'boolean',
                    default: true
                },
                layout: {
                    type: 'string',
                    default: 'standard'
                }
            },
            edit: function(props) {
                const { attributes, setAttributes } = props;
                const { dramaId, showPoster, showTitle, showEpisodes, layout } = attributes;
                
                return (
                    <Fragment>
                        <InspectorControls>
                            <PanelBody title={__('Drama Settings', 'tmu')}>
                                <TextControl
                                    label={__('Drama ID', 'tmu')}
                                    value={dramaId}
                                    onChange={(value) => setAttributes({ dramaId: parseInt(value) || 0 })}
                                    type="number"
                                />
                                <ToggleControl
                                    label={__('Show Poster', 'tmu')}
                                    checked={showPoster}
                                    onChange={() => setAttributes({ showPoster: !showPoster })}
                                />
                                <ToggleControl
                                    label={__('Show Title', 'tmu')}
                                    checked={showTitle}
                                    onChange={() => setAttributes({ showTitle: !showTitle })}
                                />
                                <ToggleControl
                                    label={__('Show Episodes', 'tmu')}
                                    checked={showEpisodes}
                                    onChange={() => setAttributes({ showEpisodes: !showEpisodes })}
                                />
                                <SelectControl
                                    label={__('Layout', 'tmu')}
                                    value={layout}
                                    options={[
                                        { label: __('Standard', 'tmu'), value: 'standard' },
                                        { label: __('Compact', 'tmu'), value: 'compact' },
                                        { label: __('Full Width', 'tmu'), value: 'full-width' }
                                    ]}
                                    onChange={(value) => setAttributes({ layout: value })}
                                />
                            </PanelBody>
                        </InspectorControls>
                        
                        <div className={`tmu-block-editor tmu-drama-metadata-editor layout-${layout}`}>
                            <Placeholder
                                icon="format-video"
                                label={__('Drama Metadata', 'tmu')}
                                instructions={__('Select a drama to display its metadata.', 'tmu')}
                            >
                                <TextControl
                                    label={__('Drama ID', 'tmu')}
                                    value={dramaId}
                                    onChange={(value) => setAttributes({ dramaId: parseInt(value) || 0 })}
                                    type="number"
                                />
                            </Placeholder>
                        </div>
                    </Fragment>
                );
            },
            save: function() {
                // Dynamic block, render via PHP
                return null;
            }
        });
        
        // People Metadata Block
        registerBlockType('tmu/people-metadata', {
            title: __('People Metadata', 'tmu'),
            icon: 'admin-users',
            category: 'tmu-blocks',
            attributes: {
                personId: {
                    type: 'number',
                    default: 0
                },
                showPhoto: {
                    type: 'boolean',
                    default: true
                },
                showName: {
                    type: 'boolean',
                    default: true
                },
                showBio: {
                    type: 'boolean',
                    default: true
                },
                showCredits: {
                    type: 'boolean',
                    default: true
                },
                layout: {
                    type: 'string',
                    default: 'standard'
                }
            },
            edit: function(props) {
                const { attributes, setAttributes } = props;
                const { personId, showPhoto, showName, showBio, showCredits, layout } = attributes;
                
                return (
                    <Fragment>
                        <InspectorControls>
                            <PanelBody title={__('Person Settings', 'tmu')}>
                                <TextControl
                                    label={__('Person ID', 'tmu')}
                                    value={personId}
                                    onChange={(value) => setAttributes({ personId: parseInt(value) || 0 })}
                                    type="number"
                                />
                                <ToggleControl
                                    label={__('Show Photo', 'tmu')}
                                    checked={showPhoto}
                                    onChange={() => setAttributes({ showPhoto: !showPhoto })}
                                />
                                <ToggleControl
                                    label={__('Show Name', 'tmu')}
                                    checked={showName}
                                    onChange={() => setAttributes({ showName: !showName })}
                                />
                                <ToggleControl
                                    label={__('Show Biography', 'tmu')}
                                    checked={showBio}
                                    onChange={() => setAttributes({ showBio: !showBio })}
                                />
                                <ToggleControl
                                    label={__('Show Credits', 'tmu')}
                                    checked={showCredits}
                                    onChange={() => setAttributes({ showCredits: !showCredits })}
                                />
                                <SelectControl
                                    label={__('Layout', 'tmu')}
                                    value={layout}
                                    options={[
                                        { label: __('Standard', 'tmu'), value: 'standard' },
                                        { label: __('Compact', 'tmu'), value: 'compact' },
                                        { label: __('Full Width', 'tmu'), value: 'full-width' }
                                    ]}
                                    onChange={(value) => setAttributes({ layout: value })}
                                />
                            </PanelBody>
                        </InspectorControls>
                        
                        <div className={`tmu-block-editor tmu-people-metadata-editor layout-${layout}`}>
                            <Placeholder
                                icon="admin-users"
                                label={__('People Metadata', 'tmu')}
                                instructions={__('Select a person to display their metadata.', 'tmu')}
                            >
                                <TextControl
                                    label={__('Person ID', 'tmu')}
                                    value={personId}
                                    onChange={(value) => setAttributes({ personId: parseInt(value) || 0 })}
                                    type="number"
                                />
                            </Placeholder>
                        </div>
                    </Fragment>
                );
            },
            save: function() {
                // Dynamic block, render via PHP
                return null;
            }
        });
    }
    
    // Initialize when the DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerBlocks);
    } else {
        registerBlocks();
    }
    
})(window.wp);