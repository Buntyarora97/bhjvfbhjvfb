/* ================================================
   LIVVRA — Hero / Swiper Slider Init (hero-slider.js)
   Loaded deferred in footer on every page.
   Initialises any Swiper instances found on the page.
   ================================================ */

(function () {
    'use strict';

    function initSwipers() {
        if (typeof Swiper === 'undefined') return;

        // ── Hero / Banner Swiper ──────────────────────
        var heroEl = document.querySelector('.hero-swiper, .swiper-hero, [data-swiper="hero"]');
        if (heroEl && !heroEl.swiper) {
            var heroSlides = heroEl.querySelectorAll('.swiper-slide').length;
            new Swiper(heroEl, {
                loop: heroSlides > 1,
                autoplay: heroSlides > 1 ? { delay: 4500, disableOnInteraction: false } : false,
                speed: 700,
                effect: 'fade',
                fadeEffect: { crossFade: true },
                pagination: {
                    el: heroEl.querySelector('.swiper-pagination') || '.swiper-pagination',
                    clickable: true
                },
                navigation: {
                    nextEl: heroEl.querySelector('.swiper-button-next') || '.swiper-button-next',
                    prevEl: heroEl.querySelector('.swiper-button-prev') || '.swiper-button-prev'
                }
            });
        }

        // ── Product / Review Swiper ───────────────────
        document.querySelectorAll('.product-swiper, .review-swiper, [data-swiper="products"]').forEach(function (el) {
            if (!el.swiper) {
                new Swiper(el, {
                    loop: false,
                    slidesPerView: 1.2,
                    spaceBetween: 16,
                    autoplay: { delay: 3500, disableOnInteraction: true },
                    breakpoints: {
                        480:  { slidesPerView: 2.1, spaceBetween: 16 },
                        768:  { slidesPerView: 3,   spaceBetween: 20 },
                        1024: { slidesPerView: 4,   spaceBetween: 24 }
                    },
                    pagination: { el: '.swiper-pagination', clickable: true },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev'
                    }
                });
            }
        });

        // ── Testimonial / Google Review Swiper ────────
        document.querySelectorAll('.testimonial-swiper, [data-swiper="testimonials"]').forEach(function (el) {
            if (!el.swiper) {
                var slides = el.querySelectorAll('.swiper-slide').length;
                var perView = window.innerWidth >= 1024 ? 3 : window.innerWidth >= 640 ? 2 : 1;
                new Swiper(el, {
                    loop: slides > perView,
                    autoplay: slides > 1 ? { delay: 4000, disableOnInteraction: false } : false,
                    slidesPerView: 1,
                    spaceBetween: 20,
                    breakpoints: {
                        640:  { slidesPerView: 2, spaceBetween: 20 },
                        1024: { slidesPerView: 3, spaceBetween: 24 }
                    },
                    pagination: { el: '.swiper-pagination', clickable: true }
                });
            }
        });
    }

    // Run on DOM ready (covers defer loading)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSwipers);
    } else {
        initSwipers();
    }

    // Also try after full page load in case Swiper loaded late
    window.addEventListener('load', function () {
        initSwipers();
    });

    // Expose globally so pages can call it after dynamic content load
    window.lvInitSwipers = initSwipers;
})();
