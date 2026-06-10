---
name: AOS defer timing
description: AOS.init() must be wrapped in DOMContentLoaded when aos.min.js is defer
---
cart.php and products.php loaded aos.min.js with `defer` then immediately called AOS.init() in the next inline script block. Since defer runs after HTML parsing, the inline script ran first, causing "AOS is not defined".

**Fix:** Wrap AOS.init() in `document.addEventListener('DOMContentLoaded', function() { if (typeof AOS !== 'undefined') AOS.init(...); });`

**How to apply:** Any page that loads AOS via defer must guard the init call with DOMContentLoaded.
