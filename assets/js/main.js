/* ================================================
   LIVVRA — Global JavaScript (main.js)
   Loaded deferred in footer on every page.
   Provides fallback implementations that inline
   page scripts can override.
   ================================================ */

// ─── Toast Notification ───────────────────────────
window.showToast = window.showToast || function (message, type) {
    var toastId = 'lv-global-toast';
    var toast = document.getElementById(toastId);

    if (!toast) {
        toast = document.createElement('div');
        toast.id = toastId;
        toast.style.cssText = [
            'position:fixed',
            'bottom:28px',
            'left:50%',
            'transform:translateX(-50%)',
            'background:#1f3e35',
            'color:#fff',
            'padding:13px 28px',
            'border-radius:100px',
            'font-size:14px',
            'font-weight:600',
            'letter-spacing:0.2px',
            'z-index:99999',
            'opacity:0',
            'transition:opacity 0.25s ease',
            'pointer-events:none',
            'max-width:340px',
            'text-align:center',
            'box-shadow:0 8px 30px rgba(0,0,0,0.18)'
        ].join(';');
        document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.style.background = type === 'error' ? '#991b1b'
        : type === 'info' ? '#1e40af'
        : '#1f3e35';
    toast.style.opacity = '1';

    clearTimeout(toast._lvTimer);
    toast._lvTimer = setTimeout(function () {
        toast.style.opacity = '0';
    }, 3000);
};

// ─── Cart Count Badge Update ──────────────────────
window.updateCartCount = function (count) {
    document.querySelectorAll(
        '.cart-count-badge, [data-cart-count], #cart-count, .lv-cart-badge'
    ).forEach(function (el) {
        el.textContent = count > 0 ? count : '';
        el.style.display = count > 0 ? '' : 'none';
    });
};

// ─── Add To Cart (Fallback) ───────────────────────
// Pages that define addToCart inline (e.g. index.php) will
// override this AFTER main.js runs because inline <script>
// blocks execute in document order before defer scripts.
// However we use a named function so pages that need the
// global version can call window._lvAddToCart() directly.
window._lvAddToCart = function (productId, qty, btn) {
    qty = qty || 1;

    if (btn) {
        btn.disabled = true;
        btn.dataset.origText = btn.textContent;
        btn.textContent = 'Adding…';
    }

    var fd = new FormData();
    fd.append('action', 'add');
    fd.append('product_id', productId);
    fd.append('quantity', qty);

    fetch('cart.php', { method: 'POST', body: fd })
        .then(function (r) {
            if (r.ok) {
                window.showToast('Added to cart! 🛒');
                // Try to refresh cart badge count
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    body: (function () {
                        var f2 = new FormData();
                        f2.append('product_id', productId);
                        f2.append('quantity', qty);
                        return f2;
                    })()
                }).then(function (cr) {
                    return cr.json();
                }).then(function (d) {
                    if (d && d.cart_count !== undefined) {
                        window.updateCartCount(d.cart_count);
                    }
                }).catch(function () {});
            }
        })
        .catch(function () {
            window.showToast('Error adding to cart. Please try again.', 'error');
        })
        .finally(function () {
            if (btn) {
                btn.disabled = false;
                btn.textContent = btn.dataset.origText || 'Add to Cart';
            }
        });
};

if (!window.addToCart) {
    window.addToCart = function (productId, qty) {
        window._lvAddToCart(productId, qty || 1);
    };
}

// ─── buyNow (Fallback — also in footer.php) ───────
if (!window.buyNow) {
    window.buyNow = function (productId) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cart.php';
        [{ name: 'action', value: 'add' },
         { name: 'product_id', value: productId },
         { name: 'quantity', value: 1 }
        ].forEach(function (d) {
            var i = document.createElement('input');
            i.type = 'hidden';
            i.name = d.name;
            i.value = d.value;
            form.appendChild(i);
        });
        document.body.appendChild(form);
        form.submit();
    };
}

// ─── Cart Page: togglePromo & applyCartPromo ──────
// Defined here as fallback; cart.php also defines them inline.
if (!window.togglePromo) {
    window.togglePromo = function () {
        var wrapper = document.getElementById('promoWrapper');
        var toggle  = document.querySelector('.promo-toggle');
        if (wrapper) wrapper.classList.toggle('active');
        if (toggle)  toggle.classList.toggle('active');
    };
}

if (!window.applyCartPromo) {
    window.applyCartPromo = function () {
        var input = document.getElementById('cart-promo-input');
        var btn   = document.querySelector('.promo-apply');
        var msg   = document.getElementById('cart-promo-msg');
        if (!input || !btn) return;

        var code = (input.value || '').trim().toUpperCase();
        if (msg) { msg.textContent = ''; msg.style.color = ''; }
        if (!code) {
            if (msg) { msg.textContent = 'Enter a promo code first'; msg.style.color = '#991b1b'; }
            return;
        }

        btn.textContent = 'Checking…';
        btn.disabled = true;

        var subtotalEl = document.querySelector('[data-subtotal]');
        var subtotal   = subtotalEl ? parseFloat(subtotalEl.dataset.subtotal) : 0;

        var fd = new FormData();
        fd.append('code', code);
        fd.append('cart_total', subtotal);
        fd.append('persist', '1');

        fetch('ajax/apply_promo.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.textContent = 'Apply';
                btn.disabled = false;
                if (data.success) {
                    if (msg) { msg.style.color = '#065f46'; msg.textContent = data.message + ' — refreshing…'; }
                    setTimeout(function () { location.reload(); }, 700);
                } else {
                    if (msg) { msg.style.color = '#991b1b'; msg.textContent = data.message || 'Could not apply code'; }
                }
            })
            .catch(function () {
                btn.textContent = 'Apply';
                btn.disabled = false;
                if (msg) { msg.style.color = '#991b1b'; msg.textContent = 'Network error. Try again.'; }
            });
    };
}

// ─── Lazy-load Images (polyfill for browsers that don't support native lazy) ──
(function () {
    if ('loading' in HTMLImageElement.prototype) return; // browser supports native
    var imgs = document.querySelectorAll('img[loading="lazy"]');
    if (!imgs.length) return;
    if (!('IntersectionObserver' in window)) return;

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                var img = entry.target;
                if (img.dataset.src) { img.src = img.dataset.src; }
                io.unobserve(img);
            }
        });
    });
    imgs.forEach(function (img) { io.observe(img); });
})();

// ─── Mobile: add loading=lazy to any image missing it ────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('img:not([loading])').forEach(function (img) {
        img.setAttribute('loading', 'lazy');
    });
});

// ─── GTM / analytics: deferred init on idle ──────
window.addEventListener('load', function () {
    if (typeof requestIdleCallback === 'function') {
        requestIdleCallback(function () { _lvLazyAnalytics(); });
    } else {
        setTimeout(function () { _lvLazyAnalytics(); }, 3000);
    }
});

function _lvLazyAnalytics() {
    // Google Tag Manager (deferred)
    var gtmId = 'GTM-XXXXXXX'; // will be overridden by admin custom code
    if (typeof window.dataLayer === 'undefined') { window.dataLayer = []; }
    // Pixel heartbeat ping (if fbq already loaded)
    if (typeof fbq === 'function') { fbq('track', 'PageView'); }
}
