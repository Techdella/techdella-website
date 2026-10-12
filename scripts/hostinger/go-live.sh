#!/usr/bin/env bash
# Make a site live after its reviewed staging copy has been published over it (hPanel → Staging →
# Publish), or flip Studios in place. Run on the server:
#
#   bash ~/techdella-deploy/go-live.sh <live site folder> --site=marketing|journal|studios
#
# What it does, in order (it asks you to type the domain first):
#   1. TD_SITE and TD_ENV=production in wp-config; the staging-only TD_URL_* links removed.
#   2. Every staging address stored in the database rewritten to the real domain
#      (posts, menus, options, block attributes), shown as a dry run first.
#   3. The site opened to search engines (blog_public = 1), caches and sitemap rebuilt.
#   4. Checks: no noindex, no staging address left on the home page, redirects (Marketing).
set -euo pipefail

BUNDLE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SITE_DIR="${1:?usage: go-live.sh <live site folder> --site=marketing|journal|studios}"
shift
SITE=""
for a in "$@"; do
  case "$a" in
    --site=*) SITE="${a#*=}" ;;
    *) echo "Unknown option: $a" >&2; exit 2 ;;
  esac
done
case "$SITE" in
  marketing) DOMAIN=techdella.com ;;
  journal) DOMAIN=blog.techdella.com ;;
  studios) DOMAIN=studios.techdella.com ;;
  *) echo "--site must be marketing, journal or studios" >&2; exit 2 ;;
esac
case "$SITE_DIR" in *staging*) echo "That is a staging folder. go-live.sh is for the live folder." >&2; exit 1 ;; esac
SITE_DIR="$(cd "$SITE_DIR" && pwd)"
WP="wp --path=$SITE_DIR"
$WP core is-installed || { echo "WordPress is not installed in $SITE_DIR." >&2; exit 1; }

HOME_URL="$($WP option get home)"
HOST="${HOME_URL#*://}"; HOST="${HOST%%/*}"
if [ "$HOST" != "$DOMAIN" ]; then
  echo "This folder's home URL is $HOME_URL, not https://$DOMAIN. Stopped; nothing was changed." >&2
  exit 1
fi
echo "▸ Going live: https://$DOMAIN ($SITE) in $SITE_DIR"
read -r -p "  Type $DOMAIN to continue: " ANSWER < /dev/tty
[ "$ANSWER" = "$DOMAIN" ] || { echo "Stopped. Nothing was changed."; exit 1; }

# 1. Site settings in wp-config.
$WP config set TD_SITE "$SITE" --type=constant
$WP config set TD_ENV production --type=constant
for c in TD_URL_MARKETING TD_URL_JOURNAL TD_URL_STUDIOS; do
  if $WP config has "$c" --type=constant > /dev/null 2>&1; then $WP config delete "$c" --type=constant; fi
done
echo "  ✓ TD_SITE=$SITE, TD_ENV=production; staging links (TD_URL_*) removed from wp-config"

# 2. Staging addresses stored in the database → real domains. Longest first; the escaped forms
#    cover URLs inside block attributes (JSON).
PAIRS=(
  "https://staging.blog.techdella.com|https://blog.techdella.com"
  "https:\\/\\/staging.blog.techdella.com|https:\\/\\/blog.techdella.com"
  "https://staging.techdella.com|https://techdella.com"
  "https:\\/\\/staging.techdella.com|https:\\/\\/techdella.com"
  "https://blog.techdella.com/staging/|https://blog.techdella.com/"
  "https:\\/\\/blog.techdella.com\\/staging\\/|https:\\/\\/blog.techdella.com\\/"
  "https://techdella.com/staging/|https://techdella.com/"
  "https:\\/\\/techdella.com\\/staging\\/|https:\\/\\/techdella.com\\/"
)
echo "  Staging addresses in the database (dry run):"
TOTAL=0
for p in "${PAIRS[@]}"; do
  from="${p%%|*}"; to="${p#*|}"
  n="$($WP search-replace "$from" "$to" --skip-columns=guid --precise --dry-run --format=count 2> /dev/null || echo 0)"
  printf '    %-48s %s\n' "$from" "$n"
  TOTAL=$((TOTAL + n))
done
if [ "$TOTAL" -gt 0 ]; then
  read -r -p "  Replace these $TOTAL staging addresses with the real domains? Type yes: " OK < /dev/tty
  [ "$OK" = yes ] || { echo "Stopped before the database changed (wp-config is already set)."; exit 1; }
  $WP db export "$HOME/golive-$SITE-$(date +%Y%m%d-%H%M).sql" > /dev/null && echo "  ✓ database backup saved in ~/"
  for p in "${PAIRS[@]}"; do
    $WP search-replace "${p%%|*}" "${p#*|}" --skip-columns=guid --precise --quiet
  done
  echo "  ✓ $TOTAL staging addresses replaced"
else
  echo "  ✓ no staging addresses in the database"
fi

# 3. Open to search engines; rebuild.
$WP option update blog_public 1 > /dev/null
$WP td setup --quiet
$WP rewrite flush
$WP cache flush
$WP litespeed-purge all 2> /dev/null || true
$WP rankmath sitemap generate 2> /dev/null || true
echo "  ✓ indexable (blog_public=1); caches flushed; sitemap rebuilt"

# 4. Checks.
FAIL=0
HEAD="$(curl -sI "https://$DOMAIN/?golive=$RANDOM" | tr -d '\r')"
BODY="$(curl -s "https://$DOMAIN/?golive=$RANDOM")"
if echo "$HEAD" | grep -qi '^x-robots-tag:.*noindex' || echo "$BODY" | grep -qi '<meta name=.robots. content=.[^>]*noindex'; then
  echo "  ✗ the home page still says noindex"; FAIL=1
else
  echo "  ✓ home page is indexable"
fi
if echo "$BODY" | grep -qE 'staging\.(blog\.)?techdella\.com|techdella\.com/staging'; then
  echo "  ✗ a staging address is still on the home page:"; echo "$BODY" | grep -oE 'https?://[^"]*staging[^"]*' | sort -u | head -5; FAIL=1
else
  echo "  ✓ no staging addresses on the home page"
fi
if [ "$SITE" = marketing ]; then
  $WP td redirects:verify --file="$BUNDLE/data/redirects.csv" || FAIL=1
fi
[ "$FAIL" = 0 ] && echo "✓ https://$DOMAIN is live." || { echo "Fix the ✗ items above (the site is live)."; exit 1; }
