#!/usr/bin/env bash
# One-time setup of a fresh Hostinger WordPress install for TechDella (run before the first deploy).
#   ./scripts/remote-setup.sh staging|production   (uses the same HOSTINGER_* / PATH_* env as deploy.sh)
# Sets the TD_* constants in wp-config.php (only if missing — never overwrites), installs the §5.2
# plugins, and blocks search engines on staging. Secrets (Startbuddi endpoints, Turnstile, AdSense…)
# are still added by a person in wp-config.php or hPanel.
set -euo pipefail
TARGET="${1:?usage: remote-setup.sh staging|production}"
: "${HOSTINGER_SSH_HOST:?}" "${HOSTINGER_SSH_USER:?}"
PORT="${HOSTINGER_SSH_PORT:-65002}"
SSH="ssh -p $PORT $HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST"

if [ "$TARGET" = production ]; then
  SITES=("marketing:$PATH_MARKETING" "journal:$PATH_JOURNAL" "studios:$PATH_STUDIOS")
else
  SITES=("marketing:${PATH_STAGING:?}")
fi

for entry in "${SITES[@]}"; do
  SITE="${entry%%:*}"; P="${entry#*:}"
  echo "▸ $TARGET/$SITE → $P"
  $SSH bash -s -- "$P" "$SITE" "$TARGET" <<'REMOTE'
set -euo pipefail
cd "$1"
wp core is-installed || { echo "WordPress is not installed in $1 — install it from hPanel first."; exit 1; }
wp config has TD_SITE --type=constant || wp config set TD_SITE "$2" --type=constant
wp config has TD_ENV  --type=constant || wp config set TD_ENV "$3" --type=constant
for p in seo-by-rank-math advanced-custom-fields safe-svg litespeed-cache; do
  wp plugin is-installed "$p" || wp plugin install "$p"
  wp plugin activate "$p" || true
done
if [ "$3" = staging ]; then wp option update blog_public 0; fi
wp rewrite structure '/%postname%/' --hard || true
REMOTE
done
echo "✓ remote setup done — now run scripts/deploy.sh $TARGET"
