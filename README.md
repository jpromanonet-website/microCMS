# microCMS

PHP + MySQL admin for the personal site. Drop this folder inside the site root (`website/microCMS` or `W:\jpromanonet\microCMS`), edit `.env`, done.

## Deploy

1. Copy `microCMS/` into the site directory (next to `index.php` and `includes/`).
2. Copy `.env.example` → `.env` and set DB credentials / admin password.
3. Open `/microCMS/admin/` — tables and seed run automatically on first load.
4. Deploy the updated site PHP (`includes/bootstrap.php`, `includes/helpers.php`, `index.php`, catalog sidebars) so the front reads from MySQL.

Default login comes from `.env`: `ADMIN_USER` / `ADMIN_PASS` (only created if `users` is empty).

## Local sibling layout

If this repo sits next to `website/` (not inside it), leave `SITE_ROOT` empty — paths auto-detect `../website`. The site bootstrap also finds `../microCMS/bootstrap.php`.

## What it manages

- Settings: email, phone, socials, tagline, GA, Medium
- Home copy (hero, about, signals, skills, contact) — sections cannot be deleted
- Cards for Portfolio, Books, Writing, Ventures, News, Resumes
- Custom catalog pages (Ventures template) inserted in the navbar before Resumes
