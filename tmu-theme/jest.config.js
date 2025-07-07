/**
 * Jest Configuration for TMU Theme JavaScript Tests
 */

module.exports = {
  // Test environment
  testEnvironment: 'jsdom',
  
  // Setup files
  setupFilesAfterEnv: ['<rootDir>/tests/jest-setup.js'],
  
  // Test file patterns
  testMatch: [
    '<rootDir>/tests/**/*.test.js',
    '<rootDir>/assets/src/js/**/*.test.js'
  ],
  
  // Module paths
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/assets/src/js/$1',
    '^@tests/(.*)$': '<rootDir>/tests/$1'
  },
  
  // Transform configuration
  transform: {
    '^.+\\.js$': 'babel-jest'
  },
  
  // Coverage configuration
  collectCoverage: true,
  coverageDirectory: '<rootDir>/coverage/js',
  coverageReporters: ['text', 'lcov', 'html'],
  collectCoverageFrom: [
    'assets/src/js/**/*.js',
    '!assets/src/js/**/*.test.js',
    '!assets/src/js/vendor/**',
    '!node_modules/**'
  ],
  
  // Coverage thresholds
  coverageThreshold: {
    global: {
      branches: 80,
      functions: 80,
      lines: 80,
      statements: 80
    }
  },
  
  // Test timeout
  testTimeout: 10000,
  
  // Module file extensions
  moduleFileExtensions: ['js', 'json'],
  
  // Clear mocks between tests
  clearMocks: true,
  
  // Restore mocks after each test
  restoreMocks: true,
  
  // Verbose output
  verbose: true,
  
  // Global variables
  globals: {
    'window': {},
    'document': {},
    'wp': {
      'ajax': {
        'post': jest.fn()
      },
      'i18n': {
        '__': jest.fn((text) => text),
        '_e': jest.fn((text) => text),
        '_n': jest.fn((single, plural, number) => number === 1 ? single : plural)
      }
    },
    'tmu_ajax': {
      'url': 'http://localhost/wp-admin/admin-ajax.php',
      'nonce': 'test-nonce'
    },
    'tmu_config': {
      'api_url': 'http://localhost/wp-json/tmu/v1/',
      'nonce': 'test-nonce'
    }
  }
};