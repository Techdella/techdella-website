// Content blocks (worker A3): Automation, Email, Lead gen, Studios, Journal and Post
// blocks rendered on throwaway pages built with WP-CLI, then removed.
// Needs the local Docker stack.
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { site, expectNoA11yViolations, expectNoHorizontalScroll } from './helpers.js';

/** Run WP-CLI in a site's container; returns trimmed stdout. */
function wp(name, args) {
  return execFileSync('docker', ['compose', 'exec', '-T', '-u', 'www-data', `wp-${name}`, 'wp', ...args], {
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  }).trim();
}

/** Serialized dynamic block. */
const blk = (name, attrs = {}) => `<!-- wp:techdella/${name} ${JSON.stringify(attrs)} /-->`;
const heading = (level, text) =>
  `<!-- wp:heading${level === 3 ? ' {"level":3}' : ''} --><h${level} class="wp-block-heading">${text}</h${level}><!-- /wp:heading -->`;
const para = (text) => `<!-- wp:paragraph --><p>${text}</p><!-- /wp:paragraph -->`;

const SLUG = 'blocks-a3-test';
const created = { marketing: { posts: [], users: [] }, studios: { posts: [], users: [] } };

function createPost(name, args) {
  const id = wp(name, ['post', 'create', ...args, '--porcelain', '--user=admin']);
  created[name].posts.push(id);
  return id;
}

let postId;

test.describe.configure({ mode: 'serial' });

test.beforeAll(() => {
  // Leftovers from an aborted run.
  for (const name of ['marketing', 'studios']) {
    const old = wp(name, ['post', 'list', '--post_type=page', `--name=${SLUG}`, '--field=ID', '--post_status=any']);
    old.split(/\s+/).filter(Boolean).forEach((id) => wp(name, ['post', 'delete', id, '--force']));
    const types = name === 'studios' ? 'studio_work' : 'post,case_study,td_team';
    for (const search of ['E2E', 'Ada Example']) {
      wp(name, ['post', 'list', `--post_type=${types}`, `--s=${search}`, '--field=ID', '--post_status=any'])
        .split(/\s+/).filter(Boolean).forEach((id) => wp(name, ['post', 'delete', id, '--force']));
    }
    wp(name, ['user', 'list', '--search=a3-*', '--field=ID']).split(/\s+/).filter(Boolean)
      .forEach((id) => wp(name, ['user', 'delete', id, '--yes']));
  }

  // Named author + reviewer with a td_team profile (E-E-A-T byline).
  const ts = Date.now();
  const author = wp('marketing', ['user', 'create', `a3-author-${ts}`, `a3-author-${ts}@example.com`, '--role=editor', '--display_name=Ada Example', '--porcelain']);
  const reviewer = wp('marketing', ['user', 'create', `a3-reviewer-${ts}`, `a3-reviewer-${ts}@example.com`, '--role=editor', '--display_name=Ben Example', '--porcelain']);
  created.marketing.users.push(author, reviewer);
  createPost('marketing', ['--post_type=td_team', '--post_title=Ada Example', '--post_status=publish',
    `--meta_input=${JSON.stringify({ wp_user: author, role: 'Example Strategist', linkedin: 'https://www.linkedin.com/in/example' })}`]);

  postId = createPost('marketing', ['--post_type=post', '--post_title=E2E A3 post', '--post_status=publish', `--post_author=${author}`,
    `--post_content=${heading(2, 'First part')}${para('Text.')}${heading(3, 'Detail')}${para('Text.')}`,
    `--meta_input=${JSON.stringify({ _td_checklist_override: '1', reviewer, last_reviewed: '2026-10-01', tldr: 'Short answer for the test.', cta_service: 'paid-ads' })}`]);

  const caseId = createPost('marketing', ['--post_type=case_study', '--post_title=E2E Paycape case', '--post_status=publish',
    `--meta_input=${JSON.stringify({ _td_checklist_override: '1', permission_confirmed: '1', client: 'Paycape', metric_1_num: '50 days', metric_1_label: 'to first result' })}`]);

  const content = [
    blk('toc', {}),
    blk('tldr', {}),
    heading(2, 'How it works'),
    para('Intro paragraph.'),
    heading(3, 'Example'),
    para('First example.'),
    heading(3, 'Example'),
    para('Second example with the same heading text.'),
    heading(2, 'What you can do'),
    para('Closing paragraph.'),
    blk('post-cta', { service: 'paid-ads' }),
    blk('workflow-canvas', { contained: true, steps: [
      { kind: 'trigger', label: 'Trigger', title: 'New form submission' },
      { kind: 'ai', label: 'AI step', title: 'Score, summarize, draft reply' },
      { kind: 'branch', branches: [{ label: 'If hot', title: 'Slack alert to sales', hot: true }, { label: 'If warm', title: 'Start email nurture' }] },
      { kind: 'crm', label: 'CRM', title: 'Contact saved' },
    ], bubble: { meta: 'WhatsApp · 2 min after form', text: 'Hi Ada, thanks for reaching out.' } }),
    blk('email-mock', { variant: 'designs', title: 'Emails people *actually* read', caption: 'Example email designs', emails: [
      { type: 'newsletter', tag: 'Newsletter', subject: 'The Monthly Brief', cta: 'Read more' },
      { type: 'nurture', tag: 'Nurture · Day 3', subject: 'How Paycape reached 15,000 monthly visitors', stats: [{ num: '50 days', label: 'first result' }] },
    ] }),
    blk('email-mock', { variant: 'sequence', contained: true, sequence: [{ day: '0', title: 'Welcome', status: 'Sent', metric: '58% opened' }, { day: '3', title: 'Case study', status: 'Scheduled' }] }),
    blk('lead-mock', { contained: true, offer: 'The SOC 2 readiness checklist', leads: [{ who: 'Head of IT', status: 'Hot', hot: true }], stages: ['Visits', 'Leads', 'Calls'] }),
    blk('journey', { title: 'From stranger to *sales call*', highlight: 1, stages: [{ title: 'Sees an ad', text: 'Paid or SEO' }, { title: 'Becomes a lead', text: 'Form' }, { title: 'Nurtured', text: 'Email' }] }),
    blk('case-card', { postId: Number(caseId) }),
    blk('case-grid', { source: 'case_study', title: 'Case *studies*', count: 3 }),
    blk('post-grid', { title: 'Keep *reading*', count: 3, filters: true }),
    blk('post-card', { postId: Number(postId) }),
    blk('author-box', { postId: Number(postId) }),
    blk('newsletter', {}),
  ].join('\n');
  const pageId = createPost('marketing', ['--post_type=page', `--post_name=${SLUG}`, '--post_title=Blocks A3 test', '--post_status=publish',
    `--post_content=${content}`, `--meta_input=${JSON.stringify({ tldr: 'An answer-first summary for the test page.' })}`]);
  expect(pageId).toMatch(/^\d+$/);

  // Studios: hero + work grid with Live and Concept items.
  createPost('studios', ['--post_type=studio_work', '--post_title=E2E Live work', '--post_status=publish', '--menu_order=1',
    `--meta_input=${JSON.stringify({ _td_checklist_override: '1', status: 'Live', client: 'Example client' })}`]);
  createPost('studios', ['--post_type=studio_work', '--post_title=E2E Concept work', '--post_status=publish', '--menu_order=2',
    `--meta_input=${JSON.stringify({ _td_checklist_override: '1', status: 'Concept' })}`]);
  const studios = [
    blk('studio-hero', { chip: 'Apps · Design', title: 'From idea to something *real*', lead: 'We design and build.', primaryLabel: 'Start a project', primaryUrl: '/start/',
      screens: [{ title: 'Requests', kind: 'list' }, { title: 'Bookings', kind: 'dashboard', concept: true }, { title: 'Automations', kind: 'toggle', concept: true }] }),
    blk('built-for', { items: ['Bootstrapped founders', 'Agencies'] }),
    blk('photo-cards', { image: 'stock/designer-drawing-tablet-studio', title: 'Build faster, and *finally* ship it', cards: [{ title: 'Fixed scope', icon: 'scope', linkLabel: 'See packages', url: '#packages' }, { title: 'Milestones', icon: 'milestone' }] }),
    blk('case-grid', { source: 'studio_work', variant: 'cover', title: 'Selected *work*', count: 4 }),
    blk('arch-people', { title: 'Where ideas get *built*', source: 'inline', people: [
      { name: 'Joanna Okedara-Kalu', role: 'Founder & CEO', image: 'team/joanna-okedara-kalu' },
      { name: 'Abel Saka', role: 'Brand Designer & UI/UX', image: 'team/abel-saka' },
    ], asideTitle: 'Quickly expand your team.', asideCards: [{ tag: 'Founders', title: 'Build an MVP', url: '/start/' }] }),
  ].join('\n');
  createPost('studios', ['--post_type=page', `--post_name=${SLUG}`, '--post_title=Blocks A3 test', '--post_status=publish', `--post_content=${studios}`]);
});

test.afterAll(() => {
  for (const [name, { posts, users }] of Object.entries(created)) {
    posts.forEach((id) => { try { wp(name, ['post', 'delete', id, '--force']); } catch { /* already gone */ } });
    users.forEach((id) => { try { wp(name, ['user', 'delete', id, '--yes']); } catch { /* already gone */ } });
  }
});

test('toc links match the heading ids, including duplicate headings', async ({ page }) => {
  await page.goto(site('marketing', `/${SLUG}/`));
  const toc = page.locator('nav.td-toc');
  await expect(toc).toHaveCount(1);
  const hrefs = await toc.locator('a.td-toc__link').evaluateAll((as) => as.map((a) => a.getAttribute('href')));
  expect(hrefs).toEqual(['#how-it-works', '#example', '#example-2', '#what-you-can-do']);
  for (const href of hrefs) {
    const target = page.locator(`article ${href}`);
    await expect(target).toHaveCount(1);
    expect(await target.evaluate((el) => el.tagName)).toMatch(/^H[23]$/);
  }
  // Current-section highlight (progressive enhancement).
  await page.evaluate(() => {
    const el = document.getElementById('what-you-can-do');
    window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - 100);
  });
  await expect(toc.locator('a[href="#what-you-can-do"]')).toHaveAttribute('aria-current', 'true');
});

test('toc works without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto(site('marketing', `/${SLUG}/`));
  const details = page.locator('nav.td-toc details');
  await expect(details).toHaveAttribute('open', '');
  const link = page.locator('nav.td-toc a[href="#example-2"]');
  await expect(link).toBeVisible();
  await link.click();
  await expect(page).toHaveURL(/#example-2$/);
  await expect(page.locator('#example-2')).toBeInViewport();
  await context.close();
});

test('tldr, author box and post CTA read the post fields', async ({ page }) => {
  await page.goto(site('marketing', `/${SLUG}/`));
  await expect(page.locator('.td-tldr')).toContainText('An answer-first summary for the test page.');
  const box = page.locator('.td-author-box');
  await expect(box).toContainText('Ada Example');
  await expect(box).toContainText('Example Strategist');
  await expect(box).toContainText('Reviewed by');
  await expect(box).toContainText('Ben Example');
  await expect(box.locator('time[datetime="2026-10-01"]')).toHaveCount(1);
  await expect(box).not.toContainText(/TechDella Team/i);
  await expect(page.locator('.td-post-cta')).toContainText('paid ads');
  await expect(page.locator('.td-post-cta a')).toHaveAttribute('href', /growth-audit/);
});

test('mocks are labelled as examples; case blocks show permissioned proof', async ({ page }) => {
  await page.goto(site('marketing', `/${SLUG}/`));
  await expect(page.locator('.td-workflow__caption')).toHaveText('Example workflow');
  await expect(page.locator('.td-workflow__steps > li')).toHaveCount(4);
  await expect(page.locator('.td-email-mock-block').first()).toContainText('Example email designs');
  await expect(page.locator('.td-email-seq__metric')).toContainText('(example)');
  await expect(page.locator('.td-lead-mock')).toContainText('Example lead view');
  await expect(page.locator('.td-journey__stages > li')).toHaveCount(3);
  await expect(page.locator('.td-case-card-block')).toContainText('E2E Paycape case');
  await expect(page.locator('.td-case-grid')).toContainText('E2E Paycape case');
  await expect(page.locator('.td-post-grid')).toContainText('E2E A3 post');
  await expect(page.locator('.td-post-card-block')).toContainText('E2E A3 post');
});

test('newsletter has a labelled email field and an unticked consent box', async ({ page }) => {
  await page.goto(site('marketing', `/${SLUG}/`));
  const nl = page.locator('.td-newsletter');
  const form = nl.locator('form');
  await expect(form).toHaveAttribute('action', /\/techdella\/v1\/submit\/newsletter/);
  await expect(form).toHaveAttribute('method', /post/i);
  const email = form.locator('input[type="email"]');
  await expect(email).toHaveCount(1);
  const emailId = await email.getAttribute('id');
  await expect(form.locator(`label[for="${emailId}"]`)).toHaveCount(1);
  const consent = form.locator('input[type="checkbox"]');
  await expect(consent).toHaveCount(1);
  await expect(consent).not.toBeChecked();
  const consentId = await consent.getAttribute('id');
  await expect(form.locator(`label[for="${consentId}"]`)).toHaveCount(1);
});

test('studios: Concept labels on concept screens and work', async ({ page }) => {
  await page.goto(site('studios', `/${SLUG}/`));
  await expect(page.locator('.td-studio-hero h1')).toContainText('From idea to something');
  await expect(page.locator('.td-phone--concept .td-label-example')).toHaveCount(2);
  await expect(page.locator('.td-phone--concept .td-label-example').first()).toHaveText('Concept');
  const concept = page.locator('.td-case-card--cover', { hasText: 'E2E Concept work' });
  await expect(concept.locator('.td-label-example')).toHaveText('Concept');
  await expect(concept.locator('.td-case-card__tag')).toContainText('Concept');
  const live = page.locator('.td-case-card--cover', { hasText: 'E2E Live work' });
  await expect(live.locator('.td-case-card__live')).toContainText('Live');
  await expect(live.locator('.td-label-example')).toHaveCount(0);
  await expect(page.locator('.td-arch-people__name')).toContainText(['Joanna Okedara-Kalu', 'Abel Saka']);
});

for (const name of ['marketing', 'studios']) {
  test(`${name}: axe clean, no horizontal scroll at 390px`, async ({ page }) => {
    await page.goto(site(name, `/${SLUG}/`));
    await expectNoA11yViolations(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(site(name, `/${SLUG}/`));
    await expectNoHorizontalScroll(page);
    await expectNoA11yViolations(page);
  });
}
