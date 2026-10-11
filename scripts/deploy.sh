#!/usr/bin/env bash
# Deploy theme + mu-plugins over SSH (§5.3). Uploads and wp-config.php are never touched.
#   ./scripts/deploy.sh staging|production
set -euo pipefail
cd "$(dirname "$0")/.."
TARGET="${1:?usage: deploy.sh staging|production}"
: "${HOSTINGER_SSH_HOST:?}" "${HOSTINGER_SSH_USER:?}"
PORT="${HOSTINGER_SSH_PORT:-65002}"
SSH="ssh -p $PORT $HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST"

test -f wp-content/themes/techdella/assets/build/main.css || { echo "Run npm run build first"; exit 1; }

if [ "$TARGET" = production ]; then
  PATHS=("$PATH_MARKETING" "$PATH_JOURNAL" "$PATH_STUDIOS")
else
  PATHS=("${PATH_STAGING:?}")
fi

for P in "${PATHS[@]}"; do
  echo "▸ $TARGET → $P"
  rsync -az --delete -e "ssh -p $PORT" \
    --exclude '.DS_Store' --exclude 'node_modules' \
    wp-content/themes/techdella/ "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST:$P/wp-content/themes/techdella/"
  rsync -az -e "ssh -p $PORT" \
    wp-content/mu-plugins/techdella-core.php "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST:$P/wp-content/mu-plugins/"
  rsync -az --delete -e "ssh -p $PORT" \
    wp-content/mu-plugins/techdella-core/ "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST:$P/wp-content/mu-plugins/techdella-core/"
  $SSH "cd $P && wp theme activate techdella && wp cache flush && (wp litespeed-purge all || true) && (wp rankmath sitemap generate || true) && wp td setup --quiet"
  # Motion samples (§6.3): upload the renders outside the web root, then import them through
  # WordPress (wp td media:videos is idempotent: unchanged files are skipped).
  if compgen -G "assets/video/*.mp4" > /dev/null; then
    rsync -az -e "ssh -p $PORT" --include '*.mp4' --include '*.webm' --include '*.jpg' --include '*.vtt' --exclude '*' \
      assets/video/ "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST:td-video/"
    $SSH "cd $P && wp td media:videos ~/td-video"
  fi
  # First run only: record the owner's confirmation that every existing case study and
  # testimonial is cleared (Oct 2026). Later ones get permission_confirmed one by one in WP.
  if [ "${FIRST_RUN:-}" = true ] && [ -n "${TD_PERMISSIONS_NOTE:-}" ]; then
    $SSH "cd $P && wp td permissions:grant --note=$(printf %q "$TD_PERMISSIONS_NOTE")"
  fi
done
echo "✓ deployed to $TARGET"
