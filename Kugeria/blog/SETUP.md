# Kūgeria Blog CMS — Deployment Guide

A self-hosted PHP + MySQL blog system for kugeria.co.ke/blog with an admin
backend, media uploads, ad slots, email subscriptions, and an RSS feed.

## 1. Create the database (cPanel)
1. cPanel → **MySQL Databases** → Create database (e.g. `kugeria_blog`)
2. Create a database user with a strong password
3. Add the user to the database with **ALL PRIVILEGES**
4. Note the full database name and username (cPanel prefixes them,
   e.g. `kugeXXXX_blog` / `kugeXXXX_admin`)

## 2. Configure
Open `config.php` and fill in:
- DB_NAME, DB_USER, DB_PASS (DB_HOST stays `localhost` on cPanel)
- SITE_URL / HOME_URL once your domain is live
- FROM_EMAIL — must be an email that exists on your hosting
  (create it in cPanel → Email Accounts first) or messages may go to spam

## 3. Upload
IMPORTANT: this CMS **replaces** the static blog folder.
1. Delete (or rename to `blog-old/`) the current static `blog/` folder
2. Upload this entire `blog-cms/` folder to `public_html/` and rename it `blog/`
3. Ensure the `blog/uploads/` folder exists with permissions **755**

## 4. Install
1. Visit `https://yourdomain.co.ke/blog/install.php`
2. Create your admin username + password (8+ chars)
3. **DELETE install.php from the server** — this is critical
4. Log in at `https://yourdomain.co.ke/blog/admin/login.php`

## 5. Re-post the three existing articles
Open each old static article (from blog-old/), copy the article body HTML,
and paste it into the editor. Set the same titles so the slugs match the
old URLs as closely as possible. Then delete blog-old/.

## 6. Google News & browser feeds — how it actually works
- **RSS feed** is live immediately at `/blog/rss.php`. This is the standard
  that feed readers use, and it powers **Chrome's "Follow" feature**
  (mobile Chrome → ⋮ menu → Follow) which surfaces new posts in users'
  new-tab Following feed.
- **Google News**: go to https://publishercenter.google.com, add your
  publication, verify site ownership (via Search Console), and submit the
  RSS feed. Approval is not automatic — Google looks for consistent
  publishing, clear authorship, and original content. Publish regularly
  for a few weeks before applying.
- **Google Discover** (the phone new-tab feed): no submission exists — it
  picks up indexed articles automatically. The Article structured data on
  each post page is already in place to maximise eligibility.

## 7. Sending limits (important)
The "email subscribers" feature uses PHP mail(), fine for tens of
subscribers. Shared hosting typically caps outgoing mail (often
100–300/hour). When your list grows past a few hundred, move
notifications to a service like Brevo or Mailchimp (export subscribers
as CSV from the admin).

## 8. Selling the ad slots
Three placements exist: hub top banner (1200×200), in-article (720×200),
below-article (1200×200). Empty slots show an "Advertise here" placeholder
linking to your contact page. Book placements in Admin → Ads with start
and end dates; expired ads drop off automatically.

## 9. Multi-author system (guest writers)
Your blog supports invite-only guest authors with public accountability.

**Upgrading an existing installation:** visit `/blog/migrate-authors.php`
once, click Run Upgrade, then DELETE that file. Fresh installs via the
new install.php already include the author tables.

**Inviting an author (you, in Admin → Authors):**
1. Create their account: username, temp password, their real name and
   profession (these two are ALWAYS shown publicly on their posts)
2. Privately send them: `/blog/author/login.php` + credentials
3. They sign in, change their password, and complete their profile

**What authors control (Author Portal → My Profile):**
- Their display name, profession, and LinkedIn URL
- Profile photo upload + a toggle for whether it shows publicly
  (name and profession cannot be hidden — accountability by design)
- Writing, editing, publishing, and deleting THEIR OWN posts only

**What you control (Admin → Authors):**
- **Ban/Unban** — a ban locks the author out on their very next request,
  even mid-session. Their published posts remain live.
- **Remove** — deletes the account; their posts stay published but the
  byline reverts to the default Kūgeria byline.
- Full edit/delete power over every post from the main admin, and the
  ability to attribute any post you paste in to any author via the
  "Attributed author" dropdown in the editor.

**Governance notes:** author publishes do NOT email your subscriber
list — only you can trigger subscriber notifications (Admin editor).
Authors upload media through their own portal; files are tagged with
their author ID for traceability.

## 10. Unsubscribe, dynamic sitemap, and the schedule bridge
- Set **UNSUB_SECRET** in config.php to any random 30+ character string
  BEFORE going live, and never change it afterwards (changing it breaks
  unsubscribe links in already-sent emails).
- Every welcome and new-post email now carries a one-click, token-signed
  unsubscribe link handled by `unsubscribe.php`.
- `sitemap.php` auto-lists the hub and every published post; robots.txt
  references it alongside the static sitemap.xml. Submit BOTH in Google
  Search Console.
- `latest.php` feeds the Schedule page's success screen with your three
  most recent posts. If the blog is unreachable the section simply stays
  hidden — bookings are never affected.

## Security notes
- Passwords hashed (bcrypt), all queries use prepared statements
- Admin forms are CSRF-protected; login has a basic attempt limiter
- uploads/ blocks script execution via .htaccess
- Keep your admin URL private; consider adding cPanel "Directory Privacy"
  on /blog/admin for a second password layer
