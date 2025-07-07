/**
 * Blog Posts List Block
 * 
 * Simple React component for displaying blog posts lists.
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

const BlogPostsListBlock = {
    title: __('Blog Posts List', 'tmu-theme'),
    icon: 'list-view',
    category: 'tmu-blocks',
    description: __('Display a list of blog posts', 'tmu-theme'),
    keywords: [__('blog', 'tmu-theme'), __('posts', 'tmu-theme'), __('list', 'tmu-theme')],
    attributes: {
        posts_per_page: { type: 'number', default: 5 },
        layout: { type: 'string', default: 'list' },
        show_excerpt: { type: 'boolean', default: true },
        show_date: { type: 'boolean', default: true },
        show_author: { type: 'boolean', default: false },
        show_featured_image: { type: 'boolean', default: true },
        category_filter: { type: 'string', default: '' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Display Settings', 'tmu-theme')} initialOpen={true}>
                        <NumberControl
                            label={__('Posts per page', 'tmu-theme')}
                            value={attributes.posts_per_page}
                            onChange={(value) => setAttributes({ posts_per_page: parseInt(value) || 5 })}
                            min={1}
                            max={20}
                        />
                        
                        <SelectControl
                            label={__('Layout', 'tmu-theme')}
                            value={attributes.layout}
                            options={[
                                { label: __('List', 'tmu-theme'), value: 'list' },
                                { label: __('Grid', 'tmu-theme'), value: 'grid' },
                                { label: __('Cards', 'tmu-theme'), value: 'cards' },
                            ]}
                            onChange={(value) => setAttributes({ layout: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Featured Image', 'tmu-theme')}
                            checked={attributes.show_featured_image}
                            onChange={(value) => setAttributes({ show_featured_image: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Excerpt', 'tmu-theme')}
                            checked={attributes.show_excerpt}
                            onChange={(value) => setAttributes({ show_excerpt: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Date', 'tmu-theme')}
                            checked={attributes.show_date}
                            onChange={(value) => setAttributes({ show_date: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Author', 'tmu-theme')}
                            checked={attributes.show_author}
                            onChange={(value) => setAttributes({ show_author: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-blog-posts-list-block">
                    <Placeholder
                        icon="list-view"
                        label={__('Blog Posts List', 'tmu-theme')}
                        instructions={__('This block will display a list of blog posts on the frontend.', 'tmu-theme')}
                    >
                        <div className="placeholder-preview bg-gray-50 p-4 rounded">
                            <div className="text-sm text-gray-600 mb-2">
                                {__('Preview:', 'tmu-theme')} {attributes.posts_per_page} {__('posts in', 'tmu-theme')} {attributes.layout} {__('layout', 'tmu-theme')}
                            </div>
                            <div className="space-y-2">
                                {[1, 2, 3].map(i => (
                                    <div key={i} className="flex items-center space-x-3 p-2 bg-white rounded border">
                                        {attributes.show_featured_image && (
                                            <div className="w-12 h-12 bg-gray-200 rounded"></div>
                                        )}
                                        <div className="flex-1">
                                            <div className="font-medium text-sm">Sample Blog Post {i}</div>
                                            {attributes.show_excerpt && (
                                                <div className="text-xs text-gray-500">Sample excerpt...</div>
                                            )}
                                            {(attributes.show_date || attributes.show_author) && (
                                                <div className="text-xs text-gray-400 mt-1">
                                                    {attributes.show_date && 'Jan 1, 2024'}
                                                    {attributes.show_date && attributes.show_author && ' • '}
                                                    {attributes.show_author && 'Author Name'}
                                                </div>
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
            <div className="tmu-blog-posts-list">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

export default BlogPostsListBlock;