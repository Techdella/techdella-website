# Owner setup checklist

Things a person has to do or supply. Claude Code must never invent these values (CLAUDE.md §0, §17).
Tick them off here as they're done.

## 1. Hostinger (hPanel, §5.1)

- [ ] Create subdomains `blog` and `studios` (and `staging` unless using Hostinger's built-in WP staging); install WordPress on each.
- [ ] Turn on SSH; add the deploy key (GitHub secret `HOSTINGER_SSH_KEY`); confirm the SSH port (usually 65002).
- [ ] PHP 8.2+, `memory_limit` 256M, `max_execution_time` 120.
- [ ] Free SSL on all three domains + force HTTPS.
- [ ] LiteSpeed Cache: page cache on; CSS/JS minify **off**; image WebP **off**; Guest mode off; QUIC.cloud optional.
- [ ] Daily backups on, and **one restore tested** before Phase 3.
- [ ] DNS: Search Console TXT records per property; leave MX untouched.
- [ ] In each site's `wp-config.php`, above "That's all, stop editing!", define:
  ```php
  define( 'TD_SITE', 'marketing' );   // journal | studios
  define( 'TD_ENV', 'production' );   // staging on staging
  // Startbuddi endpoints, Turnstile, AdSense, GA4 … (see .env.example for the full list)
  define( 'SB_ENDPOINT_CONTACT', 'https://…' );
  ```
  The core plugin reads these constants first, then environment variables.
- [ ] PDFs must not be indexed (§13.2). Add to `wp-content/uploads/.htaccess` on each site:
  ```apache
  <IfModule mod_headers.c>
    <FilesMatch "\.pdf$">
      Header set X-Robots-Tag "noindex, nofollow"
    </FilesMatch>
  </IfModule>
  ```
- [ ] Set a real home title + meta description in Rank Math → Titles & Meta (§8.5).
- [ ] Copy `wp-content/mu-plugins/techdella-core.php` + `techdella-core/` and the theme via the deploy Action (never by hand).

## 2. GitHub (Actions secrets + environments, §5.3)

Secrets: `HOSTINGER_SSH_KEY`, `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER`, `PATH_MARKETING`,
`PATH_JOURNAL`, `PATH_STUDIOS`, `PATH_STAGING`, `STAGING_URL`, `STAGING_BASIC_AUTH` (`user:pass`).
Create a `production` environment with required reviewers (the manual approval step for `v*` tags).

### 2b. Without GitHub Actions: hPanel → GIT

While Actions can't run, deploy from inside Hostinger instead. `scripts/build-hostinger.sh` (run by
Claude Code or a developer after the tests pass) commits a ready-to-install bundle to the
`hostinger` branch: the theme with its built files, the core plugin, content, redirects, PDFs and
videos. In hPanel:

1. **Websites → the site → Advanced → GIT**: repository `git@github.com:Techdella/techdella-website.git`,
   branch `hostinger`, folder `techdella-deploy`. Add the SSH key hPanel shows to GitHub → repo
   **Settings → Deploy keys** (read-only). Click **Deploy**.
2. **Advanced → SSH Access** → terminal, once per site:
   `bash ~/domains/<domain>/public_html/techdella-deploy/sync.sh ~/domains/<domain>/public_html --site=marketing --env=staging --first-run`
3. Each update: **Deploy** in hPanel → GIT (or add hPanel's webhook URL in GitHub → Settings →
   Webhooks), then the same line without `--site`, `--env` and `--first-run`.

The bundle folder blocks web access, and `sync.sh` never touches uploads or `wp-config.php` secrets.
Staging's password protection (hPanel → **Password protect directories**) is a manual step on this route.

## 3. Startbuddi (§11.1)

The connected workspace is **startbuddi**. `[CONFIRM: use it, or create a separate TechDella workspace]`.
No `TechDella · …` endpoint forms exist yet (checked 10 Oct 2026). The Startbuddi connector can only create hosted question forms, not endpoint forms, so create these in the Startbuddi app: Forms → New → Endpoint. Existing endpoint forms such as
"exit intent popup - webbsite", "startbuddi blog newsletter" and "Blog posts: 60% scroll slide-in" belong to the
startbuddi site — don't reuse them for TechDella.

Create one **endpoint** form per row and paste each endpoint URL into the matching constant:

| Constant | Startbuddi form name | Automation to set up in Startbuddi |
|---|---|---|
| `SB_ENDPOINT_CONTACT` | TechDella · Contact / Book a call | contact + deal in "Marketing" pipeline, notify owner, confirmation email |
| `SB_ENDPOINT_AUDIT` | TechDella · Growth Audit | deal stage "Audit requested", tag `audit` |
| `SB_ENDPOINT_STUDIOS_BRIEF` | TechDella Studios · Project brief | deal in "Studios" pipeline |
| `SB_ENDPOINT_NEWSLETTER` | TechDella · Journal newsletter | tag `newsletter`, welcome sequence |
| `SB_ENDPOINT_LEAD_MAGNET` | TechDella · Resource download | tag `lm-<slug>`, send PDF link, 3-email nurture |
| `SB_ENDPOINT_TOOL_GATE` | TechDella · Free tool | tag `tool-<slug>` |
| `SB_ENDPOINT_POPUP_EXIT` | TechDella · Exit-intent popup | tag `popup-exit` |
| `SB_ENDPOINT_POPUP_SCROLL` | TechDella · Post slide-in | tag `popup-scroll` |
| `SB_ENDPOINT_MVP_CALC` | TechDella Studios · MVP calculator | deal in "Studios", tag `mvp-calc` |
| `SB_ENDPOINT_GUEST_POST` | TechDella · Write for us | task for editor |

For staging, create the same set as `TechDella · TEST · …` (§11.5).
`[CONFIRM]` the field names Startbuddi expects for endpoint forms and whether it supports signed requests
(`SB_SHARED_SECRET`); the mapping lives in `techdella-core/forms/schema.php`.

## 4. Values to supply (§17)

| What | Where |
|---|---|
| AdSense publisher ID; CMP choice; GA4 ID | wp-config constants / `.env` |
| Turnstile site + secret keys | wp-config constants |
| ACF Pro licence (D4) — without it the generic block editor + JSON meta boxes are used | Plugin |
| SMTP provider for system mail | WP Mail SMTP |
| Prices and minimum ad spend | `[PRICE]` / `[MINIMUM SPEND]` in content |
| Client permissions for logos, quotes, creatives | `permission_confirmed` fields |
| Real addresses, if any (LocalBusiness) | Location pages |
| Social profile URLs (`sameAs`, footer) | option `td_social_profiles` |
| AI crawler policy | option `td_ai_crawler_policy` |
| US vs UK English (default US) | Voice |
| Decisions D1 (guides location) and §10.9 (business library) | CLAUDE.md §1, §10.9 |
| Joanna's sign-off on the founder copy | Home, About |
| Photo shoot (§6.2) and official platform logo files (`assets/logos/platforms/`) | `assets/` |
| Startbuddi logo file for the logo strip (currently a text fallback) | `assets/images/clients/startbuddi.png` |

## 5. Motion videos (§6.3)

The seven motion samples are rendered from HTML (`motion/`, no Remotion licence needed) and are
not stored in Git. The Deploy workflow renders them (cached until `motion/` changes), uploads them
to `~/td-video/` on Hostinger and imports them on each site with `wp td media:videos`; the pages
already name their scene, so nothing else is needed. To do it by hand:

1. `npm run motion:render` (or `-- --only mo-03` for one), which writes `assets/video/`.
2. Copy `assets/video/` to the server and run `wp td media:videos <that folder>` in each site.
3. To add a video elsewhere, use the **Video (self-hosted)** block with `scene` set (see
   `motion/README.md`).

Before publishing: check every scene's copy (MO-04 uses the fictional brand "Northwind"; MO-07 shows
concept screens only), and replace concepts with real Studios work once it's confirmed.
