import js from '@eslint/js';
import globals from 'globals';

export default [
  { ignores: ['node_modules/', 'vendor/', '**/assets/build/', '**/*.min.js', 'prototype/', 'test-results/', 'playwright-report/', 'motion/', '.lighthouseci/'] },
  js.configs.recommended,
  {
    files: ['wp-content/**/*.js'],
    languageOptions: { ecmaVersion: 2022, sourceType: 'module', globals: { ...globals.browser, wp: 'readonly', TD: 'readonly' } },
  },
  {
    files: ['scripts/**/*.mjs', 'tests/**/*.js', 'tools/**/*.js', '*.config.js', 'pdf/**/*.mjs'],
    languageOptions: { ecmaVersion: 2022, sourceType: 'module', globals: { ...globals.node } },
  },
  {
    files: ['tests/**/*.js'],
    languageOptions: { globals: { ...globals.node, ...globals.browser } },
  },
  { files: ['tools/sb-mock/**/*.js', '**/*.cjs'], languageOptions: { sourceType: 'commonjs' } },
];
