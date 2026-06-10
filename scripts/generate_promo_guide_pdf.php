<?php
/**
 * One-shot script:  Generates PROMO_CODE_GUIDE.pdf in the project root.
 *   Usage:   php scripts/generate_promo_guide_pdf.php
 */

require __DIR__ . '/../vendor/autoload.php';

class GuidePDF extends \FPDF
{
    public $brand = 'LIVVRA';
    public $accent = [201, 162, 39]; // gold

    function Header() {
        $this->SetFont('Helvetica', 'B', 22);
        $this->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $this->Cell(0, 12, $this->brand, 0, 1, 'L');
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 6, 'Promo Code System - Owner Guide', 0, 1, 'L');
        $this->Ln(2);
        $this->SetDrawColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
    }
    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, 'LIVVRA - livvra.in   |   Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }
    function H1($t) {
        $this->Ln(2);
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(20, 20, 20);
        $this->Cell(0, 8, $t, 0, 1, 'L');
        $this->SetDrawColor(220, 220, 220);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);
    }
    function H2($t) {
        $this->Ln(1);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $this->Cell(0, 7, $t, 0, 1, 'L');
        $this->SetTextColor(40, 40, 40);
    }
    function P($t) {
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(50, 50, 50);
        $this->MultiCell(0, 5.2, $this->clean($t));
        $this->Ln(1);
    }
    function Bullet($t) {
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(5, 5.2, '');
        $this->Cell(4, 5.2, chr(149));
        $this->MultiCell(0, 5.2, $this->clean($t));
    }
    function Note($t) {
        $this->SetFillColor(255, 247, 219);
        $this->SetDrawColor(229, 200, 100);
        $this->SetTextColor(120, 80, 0);
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(0, 6, ' NOTE', 1, 1, 'L', true);
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(80, 60, 0);
        $this->MultiCell(0, 5, $this->clean($t), 'LRB', 'L', true);
        $this->Ln(2);
        $this->SetTextColor(40, 40, 40);
    }
    function Example($title, $body) {
        $this->SetFillColor(238, 246, 255);
        $this->SetDrawColor(180, 210, 250);
        $this->SetTextColor(20, 60, 120);
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(0, 6, ' ' . $title, 1, 1, 'L', true);
        $this->SetFont('Courier', '', 9);
        $this->SetTextColor(20, 50, 90);
        $this->MultiCell(0, 4.6, $this->clean($body), 'LRB', 'L', true);
        $this->Ln(2);
        $this->SetTextColor(40, 40, 40);
    }
    function clean($t) {
        // FPDF only supports cp1252 - replace common unicode glyphs
        $map = [
            "\xE2\x82\xB9" => 'Rs.',   // ₹
            "\xE2\x86\x92" => '->',    // →
            "\xE2\x80\x94" => '-',     // —
            "\xE2\x80\x93" => '-',     // –
            "\xE2\x80\x99" => "'",     // ’
            "\xE2\x80\x98" => "'",     // ‘
            "\xE2\x80\x9C" => '"',     // “
            "\xE2\x80\x9D" => '"',     // ”
            "\xE2\x9C\x93" => 'OK',    // ✓
            "\xE2\x9D\x8C" => 'X',     // ❌
            "\xF0\x9F\x8E\x89" => '*', // 🎉
            "\xF0\x9F\x9A\xAB" => '!', // 🚫
        ];
        $t = strtr($t, $map);
        return iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $t);
    }
}

$pdf = new GuidePDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 22, 15);
$pdf->AliasNbPages();
$pdf->AddPage();

/* ============ TITLE BLOCK ============ */
$pdf->SetFont('Helvetica', 'B', 18);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell(0, 10, 'Promo Code System - How It Works', 0, 1, 'L');
$pdf->SetFont('Helvetica', 'I', 10);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 6, 'A simple guide for the LIVVRA owner / admin team', 0, 1, 'L');
$pdf->Ln(4);

/* ============ 1. OVERVIEW ============ */
$pdf->H1('1. The Big Picture');
$pdf->P("Promo codes on LIVVRA give your customer a single discount on their entire cart - NOT a separate discount on each product. The code is checked twice: first when the customer applies it on the cart page, and again at checkout, just to be safe. The same discount amount is then sent to the payment gateway (Cashfree), so the customer pays the discounted total - never the full price.");

$pdf->H2('Why a single cart-level discount?');
$pdf->Bullet("It is mathematically the same as discounting each item, but feels much bigger to the customer (one big number instead of many small ones).");
$pdf->Bullet("It avoids rounding loss on combo orders - your earlier issue where each item lost a few rupees per item.");
$pdf->Bullet("It matches industry standard (Amazon, Myntra, Nykaa all show one cart-level discount line).");

/* ============ 2. CREATING A CODE ============ */
$pdf->H1('2. How to Create a Promo Code');
$pdf->P("Login to your admin dashboard and open Promo Codes from the left menu. Click + Add Promo Code and fill in the fields:");

$pdf->Bullet("Code: short text like SAVE10 or DIWALI20. Always uppercase. Customers type this exactly.");
$pdf->Bullet("Discount Type: choose Percentage (e.g. 10%) or Fixed (e.g. flat Rs.500 off).");
$pdf->Bullet("Discount Value: the number - 10 for 10%, or 500 for Rs.500.");
$pdf->Bullet("Max Discount: cap on rupee amount (only useful for percentage codes). Example: 20% off, max Rs.1000 - even on a Rs.10000 cart, customer gets only Rs.1000 off.");
$pdf->Bullet("Min Order Amount: cart subtotal must be at least this much for the code to work.");
$pdf->Bullet("Usage Limit: total times the code can be used across all customers. 0 = unlimited.");
$pdf->Bullet("Expiry Date: code stops working after this date.");
$pdf->Bullet("Influencer Name / Email: optional - for tracking which influencer brought the order.");
$pdf->Bullet("Commission Type & Value: optional - what the influencer earns per order using their code.");
$pdf->Ln(2);

$pdf->Example('EXAMPLE: 10% off campaign code',
    "Code:           DIWALI10\nDiscount Type:  Percentage\nDiscount Value: 10\nMax Discount:   1500\nMin Order:      999\nUsage Limit:    500\nExpiry:         2026-11-15");

$pdf->Example('EXAMPLE: Flat Rs.500 off',
    "Code:           NEWUSER500\nDiscount Type:  Fixed\nDiscount Value: 500\nMin Order:      2500\nUsage Limit:    0   (unlimited)");

/* ============ 3. CUSTOMER FLOW ============ */
$pdf->AddPage();
$pdf->H1('3. The Customer Flow (What the Buyer Sees)');

$pdf->H2('Step 1 - On the Cart page');
$pdf->P("Customer types the code in the Apply promo code box and clicks Apply. The site instantly shows: -Rs.X promo discount, the new total, and a remove link in case they change their mind. The applied promo is now saved in their session - it follows them to checkout automatically.");

$pdf->H2('Step 2 - On the Checkout page');
$pdf->P("The promo box already shows the code as applied. The price summary shows: Subtotal, Shipping, Promo Discount (in green), and Total. If they want to try a different code, they just type a new one and click Apply.");

$pdf->H2('Step 3 - On the Cashfree payment page');
$pdf->P("The amount you see at the top of the Cashfree window is the FINAL total - already after the discount. The order note also lists the breakdown so you can verify it. Customer pays exactly this amount - no surprises.");

$pdf->H2('Step 4 - After payment');
$pdf->P("Once Cashfree confirms success, the system automatically: marks the order paid, increments the code's used count, and writes a usage row (with influencer commission if applicable). For Cash-on-Delivery this happens immediately at order placement.");

/* ============ 4. RULES ============ */
$pdf->H1('4. The Rules in Plain English');
$pdf->Bullet("ONE code per order. Customers cannot stack two codes.");
$pdf->Bullet("Discount is calculated on the SUBTOTAL of products only - shipping is never discounted.");
$pdf->Bullet("Discount can never exceed the subtotal (no negative orders).");
$pdf->Bullet("If a max-discount cap is set, that cap is the hard ceiling.");
$pdf->Bullet("If the cart subtotal is below the minimum, the code is silently rejected with a friendly message.");
$pdf->Bullet("Once usage limit is reached, the code stops working but stays in the system for reporting.");
$pdf->Bullet("Expired or inactive codes do not work.");
$pdf->Bullet("Used count only increments AFTER successful payment (online) or order placement (COD). Failed/abandoned online orders do NOT eat into the usage limit.");

/* ============ 5. SCENARIOS ============ */
$pdf->H1('5. Worked Examples');

$pdf->Example('Scenario A: 10% code on a 3-item combo',
    "Cart:\n  Saree     Rs.2500\n  Kurti     Rs.1800\n  Dupatta   Rs.700\n  Subtotal  Rs.5000\n\nCode SAVE10 (10% off):\n  Discount = 10% of 5000 = Rs.500\n  Shipping = FREE  (>= Rs.999)\n  ----\n  TOTAL PAYABLE = Rs.4500   (customer pays this on Cashfree)");

$pdf->Example('Scenario B: Fixed Rs.500 code, small cart',
    "Cart Subtotal:   Rs.450\nCode FLAT500:    Min order Rs.999\n  --> REJECTED.  Message: \"Minimum order Rs.999 required.\"");

$pdf->Example('Scenario C: Percentage with max cap',
    "Cart Subtotal: Rs.12000\nCode MEGA20    20% off, max Rs.1500\n  20% of 12000 = Rs.2400\n  But cap = Rs.1500\n  --> Discount applied = Rs.1500\n  TOTAL PAYABLE = Rs.10500 + shipping");

/* ============ 6. ADMIN ============ */
$pdf->AddPage();
$pdf->H1('6. Tracking and Reports (Admin Side)');
$pdf->P("Open Promo Codes in admin to see, for each code:");
$pdf->Bullet("How many times it was used.");
$pdf->Bullet("Total discount given (revenue you gave away).");
$pdf->Bullet("Total commission earned by the influencer attached to that code.");
$pdf->Bullet("Each individual order that used the code (with date, customer, amount).");
$pdf->Ln(2);
$pdf->P("On the Orders page you can also export all orders to CSV / Excel - the export includes the promo code used and discount amount for every order, ready for accounting.");

/* ============ 7. TROUBLESHOOTING ============ */
$pdf->H1('7. Quick Troubleshooting');
$pdf->H2('Customer says \"My code is not working\"');
$pdf->Bullet("Check expiry date.");
$pdf->Bullet("Check if usage limit reached.");
$pdf->Bullet("Check if their cart meets the minimum order amount.");
$pdf->Bullet("Make sure the Active toggle is ON.");

$pdf->H2('Customer says \"I was charged the full amount on Cashfree\"');
$pdf->Bullet("This should never happen now - the discounted total is sent to Cashfree, not the original.");
$pdf->Bullet("Open the order in admin - confirm Discount Amount > 0 and Total Paid = Subtotal + Shipping - Discount.");
$pdf->Bullet("Cross-check Cashfree dashboard - the order_note field shows the breakdown.");

$pdf->H2('Used count looks wrong');
$pdf->Note("The used count only increments after a SUCCESSFUL payment. If a customer abandons checkout or the payment fails, the count stays the same - this is by design and is correct behaviour.");

/* ============ 8. SECURITY ============ */
$pdf->H1('8. Safety Built-in');
$pdf->Bullet("The site re-validates the code on the server before charging - customers cannot tamper with the price using browser tools.");
$pdf->Bullet("Cashfree always charges the server-calculated final amount, not anything sent from the browser.");
$pdf->Bullet("All promo activity is logged so any disputes can be checked later.");
$pdf->Bullet("Code lookups are case-insensitive (save10 = SAVE10) so customers don't get blocked by capitals.");

/* ============ FOOTER LINE ============ */
$pdf->Ln(8);
$pdf->SetDrawColor(201, 162, 39);
$pdf->SetLineWidth(0.6);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(4);
$pdf->SetFont('Helvetica', 'I', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(0, 5, "If you ever need a change to how promo codes behave (e.g. allow stacking, free-shipping codes, first-time-buyer-only codes), just share the requirement - the system is designed to be extended easily.\n\n- LIVVRA Tech");

$out = __DIR__ . '/../PROMO_CODE_GUIDE.pdf';
$pdf->Output('F', $out);
echo "PDF generated: $out\n";
echo "Size: " . filesize($out) . " bytes\n";
