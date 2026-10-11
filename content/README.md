# content/ — Markdown source for pages and posts (§10)

Every page, post, glossary term, case study, solution, location, tool, resource and Studios page is
written here as Markdown with a YAML front-matter brief, then imported into WordPress **as a draft**:

```bash
make seed                                    # all folders, all three sites, as drafts
docker compose exec -T -u www-data wp-journal wp td import-content \
  /var/www/repo/content/journal --status=draft      # one folder (not --path: that is WP-CLI's own option)
wp td import-content <dir> --dry-run                       # report only
```

The importer never publishes (§0.4). `--status` accepts only `draft` or `pending`. Posts that are
already published, scheduled or private in WordPress are skipped: once a person owns a page, edit it in
WordPress. It matches existing drafts by **slug + post type** and updates them. It prints created / updated /
skipped per file and the number of `[CONFIRM]` tokens left in each.

## Folders → post type and site

| Folder | Post type | Site |
|---|---|---|
| `marketing/` | `page` | Marketing |
| `services/` | `service` | Marketing |
| `journal/` | `post` | Journal |
| `glossary/` | `em-glossary` | Marketing |
| `case-studies/` | `case_study` | Marketing |
| `solutions/` | `solution` | Marketing |
| `locations/` | `location` | Marketing |
| `tools/` | `td_tool` | Marketing |
| `resources/` | `td_resource` | Marketing |
| `studios/` | `page`, or `type: studio_service` | Studios |
| `work/` | `studio_work` | Studios |

`type:` and `site:` in the front matter override the folder. Each site imports only its own files, so
running the same command on all three sites is safe. Files named `README.md` or starting with `_` are ignored.

## Front matter

```yaml
---
# Identity
title: "What is AI search optimization, and why should businesses care?"
slug: what-is-ai-search-optimization      # default: the file name
type: post                                 # optional (folder decides)
site: journal                              # optional (folder decides)
excerpt: "One or two sentences for cards and feeds."
format: markdown                           # or "blocks" to pass block markup through unchanged

# Brief (§10.1 step 1) — stored as fields on the post
primary_kw: ai search optimization
secondary_kws: [aeo, generative engine optimization]
intent: informational
audience: "B2B marketing leads [CONFIRM]"
angle: "What we know that others don't [CONFIRM]"
proof: "Which case study, screenshot or number [CONFIRM]"
internal_links:
  - /services/ai-search-optimization/
  - /glossary/aeo/
author: joanna-okedara-kalu                # user login/email, or a td_team slug linked to a user
reviewer: chinedu-emmanuel-kalu            # stored in the `reviewer` field (a user)

# Taxonomies
categories: [ai-search]
tags: []
taxonomies:
  td_service_tag: [ai-search-optimization]

# Media (path from the repo root)
featured_image: assets/images/journal/ai-search-cover.jpg
---
```

Rules:

- Every other key becomes a field: through ACF when the field exists (repeaters, user, date and
  relationship fields are mapped: lists → rows, logins → users, `2026-10-10` → date, slugs → posts),
  otherwise post meta. A repeater row with several sub-fields is one line split on ` | `, in field
  order: `- "What score should I aim for? | Aim for grade 8 or lower."` fills `question` and `answer`.
- YAML support is deliberately small: scalars, quoted strings, `[inline, lists]`, `- block lists`, and maps
  one level deep (with lists inside). No anchors, tags or `{inline: maps}`.
- The body is Markdown (converted to blocks: paragraphs, headings, lists, quotes, tables, images). The title
  is the page's only H1, so start the body with `##`.
- Link to another site with a site prefix: `[Studios work](studios:/work/)` becomes the right domain
  on each environment (localhost ports in dev). Never type a full techdella.com URL.
- A field named like a reserved key (`status`, `title`…) goes under `fields:`, e.g. Studios work
  `fields:` then `  status: Live` (Live or Concept). `menu_order:` sets the order on /work/.
- `gallery:` is a list of repo image paths, uploaded and stored in the `gallery` field. A tall
  website screenshot first, then a phone screenshot, shows as a browser + phone pair on Studios.
- Resources: `pdf: pdf/out/<slug>.pdf` (run `npm run pdf` first) uploads the PDF under an unguessable
  path; `featured_image: pdf/out/covers/<slug>.jpg` sets the cover.
- Put `[CONFIRM]` wherever a fact, number, quote or claim needs a person, `[PRICE]` for prices. Publishing
  is blocked until none are left (§10.1 step 5); `wp td audit placeholders` lists any on live pages.
- Voice: §10.2 (US English, short sentences, no hype words). Proof: only §2.4.

## Samples

`journal/sample-ai-search-optimization.md`, `marketing/sample-growth-audit-brief.md` and
`studios/sample-mvp-development-brief.md` show the format. They are **samples**: drafts with
`[CONFIRM]` placeholders, slugs prefixed `sample-` so they never collide with real pages. Delete the
drafts in WordPress once real content exists.
