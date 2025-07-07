/**
 * Jest Setup File
 * 
 * Global test configuration and utilities for Jest tests
 */

// Mock WordPress globals
global.wp = {
    ajax: {
        post: jest.fn()
    },
    i18n: {
        __: jest.fn((text) => text),
        _e: jest.fn((text) => text),
        _n: jest.fn((single, plural, number) => number === 1 ? single : plural)
    },
    element: {
        createElement: jest.fn(),
        Component: class Component {}
    },
    data: {
        useSelect: jest.fn(),
        useDispatch: jest.fn()
    }
};

// Mock TMU globals
global.tmu_ajax = {
    url: 'http://localhost/wp-admin/admin-ajax.php',
    nonce: 'test-nonce'
};

global.tmu_config = {
    api_url: 'http://localhost/wp-json/tmu/v1/',
    nonce: 'test-nonce'
};

// Mock jQuery
global.$ = global.jQuery = jest.fn((selector) => ({
    on: jest.fn(),
    off: jest.fn(),
    trigger: jest.fn(),
    find: jest.fn(() => global.jQuery()),
    addClass: jest.fn(() => global.jQuery()),
    removeClass: jest.fn(() => global.jQuery()),
    toggleClass: jest.fn(() => global.jQuery()),
    attr: jest.fn(() => global.jQuery()),
    removeAttr: jest.fn(() => global.jQuery()),
    val: jest.fn(() => global.jQuery()),
    text: jest.fn(() => global.jQuery()),
    html: jest.fn(() => global.jQuery()),
    hide: jest.fn(() => global.jQuery()),
    show: jest.fn(() => global.jQuery()),
    fadeIn: jest.fn(() => global.jQuery()),
    fadeOut: jest.fn(() => global.jQuery()),
    slideUp: jest.fn(() => global.jQuery()),
    slideDown: jest.fn(() => global.jQuery()),
    length: 0
}));

// Mock fetch API
global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({}),
        text: () => Promise.resolve('')
    })
);

// Mock console methods to reduce noise in tests
global.console = {
    ...console,
    log: jest.fn(),
    warn: jest.fn(),
    error: jest.fn()
};

// Mock window.location
delete window.location;
window.location = {
    href: 'http://localhost',
    search: '',
    pathname: '/',
    reload: jest.fn()
};

// Mock localStorage
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
    length: 0,
    key: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
global.sessionStorage = localStorageMock;

// Setup DOM testing utilities
import 'jest-environment-jsdom';

// Custom matchers
expect.extend({
    toBeInTheDocument(received) {
        const pass = received && document.body.contains(received);
        return {
            message: () => `expected element ${pass ? 'not ' : ''}to be in the document`,
            pass
        };
    }
});

// Global test helpers
global.createMockElement = (tagName = 'div', attributes = {}) => {
    const element = document.createElement(tagName);
    Object.keys(attributes).forEach(key => {
        element.setAttribute(key, attributes[key]);
    });
    return element;
};

global.createMockEvent = (type = 'click', properties = {}) => {
    const event = new Event(type, { bubbles: true, cancelable: true });
    Object.assign(event, properties);
    return event;
};

// Clean up after each test
afterEach(() => {
    // Clear all mocks
    jest.clearAllMocks();
    
    // Reset DOM
    document.body.innerHTML = '';
    
    // Reset window location
    window.location.href = 'http://localhost';
    window.location.search = '';
    window.location.pathname = '/';
});