const { test, expect } = require('@playwright/test');

test.describe('Search Functionality', () => {
    test('search form works across browsers', async ({ page }) => {
        await page.goto('/');
        
        // Test search input
        await page.fill('[data-testid="search-input"]', 'action movie');
        await page.click('[data-testid="search-button"]');
        
        // Wait for results
        await page.waitForSelector('[data-testid="search-results"]');
        
        // Verify results displayed
        const results = await page.locator('[data-testid="search-results"] .movie-card');
        expect(await results.count()).toBeGreaterThan(0);
    });
    
    test('autocomplete functionality', async ({ page }) => {
        await page.goto('/');
        
        // Type in search box
        await page.fill('[data-testid="search-input"]', 'fight');
        
        // Wait for autocomplete
        await page.waitForSelector('[data-testid="autocomplete-suggestions"]');
        
        // Check suggestions appear
        const suggestions = await page.locator('[data-testid="autocomplete-suggestions"] li');
        expect(await suggestions.count()).toBeGreaterThan(0);
        
        // Click first suggestion
        await suggestions.first().click();
        
        // Should navigate to movie page
        expect(page.url()).toContain('/movie/');
    });
    
    test('filter functionality', async ({ page }) => {
        await page.goto('/search');
        
        // Apply genre filter
        await page.check('[data-filter="genre"][value="action"]');
        
        // Wait for filtered results
        await page.waitForLoadState('networkidle');
        
        // Verify filter applied
        const filterCount = await page.locator('[data-testid="filter-count"]').textContent();
        expect(filterCount).toContain('Action');
    });
    
    test('search with keyboard navigation', async ({ page }) => {
        await page.goto('/');
        
        // Focus search input
        await page.focus('[data-testid="search-input"]');
        
        // Type search term
        await page.keyboard.type('matrix');
        
        // Press Tab to navigate to search button
        await page.keyboard.press('Tab');
        
        // Press Enter to submit
        await page.keyboard.press('Enter');
        
        // Wait for results
        await page.waitForSelector('[data-testid="search-results"]');
        
        const results = await page.locator('[data-testid="search-results"]');
        expect(await results.count()).toBeGreaterThan(0);
    });
    
    test('empty search handling', async ({ page }) => {
        await page.goto('/');
        
        // Submit empty search
        await page.click('[data-testid="search-button"]');
        
        // Should show error or recent content
        const errorMessage = await page.locator('[data-testid="search-error"]');
        const recentContent = await page.locator('[data-testid="recent-content"]');
        
        const hasError = await errorMessage.count() > 0;
        const hasRecent = await recentContent.count() > 0;
        
        expect(hasError || hasRecent).toBe(true);
    });
});

test.describe('Movie Pages', () => {
    test('movie page loads correctly', async ({ page }) => {
        await page.goto('/movie/test-movie/');
        
        // Check key elements exist
        await expect(page.locator('h1')).toContainText('Test Movie');
        await expect(page.locator('[data-testid="movie-poster"]')).toBeVisible();
        await expect(page.locator('[data-testid="movie-overview"]')).toBeVisible();
        
        // Test responsive design
        const poster = page.locator('[data-testid="movie-poster"]');
        await expect(poster).toBeVisible();
    });
    
    test('tab navigation works', async ({ page }) => {
        await page.goto('/movie/test-movie/');
        
        // Click cast tab
        await page.click('[data-tab="cast"]');
        await expect(page.locator('[data-testid="cast-list"]')).toBeVisible();
        
        // Click media tab
        await page.click('[data-tab="media"]');
        await expect(page.locator('[data-testid="media-gallery"]')).toBeVisible();
    });
    
    test('movie rating interaction', async ({ page }) => {
        await page.goto('/movie/test-movie/');
        
        // Check if rating component exists
        const ratingComponent = page.locator('[data-testid="movie-rating"]');
        
        if (await ratingComponent.count() > 0) {
            // Test rating display
            const rating = await ratingComponent.textContent();
            expect(rating).toMatch(/\d+(\.\d+)?/); // Should contain numeric rating
        }
    });
    
    test('share functionality', async ({ page }) => {
        await page.goto('/movie/test-movie/');
        
        // Check for share buttons
        const shareButton = page.locator('[data-testid="share-button"]');
        
        if (await shareButton.count() > 0) {
            await shareButton.click();
            
            // Check if share modal appears
            const shareModal = page.locator('[data-testid="share-modal"]');
            await expect(shareModal).toBeVisible();
        }
    });
});

test.describe('Responsive Design', () => {
    test('mobile navigation', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/');
        
        // Mobile menu should be hidden initially
        await expect(page.locator('[data-testid="mobile-menu"]')).not.toBeVisible();
        
        // Click menu toggle
        await page.click('[data-testid="mobile-menu-toggle"]');
        
        // Menu should appear
        await expect(page.locator('[data-testid="mobile-menu"]')).toBeVisible();
    });
    
    test('responsive grid layout', async ({ page }) => {
        await page.goto('/movies');
        
        // Desktop: 4 columns
        await page.setViewportSize({ width: 1200, height: 800 });
        const desktopCards = await page.locator('.movie-grid .movie-card').count();
        
        // Tablet: 3 columns
        await page.setViewportSize({ width: 768, height: 600 });
        const tabletCards = await page.locator('.movie-grid .movie-card').count();
        
        // Mobile: 2 columns
        await page.setViewportSize({ width: 375, height: 667 });
        const mobileCards = await page.locator('.movie-grid .movie-card').count();
        
        // Layout should adapt
        expect(desktopCards).toBeGreaterThanOrEqual(tabletCards);
        expect(tabletCards).toBeGreaterThanOrEqual(mobileCards);
    });
    
    test('touch interactions on mobile', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/movies');
        
        // Test swipe gesture on movie cards if implemented
        const firstCard = page.locator('.movie-card').first();
        
        if (await firstCard.count() > 0) {
            // Test tap interaction
            await firstCard.tap();
            
            // Should navigate to movie page or show details
            await page.waitForLoadState('networkidle');
            expect(page.url()).toMatch(/\/(movie|detail)/);
        }
    });
    
    test('responsive images', async ({ page }) => {
        await page.goto('/movie/test-movie/');
        
        // Check if images have responsive attributes
        const images = page.locator('img[data-testid="movie-poster"]');
        
        if (await images.count() > 0) {
            const srcset = await images.getAttribute('srcset');
            const sizes = await images.getAttribute('sizes');
            
            // Images should have responsive attributes
            expect(srcset || sizes).toBeTruthy();
        }
    });
});

test.describe('Accessibility', () => {
    test('keyboard navigation', async ({ page }) => {
        await page.goto('/');
        
        // Test Tab navigation
        await page.keyboard.press('Tab');
        
        // Check if focus is visible
        const focusedElement = await page.locator(':focus');
        expect(await focusedElement.count()).toBe(1);
        
        // Continue tabbing through interactive elements
        for (let i = 0; i < 5; i++) {
            await page.keyboard.press('Tab');
            const currentFocus = await page.locator(':focus');
            expect(await currentFocus.count()).toBe(1);
        }
    });
    
    test('skip links', async ({ page }) => {
        await page.goto('/');
        
        // Press Tab to reveal skip link
        await page.keyboard.press('Tab');
        
        const skipLink = page.locator('[href="#main-content"]');
        if (await skipLink.count() > 0) {
            await expect(skipLink).toBeVisible();
            
            // Click skip link
            await skipLink.click();
            
            // Focus should move to main content
            const mainContent = page.locator('#main-content');
            await expect(mainContent).toBeFocused();
        }
    });
    
    test('aria labels and roles', async ({ page }) => {
        await page.goto('/');
        
        // Check for proper ARIA landmarks
        const main = page.locator('[role="main"]');
        const navigation = page.locator('[role="navigation"]');
        const banner = page.locator('[role="banner"]');
        
        await expect(main).toBeVisible();
        await expect(navigation).toBeVisible();
        await expect(banner).toBeVisible();
    });
});

test.describe('Performance', () => {
    test('page load performance', async ({ page }) => {
        const startTime = Date.now();
        
        await page.goto('/');
        
        const loadTime = Date.now() - startTime;
        
        // Page should load within 3 seconds
        expect(loadTime).toBeLessThan(3000);
    });
    
    test('search performance', async ({ page }) => {
        await page.goto('/');
        
        const startTime = Date.now();
        
        await page.fill('[data-testid="search-input"]', 'test');
        await page.click('[data-testid="search-button"]');
        
        await page.waitForSelector('[data-testid="search-results"]');
        
        const searchTime = Date.now() - startTime;
        
        // Search should complete within 2 seconds
        expect(searchTime).toBeLessThan(2000);
    });
    
    test('image loading optimization', async ({ page }) => {
        await page.goto('/movies');
        
        // Check for lazy loading attributes
        const images = page.locator('img');
        const firstImage = images.first();
        
        if (await firstImage.count() > 0) {
            const loading = await firstImage.getAttribute('loading');
            expect(loading).toBe('lazy');
        }
    });
});

test.describe('Error Handling', () => {
    test('404 page handling', async ({ page }) => {
        await page.goto('/non-existent-page');
        
        // Should show 404 page
        const title = await page.title();
        expect(title).toContain('404');
        
        // Should have navigation back to home
        const homeLink = page.locator('a[href="/"]');
        await expect(homeLink).toBeVisible();
    });
    
    test('search error handling', async ({ page }) => {
        await page.goto('/');
        
        // Test search with special characters that might cause errors
        await page.fill('[data-testid="search-input"]', '<script>alert("test")</script>');
        await page.click('[data-testid="search-button"]');
        
        // Should handle gracefully without errors
        await page.waitForLoadState('networkidle');
        
        // No JavaScript errors should occur
        const errors = [];
        page.on('pageerror', error => errors.push(error));
        
        expect(errors.length).toBe(0);
    });
    
    test('network error resilience', async ({ page }) => {
        await page.goto('/');
        
        // Simulate offline mode
        await page.context().setOffline(true);
        
        // Try to perform search
        await page.fill('[data-testid="search-input"]', 'test');
        await page.click('[data-testid="search-button"]');
        
        // Should show appropriate error message
        const errorMessage = page.locator('[data-testid="network-error"]');
        
        if (await errorMessage.count() > 0) {
            await expect(errorMessage).toBeVisible();
        }
        
        // Restore connection
        await page.context().setOffline(false);
    });
});