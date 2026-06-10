# LIVVRA

## Apr 2026 — Promo system overhaul + admin tools
- Fixed MySQL `key` reserved-word issue in `Setting.php` → admin Settings save now works on Hostinger (`keyCol()` helper backticks for MySQL, double-quotes for Postgres).
- Promo codes now apply ONCE on the cart subtotal (not per item). Sessioned across cart → checkout → Cashfree → return.
- `cashfree-init.php` sends discounted total + `order_note` + `order_tags` so the breakdown is visible in Cashfree dashboard.
- Promo `used_count` only increments after **successful** payment (online) or order placement (COD). Stored in `$_SESSION['order_promo_pending'][$orderId]` for online flow, applied in `cashfree-return.php` and (idempotently) in `cashfree-notify.php`.
- New `admin/orders-export.php` streams full CSV/Excel export of all orders + line items + promo info.
- Added "Export All Orders" buttons on `admin/orders.php`.
- Generated `PROMO_CODE_GUIDE.pdf` (5-page client guide) via FPDF (`composer require setasign/fpdf`). Regenerate with `php scripts/generate_promo_guide_pdf.php`.

 E-Commerce (livvra.in)

PHP-based e-commerce site originally hosted on Hostinger (MySQL via phpMyAdmin),
now running on Replit using PostgreSQL.

## Tech Stack

- **Language:** PHP 8.2 (built-in dev server)
- **Database:** PostgreSQL (uses `DATABASE_URL`); falls back to MySQL config
  in `includes/database.php` when `DATABASE_URL` is not set (for Hostinger).
- **Frontend assets:** Plain HTML/CSS/JS in `assets/`, product media in `uploads/`
- **Routing (prod):** Apache `.htaccess`
- **Routing (dev on Replit):** `router.php` (PHP built-in server)

## Project Layout

- `index.php`, `products.php`, `product-detail.php`, `cart.php`, `checkout.php` — public pages
- `admin/` — admin panel
- `includes/` — config, database, models, header/footer
- `ajax/` — XHR endpoints
- `assets/` — site CSS/JS/images/videos
- `uploads/` — user/admin uploaded media (products, banners, reels, popups)
- `vendor/` — Composer dependencies (already vendored in repo)
- `database_setup_pg.sql` + `database_setup_extra.sql` — PostgreSQL schema
- `tools/convert_to_webp.sh` — WebP conversion + backup script
- `unused_images_backup/` — original JPG/JPEG/PNG images (after WebP conversion)

## Replit Setup

1. **DATABASE_URL** is set automatically; PostgreSQL schema initialized via:
   ```sh
   psql "$DATABASE_URL" -f database_setup_pg.sql
   psql "$DATABASE_URL" -f database_setup_extra.sql
   ```
2. **Workflow** ("Start application"): `php -S 0.0.0.0:5000 -t . router.php`
3. **Deployment**: Autoscale, same command.

## WebP Image Optimization

All `.jpg`, `.jpeg`, `.png` images under `assets/`, `uploads/`, and `admin/assets/`
were converted to `.webp` (quality 80) at the same paths. Originals were moved
to `unused_images_backup/` preserving the original folder structure.

- Result: ~1.4 GB of high-resolution originals → ~593 MB of WebP files.
- HTML/PHP code references were **not** changed — paths still point to `.jpg`/`.png`.

### How transparent serving works

- **Production (Apache/Hostinger):** `.htaccess` rewrites any `.jpg/.jpeg/.png`
  request to the matching `.webp` if the browser sends `Accept: image/webp`
  AND the `.webp` file exists. Includes a fallback that serves `.webp` even if
  the original file is deleted (so deleting `unused_images_backup/` is safe).
- **Dev (Replit / `php -S`):** `router.php` does the same logic in PHP.

### Re-running the conversion

```sh
tools/convert_to_webp.sh assets uploads admin/assets
```

Idempotent — already-converted files are skipped.

## Hosting Notes

- Deploy code + `.htaccess` + (optionally) the new `.webp` files to Hostinger.
- The `unused_images_backup/` folder can be **deleted** any time after upload —
  the rewrite serves WebP from the same logical paths so no images break.
- Existing WebP files (the ones present in the repo before this conversion)
  are untouched.
