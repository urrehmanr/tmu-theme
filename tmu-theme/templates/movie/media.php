<?php
/**
 * Movie Media Tab Template
 * 
 * Displays media gallery for a movie (images, videos, etc.)
 * 
 * @package TMU
 * @since 1.0.0
 */

$movie_data = $args['movie_data'] ?? [];
$post_id = get_the_ID();

// Get related videos
$videos = get_posts([
    'post_type' => 'video',
    'post_parent' => $post_id,
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'source',
            'compare' => 'EXISTS'
        ]
    ]
]);

// Sample image data (would come from TMDB in real implementation)
$sample_images = [
    'backdrops' => [
        ['file_path' => '/backdrop1.jpg', 'width' => 1920, 'height' => 1080],
        ['file_path' => '/backdrop2.jpg', 'width' => 1920, 'height' => 1080],
    ],
    'posters' => [
        ['file_path' => '/poster1.jpg', 'width' => 500, 'height' => 750],
        ['file_path' => '/poster2.jpg', 'width' => 500, 'height' => 750],
    ]
];
?>

<div class="tmu-movie-media">
    <?php if (!empty($videos) || !empty($sample_images['backdrops']) || !empty($sample_images['posters'])): ?>
        
        <!-- Media Navigation -->
        <div class="border-b border-gray-200 mb-8">
            <nav class="flex space-x-8">
                <?php if (!empty($videos)): ?>
                    <button class="tmu-media-tab active pb-4 px-1 border-b-2 border-blue-600 text-blue-600 font-medium" 
                            data-media="videos">
                        <?php _e('Videos', 'tmu-theme'); ?>
                        <span class="ml-1 text-sm">(<?php echo count($videos); ?>)</span>
                    </button>
                <?php endif; ?>
                
                <?php if (!empty($sample_images['backdrops'])): ?>
                    <button class="tmu-media-tab pb-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" 
                            data-media="backdrops">
                        <?php _e('Backdrops', 'tmu-theme'); ?>
                        <span class="ml-1 text-sm">(<?php echo count($sample_images['backdrops']); ?>)</span>
                    </button>
                <?php endif; ?>
                
                <?php if (!empty($sample_images['posters'])): ?>
                    <button class="tmu-media-tab pb-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" 
                            data-media="posters">
                        <?php _e('Posters', 'tmu-theme'); ?>
                        <span class="ml-1 text-sm">(<?php echo count($sample_images['posters']); ?>)</span>
                    </button>
                <?php endif; ?>
            </nav>
        </div>
        
        <!-- Videos Section -->
        <?php if (!empty($videos)): ?>
            <div class="tmu-media-section active" id="videos">
                <h3 class="text-xl font-semibold mb-6 text-gray-900"><?php _e('Videos', 'tmu-theme'); ?></h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($videos as $video): ?>
                        <?php 
                        $storage = new \TMU\Fields\Storage\CustomTableStorage();
                        $source = $storage->get($video->ID, 'source');
                        $content_type = $storage->get($video->ID, 'content_type') ?: 'clip';
                        ?>
                        
                        <div class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <div class="aspect-video bg-gray-900 relative group cursor-pointer" 
                                 onclick="openVideoModal('<?php echo esc_js($source); ?>')">
                                <!-- Video Thumbnail -->
                                <img src="https://img.youtube.com/vi/<?php echo esc_attr($source); ?>/maxresdefault.jpg" 
                                     alt="<?php echo esc_attr($video->post_title); ?>"
                                     class="w-full h-full object-cover">
                                
                                <!-- Play Button Overlay -->
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/50 transition-colors">
                                    <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center text-white text-2xl group-hover:scale-110 transition-transform">
                                        ▶
                                    </div>
                                </div>
                                
                                <!-- Content Type Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="px-2 py-1 bg-black/70 text-white text-xs font-medium rounded">
                                        <?php echo esc_html(ucfirst($content_type)); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h4 class="font-semibold text-gray-900 mb-1"><?php echo esc_html($video->post_title); ?></h4>
                                <?php if ($video->post_content): ?>
                                    <p class="text-sm text-gray-600 line-clamp-2"><?php echo esc_html($video->post_content); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Backdrops Section -->
        <?php if (!empty($sample_images['backdrops'])): ?>
            <div class="tmu-media-section hidden" id="backdrops">
                <h3 class="text-xl font-semibold mb-6 text-gray-900"><?php _e('Backdrops', 'tmu-theme'); ?></h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($sample_images['backdrops'] as $index => $backdrop): ?>
                        <div class="aspect-video bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                             onclick="openImageModal('backdrop', <?php echo $index; ?>)">
                            <img src="<?php echo esc_url(tmu_get_image_url($backdrop['file_path'], 'w500')); ?>" 
                                 alt="<?php echo esc_attr(get_the_title()); ?> Backdrop"
                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Posters Section -->
        <?php if (!empty($sample_images['posters'])): ?>
            <div class="tmu-media-section hidden" id="posters">
                <h3 class="text-xl font-semibold mb-6 text-gray-900"><?php _e('Posters', 'tmu-theme'); ?></h3>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <?php foreach ($sample_images['posters'] as $index => $poster): ?>
                        <div class="aspect-[2/3] bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                             onclick="openImageModal('poster', <?php echo $index; ?>)">
                            <img src="<?php echo esc_url(tmu_get_image_url($poster['file_path'], 'w300')); ?>" 
                                 alt="<?php echo esc_attr(get_the_title()); ?> Poster"
                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Media Available -->
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <div class="text-gray-400 text-6xl mb-4">📷</div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php _e('No Media Available', 'tmu-theme'); ?></h3>
            <p class="text-gray-600"><?php _e('Images and videos will be added when available.', 'tmu-theme'); ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Video Modal -->
<div id="videoModal" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg overflow-hidden max-w-4xl w-full">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold"><?php _e('Video Player', 'tmu-theme'); ?></h3>
            <button onclick="closeVideoModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="aspect-video">
            <iframe id="videoFrame" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black/90 z-50 hidden flex items-center justify-center p-4">
    <div class="max-w-6xl max-h-full overflow-auto">
        <div class="flex justify-between items-center p-4">
            <div></div>
            <button onclick="closeImageModal()" class="text-white hover:text-gray-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="text-center">
            <img id="modalImage" src="" alt="" class="max-w-full max-h-screen object-contain">
        </div>
    </div>
</div>

<script>
// Media tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const mediaTabs = document.querySelectorAll('.tmu-media-tab');
    const mediaSections = document.querySelectorAll('.tmu-media-section');
    
    mediaTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetMedia = this.dataset.media;
            
            // Update active tab
            mediaTabs.forEach(t => {
                t.classList.remove('active', 'border-blue-600', 'text-blue-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.add('active', 'border-blue-600', 'text-blue-600');
            this.classList.remove('border-transparent', 'text-gray-500');
            
            // Update active section
            mediaSections.forEach(section => {
                section.classList.add('hidden');
                section.classList.remove('active');
            });
            const targetSection = document.getElementById(targetMedia);
            if (targetSection) {
                targetSection.classList.remove('hidden');
                targetSection.classList.add('active');
            }
        });
    });
});

// Video modal functions
function openVideoModal(videoId) {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('videoFrame');
    iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('videoFrame');
    iframe.src = '';
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Image modal functions
function openImageModal(type, index) {
    const modal = document.getElementById('imageModal');
    const img = document.getElementById('modalImage');
    
    // This would be dynamic based on actual image data
    const imageData = {
        backdrop: <?php echo json_encode($sample_images['backdrops'] ?? []); ?>,
        poster: <?php echo json_encode($sample_images['posters'] ?? []); ?>
    };
    
    if (imageData[type] && imageData[type][index]) {
        const imagePath = imageData[type][index].file_path;
        img.src = '<?php echo esc_url(tmu_get_image_url('', 'original')); ?>' + imagePath;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeVideoModal();
        closeImageModal();
    }
});
</script>