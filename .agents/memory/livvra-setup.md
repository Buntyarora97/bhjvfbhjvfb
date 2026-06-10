---
name: Livvra PHP e-commerce setup
description: Key facts about the Livvra Ayurvedic e-commerce PHP app running on Replit with PostgreSQL.
---

# Livvra Setup Notes

## Stack
- PHP 8.2 built-in server (`php -S 0.0.0.0:5000 -t . router.php`)
- PostgreSQL via Replit's managed DB (DATABASE_URL secret)
- Composer dependency: `setasign/fpdf` (PDF generation)
- Frontend: Tailwind CSS, Alpine.js, Swiper, AOS

## Database
- Uses `DATABASE_URL` env var (Replit PostgreSQL)
- Schema spread across `database_setup_pg.sql` + `database_setup_extra.sql`
- Extra columns needed beyond those SQL files (added during migration):
  - `google_reviews.display_order`, `google_reviews.reviewer_img`
  - `faqs.display_order`
  - `home_banners.desktop_image`, `home_banners.mobile_image`, `home_banners.link_url`
  - `blogs.publish_date`, `blogs.meta_title`, `blogs.meta_description`, `blogs.tags`
  - `orders.user_id`, `orders.razorpay_order_id`, `orders.razorpay_payment_id`, `orders.transaction_id`, `orders.shiprocket_*`, `orders.tracking_id`
  - `products.ingredients`, `products.usage_instructions`, `products.meta_*`, `products.tags`, `products.weight`, `products.dimensions`, `products.is_new`

## Secrets / Config
- All payment keys (Razorpay, PayU, Cashfree, Instamojo, Shiprocket) moved to env secrets in `includes/config.php`
- `SITE_URL` dynamically uses `REPLIT_DEV_DOMAIN` when available
- Auth: custom session-based (no external provider)

**Why:** Hardcoded credentials were present in config.php — moved to getenv() calls for security.
