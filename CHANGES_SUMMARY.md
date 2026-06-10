# LIVVRA – WebP Migration & Replit Setup Summary

## 1. Replit Environment

| Item | Value |
| --- | --- |
| Runtime | PHP built-in server, port 5000 |
| Workflow | `Start application` → `php -S 0.0.0.0:5000 -t . router.php` |
| Database | Replit-managed PostgreSQL (auto `DATABASE_URL`) |
| Deployment | Autoscale, same command |
| Fallback DB | If `DATABASE_URL` is missing, `includes/database.php` falls back to the original Hostinger MySQL credentials, so the same code base runs locally **and** on Hostinger with no edits. |

## 2. PostgreSQL Schema

Applied to the Replit Postgres database:

* `database_setup_pg.sql` – core tables (products, categories, hero, orders, etc.)
* `database_setup_extra.sql` – `users`, `contact_inquiries`, `product_images`, `reviews`, `stories`, `promo_codes`, `video_popups`

Three demo products were seeded so the site renders locally for testing. Your real Hostinger MySQL data is untouched.

## 3. WebP Conversion (Bulk)

| Metric | Value |
| --- | --- |
| Source extensions | `.jpg`, `.jpeg`, `.png` |
| Files converted | **599** |
| Live folder size before | 1.9 GB |
| Live folder size after | 593 MB |
| Originals moved to | `unused_images_backup/` (1.4 GB, kept as backup) |
| Quality | 80 (visually lossless) |
| Tool | `tools/convert_to_webp.sh` (ImageMagick `magick`, parallel via `xargs`, idempotent – safe to re-run) |

Every original kept its filename, just the extension changed (`hero.jpg` → `hero.webp`). All file paths in the database, HTML, and CSS continue to work because of the rewrite layer below.

## 4. Transparent WebP Serving

Both environments serve `.webp` automatically when the browser requests the original `.jpg/.jpeg/.png`, without changing any URL.

* **Replit (PHP built-in server)** – `router.php`
  * Detects `Accept: image/webp`
  * URL-decodes the path so files with spaces / parentheses (e.g. `Metabolic Balance (4).png`) work
  * Streams the matching `.webp` with `Content-Type: image/webp` and `Vary: Accept`
  * Falls through to the requested file otherwise
* **Hostinger (Apache)** – `.htaccess`
  * Mirror rules using `mod_rewrite`
  * Adds `Vary: Accept` and the correct MIME type
  * Same behaviour as Replit, fully transparent

## 5. Auto-Convert New Uploads

New helper: `includes/image_upload.php`

* `saveUploadedMedia($file, $dir, $base)` – single entry point used by every admin upload form.
* `convertToWebpFile(...)` – uses PHP **GD** (already enabled on Hostinger), preserves transparency for PNG, supports JPG/PNG/GIF/WEBP.
* Videos pass through unchanged.
* Falls back to the original file gracefully if conversion fails.

### Admin pages patched to use it

| File | Uploads handled |
| --- | --- |
| `admin/product-add.php` | main image, video, gallery |
| `admin/product-edit.php` | main image, video, gallery |
| `admin/products.php` | main image, video, gallery |
| `admin/hero.php` | hero image (+ video pass-through) |
| `admin/categories.php` | category image, icon (+ video pass-through) |
| `admin/category-edit.php` | category image, icon (+ video pass-through) |
| `admin/ajax/admin_actions.php` | hero, category, product, gallery, story image uploads |

Result: from now on, **anything an admin uploads through the panel is stored as `.webp`** with the same naming pattern as before.

Untouched (video-only endpoints, no images):
* `admin/reels.php`
* `admin/ajax/popup_actions.php`

## 6. Junk / Unused Files Moved

Moved out of the live tree into `unused_images_backup/junk/`:

* `instamojo_debug.log`
* `unused_log.txt`
* `assets/images/decorative.zip`

Originals of the 599 converted images live in `unused_images_backup/` (root of that folder). Nothing was permanently deleted – everything can be restored with a simple `mv`.

## 7. Files You'll Want to Re-deploy to Hostinger

When you push back to Hostinger, upload these (besides the converted `.webp` files):

* `.htaccess` (WebP rewrite + Vary header)
* `router.php` (only used by Replit dev, harmless on Hostinger)
* `includes/image_upload.php`
* `includes/database.php` (DATABASE_URL fallback already in place)
* All patched files in `admin/` listed above
* `tools/convert_to_webp.sh` (handy for any future bulk runs)

## 8. Verification

* Homepage hero image (`Yeast-Based Protein`) renders correctly via WebP.
* All 26 homepage images returned `Content-Type: image/webp`.
* `php -l` syntax check passes on every edited file.
* No URLs were rewritten in templates or DB – everything works through the transparent WebP layer.

---

**TL;DR** – Site lives on Replit + Postgres, every old image is now WebP under the same path, every new upload is auto-converted, and the same code runs unchanged on Hostinger thanks to `.htaccess`.
