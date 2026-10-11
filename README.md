# TechDella deploy bundle (`hostinger` branch)

Built from the main code by `scripts/build-hostinger.sh`. Don't edit files here: the next build
replaces them. `BUILD.txt` says which commit it was built from.

It holds what the servers need and nothing else: the theme (with its built CSS, JS and images), the
core plugin, the content sources, redirects, PDFs, and the motion videos.

## Never use hPanel's Git deployment for this

hPanel's GitHub/Git deployment writes the repository into the site's root folder and replaced
techdella.com's files on 11 Oct 2026 (the site went down until it was restored from a backup).
Clone the bundle over SSH into your home folder instead, outside every `public_html`.

## First time

1. hPanel → **Advanced → SSH Access** → open the terminal. Make a key for GitHub:
   `ssh-keygen -t ed25519 -f ~/.ssh/techdella_deploy -N ""` then `cat ~/.ssh/techdella_deploy.pub`.
   Add that line in GitHub → repo **Settings → Deploy keys** (read-only).
2. Clone the bundle into your home folder:

   ```bash
   GIT_SSH_COMMAND="ssh -i ~/.ssh/techdella_deploy" git clone --branch hostinger --single-branch \
     git@github.com:Techdella/techdella-website.git ~/techdella-deploy
   ```
3. Install into a site (start with staging):

   ```bash
   bash ~/techdella-deploy/sync.sh <staging site folder> --site=marketing --env=staging --first-run
   ```

   `--site` is `marketing`, `journal` or `studios`; `--env` is `staging` or `production`.
   On a site that isn't marked staging, `sync.sh` asks you to type its domain first.

## See the new page designs on staging

Staging is a copy of the live site, so pages like Home and SEO keep their old content until you
replace it (the old content stays as a revision). On staging only:

```bash
bash ~/techdella-deploy/sync.sh <staging site folder> --refresh-pages
```

## Every update

```bash
cd ~/techdella-deploy && GIT_SSH_COMMAND="ssh -i ~/.ssh/techdella_deploy" git pull
bash ~/techdella-deploy/sync.sh <site folder>
```

## Safe by design

- Uploads and `wp-config.php` are never overwritten. Secrets stay in `wp-config.php`/hPanel.
- Content is imported as drafts; a person publishes it.
- This folder blocks web access (`.htaccess`), so it's safe even inside `public_html`.
