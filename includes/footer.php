<footer class="livvra-footer bg-white pt-16 pb-0 border-t border-gray-100">
    <style>
        .livvra-footer {
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .footer-brand-text {
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            font-size: 38px;
            letter-spacing: -1.5px;
            text-transform: uppercase;
            line-height: 1;
            color: #000;
        }

        .footer-heading {
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            font-size: 15px;
            color: #000;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .footer-link {
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-link:hover {
            color: #76a33a;
        }

        .footer-address {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 13px;
            line-height: 1.6;
            color: #000;
            margin-bottom: 30px;
        }

        .footer-contact-text {
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            font-size: 20px;
            color: #000;
        }

        .footer-email-text {
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            font-size: 16px;
            color: #000;
        }

        .subscription-input {
            height: 48px;
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 14px;
            width: 100%;
            outline: none;
        }

        .subscription-btn {
            height: 48px;
            width: 52px;
            background: #bcded0;
            border: none;
            border-radius: 0 4px 4px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .subscription-btn:hover {
            background: #a6d1be;
        }

        .social-icon-link {
            color: #000;
            transition: transform 0.2s;
        }

        .social-icon-link:hover {
            transform: scale(1.1);
            color: #76a33a;
        }

        .footer-bottom-link {
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            font-size: 13px;
            color: #000;
            text-decoration: none;
        }

        .copyright-bar {
            background: #1f3e35;
            color: #fff;
            text-align: center;
            font-size: 13px;
            font-family: Arial, Helvetica, sans-serif !important;
            font-weight: normal;
            letter-spacing: 0.5px;
        }
    </style>

    <div class="max-w-[1400px] mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4  text-center md:text-left">
            <!-- Brand Info -->
            <div class="footer-col flex flex-col items-center md:items-start">
               <div class="mb-6">
    <div class="footer-brand-logo">
        <img src="assets/images/logo/logo.png" onerror="this.src='assets/images/logo/logo.svg'" alt="LIVVRA Logo" class="mx-auto md:mx-0">
    </div>
</div>
<style>
    .footer-brand-logo img{
    max-width: 100px;
    height: auto;
    display: block;
}
</style>
                <?php
                $_faddr = '';
                try { if(class_exists('Setting')) $_faddr = Setting::get('footer_address',''); } catch(Exception $e){}
                if (!$_faddr) $_faddr = "DR TRIDOSHA HERBOTECH PRIVATE LIMITED\nCIN: U21003PB2025PTC066121\nGST: 03AAMCD1391C1Z1\n20062 C, Street No. 4, Jujhar Nagar, Bathinda, Punjab – 151001, India";
                ?>
                <div class="footer-address hidden md:block">
                   <?= nl2br(htmlspecialchars($_faddr)) ?>
                </div>
                
                <div class="mb-6 flex items-center gap-4">
                    <img src="assets/images/logo/fssai.jpg" onerror="this.src='assets/images/logo/fssai.svg'" alt="FSSAI Logo" style="height: 35px; width: auto;">
                    <div style="font-size: 11px; color: #000; line-height: 1.2; font-family: Arial, Helvetica, sans-serif; font-weight: bold;">
                        Lic. No.<br>
                        <strong>22126646000350</strong>
                    </div>
                </div>

                <?php
                $_fphone = $_femail = '';
                try { if(class_exists('Setting')) { $_fphone = Setting::get('footer_phone','8958489684'); $_femail = Setting::get('footer_email','livvraindia@gmail.com'); } } catch(Exception $e){}
                if(!$_fphone) $_fphone='8958489684'; if(!$_femail) $_femail='livvraindia@gmail.com';
                ?>
                <div class="flex items-center gap-3 mb-4">
                    <i data-lucide="phone" class="w-5 h-5 text-black"></i>
                    <span class="footer-contact-text"><?= htmlspecialchars($_fphone) ?></span>
                </div>
                <div class="flex items-center gap-3">
                    <i data-lucide="mail" class="w-5 h-5 text-black"></i>
                    <span class="footer-email-text"><?= htmlspecialchars($_femail) ?></span>
                </div>
            </div>

<!-- Shop Links -->
<?php
$_fShopRaw = $_fInfoRaw = '';
try { if(class_exists('Setting')){ $_fShopRaw = Setting::get('footer_shop_links',''); $_fInfoRaw = Setting::get('footer_info_links',''); } } catch(Exception $e){}
$_fShop = $_fShopRaw ? json_decode($_fShopRaw, true) : [
    ['name'=>'SHOP ALL',    'url'=>'/products.php'],
    ['name'=>'MY ACCOUNT',  'url'=>'/account.php'],
    ['name'=>'TRACK ORDER', 'url'=>'/track-order.php'],
    ['name'=>'VIEW CART',   'url'=>'/cart.php'],
];
$_fInfo = $_fInfoRaw ? json_decode($_fInfoRaw, true) : [
    ['name'=>'ABOUT US',           'url'=>'/about.php'],
    ['name'=>'BLOG',               'url'=>'/blog.php'],
    ['name'=>'SHIPPING POLICY',    'url'=>'/shipping-policy.php'],
    ['name'=>'TERMS & CONDITIONS', 'url'=>'/terms-and-conditions.php'],
    ['name'=>'REFUND POLICY',      'url'=>'/refund-cancellation.php'],
    ['name'=>'CONTACT US',         'url'=>'/contact.php'],
];
?>
<div class="footer-col">
    <ul class="space-y-2 md:space-y-5">
        <?php foreach ((array)$_fShop as $_fl): ?>
        <li><a href="<?= htmlspecialchars($_fl['url'] ?? '/') ?>" class="footer-link" style="font-weight:bold;"><?= htmlspecialchars($_fl['name'] ?? '') ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Info Links -->
<div class="footer-col">
    <ul class="space-y-2 md:space-y-5">
        <?php foreach ((array)$_fInfo as $_fl): ?>
        <li><a href="<?= htmlspecialchars($_fl['url'] ?? '/') ?>" class="footer-link" style="font-weight:bold;"><?= htmlspecialchars($_fl['name'] ?? '') ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>

            <!-- Subscription & Social -->
            <div class="footer-col">
                <?php
                $_mailingH = $_followH = $_igUrl = $_fbUrl = $_ytUrl = $_twUrl = '';
                try { if(class_exists('Setting')) {
                    $_mailingH = Setting::get('footer_mailing_heading','JOIN OUR MAILING LIST');
                    $_followH  = Setting::get('footer_follow_heading','FOLLOW US');
                    $_igUrl    = Setting::get('footer_instagram_url','https://www.instagram.com/livvraindia/');
                    $_fbUrl    = Setting::get('footer_facebook_url','https://www.facebook.com/livvra');
                    $_ytUrl    = Setting::get('footer_youtube_url','https://www.youtube.com/@LivvraIndia');
                    $_twUrl    = Setting::get('footer_twitter_url','');
                }} catch(Exception $e){}
                if(!$_mailingH) $_mailingH='JOIN OUR MAILING LIST';
                if(!$_followH)  $_followH='FOLLOW US';
                if(!$_igUrl)    $_igUrl='https://www.instagram.com/livvraindia/';
                if(!$_fbUrl)    $_fbUrl='https://www.facebook.com/livvra';
                if(!$_ytUrl)    $_ytUrl='https://www.youtube.com/@LivvraIndia';
                ?>
                <h3 class="footer-heading"><?= htmlspecialchars($_mailingH) ?></h3>
                <form class="relative mb-12 flex max-w-[320px]">
                    <input type="email" placeholder="Enter Email" class="subscription-input">
                    <button type="submit" class="subscription-btn">
                        <i data-lucide="arrow-right" class="w-6 h-6 text-white"></i>
                    </button>
                </form>

                <h3 class="footer-heading"><?= htmlspecialchars($_followH) ?></h3>
                <div class="flex items-center justify-center md:justify-start gap-6">
                    <?php if($_igUrl): ?><a href="<?= htmlspecialchars($_igUrl) ?>" class="social-icon-link"><img src="/assets/images/social/instagram.svg" class="w-8 h-8" alt="Instagram"></a><?php endif; ?>
                    <?php if($_fbUrl): ?><a href="<?= htmlspecialchars($_fbUrl) ?>" class="social-icon-link"><img src="/assets/images/social/facebook.svg" class="w-8 h-8" alt="Facebook"></a><?php endif; ?>
                    <?php if($_ytUrl): ?><a href="<?= htmlspecialchars($_ytUrl) ?>" class="social-icon-link"><img src="/assets/images/social/youtube.svg" class="w-8 h-8" alt="YouTube"></a><?php endif; ?>
                    <?php if($_twUrl): ?><a href="<?= htmlspecialchars($_twUrl) ?>" class="social-icon-link"><img src="/assets/images/social/twitter.svg" class="w-8 h-8" alt="Twitter/X"></a><?php endif; ?>
                </div>
            </div>
        </div>

       <!-- Partnerships & Payments -->
<div class="grid grid-cols-1 lg:grid-cols-2  border-b border-[#f0f0f0]" 
     style="padding:20px 0;">
    
<div class="text-center md:text-left">
    <p class="text-[15px] font-medium text-black mb-6" style="font-family:Arial,Helvetica,sans-serif;font-weight:bold;">Also available on:</p>
    
    <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 lg:gap-10">
        
        <img src="assets/images/logo/Amazon-Logo-PNG-Pic.png"
             onerror="this.src='assets/images/logo/amazon.svg'"
             alt="Amazon"
             style="height:50px; width:auto;">

        <img src="assets/images/logo/Flipkart-Logo.wine.png"
             onerror="this.src='assets/images/logo/flipkart.svg'"
             alt="Flipkart"
             style="height:50px; width:auto;">

        <img src="assets/images/logo/Zepto_Logo.svg_-1.png"
             onerror="this.src='assets/images/logo/zepto.svg'"
             alt="Zepto"
             style="height:52px; width:auto;">

        <img src="assets/images/logo/1748510129625.SwiggyInstamart.png"
             onerror="this.src='assets/images/logo/instamart.svg'"
             alt="Instamart"
             style="height:50px; width:auto;">
             
    </div>
</div>

    <div class="text-center md:text-left">
        <p class="text-[15px] font-medium text-black mb-6" style="font-family:Arial,Helvetica,sans-serif;font-weight:bold;">We Accept:</p>
        <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 lg:gap-8">
            <img src="/assets/images/social/amazon-pay.svg" alt="Amazon Pay" class="h-4 lg:h-5">
            <span style="font-family:Arial,sans-serif;font-weight:800;font-size:13px;color:#5c2d8e;background:#f0e9f8;padding:3px 8px;border-radius:4px;letter-spacing:1px;">UPI</span>
            <svg height="28" viewBox="0 0 48 30" xmlns="http://www.w3.org/2000/svg"><circle cx="18" cy="15" r="13" fill="#EB001B"/><circle cx="30" cy="15" r="13" fill="#F79E1B"/><path d="M24 5.2a13 13 0 0 1 0 19.6A13 13 0 0 1 24 5.2z" fill="#FF5F00"/></svg>
            
            <span style="font-family:Arial,sans-serif;font-weight:900;font-size:14px;color:#009cde;">Pay<span style="color:#003087;">Pal</span></span>
        </div>
    </div>

</div>
<!-- Footer Bottom Links -->
<div class="footer-bottom-links" style="padding:20px 0;">
    
    <div class="footer-links-row"
         style="display:flex; justify-content:center; align-items:center; gap:20px; flex-wrap:wrap;">

        <a href="privacy-policy.php"
           style="font-size:13px; color:black; text-decoration:none; font-family:Arial,Helvetica,sans-serif; font-weight:bold;">
           Privacy Policy
        </a>

        <a href="terms-conditions.php"
           style="font-size:13px; color:black; text-decoration:none; font-family:Arial,Helvetica,sans-serif; font-weight:bold;">
           Terms & Conditions
        </a>

        <a href="shipping-policy.php"
           style="font-size:13px; color:black; text-decoration:none; font-family:Arial,Helvetica,sans-serif; font-weight:bold;">
           Shipping Policy
        </a>

        <a href="refund-cancellation.php"
           style="font-size:13px; color:black; text-decoration:none; font-family:Arial,Helvetica,sans-serif; font-weight:bold;">
           Refund Policy
        </a>

        <a href="Cancellation-Returns-Refunds-Policy.php"
           style="font-size:13px; color:black; text-decoration:none; font-family:Arial,Helvetica,sans-serif; font-weight:bold;">
           Cancellation Policy
        </a>
        
    </div>
</div>
    </div>
    
    <!-- Copyright Bar -->
    <div class="copyright-bar py-4">
        Livvra is a company of Dr Tridosha Herbotech Private Limited &copy; Copyright 2025 Livvra
    </div>
</footer>

<!-- WhatsApp Floating Button (Both Numbers) -->
<style>
.wa-float-wrap {
    position: fixed;
    bottom: 28px;
    right: 22px;
    z-index: 9990;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}
.wa-main-btn {
    width: 58px;
    height: 58px;
    background: #25D366;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 18px rgba(37,211,102,0.45);
    cursor: pointer;
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    color: #fff;
}
.wa-main-btn:hover { transform: scale(1.08); box-shadow: 0 6px 24px rgba(37,211,102,0.55); }
.wa-main-btn svg { width: 32px; height: 32px; }
.wa-popup {
    display: none;
    flex-direction: column;
    gap: 8px;
    align-items: flex-end;
}
.wa-popup.open { display: flex; }
.wa-chat-option {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    border-radius: 30px;
    padding: 9px 16px 9px 12px;
    box-shadow: 0 3px 14px rgba(0,0,0,0.14);
    text-decoration: none;
    color: #1a1a1a;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s, transform 0.2s;
    white-space: nowrap;
    border: 1px solid #e8f5e9;
}
.wa-chat-option:hover { background: #f1fdf5; transform: translateX(-3px); }
.wa-chat-option .wa-num-label { font-size: 11px; color: #666; font-weight: 400; display: block; line-height: 1.2; }
.wa-chat-option .wa-icon-sm { width: 28px; height: 28px; background: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.wa-chat-option .wa-icon-sm svg { width: 16px; height: 16px; }
</style>

<div class="wa-float-wrap" id="waFloatWrap">
    <div class="wa-popup" id="waPopup">
        <a href="https://wa.me/917087072020?text=Hello%20LIVVRA%2C%20I%20need%20help!" target="_blank" class="wa-chat-option">
            <span class="wa-icon-sm">
                <svg viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.524 5.843L.057 23.428a.5.5 0 0 0 .515.572l5.782-1.516A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.882a9.876 9.876 0 0 1-5.034-1.378l-.361-.214-3.734.979.996-3.643-.235-.374A9.887 9.887 0 0 1 2.118 12C2.118 6.534 6.534 2.118 12 2.118S21.882 6.534 21.882 12 17.466 21.882 12 21.882z"/></svg>
            </span>
            <span>
                +91 70870 72020
                <span class="wa-num-label">Secondary Support</span>
            </span>
        </a>
        <a href="https://wa.me/918958489684?text=Hello%20LIVVRA%2C%20I%20need%20help!" target="_blank" class="wa-chat-option">
            <span class="wa-icon-sm">
                <svg viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.524 5.843L.057 23.428a.5.5 0 0 0 .515.572l5.782-1.516A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.882a9.876 9.876 0 0 1-5.034-1.378l-.361-.214-3.734.979.996-3.643-.235-.374A9.887 9.887 0 0 1 2.118 12C2.118 6.534 6.534 2.118 12 2.118S21.882 6.534 21.882 12 17.466 21.882 12 21.882z"/></svg>
            </span>
            <span>
                +91 89584 89684
                <span class="wa-num-label">Primary Support</span>
            </span>
        </a>
    </div>
    <button class="wa-main-btn" id="waMainBtn" aria-label="Chat on WhatsApp" onclick="toggleWaPopup()">
        <svg viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.524 5.843L.057 23.428a.5.5 0 0 0 .515.572l5.782-1.516A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.882a9.876 9.876 0 0 1-5.034-1.378l-.361-.214-3.734.979.996-3.643-.235-.374A9.887 9.887 0 0 1 2.118 12C2.118 6.534 6.534 2.118 12 2.118S21.882 6.534 21.882 12 17.466 21.882 12 21.882z"/></svg>
    </button>
</div>

<script>
function toggleWaPopup() {
    var popup = document.getElementById('waPopup');
    popup.classList.toggle('open');
}
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('waFloatWrap');
    if (wrap && !wrap.contains(e.target)) {
        var popup = document.getElementById('waPopup');
        if (popup) popup.classList.remove('open');
    }
});
</script>


<script>
// Native lazy load for images that don't already have loading attribute
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll("img:not([loading])").forEach(function(img) {
        img.setAttribute("loading", "lazy");
    });
});
</script>


<!-- Scripts -->
<script src="assets/js/main.js" defer></script>
<script src="assets/js/hero-slider.js" defer></script>
<script>
  // Initialize Lucide icons after all content is ready
  function initLucide() {
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
      document.querySelectorAll('i[data-lucide]').forEach(function(el) {
        el.style.opacity = '1';
      });
    } else {
      setTimeout(initLucide, 100);
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLucide);
  } else {
    initLucide();
  }
</script>

<script> function buyNow(productId) { const form = document.createElement('form'); form.method = 'POST'; form.action = 'cart.php'; const actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = 'action'; actionInput.value = 'add'; form.appendChild(actionInput); const idInput = document.createElement('input'); idInput.type = 'hidden'; idInput.name = 'product_id'; idInput.value = productId; form.appendChild(idInput); const qtyInput = document.createElement('input'); qtyInput.type = 'hidden'; qtyInput.name = 'quantity'; qtyInput.value = 1; form.appendChild(qtyInput); document.body.appendChild(form); form.submit(); } </script>

<?php
// Custom code injection — BODY END
try {
    if (class_exists('Setting')) {
        $_cc_body_end = Setting::get('custom_code_body_end', '');
        if (!empty(trim($_cc_body_end))) echo $_cc_body_end . "\n";
    }
} catch(Exception $e) {}
?>
