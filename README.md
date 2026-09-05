# microCMS

Lightweight PHP + MySQL CMS for a static-style PHP site: edit catalogs, home copy, settings, and create extra pages without re-uploading the whole site over FTP.

## Setup

1. Place this folder inside the site root (next to `index.php` / `includes/`), or keep it as a sibling repo — paths are auto-detected.
2. Copy `.env.example` to `.env` and fill in your MySQL credentials and initial admin user.
3. Open `/microCMS/admin/` in the browser. On first load it creates the database tables and default pages/settings (content is added from the admin).
4. Make sure the public site includes the microCMS bridge (`includes/bootstrap.php` / helpers) so pages read from MySQL.

The initial admin account is created only when the `users` table is empty (values from `.env`). Change it later from the Account screen in the admin — editing `.env` afterwards does not update an existing user.

Also deploy the `custom_page/` front controller with the site; custom pages are served as `/custom_page/?slug=…`.

## What it manages

- Site settings (contact, social links, tagline, analytics, Medium)
- Home copy (hero, about, signals, skills, contact) — sections stay fixed; only text is editable
- Elements for Portfolio, Books, Writing, Ventures, News, Resumes
- Custom catalog pages (Ventures-style template), shown in the nav before Resumes and in the home “numbers” stats

## Notes

- All runtime content lives in MySQL
- Uploaded media falls back to MySQL storage if the web user cannot write into `assets/media/`
- Keep `.env` out of public repos; the CMS blocks direct web access to it when Apache allows `.htaccess`
