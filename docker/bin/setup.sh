#!/usr/bin/env bash
# First-run install for the local sites (§4.2 `make up`). Idempotent: safe to
# re-run. Plugin zips placed in docker/cache/plugins/<slug>.zip are installed
# from disk (offline); otherwise WP-CLI downloads them from wordpress.org.
set -euo pipefail
cd "$(dirname "$0")/../.."

SITES="${*:-marketing journal studios}"
# §5.2 — everything else gets removed. ACF Pro needs a licence [CONFIRM];
# the free ACF plugin is installed locally so field groups load.
PLUGINS="seo-by-rank-math safe-svg advanced-custom-fields"

declare -A PORTS=( [marketing]=8081 [journal]=8082 [studios]=8083 )
declare -A TITLES=( [marketing]="TechDella" [journal]="TechDella Journal" [studios]="TechDella Studios" )

wp() { docker compose exec -T -u www-data "wp-$SITE" wp "$@"; }

for SITE in $SITES; do
  echo "▸ $SITE (http://localhost:${PORTS[$SITE]})"
  # Wait for the container's wp-config.php (written by the image entrypoint).
  for _ in $(seq 1 30); do
    docker compose exec -T "wp-$SITE" test -f /var/www/html/wp-config.php && break
    sleep 2
  done

  if ! wp core is-installed >/dev/null 2>&1; then
    wp core install --url="http://localhost:${PORTS[$SITE]}" --title="${TITLES[$SITE]}" \
      --admin_user=admin --admin_password=admin --admin_email="dev@techdella.local" --skip-email
  fi

  wp rewrite structure '/%postname%/' --hard >/dev/null
  wp option update blog_public 1 >/dev/null
  wp option update default_comment_status closed >/dev/null
  wp option update timezone_string 'Africa/Lagos' >/dev/null

  for p in $PLUGINS; do
    if ! wp plugin is-installed "$p"; then
      if docker compose exec -T "wp-$SITE" test -f "/var/www/repo/cache/plugins/$p.zip"; then
        wp plugin install "/var/www/repo/cache/plugins/$p.zip" --activate
      else
        wp plugin install "$p" --activate
      fi
    else
      wp plugin activate "$p" >/dev/null
    fi
  done
  wp plugin delete hello akismet >/dev/null 2>&1 || true

  wp theme activate techdella
  # Rank Math: skip the setup wizard, keep our schema (D5).
  wp option update rank_math_wizard_completed 1 >/dev/null || true
  wp rewrite flush --hard >/dev/null
  wp td setup --quiet || echo "  (wp td setup not available yet)"
done
echo "✓ Sites ready: marketing :8081 · journal :8082 · studios :8083 · mail :8025 · sb-mock :8090"
