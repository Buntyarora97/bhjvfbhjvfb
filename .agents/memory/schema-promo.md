---
name: Schema promo column and missing tables
description: promo_codes.expires_at renamed to expiry_date; 3 tables were missing
---
The promo_codes table was created with column `expires_at` but all PHP code (checkout.php, ajax/apply_promo.php, cashfree-return.php) queries `expiry_date`. Fixed by renaming in PostgreSQL.

Missing tables created via schema patch:
- promo_code_usage (promo_code_id, order_id, user_id, used_at)
- reward_coins (user_id UNIQUE, balance)
- coin_transactions (user_id, amount, transaction_type, description, order_id)

Missing columns also added:
- categories: image, video, icon_upload, show_on_mobile_top_slider, show_on_mobile_concern, show_on_desktop_concern, show_in_top_menu
- products: offer_price, offer_label, description, features, weight_kg, length_cm, width_cm, height_cm, final_price
- orders: payment_id, bank_ref, payment_response, cashfree_order_id, address_line_1, address_line_2, landmark, country, order_note, order_tags
