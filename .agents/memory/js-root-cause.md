---
name: JS root cause fix
description: main.js and hero-slider.js were emptied; core cart/UI broken
---
main.js and hero-slider.js were EMPTIED (0 bytes) during a previous "speed optimization" run. This broke all cart interactions, toast notifications, promo code toggles, and Swiper sliders sitewide.

**Why:** Someone likely ran a JS minifier/cleaner that truncated the files.

**How to apply:** Never delete or truncate main.js or hero-slider.js. They are loaded globally in includes/footer.php. main.js provides: showToast, addToCart fallback, updateCartCount, togglePromo, applyCartPromo, lazy-load polyfill. hero-slider.js provides Swiper init.

Guard pattern used: `if (!window.addToCart) { window.addToCart = function() {...}; }` so page-level definitions (index.php inline) take priority.
