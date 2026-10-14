# Motion samples (§6.3)

Seven short, on-brand videos rendered from HTML scenes. Each scene is a page in `motion/scenes/`
animated with CSS keyframes only; `motion/render.mjs` opens it in Chromium, pauses every animation,
steps them frame by frame (30 fps) and encodes with ffmpeg. Change the copy in the HTML, re-render,
re-upload. Nothing to install beyond the repo's own tooling (Playwright, sharp, ffmpeg).

We don't use Remotion: its licence requires a paid Company License for companies with more than
3 employees. The owner chose this licence-free renderer instead (Oct 2026).

## Render

```bash
npm run motion:render                          # everything → assets/video/
npm run motion:render -- --only mo-03,mo-05    # some scenes
npm run motion:render -- --format wide         # tall | square | wide
npm run motion:render -- --preview             # 8-frame contact sheets → motion/out/preview/
```

Outputs per scene and format (`tall` 1080×1920, `square` 1080×1080, `wide` 1920×1080):
`<scene>-<format>.mp4` (H.264, CRF 23, no audio), `.webm` (VP9), `.jpg` (poster), plus
`<scene>.vtt` captions and `report.json` with sizes. Renders are not committed; the build fails if a
looping web video is 2.5 MB or more.

## Scenes

| ID | File | Length | Where it goes |
|---|---|---|---|
| MO-01 | `mo-01-hero-loop` | 6 s loop | Home hero (optional; the live hero is the lighter HTML orbit) |
| MO-02 | `mo-02-connected-system` | 15 s | Home bento, Services hub |
| MO-03 | `mo-03-ai-answer-cited` | 12 s | AI Search page |
| MO-04 | `mo-04a-tiktok-hook`, `mo-04b-meta-carousel`, `mo-04c-linkedin-document` | 3 × 10 s | Paid page gallery, Studios Motion. Fictional brand "Northwind", labelled Example |
| MO-05 | `mo-05-workflow` | 10 s | Automation page |
| MO-06 | `mo-06-logo-reveal` | 8 s | Studios, social |
| MO-07 | `mo-07-studios-reel` | 20 s | Studios hero / Work. Concept screens only, labelled Concept |

Client work (startbuddi, Paycape, Shopinbos, Epic) stays out of the reel until the Studios credit is
confirmed (`content/work/`). Scenes use the placeholder brand "YourCo" or the fictional "Northwind",
never real clients' data, and carry an Example or Concept label.

## Put a video on a page

1. Import the renders into each site's Media Library: `wp td media:videos <dir>` (locally
   `/var/www/repo/assets/video`; the staging/production deploy renders them, uploads them to
   `~/td-video/` and runs this for you). It is idempotent and records the files per scene.
2. Add the **Video (self-hosted)** block and set `scene` (e.g. `mo-03-ai-answer-cited`): the ratio
   picks the format (16:9 wide, 1:1 square, 9:16 tall). Set `mode` (`loop` for silent animations,
   `player` for controls), a `title` (screen readers and VideoObject schema), `duration`, and
   `label` (Example / Concept) where the video doesn't carry one already. `items` puts several
   clips in a row (swipeable on phones); `heading`/`intro`/`chip` add a section heading.
   You can still pick files by hand: they override the scene.

Where they are today: Services hub (MO-02), AI search (MO-03), Paid ads and Studios motion design
(MO-04 a/b/c, 9:16), Marketing automation (MO-05), Studios home (MO-07). MO-01 stays off the home
hero (LCP budget, §8.4) and MO-06 off the pages (the gradient is used once per page, §2.1): both
are in the Media Library for social.

The block never autoplays with sound, plays loops only near the viewport, shows the poster only
to visitors who prefer reduced motion, and gives loops a pause button.

## Write a new scene

Copy a scene, keep the `#scene-meta` JSON (`id`, `title`, `duration` in seconds, `poster` time,
`loop`, `captions` as `[start, end, text]`) and animate with CSS only (keyframes with delays). No
JavaScript timers: the renderer seeks `document.getAnimations()`, so anything else won't render.
`scene.css` sets `1rem` = 10 px on the 1080 px side and has `.only-tall` / `.only-wide` hooks.
