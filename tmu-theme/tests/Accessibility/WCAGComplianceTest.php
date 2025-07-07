<?php
/**
 * WCAG Compliance Test
 * 
 * Tests for WCAG 2.1 AA accessibility compliance.
 * 
 * @package TMU\Tests\Accessibility
 * @since 1.0.0
 */

namespace TMU\Tests\Accessibility;

use TMU\Tests\Utilities\DatabaseTestCase;

/**
 * WCAGComplianceTest class
 * 
 * Accessibility tests for WCAG 2.1 AA compliance
 */
class WCAGComplianceTest extends DatabaseTestCase {
    
    /**
     * Test semantic HTML structure
     */
    public function test_semantic_html_structure(): void {
        $movie_id = $this->create_movie(['title' => 'Accessibility Test Movie']);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        // Check for proper heading hierarchy
        $this->assertStringContains('<h1', $content, 'Page should have h1 heading');
        $this->assertMatchesRegularExpression('/<h[1-6][^>]*>/', $content, 'Page should have proper heading elements');
        
        // Check for main landmark
        $this->assertStringContains('<main', $content, 'Page should have main landmark');
        
        // Check for navigation landmark
        $this->assertStringContains('<nav', $content, 'Page should have navigation landmark');
    }
    
    /**
     * Test image alt attributes
     */
    public function test_image_alt_attributes(): void {
        $movie_id = $this->create_movie(['title' => 'Alt Text Test']);
        $attachment_id = $this->create_attachment($movie_id, 'poster.jpg');
        set_post_thumbnail($movie_id, $attachment_id);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        // All images should have alt attributes
        preg_match_all('/<img[^>]+>/', $content, $images);
        
        foreach ($images[0] as $img_tag) {
            $this->assertStringContains('alt=', $img_tag, 'Image missing alt attribute: ' . $img_tag);
        }
    }
    
    /**
     * Test form labels
     */
    public function test_form_labels(): void {
        // Test search form
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // All form inputs should have associated labels
        preg_match_all('/<input[^>]+>/', $content, $inputs);
        
        foreach ($inputs[0] as $input_tag) {
            if (strpos($input_tag, 'type="hidden"') === false) {
                // Input should have label, aria-label, or aria-labelledby
                $has_label = preg_match('/(?:aria-label|aria-labelledby|id=")/', $input_tag);
                $this->assertTrue($has_label, 'Input missing label: ' . $input_tag);
            }
        }
    }
    
    /**
     * Test color contrast
     */
    public function test_color_contrast(): void {
        // This would typically integrate with automated tools
        // For now, we test that contrast-related CSS variables are defined
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Check that contrast-related CSS variables are present in styles
        $has_color_vars = strpos($content, '--text-color') !== false ||
                          strpos($content, '--background-color') !== false ||
                          strpos($content, '--primary-color') !== false;
        
        $this->assertTrue($has_color_vars, 'Color contrast variables should be defined');
    }
    
    /**
     * Test keyboard navigation
     */
    public function test_keyboard_navigation(): void {
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Check for skip links
        $this->assertStringContains('skip-to-content', $content, 'Page should have skip to content link');
        
        // Interactive elements should be focusable
        preg_match_all('/<(?:a|button|input|select|textarea)[^>]*>/', $content, $interactive);
        
        foreach ($interactive[0] as $element) {
            // Should not have tabindex="-1" unless it's intentional
            if (strpos($element, 'tabindex="-1"') !== false) {
                $this->assertStringContains('aria-hidden="true"', $element, 'Focusable element should not have tabindex="-1": ' . $element);
            }
        }
    }
    
    /**
     * Test ARIA attributes
     */
    public function test_aria_attributes(): void {
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Check for proper ARIA usage
        $this->assertStringContains('role="banner"', $content, 'Header should have banner role'); // Header
        $this->assertStringContains('role="main"', $content, 'Main content should have main role');   // Main content
        $this->assertStringContains('role="navigation"', $content, 'Navigation should have navigation role'); // Navigation
        
        // Interactive elements should have proper ARIA
        if (strpos($content, 'aria-expanded') !== false) {
            $this->assertMatchesRegularExpression('/aria-expanded="(?:true|false)"/', $content, 'aria-expanded should have valid values');
        }
    }
    
    /**
     * Test focus management
     */
    public function test_focus_management(): void {
        $movie_id = $this->create_movie(['title' => 'Focus Test Movie']);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        // Check for focus indicators in CSS
        $this->assertStringContains(':focus', $content, 'Should have focus styles defined');
        
        // Interactive elements should be keyboard accessible
        preg_match_all('/<(?:a|button)[^>]*>/', $content, $interactive);
        
        foreach ($interactive[0] as $element) {
            // Should not remove focus outline unless custom styling is provided
            $this->assertStringNotContains('outline: none', $element, 'Should not remove focus outline without replacement');
        }
    }
    
    /**
     * Test heading hierarchy
     */
    public function test_heading_hierarchy(): void {
        $movie_id = $this->create_movie(['title' => 'Heading Test Movie']);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        // Extract headings
        preg_match_all('/<h([1-6])[^>]*>.*?<\/h[1-6]>/', $content, $headings, PREG_SET_ORDER);
        
        if (!empty($headings)) {
            $levels = [];
            foreach ($headings as $heading) {
                $levels[] = (int) $heading[1];
            }
            
            // Check hierarchy is logical
            $this->assertEquals(1, $levels[0], 'First heading should be h1');
            
            // Check no levels are skipped
            for ($i = 1; $i < count($levels); $i++) {
                $diff = $levels[$i] - $levels[$i - 1];
                $this->assertLessThanOrEqual(1, $diff, 'Heading levels should not skip');
            }
        }
    }
    
    /**
     * Test link accessibility
     */
    public function test_link_accessibility(): void {
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Extract links
        preg_match_all('/<a[^>]+href=["\'][^"\']*["\'][^>]*>.*?<\/a>/', $content, $links);
        
        foreach ($links[0] as $link) {
            // Links should have descriptive text or aria-label
            $link_text = strip_tags($link);
            $has_aria_label = strpos($link, 'aria-label') !== false;
            $has_title = strpos($link, 'title') !== false;
            
            if (trim($link_text) === '' && !$has_aria_label && !$has_title) {
                $this->fail('Link without accessible text found: ' . $link);
            }
            
            // Links should not use generic text like "click here"
            $generic_texts = ['click here', 'read more', 'more'];
            foreach ($generic_texts as $generic) {
                if (stripos($link_text, $generic) !== false) {
                    $this->fail('Link uses generic text "' . $generic . '": ' . $link);
                }
            }
        }
    }
    
    /**
     * Test form accessibility
     */
    public function test_form_accessibility(): void {
        // Test admin forms if available
        if (is_admin()) {
            $this->go_to(admin_url('admin.php?page=tmu-settings'));
        } else {
            $this->go_to(home_url('/'));
        }
        
        $content = $this->get_page_content();
        
        // Check form structure
        preg_match_all('/<form[^>]*>.*?<\/form>/s', $content, $forms);
        
        foreach ($forms[0] as $form) {
            // Forms should have proper labels
            preg_match_all('/<input[^>]+>/', $form, $inputs);
            
            foreach ($inputs[0] as $input) {
                if (strpos($input, 'type="submit"') === false && 
                    strpos($input, 'type="hidden"') === false) {
                    
                    $has_label = preg_match('/id=["\']([^"\']+)["\']/', $input, $id_matches);
                    if ($has_label) {
                        $input_id = $id_matches[1];
                        $has_for_label = strpos($form, 'for="' . $input_id . '"') !== false;
                        $has_aria_label = strpos($input, 'aria-label') !== false;
                        
                        $this->assertTrue($has_for_label || $has_aria_label, 'Input should have associated label: ' . $input);
                    }
                }
            }
        }
    }
    
    /**
     * Test table accessibility
     */
    public function test_table_accessibility(): void {
        // Check for data tables on pages
        $this->go_to(home_url('/movies/'));
        $content = $this->get_page_content();
        
        preg_match_all('/<table[^>]*>.*?<\/table>/s', $content, $tables);
        
        foreach ($tables[0] as $table) {
            // Tables should have headers
            $has_th = strpos($table, '<th') !== false;
            $has_thead = strpos($table, '<thead') !== false;
            $has_caption = strpos($table, '<caption') !== false;
            $has_summary = strpos($table, 'summary=') !== false;
            
            if ($has_th || $has_thead) {
                $this->assertTrue(true, 'Table has proper header structure');
            } else {
                $this->assertTrue($has_caption || $has_summary, 'Table should have caption or summary if no headers');
            }
        }
    }
    
    /**
     * Test media accessibility
     */
    public function test_media_accessibility(): void {
        $movie_id = $this->create_movie(['title' => 'Media Test Movie']);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        // Check for video elements
        preg_match_all('/<video[^>]*>.*?<\/video>/s', $content, $videos);
        
        foreach ($videos[0] as $video) {
            // Videos should have controls and accessible attributes
            $this->assertStringContains('controls', $video, 'Video should have controls');
            
            // Check for captions or transcripts
            $has_track = strpos($video, '<track') !== false;
            $has_caption_attr = strpos($video, 'data-caption') !== false;
            
            if (!$has_track && !$has_caption_attr) {
                // This is a warning rather than failure as not all videos need captions
                error_log('Video may need captions or transcript: ' . substr($video, 0, 100));
            }
        }
        
        // Check for audio elements
        preg_match_all('/<audio[^>]*>.*?<\/audio>/s', $content, $audios);
        
        foreach ($audios[0] as $audio) {
            $this->assertStringContains('controls', $audio, 'Audio should have controls');
        }
    }
    
    /**
     * Test responsive accessibility
     */
    public function test_responsive_accessibility(): void {
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Check viewport meta tag
        $this->assertStringContains('viewport', $content, 'Should have viewport meta tag');
        
        // Check for mobile-friendly navigation
        $has_mobile_menu = strpos($content, 'mobile-menu') !== false ||
                           strpos($content, 'menu-toggle') !== false ||
                           strpos($content, 'hamburger') !== false;
        
        $this->assertTrue($has_mobile_menu, 'Should have mobile-friendly navigation');
    }
    
    /**
     * Get page content for testing
     */
    private function get_page_content(): string {
        ob_start();
        
        // Include head content for meta tags and styles
        get_header();
        
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                the_content();
            }
        }
        
        get_footer();
        
        return ob_get_clean();
    }
}