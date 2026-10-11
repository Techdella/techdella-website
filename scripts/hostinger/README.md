# TechDella deploy bundle (`hostinger` branch)

Built from the main code by `scripts/build-hostinger.sh`. Don't edit files here: the next build
replaces them. `BUILD.txt` says which commit it was built from.

It holds what the servers need and nothing else: the theme (with its built CSS, JS and images), the
core plugin, the content sources, redirects, PDFs, and the motion videos.

## First time (per site)

1. hPanel → **Websites → the site → Advanced → GIT**. Repository
   `git@github.com:Techdella/techdella-website.git`, branch `hostinger`, an empty folder such as
   `techdella-deploy`. Copy the SSH key hPanel shows into GitHub → repo **Settings → Deploy keys**
   (read-only), then **Deploy**.
2. hPanel → **Advanced → SSH Access** → open the terminal and run (with your own folders):

   ```bash
   bash ~/domains/<domain>/public_html/techdella-deploy/sync.sh ~/domains/<domain>/public_html \
     --site=marketing --env=staging --first-run
   ```

   `--site` is `marketing`, `journal` or `studios`; `--env` is `staging` or `production`.

## Every update

Click **Deploy** in hPanel → GIT (or add hPanel's webhook URL in GitHub → **Settings → Webhooks**
so it pulls by itself), then run the same `sync.sh` line without `--site`, `--env` and `--first-run`.

## Safe by design

- Uploads and `wp-config.php` are never overwritten. Secrets stay in `wp-config.php`/hPanel.
- Content is imported as drafts; a person publishes it.
- This folder blocks web access (`.htaccess`), so it's safe even inside `public_html`.
