# Launch: staging → live (all three sites)

Run on the server after `ssh -p 65002 u685734478@195.179.236.59`. Always update the bundle first:

```
cd ~/techdella-deploy && GIT_SSH_COMMAND="ssh -i ~/.ssh/techdella_deploy" git pull && head -1 BUILD.txt
```

Order: **Studios → blog → techdella.com.** techdella.com goes last because its `/guide/*`
redirects point at the blog.

`go-live.sh` runs on each **live** folder. It does four things:

1. Sets `TD_SITE`, sets `TD_ENV=production` and removes the staging-only `TD_URL_*` links from wp-config.
2. Shows every staging address stored in the database, then replaces them with the real domain once
   you type yes. It saves a database backup in `~/` first.
3. Opens the site to search engines (`blog_public=1`) and rebuilds caches and the sitemap.
4. Checks that the home page has no `noindex` and no staging address. On Marketing it also checks the
   redirects.

It refuses to run on a folder with `staging` in its path, or on a site whose home URL isn't the
expected domain.

## 0. Before you start (all sites)

- **Backups:** hPanel → each website → Backups → create one for techdella.com and
  blog.techdella.com. Studios lives inside techdella.com's folder, so the same backup covers it.
- **Form endpoints on the live folders.** Copy the `SB_ENDPOINT_*` commands used for staging, with the
  live paths: `~/domains/techdella.com/public_html` and `~/domains/blog.techdella.com/public_html`.
  These only add constants, so the old sites are unaffected.
- **GA4 IDs** for techdella.com and the blog, if you have them:
  `wp --path=<live folder> config set GA4_ID G-… --type=constant`.
- **Test the forms on staging:** send one Studios brief, one MVP calculator and one quote, then check
  they arrived in Startbuddi.

## 1. Studios (in place, no publish step)

```
bash ~/techdella-deploy/go-live.sh ~/domains/techdella.com/public_html/studios --site=studios
```

## 2. Blog

1. hPanel → Websites → blog.techdella.com → WordPress → **Staging** → **Publish** (files and
   database). This replaces the live blog with the reviewed staging copy.
2. Straight after the publish:
   ```
   bash ~/techdella-deploy/go-live.sh ~/domains/blog.techdella.com/public_html --site=journal
   ```
3. Remove the old plugins:
   - `wp --path=~/domains/blog.techdella.com/public_html plugin deactivate wordpress-seo`
   - Once GA4_ID is set, also deactivate `google-site-kit`.

## 3. techdella.com

1. hPanel → Websites → techdella.com → WordPress → **Staging** → **Publish** (files and database).
2. Straight after:
   ```
   bash ~/techdella-deploy/go-live.sh ~/domains/techdella.com/public_html --site=marketing
   ```
3. Check that **studios.techdella.com still loads**: its folder sits inside techdella.com's. If the
   publish removed it, restore `public_html/studios` from the backup.
4. Remove the old plugins:
   - `wp --path=~/domains/techdella.com/public_html plugin deactivate wordpress-seo emo-glossary-pro`
   - Once GA4_ID is set, also deactivate `google-site-kit`.

## 4. After launch (§9.4)

- **Purge the CDN:** hPanel → each website → Performance → CDN → Purge all.
- **Search Console:** submit `https://<site>/sitemap_index.xml` for each property, and use URL
  Inspection on the top 20 pages. The top pages are listed in `docs/GSC-REVIEW-2026-10.md`.
- **Watch for 404s and errors:** check GSC → Pages and the Rank Math 404 monitor daily for 14 days,
  then weekly until day 60.

## Rollback

hPanel → Backups → restore the backup taken in step 0 for that website.
