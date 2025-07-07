/**
 * TMU Blocks Registration Entry Point
 * 
 * This file registers all TMU Gutenberg blocks and imports
 * the necessary React components for the block editor.
 */

// Import WordPress dependencies
import { registerBlockType } from '@wordpress/blocks';

// Import block styles
import './editor.scss';

// Import individual block components
import MovieMetadataBlock from './components/MovieMetadataBlock.jsx';
import TvSeriesMetadataBlock from './components/TvSeriesMetadataBlock.jsx';
import DramaMetadataBlock from './components/DramaMetadataBlock.jsx';
import PeopleMetadataBlock from './components/PeopleMetadataBlock.jsx';
import TvEpisodeMetadataBlock from './components/TvEpisodeMetadataBlock.jsx';
import DramaEpisodeMetadataBlock from './components/DramaEpisodeMetadataBlock.jsx';
import SeasonMetadataBlock from './components/SeasonMetadataBlock.jsx';
import VideoMetadataBlock from './components/VideoMetadataBlock.jsx';
import TaxonomyImageBlock from './components/TaxonomyImageBlock.jsx';
import TaxonomyFaqsBlock from './components/TaxonomyFaqsBlock.jsx';
import BlogPostsListBlock from './components/BlogPostsListBlock.jsx';
import TrendingContentBlock from './components/TrendingContentBlock.jsx';
import TmdbSyncBlock from './components/TmdbSyncBlock.jsx';

// Block configurations
const blocks = [
    {
        name: 'tmu/movie-metadata',
        settings: MovieMetadataBlock
    },
    {
        name: 'tmu/tv-series-metadata',
        settings: TvSeriesMetadataBlock
    },
    {
        name: 'tmu/drama-metadata',
        settings: DramaMetadataBlock
    },
    {
        name: 'tmu/people-metadata',
        settings: PeopleMetadataBlock
    },
    {
        name: 'tmu/tv-episode-metadata',
        settings: TvEpisodeMetadataBlock
    },
    {
        name: 'tmu/drama-episode-metadata',
        settings: DramaEpisodeMetadataBlock
    },
    {
        name: 'tmu/season-metadata',
        settings: SeasonMetadataBlock
    },
    {
        name: 'tmu/video-metadata',
        settings: VideoMetadataBlock
    },
    {
        name: 'tmu/taxonomy-image',
        settings: TaxonomyImageBlock
    },
    {
        name: 'tmu/taxonomy-faqs',
        settings: TaxonomyFaqsBlock
    },
    {
        name: 'tmu/blog-posts-list',
        settings: BlogPostsListBlock
    },
    {
        name: 'tmu/trending-content',
        settings: TrendingContentBlock
    },
    {
        name: 'tmu/tmdb-sync',
        settings: TmdbSyncBlock
    }
];

// Register all blocks
blocks.forEach(({ name, settings }) => {
    registerBlockType(name, settings);
});

// Log registration for debugging
if (process.env.NODE_ENV === 'development') {
    console.log('TMU Blocks registered:', blocks.map(block => block.name));
}