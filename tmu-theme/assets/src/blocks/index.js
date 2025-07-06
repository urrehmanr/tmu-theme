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
import MovieMetadataBlock from './components/MovieMetadataBlock';
import TvSeriesMetadataBlock from './components/TvSeriesMetadataBlock';
import DramaMetadataBlock from './components/DramaMetadataBlock';
import PeopleMetadataBlock from './components/PeopleMetadataBlock';
import TvEpisodeMetadataBlock from './components/TvEpisodeMetadataBlock';

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