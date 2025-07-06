# TMU WordPress Theme

A modern WordPress theme for movie and TV database management, built with Tailwind CSS and modern development practices.

## Overview

The TMU theme is a comprehensive entertainment content management system that replaces the TMU plugin with a modern WordPress theme architecture. It features TMDB integration, advanced cast/crew management, and a responsive design powered by Tailwind CSS.

## Features

- **Modern Architecture**: Built with PSR-4 autoloading and object-oriented PHP
- **Tailwind CSS Integration**: Utility-first CSS framework for rapid development
- **TMDB Integration**: Automatic synchronization with The Movie Database API
- **Cast/Crew Management**: Comprehensive system for managing movie and TV show credits
- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Performance Optimized**: Modern build tools and asset optimization
- **Accessibility Compliant**: WCAG 2.1 AA standards
- **SEO Optimized**: Schema markup and meta tags

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Node.js 16+ and npm
- Composer

## Installation

1. **Download or clone the theme**:
   ```bash
   git clone <repository-url> wp-content/themes/tmu
   cd wp-content/themes/tmu
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**:
   ```bash
   npm install
   ```

4. **Build assets**:
   ```bash
   # For development
   npm run dev
   
   # For production
   npm run build
   ```

5. **Activate the theme** in WordPress admin

## Development

### Asset Development

The theme uses Webpack for asset compilation with Tailwind CSS:

```bash
# Watch for changes and rebuild automatically
npm run dev

# Build optimized assets for production
npm run build

# Build CSS only
npm run build:css

# Build CSS for production
npm run build:css:prod
```

### Directory Structure

```
tmu-theme/
├── assets/
│   ├── src/                 # Source files
│   │   ├── css/            # Tailwind CSS files
│   │   └── js/             # JavaScript files
│   └── build/              # Compiled assets
├── includes/
│   ├── classes/            # PHP classes
│   ├── config/             # Configuration files
│   └── helpers/            # Helper functions
├── templates/              # Template files
├── languages/              # Translation files
└── docs/                   # Documentation
```

### Post Types

The theme supports the following content types:

- **Movies**: Film content management
- **TV Series**: Television series management
- **Dramas**: Drama series management
- **People**: Cast and crew profiles
- **Videos**: Trailers, clips, and features
- **Seasons**: TV season management
- **Episodes**: Individual episode management

### Custom Fields

The theme includes comprehensive custom fields for:

- Movie metadata (release date, runtime, budget, etc.)
- TV series information (seasons, episodes, networks)
- Cast and crew credits with department/job system
- Person details (biography, filmography, social media)
- TMDB synchronization data

### Tailwind CSS

The theme is built with Tailwind CSS and includes:

- Custom TMU color palette
- Movie-specific aspect ratios
- Responsive design utilities
- Custom components for entertainment content

### API Integration

- **TMDB API**: Automatic data synchronization
- **WordPress REST API**: Custom endpoints
- **AJAX**: Dynamic content loading

## Configuration

### TMDB API Setup

1. Get an API key from [The Movie Database](https://www.themoviedb.org/settings/api)
2. Add the API key in WordPress admin under TMU Settings
3. Configure synchronization preferences

### Theme Options

Access theme options through:
- WordPress Customizer
- TMU Settings page in admin
- Individual post/page meta boxes

## Customization

### Adding Custom Styles

Edit `assets/src/css/main.css` and use Tailwind utilities:

```css
@layer components {
  .my-custom-component {
    @apply bg-tmu-primary text-white rounded-lg p-4;
  }
}
```

### Custom JavaScript

Add functionality in `assets/src/js/main.js`:

```javascript
// Your custom code here
function myCustomFunction() {
    // Implementation
}
```

### Template Customization

Override templates by creating files in your child theme or editing templates in the `templates/` directory.

## Performance

The theme is optimized for performance with:

- Compiled and minified assets
- Lazy loading for images
- Efficient database queries
- Caching mechanisms
- Optimized Tailwind CSS (unused styles purged)

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- IE 11+ (limited support)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This theme is licensed under the MIT License. See LICENSE file for details.

## Support

For support and documentation, visit:
- [Theme Documentation](docs/)
- [GitHub Issues](https://github.com/your-repo/issues)
- [WordPress Support Forums](https://wordpress.org/support/)

## Changelog

### Version 1.0.0
- Initial release
- Tailwind CSS integration
- TMDB API integration
- Cast/crew management system
- Responsive design
- Performance optimizations

---

**Built with ❤️ for the entertainment industry**