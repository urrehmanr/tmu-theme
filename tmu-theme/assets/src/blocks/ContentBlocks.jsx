/**
 * Content Blocks
 * 
 * Consolidated React components for all content curation blocks.
 * Contains BlogPostsListBlock, TrendingContentBlock, and content recommendation systems.
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
    SelectControl,
    ToggleControl,
    RangeControl,
    Button,
    Placeholder,
    Spinner
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Blog Posts List Block
 * 
 * Displays and manages blog posts lists with various display options
 */
const BlogPostsListBlock = {
    title: __('Blog Posts List', 'tmu-theme'),
    icon: 'list-view',
    category: 'tmu-blocks',
    description: __('Display blog posts with customizable layouts and filtering', 'tmu-theme'),
    keywords: [__('blog', 'tmu-theme'), __('posts', 'tmu-theme'), __('list', 'tmu-theme'), __('articles', 'tmu-theme')],
    supports: {
        html: false,
        multiple: true,
        reusable: true,
    },
    attributes: {
        post_count: { type: 'number', default: 5 },
        display_style: { type: 'string', default: 'list' },
        show_excerpt: { type: 'boolean', default: true },
        excerpt_length: { type: 'number', default: 150 },
        show_featured_image: { type: 'boolean', default: true },
        image_size: { type: 'string', default: 'medium' },
        show_date: { type: 'boolean', default: true },
        show_author: { type: 'boolean', default: true },
        show_categories: { type: 'boolean', default: true },
        show_tags: { type: 'boolean', default: false },
        date_format: { type: 'string', default: 'F j, Y' },
        order_by: { type: 'string', default: 'date' },
        order: { type: 'string', default: 'DESC' },
        category_filter: { type: 'array', default: [] },
        tag_filter: { type: 'array', default: [] },
        exclude_posts: { type: 'array', default: [] },
        show_pagination: { type: 'boolean', default: false },
        posts_per_page: { type: 'number', default: 10 },
        enable_ajax: { type: 'boolean', default: false },
        custom_css_class: { type: 'string', default: '' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [posts, setPosts] = useState([]);
        const [loading, setLoading] = useState(false);
        const [categories, setCategories] = useState([]);
        const [tags, setTags] = useState([]);
        
        // Get categories and tags
        const categoriesData = useSelect((select) => {
            return select('core').getEntityRecords('taxonomy', 'category', {
                per_page: -1,
                hide_empty: false,
            });
        }, []);
        
        const tagsData = useSelect((select) => {
            return select('core').getEntityRecords('taxonomy', 'post_tag', {
                per_page: -1,
                hide_empty: false,
            });
        }, []);
        
        useEffect(() => {
            if (categoriesData) {
                const categoryOptions = categoriesData.map(cat => ({
                    label: cat.name,
                    value: cat.id
                }));
                setCategories(categoryOptions);
            }
        }, [categoriesData]);
        
        useEffect(() => {
            if (tagsData) {
                const tagOptions = tagsData.map(tag => ({
                    label: tag.name,
                    value: tag.id
                }));
                setTags(tagOptions);
            }
        }, [tagsData]);
        
        // Fetch posts based on current settings
        const fetchPosts = async () => {
            setLoading(true);
            try {
                const query = new URLSearchParams({
                    per_page: attributes.post_count,
                    orderby: attributes.order_by,
                    order: attributes.order,
                    _embed: 'true',
                });
                
                if (attributes.category_filter.length > 0) {
                    query.append('categories', attributes.category_filter.join(','));
                }
                
                if (attributes.tag_filter.length > 0) {
                    query.append('tags', attributes.tag_filter.join(','));
                }
                
                if (attributes.exclude_posts.length > 0) {
                    query.append('exclude', attributes.exclude_posts.join(','));
                }
                
                const response = await fetch(`/wp-json/wp/v2/posts?${query}`);
                const postsData = await response.json();
                setPosts(postsData);
            } catch (error) {
                console.error('Error fetching posts:', error);
            } finally {
                setLoading(false);
            }
        };
        
        useEffect(() => {
            fetchPosts();
        }, [attributes.post_count, attributes.order_by, attributes.order, attributes.category_filter, attributes.tag_filter]);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Display Settings', 'tmu-theme')} initialOpen={true}>
                        <RangeControl
                            label={__('Number of Posts', 'tmu-theme')}
                            value={attributes.post_count}
                            onChange={(value) => setAttributes({ post_count: value })}
                            min={1}
                            max={20}
                        />
                        
                        <SelectControl
                            label={__('Display Style', 'tmu-theme')}
                            value={attributes.display_style}
                            options={[
                                { label: __('List', 'tmu-theme'), value: 'list' },
                                { label: __('Grid', 'tmu-theme'), value: 'grid' },
                                { label: __('Cards', 'tmu-theme'), value: 'cards' },
                                { label: __('Minimal', 'tmu-theme'), value: 'minimal' },
                                { label: __('Featured', 'tmu-theme'), value: 'featured' },
                            ]}
                            onChange={(value) => setAttributes({ display_style: value })}
                        />
                        
                        <SelectControl
                            label={__('Order By', 'tmu-theme')}
                            value={attributes.order_by}
                            options={[
                                { label: __('Date', 'tmu-theme'), value: 'date' },
                                { label: __('Title', 'tmu-theme'), value: 'title' },
                                { label: __('Menu Order', 'tmu-theme'), value: 'menu_order' },
                                { label: __('Random', 'tmu-theme'), value: 'rand' },
                                { label: __('Comment Count', 'tmu-theme'), value: 'comment_count' },
                            ]}
                            onChange={(value) => setAttributes({ order_by: value })}
                        />
                        
                        <SelectControl
                            label={__('Order', 'tmu-theme')}
                            value={attributes.order}
                            options={[
                                { label: __('Descending', 'tmu-theme'), value: 'DESC' },
                                { label: __('Ascending', 'tmu-theme'), value: 'ASC' },
                            ]}
                            onChange={(value) => setAttributes({ order: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Content Options', 'tmu-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Show Featured Image', 'tmu-theme')}
                            checked={attributes.show_featured_image}
                            onChange={(value) => setAttributes({ show_featured_image: value })}
                        />
                        
                        {attributes.show_featured_image && (
                            <SelectControl
                                label={__('Image Size', 'tmu-theme')}
                                value={attributes.image_size}
                                options={[
                                    { label: __('Thumbnail', 'tmu-theme'), value: 'thumbnail' },
                                    { label: __('Medium', 'tmu-theme'), value: 'medium' },
                                    { label: __('Large', 'tmu-theme'), value: 'large' },
                                    { label: __('Full', 'tmu-theme'), value: 'full' },
                                ]}
                                onChange={(value) => setAttributes({ image_size: value })}
                            />
                        )}
                        
                        <ToggleControl
                            label={__('Show Excerpt', 'tmu-theme')}
                            checked={attributes.show_excerpt}
                            onChange={(value) => setAttributes({ show_excerpt: value })}
                        />
                        
                        {attributes.show_excerpt && (
                            <RangeControl
                                label={__('Excerpt Length', 'tmu-theme')}
                                value={attributes.excerpt_length}
                                onChange={(value) => setAttributes({ excerpt_length: value })}
                                min={50}
                                max={500}
                            />
                        )}
                        
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
                        
                        <ToggleControl
                            label={__('Show Categories', 'tmu-theme')}
                            checked={attributes.show_categories}
                            onChange={(value) => setAttributes({ show_categories: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Tags', 'tmu-theme')}
                            checked={attributes.show_tags}
                            onChange={(value) => setAttributes({ show_tags: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Advanced Options', 'tmu-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Enable AJAX Loading', 'tmu-theme')}
                            checked={attributes.enable_ajax}
                            onChange={(value) => setAttributes({ enable_ajax: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Pagination', 'tmu-theme')}
                            checked={attributes.show_pagination}
                            onChange={(value) => setAttributes({ show_pagination: value })}
                        />
                        
                        <TextControl
                            label={__('Custom CSS Class', 'tmu-theme')}
                            value={attributes.custom_css_class}
                            onChange={(value) => setAttributes({ custom_css_class: value })}
                            placeholder={__('custom-class-name', 'tmu-theme')}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-blog-posts-list-block">
                    {loading ? (
                        <div className="loading-posts">
                            <Spinner />
                            <p>{__('Loading posts...', 'tmu-theme')}</p>
                        </div>
                    ) : posts.length > 0 ? (
                        <div className={`posts-preview posts-${attributes.display_style}`}>
                            <div className="block-header">
                                <h3>{__('Blog Posts', 'tmu-theme')}</h3>
                                <span className="post-count">
                                    {posts.length} {__('posts', 'tmu-theme')}
                                </span>
                            </div>
                            
                            <div className="posts-list">
                                {posts.slice(0, 3).map((post) => (
                                    <div key={post.id} className="post-preview-item">
                                        {attributes.show_featured_image && post._embedded?.['wp:featuredmedia']?.[0] && (
                                            <img 
                                                src={post._embedded['wp:featuredmedia'][0].source_url}
                                                alt={post.title.rendered}
                                                className="post-thumbnail"
                                            />
                                        )}
                                        <div className="post-content">
                                            <h4 dangerouslySetInnerHTML={{ __html: post.title.rendered }} />
                                            {attributes.show_excerpt && (
                                                <p className="post-excerpt">
                                                    {post.excerpt.rendered.replace(/<[^>]*>/g, '').substring(0, 100)}...
                                                </p>
                                            )}
                                            <div className="post-meta">
                                                {attributes.show_date && (
                                                    <span className="post-date">
                                                        {new Date(post.date).toLocaleDateString()}
                                                    </span>
                                                )}
                                                {attributes.show_author && post._embedded?.author?.[0] && (
                                                    <span className="post-author">
                                                        {__('by', 'tmu-theme')} {post._embedded.author[0].name}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                
                                {posts.length > 3 && (
                                    <div className="more-posts-indicator">
                                        <p>{__('+ %d more posts', 'tmu-theme').replace('%d', posts.length - 3)}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    ) : (
                        <Placeholder
                            icon="list-view"
                            label={__('Blog Posts List', 'tmu-theme')}
                            instructions={__('No posts found with the current settings.', 'tmu-theme')}
                        >
                            <Button isPrimary onClick={fetchPosts}>
                                {__('Refresh Posts', 'tmu-theme')}
                            </Button>
                        </Placeholder>
                    )}
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

/**
 * Trending Content Block
 * 
 * Displays trending movies, TV shows, and other content
 */
const TrendingContentBlock = {
    title: __('Trending Content', 'tmu-theme'),
    icon: 'chart-line',
    category: 'tmu-blocks',
    description: __('Display trending movies, TV shows, and other popular content', 'tmu-theme'),
    keywords: [__('trending', 'tmu-theme'), __('popular', 'tmu-theme'), __('content', 'tmu-theme'), __('movies', 'tmu-theme')],
    supports: {
        html: false,
        multiple: true,
        reusable: true,
    },
    attributes: {
        content_types: { type: 'array', default: ['movie', 'tv', 'drama'] },
        display_count: { type: 'number', default: 10 },
        time_period: { type: 'string', default: 'week' },
        display_style: { type: 'string', default: 'grid' },
        show_poster: { type: 'boolean', default: true },
        show_rating: { type: 'boolean', default: true },
        show_year: { type: 'boolean', default: true },
        show_genre: { type: 'boolean', default: false },
        trending_metric: { type: 'string', default: 'views' },
        exclude_adult: { type: 'boolean', default: true },
        min_rating: { type: 'number', default: 0 },
        auto_refresh: { type: 'boolean', default: false },
        refresh_interval: { type: 'number', default: 60 },
        custom_title: { type: 'string', default: '' },
        show_trending_indicator: { type: 'boolean', default: true },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [trendingContent, setTrendingContent] = useState([]);
        const [loading, setLoading] = useState(false);
        
        const fetchTrendingContent = async () => {
            setLoading(true);
            try {
                const query = new URLSearchParams({
                    content_types: attributes.content_types.join(','),
                    count: attributes.display_count,
                    period: attributes.time_period,
                    metric: attributes.trending_metric,
                    exclude_adult: attributes.exclude_adult,
                    min_rating: attributes.min_rating,
                });
                
                // This would be a custom endpoint in your REST API
                const response = await fetch(`/wp-json/tmu/v1/trending?${query}`);
                const data = await response.json();
                setTrendingContent(data.results || []);
            } catch (error) {
                console.error('Error fetching trending content:', error);
                // Mock data for preview
                setTrendingContent([
                    { id: 1, title: 'Trending Movie 1', type: 'movie', rating: 8.5, year: 2023 },
                    { id: 2, title: 'Popular TV Show', type: 'tv', rating: 9.1, year: 2023 },
                    { id: 3, title: 'Hot Drama Series', type: 'drama', rating: 8.8, year: 2023 },
                ]);
            } finally {
                setLoading(false);
            }
        };
        
        useEffect(() => {
            fetchTrendingContent();
        }, [attributes.content_types, attributes.display_count, attributes.time_period, attributes.trending_metric]);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Content Settings', 'tmu-theme')} initialOpen={true}>
                        <div className="content-types-selection">
                            <p><strong>{__('Content Types', 'tmu-theme')}</strong></p>
                            {['movie', 'tv', 'drama', 'people'].map(type => (
                                <ToggleControl
                                    key={type}
                                    label={__(type.charAt(0).toUpperCase() + type.slice(1), 'tmu-theme')}
                                    checked={attributes.content_types.includes(type)}
                                    onChange={(checked) => {
                                        const newTypes = checked 
                                            ? [...attributes.content_types, type]
                                            : attributes.content_types.filter(t => t !== type);
                                        setAttributes({ content_types: newTypes });
                                    }}
                                />
                            ))}
                        </div>
                        
                        <RangeControl
                            label={__('Items to Display', 'tmu-theme')}
                            value={attributes.display_count}
                            onChange={(value) => setAttributes({ display_count: value })}
                            min={3}
                            max={50}
                        />
                        
                        <SelectControl
                            label={__('Time Period', 'tmu-theme')}
                            value={attributes.time_period}
                            options={[
                                { label: __('Today', 'tmu-theme'), value: 'day' },
                                { label: __('This Week', 'tmu-theme'), value: 'week' },
                                { label: __('This Month', 'tmu-theme'), value: 'month' },
                                { label: __('This Year', 'tmu-theme'), value: 'year' },
                                { label: __('All Time', 'tmu-theme'), value: 'all' },
                            ]}
                            onChange={(value) => setAttributes({ time_period: value })}
                        />
                        
                        <SelectControl
                            label={__('Trending Metric', 'tmu-theme')}
                            value={attributes.trending_metric}
                            options={[
                                { label: __('Views', 'tmu-theme'), value: 'views' },
                                { label: __('Rating', 'tmu-theme'), value: 'rating' },
                                { label: __('Popularity', 'tmu-theme'), value: 'popularity' },
                                { label: __('Comments', 'tmu-theme'), value: 'comments' },
                                { label: __('Shares', 'tmu-theme'), value: 'shares' },
                            ]}
                            onChange={(value) => setAttributes({ trending_metric: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Display Options', 'tmu-theme')} initialOpen={false}>
                        <SelectControl
                            label={__('Display Style', 'tmu-theme')}
                            value={attributes.display_style}
                            options={[
                                { label: __('Grid', 'tmu-theme'), value: 'grid' },
                                { label: __('List', 'tmu-theme'), value: 'list' },
                                { label: __('Carousel', 'tmu-theme'), value: 'carousel' },
                                { label: __('Masonry', 'tmu-theme'), value: 'masonry' },
                            ]}
                            onChange={(value) => setAttributes({ display_style: value })}
                        />
                        
                        <TextControl
                            label={__('Custom Title', 'tmu-theme')}
                            value={attributes.custom_title}
                            onChange={(value) => setAttributes({ custom_title: value })}
                            placeholder={__('Trending Content', 'tmu-theme')}
                        />
                        
                        <ToggleControl
                            label={__('Show Poster/Image', 'tmu-theme')}
                            checked={attributes.show_poster}
                            onChange={(value) => setAttributes({ show_poster: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Rating', 'tmu-theme')}
                            checked={attributes.show_rating}
                            onChange={(value) => setAttributes({ show_rating: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Year', 'tmu-theme')}
                            checked={attributes.show_year}
                            onChange={(value) => setAttributes({ show_year: value })}
                        />
                        
                        <ToggleControl
                            label={__('Show Trending Indicator', 'tmu-theme')}
                            checked={attributes.show_trending_indicator}
                            onChange={(value) => setAttributes({ show_trending_indicator: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title={__('Filters', 'tmu-theme')} initialOpen={false}>
                        <ToggleControl
                            label={__('Exclude Adult Content', 'tmu-theme')}
                            checked={attributes.exclude_adult}
                            onChange={(value) => setAttributes({ exclude_adult: value })}
                        />
                        
                        <RangeControl
                            label={__('Minimum Rating', 'tmu-theme')}
                            value={attributes.min_rating}
                            onChange={(value) => setAttributes({ min_rating: value })}
                            min={0}
                            max={10}
                            step={0.1}
                        />
                        
                        <ToggleControl
                            label={__('Auto Refresh', 'tmu-theme')}
                            checked={attributes.auto_refresh}
                            onChange={(value) => setAttributes({ auto_refresh: value })}
                        />
                        
                        {attributes.auto_refresh && (
                            <RangeControl
                                label={__('Refresh Interval (minutes)', 'tmu-theme')}
                                value={attributes.refresh_interval}
                                onChange={(value) => setAttributes({ refresh_interval: value })}
                                min={5}
                                max={180}
                                step={5}
                            />
                        )}
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-trending-content-block">
                    {loading ? (
                        <div className="loading-trending">
                            <Spinner />
                            <p>{__('Loading trending content...', 'tmu-theme')}</p>
                        </div>
                    ) : trendingContent.length > 0 ? (
                        <div className={`trending-preview trending-${attributes.display_style}`}>
                            <div className="block-header">
                                <h3>
                                    {attributes.custom_title || __('Trending Content', 'tmu-theme')}
                                    {attributes.show_trending_indicator && (
                                        <span className="trending-icon">🔥</span>
                                    )}
                                </h3>
                                <span className="content-count">
                                    {trendingContent.length} {__('items', 'tmu-theme')}
                                </span>
                            </div>
                            
                            <div className="trending-list">
                                {trendingContent.slice(0, 6).map((item, index) => (
                                    <div key={item.id} className="trending-item">
                                        <div className="trending-rank">#{index + 1}</div>
                                        <div className="item-content">
                                            <h4>{item.title}</h4>
                                            <div className="item-meta">
                                                <span className="content-type">{item.type.toUpperCase()}</span>
                                                {attributes.show_year && item.year && (
                                                    <span className="content-year">{item.year}</span>
                                                )}
                                                {attributes.show_rating && item.rating && (
                                                    <span className="content-rating">⭐ {item.rating}</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                
                                {trendingContent.length > 6 && (
                                    <div className="more-trending-indicator">
                                        <p>{__('+ %d more trending items', 'tmu-theme').replace('%d', trendingContent.length - 6)}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    ) : (
                        <Placeholder
                            icon="chart-line"
                            label={__('Trending Content', 'tmu-theme')}
                            instructions={__('No trending content found for the selected criteria.', 'tmu-theme')}
                        >
                            <Button isPrimary onClick={fetchTrendingContent}>
                                {__('Refresh Trending', 'tmu-theme')}
                            </Button>
                        </Placeholder>
                    )}
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

/**
 * Content Recommendation Block
 * 
 * Displays personalized content recommendations
 */
const ContentRecommendationBlock = {
    title: __('Content Recommendations', 'tmu-theme'),
    icon: 'star-filled',
    category: 'tmu-blocks',
    description: __('Display personalized content recommendations', 'tmu-theme'),
    keywords: [__('recommendations', 'tmu-theme'), __('suggested', 'tmu-theme'), __('similar', 'tmu-theme'), __('related', 'tmu-theme')],
    supports: {
        html: false,
        multiple: true,
        reusable: true,
    },
    attributes: {
        recommendation_type: { type: 'string', default: 'similar' },
        base_content_id: { type: 'number', default: 0 },
        content_types: { type: 'array', default: ['movie', 'tv', 'drama'] },
        display_count: { type: 'number', default: 6 },
        display_style: { type: 'string', default: 'cards' },
        algorithm: { type: 'string', default: 'collaborative' },
        show_reason: { type: 'boolean', default: true },
        show_similarity_score: { type: 'boolean', default: false },
        exclude_watched: { type: 'boolean', default: true },
        personalization: { type: 'boolean', default: true },
    },
    
    edit: ({ attributes, setAttributes }) => {
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Recommendation Settings', 'tmu-theme')} initialOpen={true}>
                        <SelectControl
                            label={__('Recommendation Type', 'tmu-theme')}
                            value={attributes.recommendation_type}
                            options={[
                                { label: __('Similar Content', 'tmu-theme'), value: 'similar' },
                                { label: __('User Based', 'tmu-theme'), value: 'user_based' },
                                { label: __('Popular in Genre', 'tmu-theme'), value: 'genre_popular' },
                                { label: __('Recently Added', 'tmu-theme'), value: 'recent' },
                                { label: __('Trending Now', 'tmu-theme'), value: 'trending' },
                            ]}
                            onChange={(value) => setAttributes({ recommendation_type: value })}
                        />
                        
                        <RangeControl
                            label={__('Number of Recommendations', 'tmu-theme')}
                            value={attributes.display_count}
                            onChange={(value) => setAttributes({ display_count: value })}
                            min={3}
                            max={20}
                        />
                        
                        <SelectControl
                            label={__('Algorithm', 'tmu-theme')}
                            value={attributes.algorithm}
                            options={[
                                { label: __('Collaborative Filtering', 'tmu-theme'), value: 'collaborative' },
                                { label: __('Content Based', 'tmu-theme'), value: 'content_based' },
                                { label: __('Hybrid', 'tmu-theme'), value: 'hybrid' },
                                { label: __('Machine Learning', 'tmu-theme'), value: 'ml' },
                            ]}
                            onChange={(value) => setAttributes({ algorithm: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-content-recommendation-block">
                    <Placeholder
                        icon="star-filled"
                        label={__('Content Recommendations', 'tmu-theme')}
                        instructions={__('Personalized content recommendations will be displayed here.', 'tmu-theme')}
                    />
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-content-recommendations">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
};

// Register all content blocks
registerBlockType('tmu/blog-posts-list', BlogPostsListBlock);
registerBlockType('tmu/trending-content', TrendingContentBlock);
registerBlockType('tmu/content-recommendations', ContentRecommendationBlock);

export { BlogPostsListBlock, TrendingContentBlock, ContentRecommendationBlock };