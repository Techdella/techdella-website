# Setting up the Journal (blog) and Studios on Hostinger

Two different starting points (checked 12 Oct 2026):

- **blog.techdella.com is live.** It runs WordPress with the old `techdella-blog-theme`, 335 published
  posts, 2 pages (`/home-2/`, `/submit-guest-post/`) and 8 categories, all at `/<slug>/`. The posts are
  ordinary WordPress blocks, so they render in the new theme. It gets a **staging copy first**; the
  live blog only changes on launch day.
- **studios.techdella.com is empty.** The subdomain shows Hostinger's "Default page"; WordPress isn't
  installed yet. Nothing to protect, so it's set up in place and kept out of search (`--env=staging`)
  until it's signed off.

Run every command below **on the server** (after `ssh -p 65002 u685734478@195.179.236.59`), not on
your Mac. First update the bundle:

```
cd ~/techdella-deploy && GIT_SSH_COMMAND="ssh -i ~/.ssh/techdella_deploy" git pull && head -1 BUILD.txt
```

To find any site's folder later: `find ~/domains -maxdepth 4 -name wp-config.php`

## Studios (new site)

1. **hPanel → Websites → techdella.com → WordPress → Install** (or Auto Installer). Choose
   `studios.techdella.com`, leave the directory empty, and use your own admin login.
2. On the server, find its folder (usually `~/domains/techdella.com/public_html/studios` or
   `~/domains/studios.techdella.com/public_html`):
   ```
   find ~/domains -maxdepth 4 -name wp-config.php
   ```
3. Install the theme, plugin and content, then show the designs (still hidden from Google):
   ```
   bash ~/techdella-deploy/sync.sh <studios folder> --site=studios --env=staging --first-run
   bash ~/techdella-deploy/sync.sh <studios folder> --refresh-pages
   ```
4. Review https://studios.techdella.com: home, 7 services, Work (Epic, Paycape, Shopinbos,
   startbuddi + 3 Concepts), /start/, /mvp-calculator/, /about/.
5. **Go live** when you're happy (this is your sign-off):
   ```
   wp --path=<studios folder> config set TD_ENV production --type=constant
   wp --path=<studios folder> option update blog_public 1
   wp --path=<studios folder> litespeed-purge all
   ```

## Journal (live blog)

1. **Back up first:** hPanel → Websites → blog.techdella.com → Backups → create a backup.
2. **Make a staging copy:** hPanel → Websites → blog.techdella.com → WordPress → Staging → Create
   staging. Hostinger copies the files and database (here it is served at `staging.blog.techdella.com`).
   Find its folder with the `find` command above (the one with `staging` in the path).
3. Install on the **staging copy** and show the designs:
   ```
   bash ~/techdella-deploy/sync.sh <blog staging folder> --site=journal --env=staging --first-run
   bash ~/techdella-deploy/sync.sh <blog staging folder> --refresh-pages
   ```
   What this does to the copy:
   - new theme on all 335 existing posts (their content is untouched);
   - the 24 new and refreshed guides published (the 17 moving from techdella.com/guide/ included);
     `b2b-seo-strategy` already exists, so the new version replaces it (old text kept as a revision);
   - `/submit-guest-post/` → `/write-for-us/` and `/home-2/` → `/` (`data/redirects-journal.csv`).
4. **Link the staging sites to each other** (so nav and footer links stay on staging):
   ```
   wp --path=<marketing staging folder> config set TD_URL_JOURNAL https://staging.blog.techdella.com --type=constant
   wp --path=<blog staging folder> config set TD_URL_MARKETING https://staging.techdella.com --type=constant
   wp --path=<studios folder> config set TD_URL_MARKETING https://staging.techdella.com --type=constant
   wp --path=<studios folder> config set TD_URL_JOURNAL https://staging.blog.techdella.com --type=constant
   ```
   Remove these on launch day (`wp config delete TD_URL_JOURNAL`, etc.) so links use the real domains.
5. Review: home, a few old posts, the new guides, category pages, search, /newsletter/, /write-for-us/.

### Launch day for the blog

1. Back up the live blog again.
2. Install on the **live** folder (it asks you to type `blog.techdella.com` to confirm):
   ```
   bash ~/techdella-deploy/sync.sh <live blog folder> --site=journal --env=production --first-run
   ```
   New guides arrive as **drafts**; existing published posts are never overwritten (§0.4).
   **Straight away**, publish the new **Write for us** and **Newsletter** pages (Pages → Drafts):
   `/submit-guest-post/` already redirects to `/write-for-us/`.
3. Bring the 335 old posts into the new layout (service CTA, one mid-article CTA, a cover where
   there's no image; nobody's text is rewritten). Check the list first:
   ```
   wp --path=<live blog folder> td legacy:upgrade --dry-run
   wp --path=<live blog folder> td legacy:upgrade --production
   ```
4. Publish the 17 moved guides in WP admin first (they're the targets of the techdella.com
   `/guide/*` redirects), then the rest. Keep to **15 publishes a week** (§8.1).
5. Only after the 17 guides are live: launch techdella.com, whose redirects send `/guide/<slug>/`
   to `blog.techdella.com/<slug>/`.
6. Old plugins (blog and techdella.com). The theme already silences them, but remove them at launch
   so nothing comes back after an update:
   - **Yoast SEO** runs next to Rank Math. Deactivate it: `wp --path=<site> plugin deactivate wordpress-seo`.
     Rank Math is the SEO plugin (D5); it already holds the titles and descriptions.
   - **Site Kit** prints GA4, Tag Manager and AdSense tags before consent. Set `GA4_ID` in
     wp-config first (our loader then sends GA4 after consent, §12.3), then deactivate it:
     `wp --path=<site> plugin deactivate google-site-kit`. Its AdSense tag (Auto ads on every
     page) is already blocked: ads run only on tool pages (D8).
   - **Emo Glossary Pro** (techdella.com) registered the glossary post type without the /glossary/
     hub. Our definition now wins, so it can go: `wp --path=<site> plugin deactivate emo-glossary-pro`.
     The terms stay: they are ordinary posts of our `em-glossary` type.
   - **Hostinger Tools llms.txt** is turned off and its file removed by `wp td setup` (part of the
     sync); /llms.txt is then ours, built from the site's content.

**Rollback** (the old look comes straight back; content is unaffected):
```
wp --path=<live blog folder> theme activate techdella-blog-theme
mv <live blog folder>/wp-content/mu-plugins/techdella-core.php <live blog folder>/wp-content/mu-plugins/techdella-core.php.off
```
