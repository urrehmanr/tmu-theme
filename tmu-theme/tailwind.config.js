/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.php",    // [CREATE DIR - STEP 1] Template files
    "./includes/**/*.php",     // [CREATE DIR - STEP 1] PHP class files
    "./assets/src/js/**/*.js", // [CREATE DIR - STEP 1] JavaScript files
    "./*.php"                  // Root PHP files (functions.php, etc.)
  ],
  theme: {
    extend: {
      colors: {
        // TMU Brand Colors - FIRST TIME IMPLEMENTATION
        'tmu-primary': '#1e40af',    // Blue for primary actions
        'tmu-secondary': '#dc2626',  // Red for secondary actions
        'tmu-accent': '#059669',     // Green for success states
        'tmu-dark': '#1f2937',       // Dark gray for text
        'tmu-light': '#f9fafb',      // Light gray for backgrounds
        'tmu-yellow': '#f59e0b',     // Yellow for ratings/stars
        'tmu-purple': '#7c3aed'      // Purple for special elements
      },
      fontFamily: {
        // Custom font stacks for better typography
        'sans': ['Inter', 'system-ui', 'sans-serif'],
        'serif': ['Merriweather', 'serif'],
        'mono': ['JetBrains Mono', 'monospace']
      },
      spacing: {
        // Custom spacing for movie/TV layouts
        '18': '4.5rem',
        '88': '22rem',
        '112': '28rem',
        '128': '32rem'
      },
      screens: {
        // Additional breakpoints for responsive design
        'xs': '475px',
        '3xl': '1920px'
      },
      aspectRatio: {
        // Movie poster specific aspect ratios - CUSTOM FOR TMU
        'movie': '2/3',      // Standard movie poster ratio
        'poster': '27/40'    // TMDB poster ratio
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),        // Form styling
    require('@tailwindcss/typography'),   // Rich text content
    require('@tailwindcss/aspect-ratio')  // Aspect ratio utilities
  ],
}