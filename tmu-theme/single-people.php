<?php
/**
 * People Single Template
 * 
 * Template for displaying single people posts
 * 
 * @package TMU
 * @since 1.0.0
 */

get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $person_data = tmu_get_person_data(get_the_ID());
        $filmography = tmu_get_person_filmography(get_the_ID());
        $related_people = tmu_get_related_posts(get_the_ID(), 'people', 6);
        ?>
        
        <div class="tmu-person-single">
            <!-- Breadcrumb Navigation -->
            <div class="tmu-container">
                <?php echo tmu_render_breadcrumbs(get_the_ID()); ?>
            </div>
            
            <!-- Person Hero Section -->
            <section class="tmu-person-hero bg-gradient-to-br from-gray-900 to-gray-800 py-12">
                <div class="tmu-container">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Person Photo -->
                        <div class="lg:col-span-1">
                            <div class="tmu-person-photo max-w-sm mx-auto lg:mx-0">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('large', [
                                        'class' => 'w-full h-auto rounded-lg shadow-2xl',
                                        'alt' => get_the_title()
                                    ]); ?>
                                <?php else: ?>
                                    <div class="w-full aspect-[3/4] bg-gray-300 rounded-lg flex items-center justify-center">
                                        <span class="text-gray-500 text-6xl">👤</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Person Info -->
                        <div class="lg:col-span-2 text-white">
                            <h1 class="text-4xl lg:text-5xl font-bold mb-4"><?php the_title(); ?></h1>
                            
                            <?php if (!empty($person_data['known_for_department'])): ?>
                                <p class="text-xl text-blue-400 mb-6 font-semibold">
                                    <?php echo esc_html($person_data['known_for_department']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Person Meta Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="space-y-4">
                                    <?php if (!empty($person_data['birthday'])): ?>
                                        <div class="flex items-center space-x-3">
                                            <span class="text-gray-400 w-6">🎂</span>
                                            <div>
                                                <span class="text-sm text-gray-400"><?php _e('Born:', 'tmu-theme'); ?></span>
                                                <span class="block"><?php echo date('F j, Y', strtotime($person_data['birthday'])); ?></span>
                                                <?php if (!empty($person_data['place_of_birth'])): ?>
                                                    <span class="text-sm text-gray-400"><?php echo esc_html($person_data['place_of_birth']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($person_data['deathday'])): ?>
                                        <div class="flex items-center space-x-3">
                                            <span class="text-gray-400 w-6">⚰️</span>
                                            <div>
                                                <span class="text-sm text-gray-400"><?php _e('Died:', 'tmu-theme'); ?></span>
                                                <span class="block"><?php echo date('F j, Y', strtotime($person_data['deathday'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($person_data['gender'])): ?>
                                        <div class="flex items-center space-x-3">
                                            <span class="text-gray-400 w-6">⚧</span>
                                            <div>
                                                <span class="text-sm text-gray-400"><?php _e('Gender:', 'tmu-theme'); ?></span>
                                                <span class="block"><?php echo tmu_get_gender_label((int) $person_data['gender']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="space-y-4">
                                    <?php if (!empty($person_data['popularity'])): ?>
                                        <div class="flex items-center space-x-3">
                                            <span class="text-gray-400 w-6">⭐</span>
                                            <div>
                                                <span class="text-sm text-gray-400"><?php _e('Popularity:', 'tmu-theme'); ?></span>
                                                <span class="block"><?php echo number_format((float) $person_data['popularity'], 1); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $age = '';
                                    if (!empty($person_data['birthday'])) {
                                        $birth_date = new DateTime($person_data['birthday']);
                                        $end_date = !empty($person_data['deathday']) ? new DateTime($person_data['deathday']) : new DateTime();
                                        $age = $birth_date->diff($end_date)->y;
                                    }
                                    ?>
                                    <?php if ($age): ?>
                                        <div class="flex items-center space-x-3">
                                            <span class="text-gray-400 w-6">📅</span>
                                            <div>
                                                <span class="text-sm text-gray-400"><?php _e('Age:', 'tmu-theme'); ?></span>
                                                <span class="block">
                                                    <?php 
                                                    echo $age;
                                                    if (!empty($person_data['deathday'])) {
                                                        echo ' ' . __('(at death)', 'tmu-theme');
                                                    } else {
                                                        echo ' ' . __('years old', 'tmu-theme');
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Biography -->
                            <?php if (!empty($person_data['biography'])): ?>
                                <div class="mb-8">
                                    <h3 class="text-xl font-semibold mb-3"><?php _e('Biography', 'tmu-theme'); ?></h3>
                                    <div class="text-gray-300 leading-relaxed max-h-32 overflow-hidden" id="biography-text">
                                        <?php echo nl2br(esc_html($person_data['biography'])); ?>
                                    </div>
                                    <button class="text-blue-400 hover:text-blue-300 mt-2 text-sm font-medium" 
                                            onclick="toggleBiography()">
                                        <?php _e('Read More', 'tmu-theme'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Known For -->
                            <?php if (!empty($person_data['known_for']) && is_array($person_data['known_for'])): ?>
                                <div>
                                    <h3 class="text-xl font-semibold mb-4"><?php _e('Known For', 'tmu-theme'); ?></h3>
                                    <div class="flex space-x-4 overflow-x-auto pb-2">
                                        <?php foreach (array_slice($person_data['known_for'], 0, 6) as $known_item): ?>
                                            <div class="flex-shrink-0 w-32">
                                                <div class="bg-gray-700 rounded-lg overflow-hidden">
                                                    <?php if (!empty($known_item['poster_path'])): ?>
                                                        <img src="<?php echo esc_url(tmu_get_image_url($known_item['poster_path'], 'w200')); ?>" 
                                                             alt="<?php echo esc_attr($known_item['title'] ?? $known_item['name'] ?? ''); ?>"
                                                             class="w-full h-48 object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-48 bg-gray-600 flex items-center justify-center">
                                                            <span class="text-gray-400 text-2xl">🎬</span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="p-2">
                                                        <h4 class="text-sm font-medium truncate">
                                                            <?php echo esc_html($known_item['title'] ?? $known_item['name'] ?? ''); ?>
                                                        </h4>
                                                        <p class="text-xs text-gray-400">
                                                            <?php 
                                                            $date = $known_item['release_date'] ?? $known_item['first_air_date'] ?? '';
                                                            if ($date) echo date('Y', strtotime($date));
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Person Content Tabs -->
            <section class="tmu-person-content bg-gray-100 py-12">
                <div class="tmu-container">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-300 mb-8">
                        <nav class="flex space-x-8">
                            <button class="tmu-tab-button active pb-4 px-1 border-b-2 border-blue-600 text-blue-600 font-medium transition-colors" 
                                    data-tab="filmography">
                                <?php _e('Filmography', 'tmu-theme'); ?>
                            </button>
                            <button class="tmu-tab-button pb-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium transition-colors" 
                                    data-tab="photos">
                                <?php _e('Photos', 'tmu-theme'); ?>
                            </button>
                            <?php if (!empty($related_people)): ?>
                                <button class="tmu-tab-button pb-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium transition-colors" 
                                        data-tab="related">
                                    <?php _e('Related People', 'tmu-theme'); ?>
                                </button>
                            <?php endif; ?>
                        </nav>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="tmu-tab-content">
                        <!-- Filmography Tab -->
                        <div class="tmu-tab-pane active" id="filmography">
                            <?php if (!empty($filmography)): ?>
                                <div class="space-y-6">
                                    <?php foreach ($filmography as $year => $projects): ?>
                                        <div>
                                            <h3 class="text-xl font-bold mb-4"><?php echo esc_html($year); ?></h3>
                                            <div class="grid gap-4">
                                                <?php foreach ($projects as $project): ?>
                                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                                        <div class="flex items-start space-x-4">
                                                            <div class="w-16 h-24 flex-shrink-0">
                                                                <?php if (!empty($project['poster_path'])): ?>
                                                                    <img src="<?php echo esc_url(tmu_get_image_url($project['poster_path'], 'w200')); ?>" 
                                                                         alt="<?php echo esc_attr($project['title']); ?>"
                                                                         class="w-full h-full object-cover rounded">
                                                                <?php else: ?>
                                                                    <div class="w-full h-full bg-gray-300 rounded flex items-center justify-center">
                                                                        <span class="text-gray-500 text-xs">🎬</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex-1">
                                                                <h4 class="font-semibold text-lg"><?php echo esc_html($project['title']); ?></h4>
                                                                <?php if (!empty($project['character'])): ?>
                                                                    <p class="text-gray-600">as <?php echo esc_html($project['character']); ?></p>
                                                                <?php elseif (!empty($project['job'])): ?>
                                                                    <p class="text-gray-600"><?php echo esc_html($project['job']); ?></p>
                                                                <?php endif; ?>
                                                                <p class="text-sm text-gray-500 mt-1">
                                                                    <?php echo esc_html(ucfirst($project['media_type'] ?? 'movie')); ?>
                                                                    <?php if (!empty($project['vote_average'])): ?>
                                                                        • ⭐ <?php echo number_format((float) $project['vote_average'], 1); ?>
                                                                    <?php endif; ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <p class="text-gray-600"><?php _e('No filmography information available.', 'tmu-theme'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Photos Tab -->
                        <div class="tmu-tab-pane hidden" id="photos">
                            <?php get_template_part('templates/person/photos', null, ['person_data' => $person_data]); ?>
                        </div>
                        
                        <!-- Related People Tab -->
                        <?php if (!empty($related_people)): ?>
                            <div class="tmu-tab-pane hidden" id="related">
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                                    <?php foreach ($related_people as $related_person): ?>
                                        <?php 
                                        get_template_part('templates/components/person-card', null, [
                                            'person_data' => tmu_get_person_data($related_person->ID),
                                            'post_id' => $related_person->ID
                                        ]);
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
        
        <script>
        function toggleBiography() {
            const biographyText = document.getElementById('biography-text');
            const button = event.target;
            
            if (biographyText.classList.contains('max-h-32')) {
                biographyText.classList.remove('max-h-32', 'overflow-hidden');
                button.textContent = '<?php _e('Read Less', 'tmu-theme'); ?>';
            } else {
                biographyText.classList.add('max-h-32', 'overflow-hidden');
                button.textContent = '<?php _e('Read More', 'tmu-theme'); ?>';
            }
        }
        </script>
        
        <?php
    endwhile;
endif;

get_footer();
?>