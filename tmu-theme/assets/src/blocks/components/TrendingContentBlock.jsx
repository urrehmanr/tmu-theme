/**
 * Trending Content Block
 * 
 * Simple React component for displaying trending content.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    NumberControl,
    SelectControl,
    ToggleControl,
    Placeholder
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const TrendingContentBlock = {
    title: __('Trending Content', 'tmu-theme'),
    icon: 'chart-line',
    category: 'tmu-blocks',
    description: __('Display trending movies, TV shows, and dramas', 'tmu-theme'),
    keywords: [__('trending', 'tmu-theme'), __('popular', 'tmu-theme'), __('content', 'tmu-theme')],
    attributes: {
        content_type: { type: 'string', default: 'all' },
        items_count: { type: 'number', default: 10 },
        time_period: { type: 'string', default: 'week' },
        layout: { type: 'string', default: 'grid' },
        show_ranking: { type: 'boolean', default: true },
        show_rating: { type: 'boolean', default: true },
        show_poster: { type: 'boolean', default: true },
    },
    
    edit: ({ attributes, setAttributes }) => {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Trending Settings', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Content Type', 'tmu-theme')}
                            value={attributes.content_type}
                            options={[
                                { label: __('All Content', 'tmu-theme'), value: 'all' },
                                { label: __('Movies', 'tmu-theme'), value: 'movie' },
                                { label: __('TV Shows', 'tmu-theme'), value: 'tv' },
                                { label: __('Dramas', 'tmu-theme'), value: 'drama' },
                            ]}
                            onChange={(value) => setAttributes({ content_type: value })}
                        />
                        
                        <SelectControl
                            label={__('Time Period', 'tmu-theme')}
                            value={attributes.time_period}
                            options={[
                                { label: __('Today', 'tmu-theme'), value: 'day' },
                                { label: __('This Week', 'tmu-theme'), value: 'week' },
                                { label: __('This Month', 'tmu-theme'), value: 'month' },
                                { label: __('All Time', 'tmu-theme'), value: 'all' },
                            ]}
                            onChange={(value) => setAttributes({ time_period: value })}
                        />
                        
                        <NumberControl
                            label={__('Number of Items', 'tmu-theme')}
                            value={attributes.items_count}
                            onChange={(value) => setAttributes({ items_count: parseInt(value) || 10 })}
                            min={5}
                            max={50}
                        />
                        
                        <SelectControl
                            label={__('Layout', 'tmu-theme')}
                            value={attributes.layout}
                            options={[
                                { label: __('Grid', 'tmu-theme'), value: 'grid' },
                                { label: __('List', 'tmu-theme'), value: 'list' },
                                { label: __('Carousel', 'tmu-theme'), value: 'carousel' },
                            ]}
                            onChange={(value) => setAttributes({ layout: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Ranking Numbers', 'tmu-theme')}
                            checked={attributes.show_ranking}
                            onChange={(value) => setAttributes({ show_ranking: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Ratings', 'tmu-theme')}
                            checked={attributes.show_rating}
                            onChange={(value) => setAttributes({ show_rating: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Posters', 'tmu-theme')}
                            checked={attributes.show_poster}
                            onChange={(value) => setAttributes({ show_poster: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-trending-content-block">
                    <Placeholder
                        icon="chart-line"
                        label={__('Trending Content', 'tmu-theme')}
                        instructions={__('This block will display trending content based on your settings.', 'tmu-theme')}
                    >
                        <div className="placeholder-preview bg-gray-50 p-4 rounded">
                            <div className="text-sm text-gray-600 mb-3">
                                {__('Preview:', 'tmu-theme')} {attributes.items_count} {__('trending', 'tmu-theme')} {attributes.content_type} {__('items', 'tmu-theme')}
                                <br />
                                {__('Time period:', 'tmu-theme')} {attributes.time_period} | {__('Layout:', 'tmu-theme')} {attributes.layout}
                            </div>
                            <div className={`trending-preview ${attributes.layout === 'grid' ? 'grid grid-cols-5 gap-2' : 'space-y-2'}`}>
                                {[1, 2, 3, 4, 5].map(i => (
                                    <div key={i} className={`trending-item ${attributes.layout === 'list' ? 'flex items-center space-x-3' : ''} p-2 bg-white rounded border`}>
                                        {attributes.show_ranking && (
                                            <div className={`ranking text-xs font-bold ${attributes.layout === 'grid' ? 'absolute top-1 left-1 bg-red-500 text-white w-5 h-5 rounded-full flex items-center justify-center' : 'text-red-500'}`}>
                                                {i}
                                            </div>
                                        )}
                                        {attributes.show_poster && (
                                            <div className={`poster bg-gray-200 ${attributes.layout === 'grid' ? 'w-full h-20' : 'w-12 h-16'} rounded`}></div>
                                        )}
                                        <div className={`details ${attributes.layout === 'grid' ? 'mt-1' : 'flex-1'}`}>
                                            <div className={`title font-medium ${attributes.layout === 'grid' ? 'text-xs' : 'text-sm'}`}>Item {i}</div>
                                            {attributes.show_rating && (
                                                <div className="rating text-xs text-yellow-600">★ 8.{i}</div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Placeholder>
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-trending-content">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

export default TrendingContentBlock;