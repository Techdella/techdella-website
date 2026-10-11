# TechDella: Website Build Spec for Claude Code

**Sites:** techdella.com (Marketing) · blog.techdella.com (Journal) · studios.techdella.com (Studios)
**Host:** Hostinger (WordPress) · **Forms/CRM/automation:** Startbuddi · **Spec date:** 10 Oct 2026
**Owner:** Joanna Okedara-Kalu

> **How to use this file.** Put this kit in the root of a new Git repo, copy this file to `CLAUDE.md`, and open the folder in Claude Code. Work one phase at a time (§15). Each phase ends with a checklist; don't start the next phase until it passes. Anything in `[BRACKETS]` is a value a person must supply. Claude Code must never invent it.

---

## 0. Ground rules for Claude Code

1. **The prototype is the design source of truth.** It lives in `prototype/*.dc.html` (also on the canvas). Match its layout, spacing, type and colour. The prototype uses `/_blob/…` image URLs: treat those as placeholders and replace them with assets from `assets/` (§6).
2. **Never break a URL that ranks.** Every URL in `data/url-map.csv` needs an action before launch. A URL is redirected only if the map says `redirect`/`move`, or if a `merge-review` passes its GSC check (§9).
3. **No secrets in Git.** Endpoints, API keys, the AdSense ID and SSH details go in `.env` / `wp-config.php` constants only. `.env.example` is committed; `.env` is not.
4. **Content is drafted, never auto-published.** Claude Code may create posts as `draft` or `pending` via WP-CLI. A named human reviews and publishes (§10.1). This is required by Google's scaled-content-abuse policy, not just a preference.
5. **Don't fabricate proof.** No invented metrics, clients, testimonials, reviews, awards or logos. Use only the verified proof in §2.4. Mark anything else as `[CONFIRM]`.
6. **Platform logos.** Use official brand-asset files that the team downloads from each platform's press/brand page (`assets/logos/platforms/`). Follow each platform's usage rules: no recolouring, minimum clear space. If a file is missing, fall back to the text badge from the prototype. Never hand-draw a logo.
7. **Performance budget is a requirement** (§8.4). A page that fails it isn't done.
8. **Small commits, one concern each.** Run `composer lint`, `npm run build` and `npm run test:e2e` before every push.

---

## 1. Decisions taken in this spec (change them here if you disagree)

| # | Decision | Default in this spec | Why / trade-off |
|---|---|---|---|
| D1 | Guides location | Move the 17 `/guide/*` posts to `blog.techdella.com/<slug>/` with 1:1 301s, as the brief asks | A subdomain is treated more like a separate site: some ranking strength stays behind on the main domain. The safer SEO option is keeping guides at `techdella.com/guides/`. Moving 17 posts is recoverable, but expect 4–8 weeks of volatility. |
| D2 | Install layout | Three separate WordPress installs on Hostinger, one per (sub)domain, sharing one theme and one core plugin from this repo | Simpler than multisite on shared hosting, and each site can be restored on its own |
| D3 | Theme model | One theme, `techdella`, plus a must-use/core plugin, `techdella-core`. A `TD_SITE` constant (`marketing`/`journal`/`studios`) switches nav, footer and templates | One design system, no drift between three themes |
| D4 | Editing model | Gutenberg with **ACF Pro blocks** that mirror prototype sections, and locked page patterns | Team edits text and images, but layout stays on-brand. Needs an ACF Pro licence `[CONFIRM]`. If none, use native block patterns. |
| D5 | SEO plugin | Rank Math (free) for titles/meta UI, sitemaps and the 404 monitor. Schema comes from `techdella-core`; Rank Math schema is off | Easy for the team. Our schema is precise per template. |
| D6 | Redirects | Stored in `data/redirects.csv`, imported into Rank Math Redirections. Old theme code in `td_redirects()` is **deleted** | The old function uses `strpos` substring matching and sends live URLs to pages that don't exist (see §9.2) |
| D7 | Forms | Every form posts to our own REST route. WordPress validates it, then forwards it server-side to the Startbuddi endpoint for that form. **WordPress stores nothing permanently.** | Hides endpoints, blocks spam, retries if Startbuddi is down. CRM and automations live in Startbuddi only. |
| D8 | Ads | AdSense only on `/tools/*` pages, with manual ad units, a certified CMP, and no Auto ads | Keeps service pages clean and protects Core Web Vitals |
| D9 | Location pages | Keep 7 market pages. Merge 6 thin city/country pages, but only after a GSC check | Google's doorway-page policy. Pages must carry unique proof, not a swapped city name. |
| D10 | Positioning | Marketing = search + paid + lead gen + email + AI automation, with search as the lead offer. Studios = build. | This is the expanded scope the owner chose in October. It goes beyond the original brief's search-only positioning. |

---

## 2. Brand system (implement exactly)

Source: `prototype/System.dc.html`. Put these tokens in `theme/assets/css/tokens.css` and in `theme.json`, so the editor and the front end share them.

### 2.1 Colour tokens

```css
:root{
  /* Neutrals (≈60%) */
  --td-ink:#0D0D0D;      --td-paper:#FFFFFF;   --td-canvas:#F7F5F2;  --td-sand:#EFE7DD;
  --td-body:#57534E;     --td-muted:#6B6560;   --td-line:#E7E4DF;    --td-line-soft:#EFEDE9;
  /* Dark surfaces (≈30% on dark sections) */
  --td-dark-1:#1A1715;   --td-dark-border:#2E2A27; --td-dark-2:#262321; --td-on-dark:#C9C4BE;
  /* Signal (≤10% — accents only) */
  --td-orange:#E8470A;   --td-orange-tint:#FDF0E9; --td-orange-text:#C23B07;
  --td-gradient:linear-gradient(135deg,#D62F14 0%,#F98A0F 100%); /* logo + one CTA arch per page max */
  /* Radii, shadow, space */
  --td-r-sm:12px; --td-r-md:20px; --td-r-lg:28px; --td-r-pill:999px; --td-arch:999px 999px 24px 24px;
  --td-shadow:0 1px 2px rgba(13,13,13,.04),0 8px 24px rgba(13,13,13,.06);
  --td-space:clamp(64px,8vw,120px); /* section padding */
  --td-max:1240px;
}
```

**Orange rules.** These enforce the owner's "not too much orange/red":

- Orange is allowed only in: the button arrow circle, label-chip dots, one highlighted stat or word per section, links on hover, and focus rings.
- Orange is never used for section backgrounds or body text.
- Large text on paper uses `--td-orange-text`, which passes 4.5:1 contrast.
- The gradient appears at most once per page: the logo or the final CTA arch.
- Colour on a page should come from **photography and creatives**, not UI chrome.

### 2.2 Type

- **Geist** (UI and body, 400/500/600) and **Instrument Serif Italic** (one accent word per headline, never a full sentence).
- Self-host both as WOFF2 subsets in `theme/assets/fonts/`. Do not use the Google Fonts CDN, because of privacy and LCP.
- `font-display: swap`. Preload the Geist 500 and Instrument Serif files only.
- Scale: H1 `clamp(44px,6vw,84px)/1.02`, tracking −0.03em · H2 `clamp(32px,4vw,56px)/1.06` · H3 24/1.2 · body 17/1.6 · small 14/1.5.
- **Syne, Outfit and Manrope are removed everywhere**, including in the blog theme and in email templates.

### 2.3 Components (build once in `theme/blocks/`, reuse everywhere)

| Component | Prototype reference | Notes |
|---|---|---|
| `btn-primary` | System → signature pill (black pill + orange circle arrow) | Min height 52px. Arrow nudges 2px on hover. Visible focus ring. |
| `btn-secondary` | System | White pill with ink border |
| `label-chip` | System | Orange dot + uppercase 12px label |
| `nav` | Main | Sticky. Blur on scroll. Service mega-menu shows the 6 services + Studios. Mobile sheet. |
| `hero-orbit` | Main | Headline + platform chips orbiting. Pause animation when `prefers-reduced-motion`. |
| `logo-strip` | Main | Client logos (real, permissioned only) |
| `bento-grid` | Main, all service pages | 12-col CSS grid. Tiles: stat, image, quote, link, platform-list |
| `steps-fold` | Main "Clear steps" | `<details>`/`<summary>` accordion with chevron rotate. Works without JS. |
| `hub-spoke` | Options B | Alternative "connected system" block, available as a pattern |
| `timeline` | Options | Vertical alternating |
| `stat-pills` | Main (Paycape) | |
| `testimonial` | SEO, AISearch | Verbatim quotes only (§2.4). Outputs `Review` schema **only** if shown on the page it's about. |
| `faq` | All service pages | `<details>` + FAQ content. **Do not emit FAQPage schema** (§8.2). |
| `platform-badges` | SEO, AISearch, Paid | Official logo files or text badge fallback |
| `ad-mock` / `creative-gallery` | Paid | Real creatives from `assets/creatives/`, each labelled "Example" if not a live client ad |
| `tabs` | Paid | Accessible ARIA tabs, keyboard arrows |
| `workflow-canvas` | Automation | SVG nodes and lines |
| `email-mock` | Email | |
| `serp-mock` | SEO | |
| `case-card` | Main, Studios, Automation | Pulls from `case_study` CPT |
| `arch-people` | Studios | Arch-masked team photos |
| `cta-dark` | Main | Dark section + gradient arch (the page's one gradient use) |
| `form` | Audit, Start, Contact | See §11. Pill options, inline validation, success state. |
| `toc` | Post | Sticky on desktop, collapsible on mobile |
| `newsletter` | Blog | Dark block |
| `ad-slot` | Tools | Reserved height, labelled "Advertisement" (§12) |
| `popup` | — | Exit-intent / scroll slide-in / bar (§13) |

Blocks must:

- Render server-side (PHP `render.php`).
- Have no jQuery.
- Share one small vanilla JS bundle under 25 KB gzipped.
- Pass axe with no serious or critical issues.

### 2.4 Verified proof (the only proof copy allowed without `[CONFIRM]`)

- **Paycape:**
  - 50% of conversions came from inbound.
  - ~15,000 monthly organic visitors.
  - 50 days to first result.
  - 5-month engagement (Q3 2025).
  - Source: the published case study. Re-confirm before use in ads.
- **11 published case studies:** Paycape, startbuddi, Lykluk, Epic Entertainment Games, Havyn, WezOps, Shopinbos, Angie Diedricks, BlueCart, Johan Consults, Three Cat Agency.
- **Client countries:** Nigeria, US, Mexico, South Africa, Lebanon.
- **Testimonials (verbatim, do not edit):**
  - **Levi Barbosa, CEO, WezOps:** "Techdella reframed how we were thinking about our market entirely. We went from scattered tactics to a positioning and GTM system that actually made sense for where we were heading. The difference was immediate."
  - **Akin Oyegoke, CEO, Johan Consults:** "The clarity they brought to our strategy saved us months of trial and error — and probably a lot of money too…" (use the full original from the case study).
- **Team:**
  - Joanna Okedara-Kalu (Founder & CEO)
  - Chinedu Emmanuel Kalu (COO & Co-founder)
  - Abel Saka (Brand Designer & UI/UX)
  - Omolola Akiyode (TechDella Team)
  - Mercy Olumakinwa (TechDella Team)
- **Approved by Joanna (11 Oct 2026):** the founder letter ("Hi, I'm Joanna") as drafted, and client
  permissions for every existing case study, testimonial and logo (`wp td permissions:grant`).
- **Draft copy that still needs Joanna's sign-off before launch:**
  - Any new quote attributed to Joanna.
  - The 3 Studios "Concept" projects, which stay labelled "Concept".
  - All prices: `[PRICE]`, `[MINIMUM SPEND]`.

---

## 3. Repository layout

```
techdella-web/
├─ CLAUDE.md                     ← this file
├─ .env.example                  ← every variable in §4.3, no values
├─ docker-compose.yml            ← local stack (§4)
├─ Makefile                      ← make up / down / pull-db / deploy-staging …
├─ composer.json                 ← PHPCS (WordPress standard), PHPStan
├─ package.json                  ← build (esbuild + lightningcss), Playwright, Lighthouse CI
├─ wp-content/
│  ├─ themes/techdella/
│  │  ├─ theme.json  style.css  functions.php
│  │  ├─ assets/{css,js,fonts,img,svg}
│  │  ├─ blocks/<block-name>/{block.json,render.php,style.css,view.js}
│  │  ├─ patterns/               ← full-page patterns per template (Home, Service, Solution …)
│  │  ├─ templates/              ← PHP templates (see §5)
│  │  └─ parts/{nav-marketing,nav-journal,nav-studios,footer-*}.php
│  └─ mu-plugins/
│     ├─ techdella-core.php      ← loader
│     └─ techdella-core/
│        ├─ cpt/                 ← CPTs + taxonomies (§7)
│        ├─ acf-json/            ← ACF field groups as JSON (version-controlled)
│        ├─ forms/               ← REST route + Startbuddi forwarder + retry queue (§11)
│        ├─ seo/                 ← schema, canonicals, robots, llms.txt, breadcrumbs (§8)
│        ├─ popups/              ← rules + render (§13)
│        ├─ ads/                 ← AdSense + CMP loader, ads.txt (§12)
│        ├─ admin/               ← dashboard, menus, roles, branding (§14)
│        └─ cli/                 ← WP-CLI commands: td import-content, td redirects, td audit
├─ content/                      ← Markdown + front-matter source for every page/post (§10)
│  ├─ marketing/ journal/ studios/ glossary/ case-studies/ solutions/ locations/ tools/ resources/
├─ assets/                       ← source images, creatives, logos, video (§6) — large files via Git LFS
├─ motion/                       ← Remotion project for motion samples (§6.3)
├─ pdf/                          ← lead-magnet HTML templates → PDF (§13.3)
├─ data/
│  ├─ content-inventory.csv      ← every item in the current WP export (generated 10 Oct 2026)
│  ├─ url-map.csv                ← action per old URL
│  └─ redirects.csv              ← 301 list (§9)
├─ prototype/                    ← design source (.dc.html)
└─ tests/{e2e,lighthouse,a11y}
```

---

## 4. Local development (localhost)

### 4.1 Stack

Docker Compose runs:

- one MariaDB 10.11 container, with three databases;
- three `wordpress:php8.2-apache` containers. Hostinger runs PHP 8.2/8.3 on LiteSpeed. Apache is close enough, and `.htaccess` behaves the same.

All three containers mount this repo's `wp-content/themes/techdella` and `wp-content/mu-plugins`.

| Site | Local URL | Container | `TD_SITE` |
|---|---|---|---|
| Marketing | http://localhost:8081 | `wp-marketing` | `marketing` |
| Journal | http://localhost:8082 | `wp-journal` | `journal` |
| Studios | http://localhost:8083 | `wp-studios` | `studios` |
| Mail catcher | http://localhost:8025 | `mailpit` | — catches every `wp_mail` |
| phpMyAdmin | http://localhost:8080 | `pma` | — |
| Startbuddi mock | http://localhost:8090 | `sb-mock` | — echoes and logs form POSTs (§11.5) |

Cross-links use a `td_url('studios', '/path/')` helper, so they resolve to the localhost ports in dev and to real domains in production. **Never hard-code domains in templates.**

### 4.2 Commands (Makefile)

```bash
make up            # docker compose up -d, install WP core + plugins via WP-CLI on first run, activate theme
make seed          # import content/ (Markdown) as DRAFTS + menus + ACF options  → see §10
make pull-db SITE=marketing   # ssh to Hostinger, wp db export, download, import, search-replace to localhost:8081
make pull-uploads SITE=marketing  # rsync uploads (read-only from prod)
make watch         # npm run watch (CSS/JS rebuild)
make test          # lint + Playwright e2e + axe + Lighthouse CI against localhost
make deploy-staging / make deploy-prod   # §5.3
make down
```

Seeing the sites:

- **On your own machine:** open the localhost URLs above in a browser.
- **For a team preview without deploying:** `make share SITE=marketing` starts a temporary Cloudflare quick tunnel (`cloudflared tunnel --url http://localhost:8081`) and prints a public URL. Stop it when done.
- **For anything reviewed by more than one person:** use Hostinger staging (§5.3).

### 4.3 Environment variables (`.env.example`)

```
# Hostinger
HOSTINGER_SSH_HOST=[HOST]          HOSTINGER_SSH_PORT=65002   HOSTINGER_SSH_USER=[USER]
PATH_MARKETING=[e.g. ~/domains/techdella.com/public_html]
PATH_JOURNAL=[path for blog.techdella.com]     PATH_STUDIOS=[path for studios.techdella.com]
PATH_STAGING=[staging path]
# Startbuddi — one endpoint URL per form (copy from Startbuddi → Forms → <endpoint form> → settings)
SB_ENDPOINT_CONTACT=  SB_ENDPOINT_AUDIT=  SB_ENDPOINT_STUDIOS_BRIEF=  SB_ENDPOINT_NEWSLETTER=
SB_ENDPOINT_LEAD_MAGNET=  SB_ENDPOINT_TOOL_GATE=  SB_ENDPOINT_POPUP_EXIT=  SB_ENDPOINT_POPUP_SCROLL=
SB_ENDPOINT_MVP_CALC=  SB_ENDPOINT_GUEST_POST=
SB_SHARED_SECRET=[optional HMAC secret if Startbuddi supports signed requests]
# Spam
TURNSTILE_SITE_KEY=  TURNSTILE_SECRET_KEY=
# Ads + analytics
ADSENSE_PUBLISHER_ID=[ca-pub-…]   GA4_ID=[G-…]   GTM_ID=[optional]   CLARITY_ID=[optional]
# Misc
TD_ENV=local|staging|production
```

`wp-config.php` reads these with `getenv()` and defines PHP constants. **The production values are set in hPanel or in `wp-config.php` on the server, never committed.**

---

## 5. Hosting & deployment (Hostinger)

### 5.1 One-time setup in hPanel (a person does this; Claude Code gives the checklist)

1. Create the subdomains `blog` and `studios` (and `staging` if you're not using Hostinger's built-in WordPress staging). Install WordPress on each one.
2. Turn on SSH access. Add the deploy SSH key. Hostinger's SSH port is usually **65002**: confirm it in hPanel.
3. Set PHP to 8.2 or newer, `memory_limit` 256M, and `max_execution_time` 120.
4. Turn on free SSL for all three domains and force HTTPS.
5. Install **LiteSpeed Cache**. Settings:
   - page cache on;
   - CSS/JS minify **off**, because our build already does it;
   - image WebP **off**, because we ship AVIF/WebP ourselves;
   - Guest mode off;
   - QUIC.cloud CDN optional.
6. Turn on daily backups and confirm one restore works before Phase 3.
7. Set up DNS (on Hostinger or Cloudflare):
   - add Google Search Console TXT verification for each domain property;
   - keep MX records untouched.

### 5.2 Plugins (keep the list short; anything not listed gets removed)

**Install:**

- ACF Pro `[licence]`
- Rank Math
- LiteSpeed Cache
- WP Mail SMTP (sends via `[SMTP provider]`; only for admin and system mail, since Startbuddi sends marketing mail)
- Safe SVG
- Simple Cloudflare Turnstile is **not** needed, because we verify Turnstile in core

**Remove** after migration:

- Elementor (an `e-landing-page` exists in the export: rebuild it, then remove the plugin)
- duplicate SEO plugins
- form plugins
- popup plugins
- page builders

### 5.3 Deploy

- **GitHub Actions** builds assets, then rsyncs over SSH:
  - `wp-content/themes/techdella/` and `wp-content/mu-plugins/` go to each site path.
  - Uploads and `wp-config.php` are never touched.
- A merge to `main` deploys to **staging**. A tagged release `v*` deploys to **production**. This needs a manual approval step in the Action.
- After deploy:
  - `wp cache flush` and `wp litespeed-purge all` over SSH;
  - Rank Math sitemap regenerates;
  - smoke tests (Playwright `@smoke`) run against the deployed URL.
- Staging carries `X-Robots-Tag: noindex` and HTTP basic auth. Check this in CI: **staging must never be indexed.**
- Rollback: `make rollback TAG=vX.Y.Z` redeploys the previous tag.

---

## 6. Images, creatives and motion

### 6.1 Image direction ("cleaner images")

- **Real over stock.** Shoot a half-day team session: the 6 team members and the workspace. Capture:
  - laptop and phone screens with real dashboards (blur client data);
  - hands-on-whiteboard shots;
  - portraits on Canvas `#F7F5F2` and Sand `#EFE7DD` backdrops (these feed the arch masks);
  - natural light and warm tones, which carry the page colour.
- **Product visuals:** UI mocks (SERP, ads, email, workflows) are built in HTML/SVG in the blocks, not screenshots. That keeps them crisp, light and editable.
- **Creatives:** real ads the team made, with client permission → `assets/creatives/<platform>/`. When an ad was made for the site, label it "Example".
- **If stock is unavoidable:** use one source, then colour-grade it to the warm palette. No handshake, no "diverse team pointing at laptop".
- **Export:**
  - AVIF + WebP + JPEG fallback through `<picture>`, generated by `npm run images` (sharp);
  - widths 480/768/1200/1600/2400;
  - `sizes` always set;
  - hero images `fetchpriority="high"` and no lazy-load; everything else `loading="lazy" decoding="async"`.
- **Alt text** describes what is shown. It is not stuffed with keywords.
- **Files are named descriptively**, e.g. `b2b-seo-serp-example.avif`.

### 6.2 Image shot list (send to the photographer/designer)

| ID | Use | Spec |
|---|---|---|
| IMG-01 | Home hero background | Wide, airy desk scene, warm. 2400×1400, space on the left for the headline |
| IMG-02 | Team (Main "steps", About) | Group, natural, 3:2 |
| IMG-03–08 | Arch portraits ×6 | 4:5, Sand backdrop, shoulders up |
| IMG-09 | Studios hero phone screens | Real app screens (Epic) + 2 concept screens labelled Concept |
| IMG-10 | Paid creatives ×12 | 3 each for TikTok (9:16), Meta (1:1, 4:5), LinkedIn (1.91:1), Google display |
| IMG-11 | Case study covers ×11 | Client product shot or logo on Canvas, 16:9 |
| IMG-12 | Journal covers | Template-driven (title + one image) so the team can make them in 2 minutes |
| IMG-13 | OG images | 1200×630, auto-generated per page from a template (§8.5) |

### 6.3 Motion video samples (Remotion, in `motion/`)

Claude Code builds these with **Remotion** (React → MP4), so they stay on-brand and can be re-rendered when copy changes. Each one renders three outputs:

- 1080×1920 (Reels/TikTok)
- 1080×1080
- 1920×1080

Each output gets an H.264 MP4 (CRF 23) and a WebM (VP9), plus a poster JPG.

| ID | Length | Content | Used on |
|---|---|---|---|
| MO-01 | 6s loop | Homepage hero: platform chips orbit, "Grow." word swap | Home hero (muted, `autoplay loop playsinline`, poster first) |
| MO-02 | 15s | "Search → Ads → Lead → Email → Automation" connected-system explainer | Home bento, Services hub |
| MO-03 | 12s | SERP + AI answer appearing, brand gets cited | AI Search page |
| MO-04 | 3 × 10s | Ad creative samples: TikTok hook, Meta carousel, LinkedIn doc ad (fictional brand "Northwind" — labelled Example) | Paid page gallery, Studios Motion |
| MO-05 | 10s | Workflow nodes lighting up (form → CRM → email → Slack) | Automation page |
| MO-06 | 8s | Logo reveal with gradient arch | Studios, social |
| MO-07 | 20s | Studios reel: UI screens, motion type, product shots | Studios hero/Work |

Rules:

- Under 2.5 MB per web loop.
- Never autoplay with sound.
- Respect `prefers-reduced-motion`: show the poster only.
- Each video sits in a `<video>` with captions (`.vtt`) where there's speech or text.
- **Self-host in Uploads.** No YouTube embeds above the fold.

Render with `npm run motion:render`. Output goes to `assets/video/`.

---

## 7. Content model (CPTs, taxonomies, fields)

Existing post types in the export:

- `post` (17)
- `case_study` (11)
- `em-glossary` (148)
- `business` (53)
- `location` (13)
- `solution` (13)
- `service` (19)
- `td_tool` (8)
- `page` (20)
- `thank-you` (13)
- `document` (42, mostly private client proposals)

**Problem to fix.** The theme registers `td_solution` (slug `solutions`), but the live content uses `solution` (URL `/solution/`). There are also duplicate single templates (`single-location.php` / `single-td_location.php`, etc.). Fix: unify on the post types that hold the live content, and keep their **live URL bases**.

| CPT (key) | URL base | Site | Key ACF fields |
|---|---|---|---|
| `service` | `/services/` | Marketing | `hero_title`, `hero_accent_word`, `primary_kw`, `secondary_kws[]`, `platforms[]` (rel → platform), `bento[]` (flexible), `process[]`, `faq[]`, `related_services[]`, `related_case_studies[]`, `cta_variant` |
| `solution` | `/solution/` (keep) | Marketing | `industry`, `pains[]`, `services_used[]`, `proof` (rel case_study), `compliance_notes`, `faq[]` |
| `location` | `/locations/` | Marketing | `market`, `currency`, `timezone`, `local_proof[]` (clients/case studies **from that market only**), `local_channels[]`, `nap` (only if a real address exists), `faq[]` |
| `case_study` | `/case-studies/` | Marketing (+ Studios via `division` tax) | `client`, `industry`, `country`, `services[]`, `metric_1..3_num/label`, `timeframe`, `challenge`, `approach`, `results`, `quote` (verbatim), `quote_author`, `quote_role`, `permission_confirmed` (bool — page won't publish without it), `gallery[]`, `video` |
| `td_tool` | `/tools/` | Marketing | `tool_component` (which JS app), `primary_kw`, `how_to[]`, `faq[]`, `ad_slots` (bool), `gate` (none/email) |
| `em-glossary` | `/glossary/` | Marketing | `short_definition` (≤40 words, shown first — answer-first), `category`, `related_terms[]`, `related_service`, `last_reviewed`, `reviewer` |
| `td_resource` **(new)** | `/resources/` | Marketing | `pdf` (file), `cover`, `pages`, `form` (Startbuddi endpoint key), `bullets[]` |
| `business` | `/business/` (keep for now) | Marketing | unchanged; decision in Phase 5 |
| `thank-you` | `/thank-you/` | all | `noindex` forced; `next_step_cta` |
| `document` | `/document/` | Marketing | **forced noindex, excluded from sitemaps and search, `private` by default**; never migrated to Journal/Studios |
| `post` | `/` on Journal | Journal | `tldr`, `primary_kw`, `reviewer` (user), `last_reviewed`, `sources[]`, `cta_service` |
| `studio_service` **(new)** | `/services/` on Studios | Studios | same shape as `service` |
| `studio_work` **(new)** | `/work/` on Studios | Studios | `client`, `status` (Live/Concept — Concept shows a visible label), `disciplines[]`, `gallery[]`, `video`, `case_study` (rel) |
| `td_team` | (no archive) | all | `role`, `bio`, `linkedin`, `expertise[]`, `photo` — feeds author boxes + Person schema |
| `td_testimonial` | (no archive) | all | `quote` (verbatim), `name`, `role`, `company`, `source_url`, `permission_confirmed` |
| `platform` **(new, no front end)** | — | all | `name`, `logo_svg`, `brand_rules_url` — for badges |

Taxonomies:

- `td_industry` (shared by solution, case_study and post)
- `td_service_tag` (links posts and glossary terms to services, for internal linking)
- `division` (marketing/studios)
- `glossary_category`

**ACF field groups are stored as JSON in `mu-plugins/techdella-core/acf-json/`,** so they're version-controlled.

---

## 8. Technical SEO & Google alignment

### 8.1 Google's systems as of Oct 2026, and what they mean for this build

Updates so far in 2026:

- March 2026 spam update (24 Mar)
- March 2026 core update (27 Mar)
- May–June 2026 core update (21 May–2 Jun)
- spam updates in June, August and from 24 September (still rolling out in early October)

Google also extended its spam policies to AI Overviews and AI Mode in May 2026. Check the [Search Status Dashboard](https://status.search.google.com) before each launch, and don't launch during an active core update if it can be avoided.

What that means for this build:

| Google system/policy | Build rule |
|---|---|
| Helpful content (now inside core ranking), E-E-A-T | Every article has:<br>- a named author (a real team member, with a `td_team` profile and LinkedIn);<br>- a reviewer;<br>- a "last reviewed" date;<br>- first-hand evidence: screenshots, our numbers, what we did.<br>No anonymous "Techdella Team" bylines on guides. |
| Scaled content abuse | Glossary and guides are **refreshed and pruned**, not mass-generated. AI drafts need human review and an original element (an example, opinion, data or screenshot) before publish. Hard cap: **≤ 15 publishes per week per site** during the rebuild. |
| Site reputation abuse | No third-party or guest content on the main domain without editorial control. Guest posts (the blog has a guest-post page) are edited by us, and their links are `rel="nofollow"` or `"sponsored"`. |
| Doorway pages | Location and solution pages need unique substance (§10.4). No city-swap templates. |
| Expired/irrelevant content | The off-topic glossary (personal finance, legal) is pruned. See `url-map.csv` → `prune-review` (39 terms). |
| Core Web Vitals (INP, LCP, CLS) | Budget in §8.4 |
| Intrusive interstitials | Popup rules in §13: no full-screen mobile interstitial on landing from search |
| AI Overviews / AI Mode / LLM answers | Answer-first paragraphs. Clear entity facts. Consistent NAP and `sameAs`. Cite sources. `llms.txt`. Server-rendered content (no JS-only text). |

### 8.2 Structured data (JSON-LD, from `techdella-core/seo/schema.php`)

Rank Math schema is turned **off**, to avoid duplicates. Each node is emitted once, in a single `@graph` per page, with `@id` references.

| Template | Types |
|---|---|
| All | `Organization` (`@id` …/#org — logo, sameAs: LinkedIn, Instagram, X, TikTok, YouTube `[URLS]`, founder → Person), `WebSite` (with `SearchAction` on Journal), `WebPage`, `BreadcrumbList` |
| Service / Studio service | `Service` (provider → #org, serviceType, areaServed: the markets from §10.4, `offers` only if a real price is published) |
| Solution | `Service` + `audience` (BusinessAudience, industry) |
| Location | `Service` with `areaServed` = that market. **No `LocalBusiness` unless a real staffed address exists there.** `[CONFIRM addresses]` |
| Case study | `Article` (about → client Organization) + `Review` only if a verbatim, permissioned quote is shown |
| Post / guide | `BlogPosting` (author → Person with url/sameAs, reviewedBy, dateModified, image, wordCount) |
| Glossary term | `DefinedTerm` in a `DefinedTermSet` (the glossary hub) |
| Tool | `SoftwareApplication` (applicationCategory BusinessApplication, offers price 0) |
| Resource (PDF) | `CreativeWork` / `DigitalDocument` |
| About | `AboutPage` + `Person` for each team member |
| Video | `VideoObject` (thumbnail, uploadDate, duration, contentUrl) |

Do **not** emit:

- `FAQPage`: Google shows these rich results only for authoritative government/health sites. The FAQ stays as page content.
- `HowTo`: deprecated.
- `AggregateRating` for our own services: self-serving reviews are ineligible.

Validate every template with the Rich Results Test and the Schema.org validator (`make test` runs `structured-data-testing` against JSON snapshots).

### 8.3 Indexing controls

- **Canonical:** self-referencing, absolute, `https`, trailing slash. Paginated archives are self-canonical, never pointing at page 1.
- **Query-string variants** are canonical to the clean URL. This includes:
  - UTM and `?ref=` parameters;
  - the tool pages' `?q=` parameter.
- **Noindex** (meta robots + `X-Robots-Tag`):
  - `thank-you`, `document`, search results, tag archives with fewer than 5 posts, author archives (except team members with at least 3 posts), staging, attachment pages (redirect these to the parent).
- **Sitemaps** (Rank Math): one index per site.
  - Excluded: `document`, `thank-you`, `td_testimonial`, `td_team`, `platform`, and anything noindex.
  - Image and video entries are included.
- **robots.txt** (each site):

  ```
  User-agent: *
  Disallow: /wp-admin/
  Allow: /wp-admin/admin-ajax.php
  Disallow: /?s=
  Disallow: /search/
  Sitemap: https://<site>/sitemap_index.xml
  ```

  AI crawlers (GPTBot, OAI-SearchBot, PerplexityBot, ClaudeBot, Google-Extended) are **allowed** by default so we can be cited. `[CONFIRM — owner can choose to block training-only bots like Google-Extended/GPTBot while allowing search bots.]`
- **`/llms.txt`** per site: a plain-Markdown summary of who we are, the services with URLs, key guides and contact. Generated from the CMS.
- **hreflang:** not used. The content is English for all markets, so don't fake locale variants.
- **Internal linking** (automatic and editorial):
  - every service links to its 2–3 related services, its solutions and its case studies;
  - every guide links to one service and three glossary terms;
  - every glossary term links to one service and to related terms;
  - breadcrumbs everywhere;
  - no orphan pages (`td audit orphans` WP-CLI command).
- **Cross-site links** (Marketing ↔ Journal ↔ Studios) are normal followed links in nav and footer. Add each subdomain as its own Search Console property, plus a Domain property.

### 8.4 Performance budget (mobile, measured by Lighthouse CI on Moto G-class throttling, and by CrUX after launch)

- **Core Web Vitals:**
  - LCP ≤ 2.0s
  - INP ≤ 200ms
  - CLS ≤ 0.05
  - TTFB ≤ 600ms (with LiteSpeed cache)
- **Page weight (excluding ads on tool pages):**
  - first-load JS ≤ 60 KB gz
  - CSS ≤ 45 KB gz
  - total ≤ 900 KB
- **Loading:**
  - no render-blocking third-party scripts;
  - GA4 loads after consent (§12.3);
  - videos lazy-load and never block LCP;
  - fonts limited to 2 preloaded files;
  - critical CSS inlined per template;
  - no jQuery on the front end (deregister it).
- **Ad pages:** reserve the slot height so ads cause no CLS.

### 8.5 On-page rules (templates enforce them; editors fill the fields)

- One H1. The title uses the `primary_kw` naturally: ≤ 60 characters, brand at the end ("… | Techdella").
- Meta description: 140–155 characters, written for clicks. It is never auto-filled from the first sentence.
- URL slugs are short and keyword-led. **Don't change a live slug** unless the URL map says so.
- An answer-first intro: 2–3 sentences that directly answer the query (helps AI Overviews and featured snippets).
- Tables and lists are real HTML. Images are `<figure>` with captions where useful.
- OG/Twitter images: auto-generated 1200×630 from title + category with the brand template (`@vercel/og`-style SVG→PNG at publish time, via `td og:generate`).

---

## 9. Redirects & URL migration

### 9.1 Files

- `data/url-map.csv`: every one of the 360 items in the export. Columns:
  - `type`, `status`, `title`, `old_url`
  - `action` (keep / rebuild / redirect / move / merge-review / keep-review / refresh / refresh-priority / prune-review / noindex / private-noindex / confirm)
  - `new_url`, `notes`, `words`
- `data/redirects.csv`: 66 rules. Includes legacy rules from the old theme, with **corrected targets**.

### 9.2 Bugs in the current `td_redirects()` (delete the function)

- It matches with `strpos`, so `solution/health-marketing-agency` (a **live** page) redirects to `/solutions/healthcare/`, which doesn't exist.
- `service/seo` targets `/services/seo-content/`, which doesn't exist. Other targets (`/services/social-media/`, `/services/cro/`, `/services/branding/`, `/services/paid-ads/`, `/services/landing-pages/`) don't exist either.
- `startup-quiz` redirects even though the page is published.
- **Run before launch:** `wp td redirects:verify`. It requests every source in `redirects.csv` and asserts:
  - one hop;
  - a 301;
  - the target returns 200.

  It fails the build on chains or loops.

### 9.3 The new information architecture (Marketing)

```
/                                  Home (Main)
/services/                         Services hub (connected-system block)
  /services/seo/                   SEO  ← keep slug        /services/technical-seo/  /services/local-seo/  (children)
  /services/ai-search-optimization/   NEW (AISearch)
  /services/paid-ads/              NEW (Paid) → Phase 6 children: /google-ads/ /meta-ads/ /tiktok-ads/ /linkedin-ads/
  /services/lead-generation/       NEW (LeadGen)
  /services/email-marketing/       keep slug (Email)
  /services/marketing-automation/  keep slug (Automation — "AI marketing automation")
/growth-audit/                     NEW (Audit)
/pricing/                          Ways to start
/case-studies/  /case-studies/<slug>/
/solution/<industry>-marketing-agency/   keep live slugs (13)
/locations/  /locations/<market>/
/tools/  /tools/<tool>/
/resources/  /resources/<guide>/   NEW (PDF lead magnets)
/glossary/  /glossary/<term>/
/about-us/  /book-a-discovery-call/  legal pages
```

- **Journal (blog.techdella.com):** `/`, `/<post-slug>/`, `/category/<cat>/`, `/author/<team>/`, `/newsletter/`, `/write-for-us/`.
- **Studios (studios.techdella.com):** `/`, `/services/{mvp-development, ai-automation, ui-ux-design, website-design, branding, graphic-design, motion-design}/`, `/work/`, `/work/<project>/`, `/start/` (Start brief builder), `/mvp-calculator/`, `/about/`.

### 9.4 Migration day checklist

1. Crawl the current site (Screaming Frog, or `td audit crawl`) and save the list.
2. Export GSC: top pages over 16 months, plus external backlinks. Add any URL with clicks or links that isn't in `url-map.csv`.
3. Import the redirects and run `redirects:verify`.
4. Launch.
5. Submit the sitemaps.
6. Use URL Inspection on the top 20 pages.
7. Watch the GSC "Pages" and 404 reports, and the Rank Math 404 monitor, daily for 14 days, then weekly until day 60.
8. Keep the redirects **at least 1 year** (preferably forever).

---

## 10. Content: rebuild & populate

### 10.1 Editorial pipeline (applies to every item)

The pipeline runs `brief → draft → expert edit → fact check → SEO check → publish → refresh`.

1. **Brief.** Write it in `content/<type>/<slug>.md` front-matter:
   - `primary_kw`, `secondary_kws`, `intent`, `audience`;
   - `angle` (what we know that others don't);
   - `proof` (which case study, screenshot or number);
   - `internal_links`, `author`, `reviewer`.
2. **Draft.** Claude Code writes Markdown in the brand voice (§10.2). It inserts `[CONFIRM]` wherever a fact, number or claim needs a human.
3. **Import.** `wp td import-content --path=content/journal --status=draft` creates or updates the WP posts, matched by slug.
4. **Human edit.** The author adds first-hand experience: an example, a screenshot, or an opinion. The reviewer signs off by filling `reviewer` and `last_reviewed` in WP.
5. **Publish.** Only Editors can publish (§14). A pre-publish checklist panel blocks publishing until:
   - the meta description is filled;
   - the primary keyword appears in the H1 and in the intro;
   - there are ≥ 3 internal links;
   - every image has alt text;
   - there are no `[CONFIRM]` tokens left.
6. **Refresh** cadence:
   - money pages: quarterly;
   - guides: every 6 months, or when GSC clicks drop 30% over 90 days (`td audit decay` lists them).

### 10.2 Voice (from the brief and the prototype)

- Clear, confident, specific. Short sentences. Use "you" and "we".
- No hype words: "revolutionary", "cutting-edge", "unlock", "supercharge", "game-changer", "in today's fast-paced world".
- Proof over adjectives: name the platform, the metric, the timeframe.
- Headlines: one Instrument Serif italic accent word, e.g. "Get found. Get noticed. *Grow.*"
- UK/US spelling: **US English** (most clients are in the US) `[CONFIRM]`.

### 10.3 Marketing pages: copy source

Copy each page from its prototype artboard, then expand it to the target length with the brief fields.

| Page | Prototype | Primary keyword (validate volume in Ahrefs/Semrush/GSC before writing) | Target words |
|---|---|---|---|
| Home | Main | B2B growth marketing agency | 900–1,200 |
| SEO | SEO | B2B SEO services / SEO agency | 1,500–2,000 |
| Technical SEO | (SEO pattern) | technical SEO services | 1,200 |
| Local SEO | (SEO pattern) | local SEO services | 1,200 |
| AI Search | AISearch | AI search optimization / generative engine optimization | 1,500–2,000 |
| Paid ads | Paid | paid ads agency / PPC agency | 1,500–2,000 |
| Lead gen | LeadGen | B2B lead generation services | 1,500 |
| Email | Email | email marketing services / B2B email marketing | 1,500 |
| Automation | Automation | AI marketing automation | 1,500 |
| Growth audit | Audit | marketing audit / growth audit | 700 |
| Services hub | Main bento + Options | marketing services | 800 |

### 10.4 Solutions (13) and Locations (13): rules that avoid doorway pages

- **Solutions:** keep all 13 live slugs, on one template. Each page must include:
  1. 3 industry-specific pains, written from our calls (not generic);
  2. which of our 6 services apply and why, in an industry-specific order;
  3. proof: a case study in that industry, or an honest "what we'd do first" if there isn't one. **Do not invent one.**
  4. the industry's compliance and channel quirks (e.g. fintech ad policies, healthcare claims);
  5. an industry FAQ.

  Proof mapping available today:

  | Solution | Case study |
  |---|---|
  | Fintech | Paycape, Havyn |
  | SaaS | WezOps, startbuddi |
  | eCommerce | Shopinbos, BlueCart |
  | Small business | Angie Diedricks, Three Cat Agency |
  | Gaming/entertainment (under AI or SaaS) | Epic Entertainment, Lykluk |
  | Consulting/whitelabel | Johan Consults |

  `[CONFIRM industries]`
- **Locations:**
  - **Keep and rebuild** 7 market pages: Nigeria, Lagos, USA, UK, Canada, Dubai/UAE, Europe. Each needs:
    - clients or case studies from that market;
    - local channels and costs (currency, ad CPM context);
    - time-zone working hours;
    - market-specific FAQs.
  - **Merge-review** 6 pages: Ibadan and Abuja → Nigeria; New York → USA; Germany → Europe; India and Pakistan → Locations hub. Redirect each one only if GSC shows fewer than 20 clicks in 12 months; otherwise rebuild it properly.
  - **Delete** the orphan templates with no content: `location-accra`, `berlin`, `cape-town`, `johannesburg`, `kano`, `london`, `nairobi`, `enugu`, `port-harcourt`, `toronto`. **Don't create pages for cities with no clients.** (Johannesburg/Cape Town could become a South Africa page once there is South African proof to show `[CONFIRM]`.)

### 10.5 Case studies (11)

- Rebuild each on the case-study template: hero metric pills, challenge → approach → results, the services used (linked), the quote, a gallery and the next case study.
- The page won't publish until `permission_confirmed` is true.
- Re-check every number with the client. Remove any number the client can't confirm.
- Studios gets its own `/work/` entries for the build projects: Epic Entertainment and startbuddi `[CONFIRM]`.

### 10.6 Glossary (148 terms → about 110)

- **`refresh-priority`** (61 core marketing/search terms):
  - rewrite to the answer-first structure: a 40-word definition → why it matters → example from our work → how to do it → related terms → related service;
  - 600–1,000 words;
  - add an author and reviewer.
- **`refresh`** (48 adjacent business/startup terms): light edit, add internal links, trim filler.
- **`prune-review`** (39 off-topic terms: personal finance, legal, corporate finance, etc.). Decide with GSC:
  - **≥ 50 clicks in 12 months** and still relevant to founders → keep, and add a growth angle;
  - **otherwise** → **410 Gone**, or 301 to the closest relevant term if one exists. Don't redirect to the glossary home.
- **New terms to add** (about 20, after keyword validation):

  | Area | Terms |
  |---|---|
  | AI search | AEO, GEO, AI Overviews, LLM visibility, llms.txt, entity SEO |
  | Paid | Performance Max, Advantage+, Meta CAPI, TikTok Spark Ads, LinkedIn Thought Leader Ads |
  | Email | SPF/DKIM/DMARC, BIMI, email warm-up |
  | Other | lead scoring, MQL/SQL, intent data, AI agents for marketing, server-side tracking, Consent Mode v2 |

### 10.7 Journal (blog)

- **Migrate** the 17 published guides (D1). Refresh them for 2026: dates, screenshots, the AI-search angle.
- **Content calendar** for the first 12 weeks: 2 posts per week, mapped to the service clusters. Each post gets one service CTA.

| Cluster | Post ideas (validate keywords first) |
|---|---|
| AI Search | What is AI search optimization (Post artboard) · How to get cited in ChatGPT/Perplexity/AI Overviews · AEO vs SEO vs GEO · Auditing your brand's LLM visibility |
| SEO | B2B SEO strategy for 2027 · Technical SEO checklist for WordPress · Topic clusters for SaaS · How we got Paycape to first results in 50 days |
| Paid | Meta ads for B2B: what works now · TikTok ads for B2B (yes, really) · LinkedIn Thought Leader Ads guide · Creative testing framework |
| Lead gen | B2B lead magnets that convert · Landing page teardown series · Lead scoring for small teams |
| Email | Deliverability after Google/Yahoo sender rules · B2B nurture sequence template · Cold vs warm email in 2027 |
| Automation | 10 marketing automations every B2B team should run · AI agents in marketing: realistic uses · Connecting forms → CRM → email |
| Founder | Joanna's monthly letter (opinion, first-person: the strongest E-E-A-T signal we have) |

- The 1 draft post ("You Ship in Days. Your Marketing Should Too.") goes to the review queue.

### 10.8 Studios content

- **Seven service pages** (§9.3):
  - Each has the prototype hero, deliverables, process, packages (`[PRICE]`), work samples, and FAQ.
  - Primary keywords to validate: MVP development agency, AI automation agency, UI/UX design agency, website design agency, branding agency, motion design studio.
- **Work:** Epic Entertainment (real) and 3 Concept projects, each clearly labelled "Concept". Each concept project uses its motion sample from §6.3.
- **Redirect** the main-site `/studio/`, `/mvp-calculator/`, website-design, landing-page and branding service pages, as listed in `redirects.csv`.

### 10.9 The `business` library (53 "How to start a … business" guides)

This library is off-positioning for a B2B search/growth agency, but it may carry much of the site's traffic. **Do nothing destructive until the GSC review in Phase 5.**

1. Pages with meaningful clicks: keep, refresh, add a "grow it" CTA to the growth audit or Studios.
2. Thin pages (< 1,000 words) with no clicks: noindex, or merge.
3. Decide whether the library stays on the main domain or moves to the Journal `[DECISION for owner]`.

---

## 11. Forms → Startbuddi

Startbuddi owns contacts, deals, automations and email. **WordPress only collects and forwards.**

### 11.1 Endpoint forms to create in Startbuddi

Create one "endpoint" type form per row, named `Techdella · …`. The connected Startbuddi workspace is "startbuddi". `[CONFIRM: use this workspace, or create a separate TechDella workspace]`.

Some existing endpoint forms may belong to the startbuddi site itself, e.g. "exit intent popup - webbsite", "free tools", "Blog posts: 60% scroll slide-in". Don't reuse them for TechDella unless the owner confirms they're TechDella's.

| Key (`SB_ENDPOINT_…`) | Startbuddi form name | Site / page | Fields | Startbuddi automation to set up (in Startbuddi, not WP) |
|---|---|---|---|---|
| `CONTACT` | Techdella · Contact / Book a call | Marketing `/book-a-discovery-call/`, footers | name, email, company, website, service_interest[], budget, message | create contact + deal in "Marketing" pipeline, notify owner, confirmation email |
| `AUDIT` | Techdella · Growth Audit | `/growth-audit/` | name, email, company, website, goals[] (traffic/competitors/ai/leads/ads/followup), monthly_budget, notes | deal stage "Audit requested", tag `audit` |
| `STUDIOS_BRIEF` | Techdella Studios · Project brief | studios `/start/` | project_type[], stage, budget (<$2k, $2–5k, $5–15k, $15k+), timeline, name, email, company, details | deal in "Studios" pipeline |
| `NEWSLETTER` | Techdella · Journal newsletter | Journal | email, first_name?, topic_interest | tag `newsletter`, welcome sequence |
| `LEAD_MAGNET` | Techdella · Resource download | `/resources/*` | email, first_name, company, resource_slug | tag `lm-<slug>`, send PDF link, 3-email nurture |
| `TOOL_GATE` | Techdella · Free tool | `/tools/*` (only tools with `gate=email`) | email, tool_slug, (tool result summary) | tag `tool-<slug>` |
| `POPUP_EXIT` | Techdella · Exit-intent popup | Marketing | email, offer, page | tag `popup-exit` |
| `POPUP_SCROLL` | Techdella · Post slide-in | Journal | email, offer, post_slug | tag `popup-scroll` |
| `MVP_CALC` | Techdella Studios · MVP calculator | studios `/mvp-calculator/` | email, estimate inputs, estimate | deal in "Studios", tag `mvp-calc` |
| `GUEST_POST` | Techdella · Write for us | Journal `/write-for-us/` | name, email, topic, outline, samples | task for editor |

### 11.2 Request flow

```
Browser form ──fetch POST──► /wp-json/techdella/v1/submit/{key}
   │                           1. nonce + Turnstile verify + honeypot + min-time (≥3s) + rate-limit (5/min/IP)
   │                           2. validate/sanitize against the field schema for {key}
   │                           3. enrich: page_url, referrer, utm_* (first + last touch from cookie), site, ts, consent flags
   │                           4. POST JSON (or form-encoded if the endpoint requires it) to SB_ENDPOINT_{KEY}
   │                              timeout 8s; on 2xx → done
   │                              on failure → queue row (encrypted payload) → WP-cron retries 1m/5m/30m/2h/12h
   │                              after 5 failures → admin email "Startbuddi delivery failed" (no PII in email)
   ◄────────── JSON {ok:true, redirect:"/thank-you/<key>/"} ───────────
```

- Successfully delivered queue rows are deleted. Failed rows are purged after 14 days.
- **No permanent local copy of leads.** The old `td_form_submissions` and `founders_hub` tables are **exported for the owner, then dropped** `[CONFIRM — after export]`.
- The old `wp_mail` notification handlers (`td_handle_contact`, `td_handle_studio_form`, `td_handle_mvp_calc`, `td_handle_tool_gate`, `td_handle_newsletter`, founders-hub emails) are removed. Startbuddi sends all lead email.
- **Thank-you pages:**
  - each fires a `generate_lead` analytics event (consent-aware);
  - keep only the slugs that live forms use;
  - **all are noindex**.

### 11.3 Payload (example)

```json
{
  "form": "audit",
  "name": "Ada Obi", "email": "ada@example.com", "company": "Example Ltd", "website": "https://example.com",
  "goals": ["traffic","ai"], "monthly_budget": "2000-5000", "notes": "…",
  "meta": { "site": "techdella.com", "page_url": "https://techdella.com/growth-audit/", "referrer": "https://www.google.com/",
            "utm_first": {"source":"google","medium":"organic"}, "utm_last": {"source":"linkedin","medium":"paid","campaign":"q4-aeo"},
            "consent_marketing": true, "submitted_at": "2026-10-10T12:00:00Z" }
}
```

Confirm the exact field names Startbuddi expects for endpoint forms `[CONFIRM in Startbuddi docs]`. Keep the mapping in one place: `forms/schema.php`.

### 11.4 Consent and privacy

- Marketing-consent checkbox: unticked by default, wherever the form adds someone to nurture.
- Link the privacy policy, and update the policy to name Startbuddi as processor.
- **Forms work without JavaScript**: plain POST fallback to the same route, followed by a redirect.

### 11.5 Testing

- The local `sb-mock` container (port 8090) logs every forwarded payload, so e2e tests assert the payload shape.
- Staging posts to Startbuddi test forms: `Techdella · TEST · …`.

---

## 12. Free tools + AdSense

### 12.1 Tools (8 existing; keep their URLs)

| Tool | Notes |
|---|---|
| UTM Parameter Generator | |
| Hashtag Generator | |
| Readability Checker | |
| Keyword Density Checker | |
| Email Subject Line Analyzer | |
| On-Page SEO Checker | |
| Persona Builder | |
| AI Business Name Generator | |
| MVP Calculator | Moves to Studios |

**New tools** (Phase 6, validate demand first):

- AI Search Visibility Checker: checks robots.txt for AI bots, `llms.txt`, schema present, answer-first intro.
- SPF/DKIM/DMARC Checker: DNS lookup via a server route.
- Ad Creative Size Guide: static.
- Meta Description Length Preview.

How the tools work:

- Each tool is a small vanilla JS app in `blocks/tool-<name>/`.
- Processing happens client-side wherever possible.
- Server-side calls (fetching a URL for the SEO checker, DNS lookups) go through a rate-limited REST route with an SSRF guard:
  - block private IP ranges;
  - 5s timeout;
  - 2 MB maximum response size.
- **AI-powered tools** (name generator, persona builder) call the model API from the server route, with:
  - per-IP daily limits;
  - a max-tokens cap;
  - the key in an env var `[AI_API_KEY]`.

  The page states the output is AI-generated.
- **Below the tool, every page carries real content:**
  - how to use it;
  - why it matters;
  - a worked example;
  - FAQ;
  - related guide and service.

  This makes the page helpful, not a thin ad page, and is an AdSense requirement.

### 12.2 AdSense placement rules

- Load AdSense **only** on `td_tool` single pages and the `/tools/` hub. Never on service, solution, location, case-study, pricing or Studios pages. Journal posts: **off** at launch `[CONFIRM later]`.
- **Manual units, Auto ads off.** At most 3 units per page:
  - one below the tool result (`ad-slot--result`, responsive);
  - one in the content (after "How to use");
  - one sidebar or footer unit on desktop only.

  Each slot reserves its height (`min-height: 280px` mobile / 250px desktop) to prevent CLS. Each is labelled "Advertisement".
- Keep units ≥ 150px away from tool buttons and inputs, to avoid accidental clicks (AdSense policy).
- `ads.txt` at the Marketing root: `google.com, pub-[ID], DIRECT, f08c47fec0942fa0`. It's generated from the env var, and `make test` checks it.
- Loading:
  - the AdSense script loads `async`, only after the CMP says consent is allowed;
  - ads load lazily when the slot is near the viewport.

### 12.3 Consent

- **What to install:** Google requires a Google-certified CMP for users in the EEA, UK and Switzerland. Use AdSense's own "Privacy & messaging" GDPR message (free) or another certified CMP.
- **Consent Mode v2:** turn it on for GA4 and ads (`ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage` default to `denied` in the EEA/UK).
- **Who sees which banner:**
  - the same CMP serves non-EEA visitors a lighter notice;
  - US state privacy: use the AdSense US-states message `[CONFIRM]`.
- **Cookie policy:** the existing `/cookies/` page is updated to list the actual cookies.

---

## 13. Popups & PDF lead magnets

### 13.1 Popup engine (`techdella-core/popups`, no plugin)

Popups are configured in an admin screen ("Growth → Popups"), with no code needed. Each popup has:

- **Type:** exit-intent modal (desktop only) · scroll slide-in (corner card) · top bar · inline end-of-post card.
- **Trigger:** exit intent, scroll %, time on page, or page count.
- **Targeting:** site, post types, categories or URLs, plus include/exclude lists.
- **Offer:** links to a `td_resource` or newsletter.
- **Form key:** which Startbuddi endpoint it uses.
- **Frequency:** default once per 7 days per visitor, and never again after a conversion. Stored in `localStorage` inside try/catch, with a cookie fallback.

Rules that keep us Google-friendly and polite:

- **Never** show a full-screen or large modal on mobile to visitors arriving from search. On mobile, use the bottom slide-in or the top bar only, and only after 50% scroll or 30 seconds.
- No popups on `/book-a-discovery-call/`, `/growth-audit/`, `/start/`, thank-you pages, tool pages before the tool is used, or on a visitor's first 10 seconds.
- Esc closes the popup, it has a visible close button, focus is trapped while open and returned on close, and it carries `role="dialog"` and `aria-modal`.
- A/B testing: up to 2 variants. Impressions and conversions go to GA4 as events, so Startbuddi only sees conversions.

Default popups at launch:

| Popup | Where | Trigger | Offer |
|---|---|---|---|
| P1 | Journal posts in the AI Search or SEO categories | 60% scroll slide-in | "AI Search Readiness Checklist" (PDF) |
| P2 | Journal, all other posts | 60% scroll slide-in | Newsletter |
| P3 | Marketing service pages | Exit-intent, desktop only | "B2B Growth Audit Worksheet" (PDF) |
| P4 | Studios | Top bar | "MVP Scoping Guide" (PDF) |

### 13.2 Resource pages (`/resources/<slug>/`)

Each resource page carries:

- the cover;
- what's inside (bullets);
- who it's for;
- page count;
- an inline form (`LEAD_MAGNET` key);
- a sample page preview.

After submit, the thank-you page shows the download link right away, and Startbuddi also emails it. Resource pages are **indexable**. The PDF files themselves get `X-Robots-Tag: noindex`, and their URLs are unguessable (hashed paths in Uploads).

### 13.3 PDF guides to produce (in `pdf/`: HTML templates on brand → `npm run pdf` with Playwright `page.pdf()`)

| ID | Title (working) | Pages | For | Linked service |
|---|---|---|---|---|
| PDF-01 | AI Search Readiness Checklist (2026) | 8 | marketing leads | AI Search |
| PDF-02 | B2B SEO Playbook: 90 days to pipeline | 20 | SEO leads | SEO |
| PDF-03 | Paid Ads Creative Brief Template (Meta, TikTok, LinkedIn, Google) | 10 | paid leads | Paid |
| PDF-04 | Email Deliverability Checklist (SPF/DKIM/DMARC + sender rules) | 6 | email leads | Email |
| PDF-05 | B2B Growth Audit Worksheet | 8 | all | Growth Audit |
| PDF-06 | 10 Marketing Automations to Set Up This Quarter | 12 | automation leads | Automation |
| PDF-07 | MVP Scoping Guide | 12 | founders | Studios |

PDF rules:

- Each PDF uses the brand template: cover with arch motif, Geist and Instrument Serif, footer with the URL and a CTA.
- A tagged, accessible PDF: set the title metadata and keep a logical reading order.
- Under 3 MB.
- Every factual claim is sourced. `[CONFIRM]` marks get resolved before release.

---

## 14. WordPress admin, made easy for the team

### 14.1 Roles

| Role | Can do |
|---|---|
| **Admin** | Joanna/Chinedu only. Everything. |
| **Editor** (custom caps) | Publish everything, edit menus, manage popups and resources. Cannot change plugins, themes or settings. |
| **Writer** | Create and edit their own drafts (posts, glossary, case studies). Submit for review. Upload media. **Cannot publish.** |
| **Designer** | Media, `studio_work`, case-study galleries, resources. Cannot publish posts. |

Remove the Subscriber/Contributor/Author roles if no one uses them. Turn on 2FA for every admin and editor (WP 2FA plugin, or Hostinger's built-in option `[CONFIRM]`).

### 14.2 Simplified admin

**Custom dashboard** ("TechDella HQ") replaces the default widgets:

- quick actions: Write a guide · Add a glossary term · Add a case study · Add work (Studios) · New resource · Edit popups;
- "Needs review" queue: pending posts;
- "Refresh due" list: from `last_reviewed` plus the GSC decay list;
- link to Startbuddi ("Leads & CRM live in Startbuddi →");
- last 5 published;
- a site-health strip: redirects OK, sitemap OK, PageSpeed of the homepage (cached daily).

**Grouped menu.** Rename and regroup the menus:

- **Content:** Pages, Guides (posts), Glossary, Case studies, Resources.
- **Growth:** Services, Solutions, Locations, Tools, Popups.
- **Studios:** Studio services, Work.
- **Brand:** Team, Testimonials, Platforms.
- **Settings:** admin only.

Also:

- **Hide** for non-admins: Comments (comments are off sitewide), Tools, Appearance → Theme File Editor (and set `DISALLOW_FILE_EDIT`), and plugin admin screens.
- **Editor UX:**
  - each post type opens with its locked pattern, so editors see filled sections with placeholder text;
  - fields have help text explaining *what good looks like* (e.g. "Answer the question in 40 words");
  - a character counter on the SEO title and meta description;
  - the pre-publish checklist from §10.1.
- **Branding:** a TechDella login screen and admin colour scheme (ink/canvas, orange focus only).
- **Team docs:** a "How to" page inside the admin (`admin.php?page=td-help`) with short Loom links `[team to record]`: publish a guide, add a case study, swap a hero image, create a popup.

---

## 15. Build phases (one at a time; check each before moving on)

| Phase | Scope | Done when |
|---|---|---|
| **0. Setup** | Repo, Docker local (§4), Makefile, CI, `.env.example`, pull prod DB + uploads to local | All three sites run on localhost:8081–8083 with current content |
| **1. Design system** | tokens, fonts, `theme.json`, all blocks in §2.3, a `/styleguide/` page (noindex) | Styleguide matches `System.dc.html`; axe passes; Lighthouse ≥ 95 on the styleguide |
| **2. Core plugin** | CPTs/taxonomies/ACF JSON (§7), unify the solution CPT, schema, canonicals, robots, sitemaps, `llms.txt`, forms → Startbuddi + mock, popups engine, ads loader + CMP, admin (§14), WP-CLI commands | Unit + e2e tests pass. A test submission arrives in `sb-mock`, then in a Startbuddi TEST form on staging |
| **3. Marketing templates** | Home, Services hub, 6 services + 2 SEO children, Growth Audit, Pricing, Case study (archive + single), Solution, Location, Tools, Resources, Glossary (hub + single), About, Book a call, legal, 404 | Every template is filled with real or `[CONFIRM]` content and matches its artboard at 1440/1024/390 (Playwright visual snapshots); performance budget passes |
| **4. Journal + Studios** | Journal home/post/category/author/newsletter; Studios home/services/work/start/MVP calculator | Same as Phase 3 |
| **5. Content** | Run the §10 pipeline: money pages → case studies → solutions → locations → guides migration → glossary refresh/prune → PDFs → motion renders; GSC review for `merge-review`/`prune-review`/`business` | No `[CONFIRM]` left on public pages; every `url-map.csv` row resolved |
| **6. Launch** | Redirect import + verify, staging sign-off, pick launch day (not during a Google core update), deploy, Search Console, post-launch monitoring (§9.4) | 0 redirect chains; 0 server errors; sitemaps submitted; CrUX/PSI green at day 28 |
| **7. After launch** | Paid ads children, new tools, new glossary terms, content calendar, quarterly refresh | — |

---

## 16. QA checklist (`make test` automates the starred items)

- \* No horizontal scroll at 390px. Tap targets ≥ 44px.
- \* axe: 0 serious or critical issues. Every page is keyboard-navigable. Focus is visible.
- \* Lighthouse mobile ≥ 90 on every template, and the CWV budget in §8.4 is met.
- \* Structured-data snapshots are valid. There is no duplicate Organization schema.
- \* `redirects:verify` passes. No 404s in the internal-link crawl.
- \* Staging is noindex and password-protected. Production is not noindex.
- \* `ads.txt` is present. AdSense appears only on `/tools/*`, and loads only after consent.
- \* All 10 forms reach `sb-mock` with the right payload. The no-JS fallback works. Turnstile blocks bots.
- Popups follow the §13 rules on a real phone, arriving from a Google search.
- PDFs open, are tagged, and are under 3 MB.
- No `[CONFIRM]`, `[PRICE]` or lorem ipsum on any public page (`td audit placeholders`).
- No Syne, Outfit or Manrope anywhere (`grep -ri "syne\|outfit\|manrope"` is empty).
- The orange audit: a screenshot of each page shows orange on ≤ ~10% of UI chrome. If in doubt, remove it.

---

## 17. Values the owner must supply

| What | Where it goes |
|---|---|
| Hostinger SSH host, user and site paths | `.env` |
| Startbuddi endpoint URLs for the 10 forms in §11.1, the workspace to use, and the field-name format | `.env`, `forms/schema.php` |
| AdSense publisher ID; CMP choice; GA4 ID | `.env` |
| ACF Pro licence | Plugin |
| SMTP provider for system mail | WP Mail SMTP |
| Prices and minimum ad spend | `[PRICE]` / `[MINIMUM SPEND]` |
| Client permissions for logos, quotes and creatives | `permission_confirmed` fields |
| Real addresses, if any (for `LocalBusiness`) | Location pages |
| Social profile URLs | `sameAs` |
| AI crawler policy | `robots.txt` |
| US vs UK English | Voice |
| Decisions D1 (guides location) and §10.9 (business library) | §1, §10.9 |
| Joanna's sign-off on the founder copy | Home, About |
| Photo shoot | §6.2 |

*Sources consulted for the Google section:*

- [Google Search Status Dashboard](https://status.search.google.com)
- [Coalition Technologies: 2026 updates recap](https://coalitiontechnologies.com/blog/will-another-google-core-update-coming-after-the-september-2026-spam-update)
- [PPC Land: June 2026 spam update](https://ppc.land/googles-june-2026-spam-update-is-live-what-it-hits-and-why-it-matters/)
- [PPC Land: March 2026 core update](https://ppc.land/googles-march-2026-core-update-is-here-and-it-follows-spam-by-just-3-days/)
