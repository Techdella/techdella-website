#!/usr/bin/env bash
# Build the deploy bundle and commit it to the `hostinger` branch, which hPanel → GIT pulls from
# (an alternative to GitHub Actions; see scripts/hostinger/README.md).
#
#   ./scripts/build-hostinger.sh            build, commit, push
#   ./scripts/build-hostinger.sh --no-push  build and commit locally only
#
# Needs: npm (build), and the motion renders in assets/video (npm run motion:render) and the PDFs in
# pdf/out (npm run pdf). Run lint and tests first: nothing on the server re-checks this code.
set -euo pipefail
cd "$(dirname "$0")/.."
PUSH=1
[ "${1:-}" = --no-push ] && PUSH=0
BRANCH=hostinger

test -z "$(git status --porcelain -- wp-content content data scripts/hostinger)" \
  || { echo "Commit your changes first: the bundle records the commit it was built from." >&2; exit 1; }
compgen -G "assets/video/*.mp4" > /dev/null || { echo "No videos in assets/video: run npm run motion:render." >&2; exit 1; }
compgen -G "pdf/out/*.pdf" > /dev/null || { echo "No PDFs in pdf/out: run npm run pdf." >&2; exit 1; }

npm run build

SRC_SHA="$(git rev-parse --short HEAD)"
WT="$(mktemp -d)"
trap 'git worktree remove --force "$WT" > /dev/null 2>&1 || rm -rf "$WT"' EXIT
git worktree add --detach "$WT" HEAD > /dev/null
(
  cd "$WT"
  if git fetch -q origin "$BRANCH" 2> /dev/null; then
    git checkout -q -B "$BRANCH" FETCH_HEAD
  else
    git checkout -q --orphan "$BRANCH"
  fi
  git rm -rqf --ignore-unmatch . > /dev/null
  find . -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
)

R="$PWD"
mkdir -p "$WT/wp-content/themes" "$WT/wp-content/mu-plugins" "$WT/assets/video" "$WT/pdf"
cp -a "$R/wp-content/themes/techdella" "$WT/wp-content/themes/"
cp -a "$R/wp-content/mu-plugins/techdella-core" "$R/wp-content/mu-plugins/techdella-core.php" "$WT/wp-content/mu-plugins/"
cp -a "$R/content" "$R/data" "$WT/"
cp -a "$R/assets/images" "$R/assets/logos" "$R/assets/creatives" "$R/assets/CREDITS.md" "$WT/assets/"
cp -a "$R"/assets/video/*.{mp4,webm,jpg,vtt} "$WT/assets/video/"
cp -a "$R/pdf/out" "$WT/pdf/"
cp "$R/scripts/hostinger/sync.sh" "$R/scripts/hostinger/README.md" "$WT/"
cp "$R/scripts/hostinger/htaccess" "$WT/.htaccess"
find "$WT" -name .DS_Store -delete
printf '%s\nbuilt %s from %s\n' "$SRC_SHA" "$(date -u +%Y-%m-%dT%H:%MZ)" "$(git rev-parse --abbrev-ref HEAD)" > "$WT/BUILD.txt"

(
  cd "$WT"
  git add -A
  git commit -q -m "Deploy bundle from $SRC_SHA" -m "Built by scripts/build-hostinger.sh. Install with sync.sh (see README.md)."
  echo "✓ $BRANCH: $(git rev-parse --short HEAD) ($(du -sh --exclude=.git . | cut -f1)) from $SRC_SHA"
  if [ "$PUSH" = 1 ]; then git push -q origin "HEAD:$BRANCH"; echo "✓ pushed to origin/$BRANCH"; fi
)
