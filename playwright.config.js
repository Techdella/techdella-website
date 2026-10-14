// Playwright e2e against the local stack (§16). Start it with `make up` first.
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: 'tests/e2e',
  timeout: 30_000,
  expect: { timeout: 7_000 },
  fullyParallel: false,
  workers: 2,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL: process.env.MARKETING_URL || 'http://localhost:8081',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } }, testIgnore: /editor-validity/ },
    { name: 'mobile', use: { ...devices['Pixel 7'] }, grep: /@mobile|@smoke/, testIgnore: /editor-validity/ },
    // The block editor is heavy: check it after the other projects, with nothing else hitting the stack.
    { name: 'editor', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } }, testMatch: /editor-validity/, dependencies: ['desktop', 'mobile'] },
  ],
});
