# Architecture & conventions

How the code in this repo fits together. `CLAUDE.md` is the spec (what to build); this file is the
house rules (how it is built). Keep both up to date.

## Moving parts

| Piece | Path | Role |
|---|---|---|
| Theme | `wp-content/themes/techdella/` | Markup, CSS, JS, blocks, templates. One theme for all three sites; `td_site()` switches nav/footer/templates (D3). |
| Core plugin | `wp-content/mu-plugins/techdella-core/` | Content model, SEO, forms → Startbuddi, popups, ads/consent, admin, WP-CLI. Must-use, so it always loads first. |
| Build | `scripts/build.mjs`, `scripts/images.mjs` | One CSS bundle (≤45 KB gz) and one JS bundle (≤25 KB gz); responsive images. Output in `assets/build/` and `assets/img/` (gitignored, CI builds them). |
| Local stack | `docker-compose.yml`, `docker/` | Marketing :8081, Journal :8082, Studios :8083, Mailpit :8025, sb-mock :8090. |

## Shared helpers (core plugin, `inc/helpers.php`)

- `td_site()` → `marketing|journal|studios`; `td_is_site('journal', …)`.
- `td_environment()` → `local|staging|production`; `td_is_production()`.
- `td_url( $site, $path )` → absolute URL on any site. **Never hard-code a domain.**
- `td_field( $name, $post_id )` → ACF value if ACF is active, else post meta.
- `td_accent( 'Get found. *Grow.*' )` → escaped headline with the one serif accent word.
- `td_mark_placeholders( $html )` → wraps `[CONFIRM]`, `[PRICE]`… in a visible `.td-confirm` span.
- `td_icon( 'arrow-up-right' )` → inline SVG from `theme/assets/svg/`.
- `td_env( 'KEY' )` → constant or env var. Config constants are defined in `inc/config.php`.

## Theme helpers

- `td_block( 'hero-orbit', $attrs )` → render a block from PHP (templates, parts, patterns).
- `td_block_wrapper( 'td-hero-orbit', [ 'id' => … ] )` → root element attributes inside `render.php`.
- `td_button( $label, $url, 'primary|secondary|light', [ 'size' => 'sm' ] )`, `td_chip( $label )`,
  `td_section_head( [ 'label', 'title', 'intro', 'align' => 'split|center' ] )`, `td_platform_badge( $name, $slug, $mark, $ink )`.
- `td_picture( 'team/joanna-okedara-kalu', [ 'alt', 'sizes', 'priority' ] )` → `<picture>` AVIF/WebP/JPEG.
  `td_image( $id_or_key, $args )` accepts an attachment ID **or** a manifest key. `td_logo( 'default|white|mark', $height )`.
- `td_part( 'nav' )` → `parts/nav-<site>.php`.
- Templates live in `templates/`; `templates/<site>/<file>.php` beats `templates/<file>.php`.

Theme image keys (source in `assets/images/`, run `npm run images`):
`brand/techdella-logo`, `brand/techdella-logo-white`, `brand/techdella-mark`,
`team/joanna-okedara-kalu`, `team/chinedu-emmanuel-kalu`, `team/akeem-feysupo`, `team/abel-saka`,
`team/omolola-akiyode`, `team/mercy-olumakinwa`, `people/levi-barbosa-wezops`, `people/akin-oyegoke-johan-consults`,
`photos/techdella-team-group`, `clients/paycape`, `clients/johan-consults`, `clients/wezops`, `clients/shopinbos`, `clients/jollomy`.
(No startbuddi logo file yet → text fallback.)

## Blocks (`theme/blocks/<name>/`)

```
block.json   apiVersion 3, "name": "techdella/<name>", "category": "techdella",
             "editorScript": "techdella-blocks-editor", "render": "file:./render.php",
             "supports": { "html": false, "anchor": true }, realistic "example" attributes,
             optional "techdella": { "controls": { "<attr>": "textarea|media|json|url|hidden" }, "help": "…" }
render.php   server-side markup. $attributes is set. Escape everything.
style.css    BEM classes .td-<name>, .td-<name>__part, .td-<name>--mod. Tokens only.
view.js      optional progressive enhancement; self-inits with window.td.ready(); never throws when absent.
```

- Do **not** add `style`, `viewScript` or `editorStyle` to block.json: CSS and JS are bundled by `scripts/build.mjs`.
- No jQuery. The page must work without JS (`<details>`, plain links, POST forms).
- The styleguide renders every block's `example` — make examples real and spec-compliant (only verified proof, §2.4).
- Image attributes take a theme image key (string) or an attachment ID (integer); render with `td_image()`.
- Orange rules (§2.1): dots, the arrow circle, one highlight per section, focus rings. Never backgrounds or body text.
  `--td-gradient` only in the logo or the final CTA arch.
- a11y: axe clean, tap targets ≥ 44px, visible focus, `prefers-reduced-motion` respected.

## Core plugin modules (`techdella-core/<module>/module.php`)

Loaded in order: `cpt, seo, forms, popups, ads, admin, cli`. Classes are namespaced
`TechDella\Core\<Module>`; global functions are prefixed `td_`. Options are prefixed `td_`. REST namespace
`techdella/v1`. Each module registers its own hooks and owns its own files.

Extension points (use these instead of reaching into another module):

| Hook / function | Owner | Purpose |
|---|---|---|
| `do_action( 'td_setup', $site )` | cli (`wp td setup`) | Idempotent site setup. Modules hook in to create their pages/options/roles. |
| `apply_filters( 'td_noindex', bool $noindex )` | seo | Force noindex (meta robots + `X-Robots-Tag`) for the current request. Post meta `_td_noindex` = 1 does the same per post. |
| `td_schema_add_node( array $node )` / `apply_filters( 'td_schema_graph', $nodes )` | seo | Add a JSON-LD node to the page's single `@graph`. |
| `td_breadcrumbs()` / `td_breadcrumb_items()` | seo | Breadcrumb nav + data (also feeds `BreadcrumbList`). |
| `td_form_render( $key, $args )` / `td_form_schema( $key )` | forms | Render a form for a Startbuddi endpoint key; field schema. |
| `td_popups_for_request()` | popups | Popups that match the current page. |
| `td_ad_slot( $position )` / `td_ads_enabled()` | ads | Manual AdSense unit with reserved height; whether this page may load ads. Filters `td_ads_enabled`, `td_ads_journal`; unit IDs in option `td_adsense_slots` (`{result, content, sidebar}`). |
| `window.tdConsent.analytics(cb)` / `.ads(cb)` | ads | Run code only after consent (inline loader, first script in `<head>`). Filters `td_consent_regions`, `td_cmp_everywhere`. |
| `td_redirects_health()` / `td_seo_health()` | cli / seo | Dashboard health rows `{ok, label}`. |
| `td_popups_admin_url` (filter) | popups | Admin URL for the "Edit popups" quick action. |
| `td_setup` priorities | cpt 5 · admin 20 · seo 20 · popups 25 | Order of the setup steps. |

Form keys (lowercase): `contact, audit, studios_brief, newsletter, lead_magnet, tool_gate, popup_exit,
popup_scroll, mvp_calc, guest_post` → env `SB_ENDPOINT_<KEY>`.

## Tests

- PHP unit (`composer test`): `tests/unit/*Test.php`, pure classes only. WordPress function stubs go in
  `tests/unit/stubs/<module>.php`, each guarded with `function_exists`.
- E2E (`npm run test:e2e`): Playwright, `tests/e2e/*.spec.js`, against the local stack. Use
  `site( 'journal', '/path/' )` and `expectNoA11yViolations( page )` from `tests/e2e/helpers.js`.
  Tag smoke tests `@smoke`.
- Lint: `composer lint` (PHPCS WordPress-Extra + PHPStan level 5), `npm run lint` (ESLint + budgets).

## Local commands

```bash
make up                       # build assets, start stack, install WP (admin/admin)
make seed                     # wp td import-content <dir> --status=draft (positional dir: --path is WP-CLI's own flag)
make wp SITE=journal ARGS="td setup"
docker compose exec -T -u www-data wp-marketing wp <command>
npm run build:assets          # CSS/JS only (fast)
npm run watch
```

## Pages (Phase 3/4): patterns → pages

- Each page is a full-page block pattern in `theme/patterns/<site>-<page>.php` (serialized
  `<!-- wp:techdella/… {json} /-->` markup copied from its prototype artboard, PHP allowed for `td_url()`/`esc_*`).
  Header fields `TD Site`, `TD Post Type`, `TD Slug`, `TD Title` (+ optional `TD Front Page: yes`, `TD Template`).
- `wp td preview:seed` turns the patterns into posts: **published on local only** (so the site can be browsed),
  drafts everywhere else (§0.4). It never overwrites content a person has edited.
- `templates/singular.php` renders block-built content full-bleed; specific templates (archives, single post,
  glossary term, case study…) go in `templates/` and pull fields with `td_field()`.
