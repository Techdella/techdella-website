#!/usr/bin/env bash
# Install this bundle into one WordPress site on Hostinger (run it in hPanel's SSH terminal).
#
#   bash sync.sh <site folder> [--site=marketing|journal|studios] [--env=staging|production] [--first-run]
#
#   bash ~/techdella-deploy/sync.sh ~/domains/staging.techdella.com/public_html --site=marketing --env=staging --first-run
#   bash ~/techdella-deploy/sync.sh ~/domains/staging.techdella.com/public_html          (every later update)
#   bash ~/techdella-deploy/sync.sh <staging folder> --refresh-pages                       (staging: show the new page designs)
#
# Every run: copies the theme and the core plugin into the site (uploads and wp-config.php are never
# touched), activates the theme, imports the motion videos, runs `wp td setup`, clears the caches.
# --refresh-pages (staging only): replace existing pages' and case studies' content with the new
# designs (old content stays as a revision) and publish them on the private staging site.
# --first-run also: sets TD_SITE/TD_ENV in wp-config.php (only if missing), installs the plugins
# (§5.2), blocks search engines on staging, imports content/ and the page patterns as DRAFTS (a
# person publishes, §10.1), imports the redirects, and records the owner's client permissions.
set -euo pipefail

BUNDLE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SITE_DIR="${1:?usage: sync.sh <site folder> [--site=…] [--env=…] [--first-run]}"
shift
SITE="" ENV="" FIRST=0 REFRESH=0
for a in "$@"; do
  case "$a" in
    --site=*) SITE="${a#*=}" ;;
    --env=*) ENV="${a#*=}" ;;
    --first-run) FIRST=1 ;;
    --refresh-pages) REFRESH=1 ;;
    *) echo "Unknown option: $a" >&2; exit 2 ;;
  esac
done
SITE_DIR="$(cd "$SITE_DIR" && pwd)"
WP="wp --path=$SITE_DIR"
$WP core is-installed || { echo "WordPress is not installed in $SITE_DIR. Install it from hPanel first." >&2; exit 1; }
case "$SITE_DIR/" in "$BUNDLE"/*) echo "The site folder can't be inside the bundle." >&2; exit 1 ;; esac

echo "▸ TechDella $(head -1 "$BUNDLE/BUILD.txt") → $SITE_DIR"

# A live site changes the moment this runs (new theme, new plugin), so production needs a typed yes.
CURRENT_ENV="$($WP config get TD_ENV 2> /dev/null || true)"
if [ "${ENV:-$CURRENT_ENV}" != staging ]; then
  HOME_URL="$($WP option get home)"
  HOST="${HOME_URL#*://}"; HOST="${HOST%%/*}"
  echo "  This is ${HOME_URL} and it is not marked as staging. Visitors will see the change at once."
  read -r -p "  Type ${HOST} to continue: " ANSWER < /dev/tty
  [ "$ANSWER" = "$HOST" ] || { echo "Stopped. Nothing was changed."; exit 1; }
fi

if [ "$FIRST" = 1 ]; then
  [[ "$SITE" =~ ^(marketing|journal|studios)$ ]] || { echo "--first-run needs --site=marketing|journal|studios" >&2; exit 2; }
  [[ "$ENV" =~ ^(staging|production)$ ]] || { echo "--first-run needs --env=staging|production" >&2; exit 2; }
  $WP config has TD_SITE --type=constant || $WP config set TD_SITE "$SITE" --type=constant
  $WP config has TD_ENV --type=constant || $WP config set TD_ENV "$ENV" --type=constant
fi

# Copy the code. rsync when the host has it; otherwise replace the folders.
copy_dir() { # <from> <to>
  mkdir -p "$2"
  if command -v rsync > /dev/null; then
    rsync -a --delete "$1/" "$2/"
  else
    rm -rf "$2.td-new" && cp -a "$1" "$2.td-new" && rm -rf "$2" && mv "$2.td-new" "$2"
  fi
}
copy_dir "$BUNDLE/wp-content/themes/techdella" "$SITE_DIR/wp-content/themes/techdella"
copy_dir "$BUNDLE/wp-content/mu-plugins/techdella-core" "$SITE_DIR/wp-content/mu-plugins/techdella-core"
cp "$BUNDLE/wp-content/mu-plugins/techdella-core.php" "$SITE_DIR/wp-content/mu-plugins/techdella-core.php"
echo "  ✓ theme and core plugin copied"

$WP theme activate techdella

if [ "$FIRST" = 1 ]; then
  for p in seo-by-rank-math advanced-custom-fields safe-svg litespeed-cache; do
    $WP plugin is-installed "$p" || $WP plugin install "$p" || { echo "  ! couldn't install $p: install it from WP admin → Plugins"; continue; }
    $WP plugin activate "$p" || true
  done
  if [ "$ENV" = staging ]; then $WP option update blog_public 0; fi
  $WP rewrite structure '/%postname%/' --hard || true
fi

# LiteSpeed must not rewrite our CSS/JS (§5.1): the build already minifies, inlines critical CSS
# and defers scripts. Its "unused CSS" and combine options strip the theme's styles.
if $WP plugin is-active litespeed-cache 2> /dev/null; then
  for o in optm-css_min optm-css_comb optm-ucss optm-css_async optm-js_min optm-js_comb optm-js_defer optm-html_min; do
    $WP litespeed-option set "$o" 0 > /dev/null 2>&1 || true
  done
  echo "  ✓ LiteSpeed CSS/JS optimisation off (page cache stays on)"
fi

$WP td setup --quiet
# New post types and pages need fresh rewrite rules. A soft flush works on Hostinger (the --hard
# one needs proc_open, which is disabled there) and leaves .htaccess alone.
$WP rewrite flush
$WP td media:videos "$BUNDLE/assets/video"
$WP td media:videos "$BUNDLE/assets/creatives/video"

if [ "$FIRST" = 1 ]; then
  # Drafts only: nothing here publishes (§0.4). Imported pages wait for a person in WP admin.
  $WP td import-content "$BUNDLE/content" --status=draft
  $WP td preview:seed
  $WP td redirects import --file="$BUNDLE/data/redirects.csv" || echo "  ! redirects import failed: run it again after Rank Math is set up"
  $WP td permissions:grant --note="Joanna Okedara-Kalu confirmed all client permissions (names, numbers, quotes, logos), 11 Oct 2026"
fi

if [ "$REFRESH" = 1 ]; then
  [ "$($WP config get TD_ENV 2> /dev/null || true)" = staging ] || { echo "--refresh-pages only runs on staging." >&2; exit 2; }
  $WP td preview:seed --force --publish
  # Case studies, rebuilt from the published originals (content/case-studies), onto the copies.
  # Rebuilt case studies, industries, markets and (on the right site) guides, glossary and the
  # business library, published on the private staging site for review (never on production).
  for d in case-studies solutions locations glossary business journal; do
    [ -d "$BUNDLE/content/$d" ] && $WP td import-content "$BUNDLE/content/$d" --status=draft --update-published --publish
  done
  # Article covers (title + photo in the arch) where an article has no image of its own.
  $WP td covers:generate
  # Old redirects that fight the new ones are switched off; old pages that are now redirected
  # (e.g. the old service pages) are unpublished (draft, never deleted).
  $WP td redirects import --file="$BUNDLE/data/redirects.csv"
  $WP td legacy:retire
fi

$WP cache flush
$WP litespeed-purge all 2> /dev/null || true
$WP rankmath sitemap generate 2> /dev/null || true

# Staging must never be indexed (§5.3).
if [ "$($WP config get TD_ENV 2> /dev/null || true)" = staging ]; then
  URL="$($WP option get home)"
  if curl -sI "$URL/" | tr -d '\r' | grep -qi '^x-robots-tag:.*noindex'; then
    echo "  ✓ staging sends X-Robots-Tag: noindex"
  else
    echo "  ! staging did NOT send X-Robots-Tag: noindex (if it's password-protected, check it in a browser)"
  fi
fi
echo "✓ done"
