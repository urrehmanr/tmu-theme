# Step 10: Frontend Templates - Modern Design System

## Overview
This step implements a complete frontend template system with modern design patterns, responsive layouts, and enhanced user experience. The system maintains all existing functionality while providing a fresh, intuitive interface.

## 1. Template Hierarchy

### 1.1 Main Templates
- `index.php` - Main template fallback
- `home.php` - Homepage template
- `front-page.php` - Static front page
- `404.php` - Error page template
- `search.php` - Search results template
- `archive.php` - General archive template

### 1.2 Post Type Templates
- `single-movie.php` - Individual movie pages
- `single-tv.php` - Individual TV show pages
- `single-drama.php` - Individual drama pages
- `single-people.php` - Individual people pages
- `single-episode.php` - Episode pages
- `single-drama-episode.php` - Drama episode pages
- `single-season.php` - Season pages
- `single-video.php` - Video pages

### 1.3 Archive Templates
- `archive-movie.php` - Movie listings
- `archive-tv.php` - TV show listings
- `archive-drama.php` - Drama listings
- `archive-people.php` - People listings
- `archive-video.php` - Video listings

### 1.4 Taxonomy Templates
- `taxonomy-genre.php` - Genre pages
- `taxonomy-country.php` - Country pages
- `taxonomy-language.php` - Language pages
- `taxonomy-by-year.php` - Year-based pages
- `taxonomy-network.php` - Network pages
- `taxonomy-channel.php` - Channel pages

## 2. Template Structure

### 2.1 Base Template (`templates/base.php`)
```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a1a1a">
    
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    
    <div class="tmu-site-wrapper">
        <?php get_template_part('templates/partials/header'); ?>
        
        <main class="tmu-main-content" role="main">
            <?php echo $content; ?>
        </main>
        
        <?php get_template_part('templates/partials/footer'); ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
```

### 2.2 Header Template (`templates/partials/header.php`)
```php
<header class="tmu-header" role="banner">
    <div class="tmu-container">
        <div class="tmu-header-inner">
            <div class="tmu-logo">
                <?php if (has_custom_logo()): ?>
                    <?php the_custom_logo(); ?>
                <?php else: ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                            <?php bloginfo('name'); ?>
                        </a>
                    </h1>
                <?php endif; ?>
            </div>
            
            <nav class="tmu-navigation" role="navigation">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class' => 'tmu-nav-menu',
                    'container' => false,
                    'fallback_cb' => 'tmu_fallback_menu'
                ]);
                ?>
            </nav>
            
            <div class="tmu-header-actions">
                <?php get_template_part('templates/partials/search-form'); ?>
                <button class="tmu-mobile-menu-toggle" aria-label="Toggle Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</header>
```

### 2.3 Movie Single Template (`single-movie.php`)
```php
<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $movie_data = tmu_get_movie_data(get_the_ID());
        ?>
        <article class="tmu-movie-single">
            <div class="tmu-movie-hero">
                <?php if ($movie_data['backdrop_path']): ?>
                    <div class="tmu-hero-backdrop">
                        <img src="<?php echo esc_url($movie_data['backdrop_path']); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                             class="tmu-backdrop-image">
                        <div class="tmu-hero-overlay"></div>
                    </div>
                <?php endif; ?>
                
                <div class="tmu-container">
                    <div class="tmu-hero-content">
                        <div class="tmu-movie-poster">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('large', ['class' => 'tmu-poster-image']); ?>
                            <?php endif; ?>
                            
                            <div class="tmu-movie-actions">
                                <button class="tmu-btn tmu-btn-primary tmu-watch-btn">
                                    <i class="fas fa-play"></i> Watch Now
                                </button>
                                <button class="tmu-btn tmu-btn-secondary tmu-trailer-btn">
                                    <i class="fas fa-video"></i> Trailer
                                </button>
                            </div>
                        </div>
                        
                        <div class="tmu-movie-info">
                            <h1 class="tmu-movie-title"><?php the_title(); ?></h1>
                            
                            <?php if ($movie_data['tagline']): ?>
                                <p class="tmu-movie-tagline"><?php echo esc_html($movie_data['tagline']); ?></p>
                            <?php endif; ?>
                            
                            <div class="tmu-movie-meta">
                                <div class="tmu-rating">
                                    <div class="tmu-rating-stars">
                                        <?php echo tmu_render_rating($movie_data['vote_average']); ?>
                                    </div>
                                    <span class="tmu-rating-text">
                                        <?php echo number_format($movie_data['vote_average'], 1); ?>/10
                                    </span>
                                </div>
                                
                                <div class="tmu-movie-details">
                                    <?php if ($movie_data['runtime']): ?>
                                        <span class="tmu-runtime">
                                            <i class="far fa-clock"></i>
                                            <?php echo esc_html($movie_data['runtime']); ?> min
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($movie_data['release_date']): ?>
                                        <span class="tmu-release-date">
                                            <i class="far fa-calendar"></i>
                                            <?php echo date('Y', strtotime($movie_data['release_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($movie_data['status']): ?>
                                        <span class="tmu-status">
                                            <i class="fas fa-info-circle"></i>
                                            <?php echo esc_html($movie_data['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="tmu-movie-genres">
                                <?php
                                $genres = get_the_terms(get_the_ID(), 'genre');
                                if ($genres && !is_wp_error($genres)):
                                    foreach ($genres as $genre):
                                        ?>
                                        <a href="<?php echo esc_url(get_term_link($genre)); ?>" 
                                           class="tmu-genre-tag">
                                            <?php echo esc_html($genre->name); ?>
                                        </a>
                                        <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                            
                            <?php if ($movie_data['overview']): ?>
                                <div class="tmu-movie-overview">
                                    <h3>Overview</h3>
                                    <p><?php echo esc_html($movie_data['overview']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tmu-movie-content">
                <div class="tmu-container">
                    <div class="tmu-content-tabs">
                        <nav class="tmu-tab-nav">
                            <button class="tmu-tab-button active" data-tab="details">Details</button>
                            <button class="tmu-tab-button" data-tab="cast">Cast & Crew</button>
                            <button class="tmu-tab-button" data-tab="media">Media</button>
                            <button class="tmu-tab-button" data-tab="similar">Similar Movies</button>
                        </nav>
                        
                        <div class="tmu-tab-content">
                            <div class="tmu-tab-pane active" id="details">
                                <?php get_template_part('templates/movie/details', null, ['movie_data' => $movie_data]); ?>
                            </div>
                            
                            <div class="tmu-tab-pane" id="cast">
                                <?php get_template_part('templates/movie/cast', null, ['movie_data' => $movie_data]); ?>
                            </div>
                            
                            <div class="tmu-tab-pane" id="media">
                                <?php get_template_part('templates/movie/media', null, ['movie_data' => $movie_data]); ?>
                            </div>
                            
                            <div class="tmu-tab-pane" id="similar">
                                <?php get_template_part('templates/movie/similar', null, ['movie_data' => $movie_data]); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
        <?php
    endwhile;
endif;

get_footer();
```

## 3. Component System

### 3.1 Movie Card Component (`templates/components/movie-card.php`)
```php
<?php
$movie_data = $args['movie_data'] ?? [];
$post_id = $args['post_id'] ?? get_the_ID();
$size = $args['size'] ?? 'medium';
$show_rating = $args['show_rating'] ?? true;
$show_year = $args['show_year'] ?? true;
?>

<div class="tmu-movie-card tmu-card-<?php echo esc_attr($size); ?>">
    <div class="tmu-card-image">
        <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
            <?php if (has_post_thumbnail($post_id)): ?>
                <?php echo get_the_post_thumbnail($post_id, 'medium', [
                    'class' => 'tmu-card-poster',
                    'alt' => get_the_title($post_id)
                ]); ?>
            <?php else: ?>
                <div class="tmu-card-placeholder">
                    <i class="fas fa-film"></i>
                </div>
            <?php endif; ?>
        </a>
        
        <div class="tmu-card-overlay">
            <div class="tmu-card-actions">
                <button class="tmu-quick-view" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="tmu-add-watchlist" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <?php if ($show_rating && !empty($movie_data['vote_average'])): ?>
                <div class="tmu-card-rating">
                    <span class="tmu-rating-badge">
                        <?php echo number_format($movie_data['vote_average'], 1); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="tmu-card-content">
        <h3 class="tmu-card-title">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php echo esc_html(get_the_title($post_id)); ?>
            </a>
        </h3>
        
        <?php if ($show_year && !empty($movie_data['release_date'])): ?>
            <div class="tmu-card-year">
                <?php echo date('Y', strtotime($movie_data['release_date'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="tmu-card-genres">
            <?php
            $genres = get_the_terms($post_id, 'genre');
            if ($genres && !is_wp_error($genres)):
                $genre_names = array_slice(wp_list_pluck($genres, 'name'), 0, 2);
                echo esc_html(implode(', ', $genre_names));
            endif;
            ?>
        </div>
    </div>
</div>
```

### 3.2 Person Card Component (`templates/components/person-card.php`)
```php
<?php
$person_data = $args['person_data'] ?? [];
$post_id = $args['post_id'] ?? get_the_ID();
$role = $args['role'] ?? '';
?>

<div class="tmu-person-card">
    <div class="tmu-person-image">
        <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
            <?php if (has_post_thumbnail($post_id)): ?>
                <?php echo get_the_post_thumbnail($post_id, 'thumbnail', [
                    'class' => 'tmu-person-photo',
                    'alt' => get_the_title($post_id)
                ]); ?>
            <?php else: ?>
                <div class="tmu-person-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </a>
    </div>
    
    <div class="tmu-person-info">
        <h4 class="tmu-person-name">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php echo esc_html(get_the_title($post_id)); ?>
            </a>
        </h4>
        
        <?php if ($role): ?>
            <p class="tmu-person-role"><?php echo esc_html($role); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($person_data['known_for_department'])): ?>
            <p class="tmu-person-department">
                <?php echo esc_html($person_data['known_for_department']); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
```

## 4. Advanced Features

### 4.1 Search Template (`search.php`)
```php
<?php
get_header();

$search_query = get_search_query();
$post_types = ['movie', 'tv', 'drama', 'people'];
$results_by_type = [];

foreach ($post_types as $post_type) {
    $results_by_type[$post_type] = new WP_Query([
        'post_type' => $post_type,
        's' => $search_query,
        'posts_per_page' => 12
    ]);
}
?>

<div class="tmu-search-results">
    <div class="tmu-container">
        <div class="tmu-search-header">
            <h1 class="tmu-search-title">
                Search Results for: <span>"<?php echo esc_html($search_query); ?>"</span>
            </h1>
            
            <div class="tmu-search-filters">
                <button class="tmu-filter-btn active" data-filter="all">All</button>
                <button class="tmu-filter-btn" data-filter="movie">Movies</button>
                <button class="tmu-filter-btn" data-filter="tv">TV Shows</button>
                <?php if (get_option('tmu_dramas') === 'on'): ?>
                    <button class="tmu-filter-btn" data-filter="drama">Dramas</button>
                <?php endif; ?>
                <button class="tmu-filter-btn" data-filter="people">People</button>
            </div>
        </div>
        
        <div class="tmu-search-content">
            <?php foreach ($results_by_type as $post_type => $query): ?>
                <?php if ($query->have_posts()): ?>
                    <div class="tmu-search-section" data-type="<?php echo esc_attr($post_type); ?>">
                        <h2 class="tmu-section-title">
                            <?php echo esc_html(ucfirst($post_type === 'tv' ? 'TV Shows' : $post_type . 's')); ?>
                            <span class="tmu-count">(<?php echo $query->found_posts; ?>)</span>
                        </h2>
                        
                        <div class="tmu-grid tmu-grid-<?php echo esc_attr($post_type); ?>">
                            <?php while ($query->have_posts()): $query->the_post(); ?>
                                <?php
                                if ($post_type === 'people') {
                                    get_template_part('templates/components/person-card', null, [
                                        'person_data' => tmu_get_person_data(get_the_ID())
                                    ]);
                                } else {
                                    get_template_part('templates/components/movie-card', null, [
                                        'movie_data' => tmu_get_movie_data(get_the_ID()),
                                        'post_id' => get_the_ID()
                                    ]);
                                }
                                ?>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php if ($query->max_num_pages > 1): ?>
                            <div class="tmu-pagination">
                                <button class="tmu-load-more" data-type="<?php echo esc_attr($post_type); ?>">
                                    Load More <?php echo esc_html(ucfirst($post_type)); ?>s
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
```

### 4.2 Archive Template (`archive-movie.php`)
```php
<?php
get_header();

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$filter_genre = get_query_var('genre_filter');
$filter_year = get_query_var('year_filter');
$sort_by = get_query_var('sort_by') ?: 'date';

$args = [
    'post_type' => 'movie',
    'posts_per_page' => 20,
    'paged' => $paged,
    'meta_query' => []
];

if ($filter_genre) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'genre',
            'field' => 'slug',
            'terms' => $filter_genre
        ]
    ];
}

if ($filter_year) {
    $args['tax_query'][] = [
        'taxonomy' => 'by-year',
        'field' => 'name',
        'terms' => $filter_year
    ];
}

switch ($sort_by) {
    case 'rating':
        $args['meta_key'] = 'vote_average';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'popularity':
        $args['meta_key'] = 'popularity';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'title':
        $args['orderby'] = 'title';
        $args['order'] = 'ASC';
        break;
    default:
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
}

$movies_query = new WP_Query($args);
?>

<div class="tmu-archive-movies">
    <div class="tmu-container">
        <div class="tmu-archive-header">
            <h1 class="tmu-archive-title">Movies</h1>
            
            <div class="tmu-archive-filters">
                <div class="tmu-filter-group">
                    <label for="genre-filter">Genre:</label>
                    <select id="genre-filter" class="tmu-filter-select">
                        <option value="">All Genres</option>
                        <?php
                        $genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => true]);
                        foreach ($genres as $genre):
                            ?>
                            <option value="<?php echo esc_attr($genre->slug); ?>" 
                                    <?php selected($filter_genre, $genre->slug); ?>>
                                <?php echo esc_html($genre->name); ?>
                            </option>
                            <?php
                        endforeach;
                        ?>
                    </select>
                </div>
                
                <div class="tmu-filter-group">
                    <label for="year-filter">Year:</label>
                    <select id="year-filter" class="tmu-filter-select">
                        <option value="">All Years</option>
                        <?php
                        $years = get_terms(['taxonomy' => 'by-year', 'hide_empty' => true]);
                        foreach ($years as $year):
                            ?>
                            <option value="<?php echo esc_attr($year->name); ?>" 
                                    <?php selected($filter_year, $year->name); ?>>
                                <?php echo esc_html($year->name); ?>
                            </option>
                            <?php
                        endforeach;
                        ?>
                    </select>
                </div>
                
                <div class="tmu-filter-group">
                    <label for="sort-by">Sort By:</label>
                    <select id="sort-by" class="tmu-filter-select">
                        <option value="date" <?php selected($sort_by, 'date'); ?>>Latest</option>
                        <option value="rating" <?php selected($sort_by, 'rating'); ?>>Rating</option>
                        <option value="popularity" <?php selected($sort_by, 'popularity'); ?>>Popularity</option>
                        <option value="title" <?php selected($sort_by, 'title'); ?>>Title</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="tmu-archive-content">
            <?php if ($movies_query->have_posts()): ?>
                <div class="tmu-movies-grid">
                    <?php while ($movies_query->have_posts()): $movies_query->the_post(); ?>
                        <?php
                        get_template_part('templates/components/movie-card', null, [
                            'movie_data' => tmu_get_movie_data(get_the_ID()),
                            'post_id' => get_the_ID()
                        ]);
                        ?>
                    <?php endwhile; ?>
                </div>
                
                <div class="tmu-pagination">
                    <?php
                    echo paginate_links([
                        'total' => $movies_query->max_num_pages,
                        'current' => $paged,
                        'format' => '?paged=%#%',
                        'prev_text' => '<i class="fas fa-chevron-left"></i>',
                        'next_text' => '<i class="fas fa-chevron-right"></i>'
                    ]);
                    ?>
                </div>
            <?php else: ?>
                <div class="tmu-no-results">
                    <h3>No movies found</h3>
                    <p>Try adjusting your filters or search for something else.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
?>
```

## 5. Responsive Design System

### 5.1 CSS Grid Layout (`assets/css/grid.css`)
```css
.tmu-grid {
    display: grid;
    gap: 2rem;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}

.tmu-grid-movies {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
}

.tmu-grid-people {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
}

@media (max-width: 768px) {
    .tmu-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .tmu-grid-movies {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .tmu-grid-people {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 480px) {
    .tmu-grid-movies {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .tmu-grid-people {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

### 5.2 Component Styles (`assets/css/components.css`)
```css
.tmu-movie-card {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: var(--card-bg);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.tmu-movie-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
}

.tmu-card-image {
    position: relative;
    aspect-ratio: 2/3;
    overflow: hidden;
}

.tmu-card-poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.tmu-movie-card:hover .tmu-card-poster {
    transform: scale(1.05);
}

.tmu-card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.8));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.tmu-movie-card:hover .tmu-card-overlay {
    opacity: 1;
}

.tmu-card-actions {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    display: flex;
    gap: 0.5rem;
}

.tmu-card-rating {
    position: absolute;
    top: 1rem;
    right: 1rem;
}

.tmu-rating-badge {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
}

.tmu-card-content {
    padding: 1rem;
}

.tmu-card-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.tmu-card-title a {
    color: var(--text-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.tmu-card-title a:hover {
    color: var(--primary-color);
}

.tmu-card-year {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}

.tmu-card-genres {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
```

## 6. JavaScript Interactions

### 6.1 Main JavaScript (`assets/js/main.js`)
```javascript
(function($) {
    'use strict';
    
    // Mobile menu toggle
    $('.tmu-mobile-menu-toggle').on('click', function() {
        $(this).toggleClass('active');
        $('.tmu-navigation').toggleClass('active');
        $('body').toggleClass('menu-open');
    });
    
    // Tab functionality
    $('.tmu-tab-button').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Update active tab button
        $('.tmu-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update active tab content
        $('.tmu-tab-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Filter functionality
    $('.tmu-filter-btn').on('click', function() {
        const filter = $(this).data('filter');
        
        $('.tmu-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        if (filter === 'all') {
            $('.tmu-search-section').show();
        } else {
            $('.tmu-search-section').hide();
            $('.tmu-search-section[data-type="' + filter + '"]').show();
        }
    });
    
    // Archive filters
    $('#genre-filter, #year-filter, #sort-by').on('change', function() {
        const genreFilter = $('#genre-filter').val();
        const yearFilter = $('#year-filter').val();
        const sortBy = $('#sort-by').val();
        
        let url = window.location.pathname;
        const params = new URLSearchParams();
        
        if (genreFilter) params.append('genre_filter', genreFilter);
        if (yearFilter) params.append('year_filter', yearFilter);
        if (sortBy) params.append('sort_by', sortBy);
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        window.location.href = url;
    });
    
    // Load more functionality
    $('.tmu-load-more').on('click', function() {
        const $button = $(this);
        const type = $button.data('type');
        const page = parseInt($button.data('page') || 1) + 1;
        
        $button.prop('disabled', true).text('Loading...');
        
        $.ajax({
            url: tmu_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tmu_load_more',
                post_type: type,
                page: page,
                nonce: tmu_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const $grid = $button.closest('.tmu-search-section').find('.tmu-grid');
                    $grid.append(response.data.html);
                    
                    if (response.data.has_more) {
                        $button.data('page', page).prop('disabled', false).text('Load More');
                    } else {
                        $button.hide();
                    }
                } else {
                    alert('Error loading more content');
                }
            },
            error: function() {
                alert('Error loading more content');
            }
        });
    });
    
    // Quick view modal
    $('.tmu-quick-view').on('click', function() {
        const postId = $(this).data('post-id');
        
        $.ajax({
            url: tmu_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tmu_quick_view',
                post_id: postId,
                nonce: tmu_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const modal = $('<div class="tmu-modal">')
                        .html(response.data.html)
                        .appendTo('body');
                    
                    modal.fadeIn(300);
                    
                    // Close modal
                    modal.on('click', '.tmu-modal-close, .tmu-modal-overlay', function() {
                        modal.fadeOut(300, function() {
                            modal.remove();
                        });
                    });
                }
            }
        });
    });
    
    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Smooth scrolling
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });
    
})(jQuery);
```

## 7. Success Metrics

- [ ] All template files created and functional
- [ ] Responsive design working across all devices
- [ ] Component system implemented
- [ ] JavaScript interactions working smoothly
- [ ] Search and filter functionality operational
- [ ] Performance optimized with lazy loading
- [ ] Accessibility standards met
- [ ] Cross-browser compatibility ensured

## Next Steps

After completing this step, the theme will have:
- Modern, responsive frontend templates
- Comprehensive component system
- Enhanced user experience features
- Optimized performance
- Accessible design patterns
- Scalable architecture for future enhancements