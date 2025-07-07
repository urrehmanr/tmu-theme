/**
 * TMU Blocks Registration Entry Point
 * 
 * This file imports all TMU Gutenberg blocks which self-register
 * using the WordPress block registration API.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

// Import WordPress dependencies
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

// Import block styles
import '../scss/blocks/editor.scss';

// Import individual metadata blocks (self-registering)
import './MovieMetadataBlock.jsx';
import './TvSeriesMetadataBlock.jsx';
import './DramaMetadataBlock.jsx';
import './PeopleMetadataBlock.jsx';

// Import universal episode block (self-registering)
import './EpisodeMetadataBlock.jsx';

// Import consolidated block files (self-registering)
import './TaxonomyBlocks.jsx';
import './ContentBlocks.jsx';

// Import specialized blocks (self-registering)
import './SeasonMetadataBlock.jsx';
import './VideoMetadataBlock.jsx';
import './TmdbSyncBlock.jsx';

// Register custom block category
addFilter(
    'blocks.registerBlockType',
    'tmu/blocks-category',
    (settings, name) => {
        if (name && name.startsWith('tmu/')) {
            return {
                ...settings,
                category: 'tmu-blocks',
            };
        }
        return settings;
    }
);

// Register block categories
const registerBlockCategories = (categories) => {
    return [
        ...categories,
        {
            slug: 'tmu-blocks',
            title: __('TMU Blocks', 'tmu-theme'),
            icon: 'video-alt3',
        },
    ];
};

// Add block category filter
addFilter(
    'blocks.getBlockTypes',
    'tmu/block-categories',
    registerBlockCategories
);

// Development logging
if (process.env.NODE_ENV === 'development') {
    console.log('TMU Blocks system initialized');
    
    // Log available blocks after registration
    setTimeout(() => {
        const { getBlockTypes } = wp.data.select('core/blocks');
        const tmuBlocks = getBlockTypes().filter(block => block.name.startsWith('tmu/'));
        console.log('Registered TMU Blocks:', tmuBlocks.map(block => ({
            name: block.name,
            title: block.title,
            category: block.category
        })));
    }, 1000);
}