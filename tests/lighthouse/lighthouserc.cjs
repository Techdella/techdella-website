// Lighthouse CI against the local stack (§8.4, §16). Mobile, Moto G-class throttling (LH default).
module.exports = {
  ci: {
    collect: {
      url: [
        'http://localhost:8081/',
        'http://localhost:8081/styleguide/',
        'http://localhost:8082/',
        'http://localhost:8083/',
      ],
      numberOfRuns: 1,
      settings: { chromeFlags: '--no-sandbox --headless=new' },
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.9 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
        'categories:seo': ['warn', { minScore: 0.9 }],
        'largest-contentful-paint': ['error', { maxNumericValue: 2000 }],
        'cumulative-layout-shift': ['error', { maxNumericValue: 0.05 }],
        'total-byte-weight': ['error', { maxNumericValue: 900 * 1024 }],
        'render-blocking-resources': ['warn', { maxLength: 0 }],
      },
    },
    upload: { target: 'filesystem', outputDir: '.lighthouseci' },
  },
};
