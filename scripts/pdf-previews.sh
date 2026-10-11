#!/usr/bin/env bash
# Page previews for the resource pages (§13.2 "sample page preview"): pages 2 and 3 of each PDF in
# pdf/out as 900px-wide JPEGs in pdf/out/previews/<slug>-p2.jpg / -p3.jpg. Needs pdftoppm (poppler).
set -euo pipefail
cd "$(dirname "$0")/.."
command -v pdftoppm > /dev/null || { echo "pdftoppm not found (install poppler-utils)." >&2; exit 1; }
mkdir -p pdf/out/previews
for pdf in pdf/out/*.pdf; do
  slug="$(basename "$pdf" .pdf)"
  pdftoppm -f 2 -l 3 -jpeg -jpegopt quality=82 -scale-to-x 900 -scale-to-y -1 "$pdf" "pdf/out/previews/$slug-p"
  # pdftoppm numbers pages with padding that depends on the page count (p-2 or p-02): normalise.
  for n in 2 3; do
    for f in "pdf/out/previews/$slug-p-$n.jpg" "pdf/out/previews/$slug-p-0$n.jpg"; do
      [ -f "$f" ] && mv "$f" "pdf/out/previews/$slug-p$n.jpg"
    done
  done
  echo "✓ $slug"
done
