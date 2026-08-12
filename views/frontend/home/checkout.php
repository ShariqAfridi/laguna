<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
require_once __DIR__ . '/../../../db.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart) && isset($_POST['sync_cart'])) {
    $cart = json_decode($_POST['sync_cart'], true);
    $_SESSION['cart'] = $cart;
}

$totalItems = 0;
$subtotal = 0;
foreach ($cart as $item) {
    $totalItems += $item['qty'];
    $subtotal += $item['price'] * $item['qty'];
}
$shipping = ($subtotal >= 50) ? 0 : 12.00;
$tax = round($subtotal * 0.08, 2); // 8% tax
$total = $subtotal + $shipping + $tax;

// US States list
$us_states = [
    'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
    'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
    'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa',
    'KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland',
    'MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri',
    'MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey',
    'NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio',
    'OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina',
    'SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont',
    'VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming'
];

$countries = [
    'US'=>'United States','CA'=>'Canada','GB'=>'United Kingdom','AU'=>'Australia',
    'DE'=>'Germany','FR'=>'France','NL'=>'Netherlands','SE'=>'Sweden',
    'NO'=>'Norway','DK'=>'Denmark','CH'=>'Switzerland','NZ'=>'New Zealand'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — LVB Atelier</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --ink: #1a1a1a;
    --muted: #6b7280;
    --border: #e5e7eb;
    --bg: #fafaf9;
    --surface: #ffffff;
    --accent: #0f4c5c;
    --accent-light: #e8f2f5;
    --accent-hover: #0a3a46;
    --stripe: #635bff;
    --paypal: #003087;
    --success: #059669;
    --error: #dc2626;
    --radius: 12px;
    --radius-sm: 8px;
    --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 24px rgba(0,0,0,0.08);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    font-size: 14px;
    line-height: 1.6;
}

/* ─── HEADER ─── */
.checkout-header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 18px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: var(--accent);
    text-decoration: none;
    letter-spacing: 0.04em;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--muted);
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.breadcrumb span.active { color: var(--accent); font-weight: 600; }
.breadcrumb i { font-size: 10px; }

/* ─── LAYOUT ─── */
.page-wrapper {
    max-width: 1140px;
    margin: 0 auto;
    padding: 40px 24px 80px;
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 40px;
    align-items: start;
}

/* ─── LEFT COLUMN ─── */
.left-col {}

.section-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 28px;
    margin-bottom: 20px;
    box-shadow: var(--shadow);
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 500;
    color: var(--ink);
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title .step-num {
    width: 26px;
    height: 26px;
    background: var(--accent);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600;
    flex-shrink: 0;
}

/* ─── FORM FIELDS ─── */
.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.field-row.triple {
    grid-template-columns: 2fr 1fr 1fr;
}

.field { margin-bottom: 16px; }
.field:last-child { margin-bottom: 0; }

label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 6px;
}

label .required { color: var(--error); margin-left: 2px; }
label .optional { font-weight: 400; text-transform: none; letter-spacing: 0; color: #9ca3af; margin-left: 4px; font-size: 11px; }

input[type="text"],
input[type="email"],
input[type="tel"],
select,
textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--ink);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    appearance: none;
    -webkit-appearance: none;
}

input:focus,
select:focus,
textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(15,76,92,0.08);
}

input.error, select.error { border-color: var(--error); }

select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}

textarea { resize: vertical; min-height: 80px; }

/* ─── DELIVERY OPTIONS ─── */
.delivery-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 10px;
}

.delivery-option:hover { border-color: var(--accent); background: var(--accent-light); }
.delivery-option.selected { border-color: var(--accent); background: var(--accent-light); }
.delivery-option:last-child { margin-bottom: 0; }

.delivery-option input[type="radio"] { display: none; }

.delivery-radio {
    width: 18px;
    height: 18px;
    border: 2px solid var(--border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}

.delivery-option.selected .delivery-radio {
    border-color: var(--accent);
    background: var(--accent);
}

.delivery-option.selected .delivery-radio::after {
    content: '';
    width: 6px;
    height: 6px;
    background: white;
    border-radius: 50%;
    display: block;
}

.delivery-info { flex: 1; }
.delivery-name { font-weight: 600; font-size: 14px; color: var(--ink); }
.delivery-desc { font-size: 12px; color: var(--muted); margin-top: 2px; }
.delivery-price { font-weight: 600; color: var(--accent); font-size: 14px; }

/* ─── BILLING CHECKBOX ─── */
.checkbox-field {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 14px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    transition: all 0.2s;
}

.checkbox-field:hover { border-color: var(--accent); background: var(--accent-light); }

.checkbox-field input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--accent);
    cursor: pointer;
    flex-shrink: 0;
}

.checkbox-field span {
    font-size: 14px;
    font-weight: 500;
    color: var(--ink);
}

.billing-fields {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    display: none;
}

/* ─── PAYMENT ─── */
.payment-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.payment-tab {
    flex: 1;
    padding: 14px 16px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
    font-size: 13px;
    font-weight: 600;
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    user-select: none;
}

.payment-tab:hover { border-color: var(--accent); color: var(--accent); }

.payment-tab.active-stripe {
    border-color: var(--stripe);
    background: #f5f4ff;
    color: var(--stripe);
}

.payment-tab.active-paypal {
    border-color: #009cde;
    background: #f0f8ff;
    color: var(--paypal);
}

.payment-tab.active-cod {
    border-color: #059669;
    background: #f0fdf4;
    color: #059669;
}

.payment-panel { display: none; }
.payment-panel.active { display: block; }


/* ─── SIMPLIFIED PAYMENT OPTION ─── */
.payment-option {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s;
    background: var(--surface);
}

.payment-option:hover {
    border-color: var(--accent);
    background: var(--accent-light);
}

.payment-option.selected {
    border-color: var(--stripe);
    background: #f5f4ff;
}

.payment-radio-wrapper {
    flex-shrink: 0;
    padding-top: 2px;
}

.payment-radio {
    width: 20px;
    height: 20px;
    border: 2px solid var(--border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.payment-option.selected .payment-radio {
    border-color: var(--stripe);
    background: var(--stripe);
}

.payment-option.selected .payment-radio::after {
    content: '';
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    display: block;
}

.payment-info {
    flex: 1;
}

.payment-name {
    font-weight: 600;
    font-size: 16px;
    color: var(--ink);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
}

.payment-desc {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.5;
    margin-bottom: 10px;
}

.payment-logos {
    display: flex;
    gap: 12px;
    align-items: center;
}

.payment-logos i {
    opacity: 0.7;
    transition: opacity 0.2s;
}

.payment-option:hover .payment-logos i {
    opacity: 1;
}

.card-fields { }

.card-number-wrapper {
    position: relative;
}

.card-number-wrapper input { padding-right: 60px; letter-spacing: 0.08em; }

.card-icons {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 4px;
    opacity: 0.6;
}

.card-icons img { height: 20px; }

.paypal-info {
    padding: 24px;
    background: #f0f8ff;
    border-radius: var(--radius-sm);
    text-align: center;
    border: 1px solid #cce4f7;
}

.paypal-info p { color: var(--muted); font-size: 13px; margin-top: 8px; }
.paypal-logo { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }
.paypal-logo span:first-child { color: #003087; }
.paypal-logo span:last-child { color: #009cde; }

/* ─── RIGHT COLUMN (STICKY) ─── */
.right-col {
    position: sticky;
    top: 24px;
}

.summary-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow-md);
}

.summary-title {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
    color: var(--ink);
}

/* ─── CART ITEMS ─── */
.cart-item {
    display: flex;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}

.cart-item:last-of-type { border-bottom: none; }

.item-img {
    width: 68px;
    height: 68px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    background: #f3f4f6;
    flex-shrink: 0;
    border: 1px solid var(--border);
}

.item-details { flex: 1; min-width: 0; }
.item-name {
    font-weight: 600;
    font-size: 13px;
    color: var(--ink);
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-variant { font-size: 11px; color: var(--muted); margin-bottom: 8px; }

.item-qty-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qty-btn {
    width: 24px;
    height: 24px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: var(--ink);
    transition: all 0.15s;
    line-height: 1;
}

.qty-btn:hover { border-color: var(--accent); color: var(--accent); }
.qty-val { font-size: 13px; font-weight: 600; min-width: 20px; text-align: center; }

.remove-item-btn {
    background: transparent;
    border: none;
    color: #9ca3af;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    margin-left: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 6px;
    border-radius: 4px;
    transition: all 0.2s ease;
}
.remove-item-btn:hover {
    background: #fef2f2;
    color: #dc2626;
}

.item-price {
    font-weight: 700;
    font-size: 14px;
    color: var(--accent);
    text-align: right;
    flex-shrink: 0;
    align-self: center;
}

/* ─── COUPON ─── */
.coupon-row {
    display: flex;
    gap: 8px;
    margin: 16px 0;
}

.coupon-input {
    flex: 1;
    padding: 10px 14px;
    border: 1.5px dashed var(--border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    transition: border-color 0.2s;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.coupon-input:focus { border-color: var(--accent); border-style: solid; }
.coupon-input::placeholder { text-transform: none; letter-spacing: 0; color: #9ca3af; }

.coupon-btn {
    padding: 10px 16px;
    background: var(--accent-light);
    color: var(--accent);
    border: 1.5px solid var(--accent-light);
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    letter-spacing: 0.04em;
    transition: all 0.2s;
    white-space: nowrap;
}

.coupon-btn:hover { background: var(--accent); color: white; }

/* ─── TOTALS ─── */
.totals-section { padding-top: 16px; border-top: 1px solid var(--border); }

.total-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 13px;
}

.total-line .label { color: var(--muted); }
.total-line .value { font-weight: 500; }

.total-line.shipping .value.free { color: var(--success); font-weight: 600; }

.total-line.discount .value { color: var(--success); }

.grand-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 2px solid var(--ink);
    font-size: 18px;
    font-weight: 700;
}

.grand-total .amount { color: var(--accent); font-family: 'Playfair Display', serif; }

/* ─── CTA ─── */
.cta-btn {
    width: 100%;
    margin-top: 20px;
    padding: 16px 24px;
    border: none;
    border-radius: 40px;
    background: var(--accent);
    color: white;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.04em;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.cta-btn:hover { background: var(--accent-hover); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(15,76,92,0.25); }
.cta-btn:active { transform: translateY(0); }
.cta-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.cta-btn.paypal-cta { background: #009cde; }
.cta-btn.paypal-cta:hover { background: #007ab8; }

.cta-btn.cod-cta { background: #059669; }
.cta-btn.cod-cta:hover { background: #047857; }

.trust-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 14px;
    font-size: 11px;
    color: var(--muted);
}

.trust-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.trust-item i { color: var(--success); font-size: 12px; }

.estimated-delivery {
    text-align: center;
    font-size: 12px;
    color: var(--muted);
    margin-top: 12px;
    padding: 10px;
    background: var(--bg);
    border-radius: var(--radius-sm);
}

.estimated-delivery strong { color: var(--ink); }

/* ─── VALIDATION MESSAGES ─── */
.field-error {
    font-size: 11px;
    color: var(--error);
    margin-top: 4px;
    display: none;
}

/* ─── LOADING ─── */
.spinner { animation: spin 0.8s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ─── EMPTY CART ─── */
.empty-cart {
    text-align: center;
    padding: 40px 20px;
    color: var(--muted);
}

.empty-cart a {
    display: inline-block;
    margin-top: 12px;
    color: var(--accent);
    font-weight: 600;
    text-decoration: none;
}

/* ─── FREE SHIPPING PROGRESS ─── */
.shipping-progress {
    margin: 12px 0;
    font-size: 11px;
    color: var(--muted);
}

.shipping-bar {
    height: 4px;
    background: var(--border);
    border-radius: 2px;
    margin-top: 6px;
    overflow: hidden;
}

.shipping-fill {
    height: 100%;
    background: var(--accent);
    border-radius: 2px;
    transition: width 0.4s ease;
}

/* ─── RESPONSIVE ─── */
@media (max-width: 900px) {
    .page-wrapper { grid-template-columns: 1fr; gap: 24px; }
    .right-col { position: static; }
    .right-col { order: -1; }
    .checkout-header { padding: 16px 20px; }
    .page-wrapper { padding: 20px 16px 60px; }
    .field-row { grid-template-columns: 1fr; gap: 0; }
    .field-row.triple { grid-template-columns: 1fr; }
    .payment-tabs { flex-direction: column; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="checkout-header">
    <a href="/" class="logo">LVB Atelier</a>
    <div class="breadcrumb">
        <span>Cart</span>
        <i class="fas fa-chevron-right"></i>
        <span class="active">Checkout</span>
        <i class="fas fa-chevron-right"></i>
        <span>Confirmation</span>
    </div>
</header>

<div class="page-wrapper">

    <!-- ═══════════════ LEFT COLUMN ═══════════════ -->
    <div class="left-col">
    <form id="checkoutForm" method="POST" action="<?php echo $base; ?>/logic/place_order.php" novalidate>
    <input type="hidden" name="place_order" value="1">
    <input type="hidden" name="cart_data" id="cartData" value="">
    <input type="hidden" name="payment_method" id="paymentMethodInput" value="stripe">

    <!-- ── SECTION 1: CONTACT ── -->
    <div class="section-card">
        <h2 class="section-title">
            <span class="step-num">1</span> Contact
        </h2>

        <div class="field-row">
            <div class="field">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                <div class="field-error" id="emailError">Please enter a valid email address.</div>
            </div>
            <div class="field">
                <label for="phone">Phone <span class="optional">(recommended)</span></label>
                <input type="tel" id="phone" name="phone" placeholder="+1 (555) 000-0000" autocomplete="tel">
            </div>
        </div>
    </div>

    <!-- ── SECTION 2: SHIPPING ADDRESS ── -->
    <div class="section-card">
        <h2 class="section-title">
            <span class="step-num">2</span> Shipping Address
        </h2>

        <div class="field-row">
            <div class="field">
                <label for="firstName">First Name <span class="required">*</span></label>
                <input type="text" id="firstName" name="first_name" placeholder="Jane" autocomplete="given-name" required>
            </div>
            <div class="field">
                <label for="lastName">Last Name <span class="required">*</span></label>
                <input type="text" id="lastName" name="last_name" placeholder="Doe" autocomplete="family-name" required>
            </div>
        </div>

        <div class="field">
            <label for="address">Address Line 1 <span class="required">*</span></label>
            <input type="text" id="address" name="address" placeholder="123 Main Street" autocomplete="address-line1" required>
        </div>

        <div class="field">
            <label for="address2">Address Line 2 <span class="optional">(Apt, Suite, etc.)</span></label>
            <input type="text" id="address2" name="apartment" placeholder="Apartment 4B" autocomplete="address-line2">
        </div>

        <div class="field-row triple">
            <div class="field">
                <label for="city">City <span class="required">*</span></label>
                <input type="text" id="city" name="city" placeholder="New York" autocomplete="address-level2" required>
            </div>
            <div class="field">
                <label for="state">State <span class="required">*</span></label>
                <select id="state" name="state" required>
                    <option value="">State</option>
                    <?php foreach ($us_states as $code => $name): ?>
                    <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="zip">ZIP Code <span class="required">*</span></label>
                <input type="text" id="zip" name="zip" placeholder="10001" autocomplete="postal-code" maxlength="10" required>
            </div>
        </div>

        <div class="field">
            <label for="country">Country <span class="required">*</span></label>
            <select id="country" name="country" required>
                <?php foreach ($countries as $code => $name): ?>
                <option value="<?php echo $code; ?>" <?php echo ($code === 'US') ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="notes">Order Notes <span class="optional">(optional)</span></label>
            <textarea id="notes" name="notes" placeholder="Special delivery instructions, gift messages..."></textarea>
        </div>
    </div>

    <!-- ── SECTION 3: DELIVERY ── -->
    <div class="section-card">
        <h2 class="section-title">
            <span class="step-num">3</span> Delivery Options
        </h2>

        <label class="delivery-option selected" id="shippingStandard">
            <input type="radio" name="delivery_type" value="standard" checked>
            <div class="delivery-radio"></div>
            <div class="delivery-info">
                <div class="delivery-name">Standard Shipping</div>
                <div class="delivery-desc">5–8 business days · Tracked</div>
            </div>
            <div class="delivery-price" id="standardShippingPrice"><?php echo ($subtotal >= 50) ? 'FREE' : '$12.00'; ?></div>
        </label>

        <label class="delivery-option" id="shippingExpress">
            <input type="radio" name="delivery_type" value="express">
            <div class="delivery-radio"></div>
            <div class="delivery-info">
                <div class="delivery-name">Express Shipping</div>
                <div class="delivery-desc">2–3 business days · Priority</div>
            </div>
            <div class="delivery-price">$18.00</div>
        </label>
    </div>

    <!-- ── SECTION 4: BILLING ── -->
    <div class="section-card">
        <h2 class="section-title">
            <span class="step-num">4</span> Billing
        </h2>

        <label class="checkbox-field" id="sameBillingLabel">
            <input type="checkbox" id="sameBilling" name="same_billing" checked>
            <span>Billing address is the same as shipping</span>
        </label>

        <div class="billing-fields" id="billingFields">
            <div class="field-row">
                <div class="field">
                    <label for="billingFirst">First Name <span class="required">*</span></label>
                    <input type="text" id="billingFirst" name="billing_first_name" placeholder="Jane">
                </div>
                <div class="field">
                    <label for="billingLast">Last Name <span class="required">*</span></label>
                    <input type="text" id="billingLast" name="billing_last_name" placeholder="Doe">
                </div>
            </div>
            <div class="field">
                <label for="billingAddress">Address <span class="required">*</span></label>
                <input type="text" id="billingAddress" name="billing_address" placeholder="123 Main Street">
            </div>
            <div class="field-row triple">
                <div class="field">
                    <label for="billingCity">City <span class="required">*</span></label>
                    <input type="text" id="billingCity" name="billing_city" placeholder="New York">
                </div>
                <div class="field">
                    <label for="billingState">State</label>
                    <select id="billingState" name="billing_state">
                        <option value="">State</option>
                        <?php foreach ($us_states as $code => $name): ?>
                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="billingZip">ZIP Code <span class="required">*</span></label>
                    <input type="text" id="billingZip" name="billing_zip" placeholder="10001">
                </div>
            </div>
            <div class="field">
                <label for="billingCountry">Country</label>
                <select id="billingCountry" name="billing_country">
                    <?php foreach ($countries as $code => $name): ?>
                    <option value="<?php echo $code; ?>" <?php echo ($code === 'US') ? 'selected' : ''; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- ── SECTION 5: PAYMENT ── -->
  <!-- ── SECTION 5: PAYMENT ── -->
<!-- ── SECTION 5: PAYMENT ── -->
<div class="section-card">
    <h2 class="section-title">
        <span class="step-num">5</span> Payment
    </h2>

    <!-- Stripe Payment Option -->
    <div class="payment-option selected" id="stripePaymentOption">
        <div class="payment-radio-wrapper">
            <div class="payment-radio selected"></div>
        </div>
        <div class="payment-info">
            <div class="payment-name">
                <i class="fab fa-stripe" style="color:#635bff; font-size:18px; margin-right:8px;"></i>
                Credit / Debit Card (Stripe)
            </div>
            <div class="payment-desc">
                Pay securely with any major credit or debit card. You'll be redirected to Stripe's PCI-DSS Level 1 certified payment gateway.
            </div>
            <div class="payment-logos">
                <i class="fab fa-cc-visa" style="font-size:24px; color:#1A1F71;"></i>
                <i class="fab fa-cc-mastercard" style="font-size:24px; color:#F79E1B;"></i>
                <i class="fab fa-cc-amex" style="font-size:24px; color:#006FCF;"></i>
                <i class="fab fa-cc-discover" style="font-size:24px; color:#FF6000;"></i>
            </div>
        </div>
    </div>

    <!-- PCI-DSS Compliance & Security Guarantee -->
    <div class="pci-compliance-box" style="margin-top: 16px; padding: 14px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-shield-check" style="font-size: 24px; color: #16a34a; flex-shrink: 0;"></i>
        <div>
            <div style="font-size: 13px; font-weight: 700; color: #15803d; display: flex; align-items: center; gap: 6px;">
                <span>PCI-DSS Level 1 Certified & 256-Bit SSL Encrypted</span>
            </div>
            <div style="font-size: 11.5px; color: #166534; margin-top: 2px; line-height: 1.4;">
                Your payment data is processed securely through Stripe's PCI-DSS certified gateway. We never store or log your credit card numbers or security (CVV) codes.
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="payment_method" id="paymentMethodInput" value="stripe">

    </form>
    </div>

    <!-- ═══════════════ RIGHT COLUMN (STICKY) ═══════════════ -->
    <div class="right-col">
        <div class="summary-card">
            <h2 class="summary-title">
                Order Summary
                <span style="font-size:13px;font-weight:400;font-family:'DM Sans',sans-serif;color:var(--muted);margin-left:6px;" id="itemCountLabel">
                    (<?php echo $totalItems; ?> <?php echo $totalItems === 1 ? 'item' : 'items'; ?>)
                </span>
            </h2>

            <!-- Cart Items -->
            <div id="cartItemsContainer">
                <?php if (!empty($cart)): ?>
                    <?php foreach ($cart as $index => $item): ?>
                        <div class="cart-item" data-index="<?php echo $index; ?>" data-price="<?php echo floatval($item['price'] ?? 0); ?>">
                            <?php 
                                $imgSrc = !empty($item['image']) ? $item['image'] : '/img/placeholder.jpg';
                                if (strpos($imgSrc, 'http') !== 0 && strpos($imgSrc, $base) !== 0) {
                                    $imgSrc = $base . '/' . ltrim($imgSrc, '/');
                                }
                            ?>
                            <img class="item-img" src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name'] ?? 'Product'); ?></div>
                                <div class="item-variant"><?php echo htmlspecialchars($item['scent'] ?? 'Standard'); ?></div>
                                <div class="item-qty-row">
                                    <button type="button" class="qty-btn" onclick="changeQty(this, -1)">−</button>
                                    <span class="qty-val"><?php echo intval($item['qty'] ?? 1); ?></span>
                                    <button type="button" class="qty-btn" onclick="changeQty(this, 1)">+</button>
                                    <button type="button" class="remove-item-btn" onclick="removeCheckoutItem(this)" title="Remove Product">
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </button>
                                </div>
                            </div>
                            <div class="item-price">$<?php echo number_format(floatval($item['price'] ?? 0) * intval($item['qty'] ?? 1), 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-cart" id="emptyCartMsg">
                        <i class="fas fa-shopping-bag" style="font-size:32px;opacity:0.3;"></i>
                        <p style="margin-top:12px;">Your cart is empty.</p>
                        <a href="/shop.php">Continue Shopping →</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Coupon -->
            <div class="coupon-row">
                <input type="text" class="coupon-input" id="couponCode" placeholder="Promo / Coupon code">
                <button type="button" class="coupon-btn" id="applyCouponBtn" onclick="applyCoupon()">Apply</button>
            </div>

            <!-- Shipping Progress -->
            <div class="shipping-progress" id="shippingProgress" style="<?php echo ($totalItems === 0) ? 'display:none;' : ''; ?>">
                <span id="shippingProgressText">
                    <?php if ($subtotal >= 50): ?>
                        <strong>🎉 Congratulations! You've unlocked FREE Shipping!</strong>
                    <?php else: ?>
                        Add $<?php echo number_format(50 - $subtotal, 2); ?> more for free shipping
                    <?php endif; ?>
                </span>
                <div class="shipping-bar">
                    <div class="shipping-fill" id="shippingFill" style="width:<?php echo min(100, ($subtotal / 50) * 100); ?>%; <?php echo ($subtotal >= 50) ? 'background-color:#059669;' : ''; ?>"></div>
                </div>
            </div>

            <!-- Totals -->
            <div class="totals-section">
                <div class="total-line">
                    <span class="label">Subtotal</span>
                    <span class="value" id="subtotalDisplay">$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="total-line shipping">
                    <span class="label">Shipping</span>
                    <span class="value <?php echo ($shipping == 0) ? 'free' : ''; ?>" id="shippingDisplay">
                        <?php echo ($shipping == 0) ? 'FREE' : '$' . number_format($shipping, 2); ?>
                    </span>
                </div>
                <div class="total-line">
                    <span class="label">Estimated Tax</span>
                    <span class="value" id="taxDisplay">$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="total-line discount" id="discountLine" style="display:none;">
                    <span class="label">Discount</span>
                    <span class="value" id="discountDisplay">−$0.00</span>
                </div>
                <div class="grand-total">
                    <span>Total</span>
                    <span class="amount" id="grandTotalDisplay">$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>

            <!-- CTA Button -->
            <button type="submit" form="checkoutForm" class="cta-btn" id="ctaButton">
                <i class="fas fa-lock"></i>
                Place Secure Order · <span id="ctaAmount">$<?php echo number_format($total, 2); ?></span>
            </button>

            <!-- Trust Signals -->
            <div class="trust-row">
                <div class="trust-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>PCI-DSS Compliant</span>
                </div>
                <div class="trust-item">
                    <i class="fas fa-lock"></i>
                    <span>256-Bit SSL</span>
                </div>
                <div class="trust-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Zero Data Retention</span>
                </div>
            </div>

            <!-- Estimated Delivery -->
            <div class="estimated-delivery" id="estimatedDelivery">
                <i class="fas fa-truck" style="color:var(--accent);"></i>
                Estimated delivery: <strong id="deliveryRange">5–8 business days</strong>
            </div>
        </div>
    </div>

</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
(function() {
    'use strict';

    // ── State ──
    let cart = [];
    let discountAmount = 0;
    let currentPayment = 'stripe';
    let expressShipping = false;

    const STRIPE_KEY = "pk_live_51TPX6YJnMt0K4iLyZzGxBoQv7xqg3I1I8VLSUC8MqqWgKaiCI3WLRjUPSy1O3QSe4jv0rgaGgCBmwOAYHRPxZS7800qAb6ZiTK";
    let stripe = null;
    try { stripe = Stripe(STRIPE_KEY); } catch(e) { console.error('Stripe init failed:', e); }

    // ── Init ──
 function init() {
    loadCart();
    setupDeliveryOptions();
    setupBillingToggle();
    setupFormSubmit();
    // setupCardFormatting(); // REMOVE THIS
    // setupPaymentMethodSync(); // REMOVE THIS
}

    // ── Cart Load ──
    function loadCart() {
        const saved = sessionStorage.getItem('lvb_cart');
        if (saved !== null) {
            try {
                cart = JSON.parse(saved) || [];
                renderCart();
                if (cart.length > 0 && !sessionStorage.getItem('cart_synced')) {
                    sessionStorage.setItem('cart_synced', '1');
                    syncServer(cart);
                }
                return;
            } catch(e) {}
        }
        // Fall through to PHP-rendered items
        const phpCart = <?php echo json_encode(!empty($cart) ? array_values($cart) : []); ?>;
        cart = phpCart || [];
        renderCart();
    }

    function syncServer(items) {
        var baseApiUrl = (typeof window.basePath !== 'undefined') ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '<?php echo $base; ?>');
        fetch(baseApiUrl + '/logic/sync_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart: items })
        }).catch(e => console.warn('Sync failed:', e));
    }

    function broadcastCartChange() {
        try {
            sessionStorage.setItem('lvb_cart', JSON.stringify(cart));
            localStorage.setItem('lvb_cart', JSON.stringify(cart));
        } catch(e){}

        if (window.LVBCart && typeof window.LVBCart.reload === 'function') {
            window.LVBCart.reload();
        } else if (window.LVBCart && typeof window.LVBCart.render === 'function') {
            window.LVBCart.render();
        }

        window.dispatchEvent(new CustomEvent('lvb_cart_updated'));
    }

    // ── Render Cart ──
    function renderCart() {
        const container = document.getElementById('cartItemsContainer');
        if (!cart || cart.length === 0) {
            cart = [];
            container.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-bag" style="font-size:32px;opacity:0.3;"></i>
                    <p style="margin-top:12px;">Your cart is empty.</p>
                    <a href="/shop.php">Continue Shopping →</a>
                </div>`;
            const ctaBtn = document.getElementById('ctaButton');
            if (ctaBtn) ctaBtn.disabled = true;
            updateTotals();
            return;
        }

        container.innerHTML = cart.map((item, idx) => `
            <div class="cart-item" data-index="${idx}" data-price="${parseFloat(item.price)||0}">
                <img class="item-img" src="${esc(item.image || '/img/placeholder.jpg')}" alt="${esc(item.name||'')}">
                <div class="item-details">
                    <div class="item-name">${esc(item.name||'Product')}</div>
                    <div class="item-variant">${esc(item.scent||'Standard')}</div>
                    <div class="item-qty-row">
                        <button type="button" class="qty-btn" onclick="changeQty(this, -1)">−</button>
                        <span class="qty-val">${parseInt(item.qty)||1}</span>
                        <button type="button" class="qty-btn" onclick="changeQty(this, 1)">+</button>
                        <button type="button" class="remove-item-btn" onclick="removeCheckoutItem(this)" title="Remove Product">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="item-price">$${((parseFloat(item.price)||0) * (parseInt(item.qty)||1)).toFixed(2)}</div>
            </div>
        `).join('');

        updateTotals();
    }

    // ── Remove Product from Checkout ──
    window.removeCheckoutItem = function(btn) {
        const itemEl = btn.closest('.cart-item');
        const index = parseInt(itemEl.dataset.index);

        if (!isNaN(index) && cart[index]) {
            cart.splice(index, 1);
        } else {
            const itemImg = itemEl.querySelector('.item-img')?.src || '';
            const itemName = itemEl.querySelector('.item-name')?.textContent || '';
            cart = cart.filter(i => (i.image && itemImg.includes(i.image)) || i.name !== itemName);
        }

        broadcastCartChange();
        syncServer(cart);
        renderCart();
    };

    // ── Qty Change (global so onclick works) ──
    window.changeQty = function(btn, delta) {
        const item = btn.closest('.cart-item');
        const qtyEl = item.querySelector('.qty-val');
        let qty = parseInt(qtyEl.textContent) + delta;

        if (qty <= 0) {
            removeCheckoutItem(btn);
            return;
        }

        qtyEl.textContent = qty;

        // Update cart array
        const index = item.dataset.index;
        if (index !== undefined && cart[index]) {
            cart[index].qty = qty;
        }

        broadcastCartChange();
        syncServer(cart);

        const price = parseFloat(item.dataset.price) || 0;
        item.querySelector('.item-price').textContent = '$' + (price * qty).toFixed(2);
        updateTotals();
    };

    // ── Totals ──
    function updateTotals() {
        let subtotal = 0;
        let count = 0;

        if (cart && cart.length > 0) {
            subtotal = 0;
            count = 0;
            cart.forEach(item => {
                const price = parseFloat(item.price) || 0;
                const qty = parseInt(item.qty) || 1;
                subtotal += price * qty;
                count += qty;
            });
        } else {
            subtotal = 0;
            count = 0;
            document.querySelectorAll('.cart-item').forEach(item => {
                const price = parseFloat(item.dataset.price) || 0;
                const qty = parseInt(item.querySelector('.qty-val')?.textContent) || 1;
                subtotal += price * qty;
                count += qty;
            });
        }

        if (count === 0 || subtotal === 0) {
            discountAmount = 0;
            const discountLine = document.getElementById('discountLine');
            if (discountLine) discountLine.style.display = 'none';
        }

        const baseShipping = (count === 0) ? 0 : (expressShipping ? 18.00 : (subtotal >= 50 ? 0 : 12.00));
        const tax = (count === 0) ? 0 : subtotal * 0.08;
        const total = (count === 0) ? 0 : Math.max(0, subtotal + baseShipping + tax - discountAmount);

        // Update DOM
        const countLabel = document.getElementById('itemCountLabel');
        if (countLabel) countLabel.textContent = `(${count} ${count === 1 ? 'item' : 'items'})`;

        const stdPriceEl = document.getElementById('standardShippingPrice');
        if (stdPriceEl) {
            if (subtotal >= 50) {
                stdPriceEl.innerHTML = '<span style="color:#059669; font-weight:700;">FREE (Waived)</span>';
            } else {
                stdPriceEl.textContent = '$12.00';
            }
        }

        const subtotalEl = document.getElementById('subtotalDisplay');
        if (subtotalEl) subtotalEl.textContent = '$' + subtotal.toFixed(2);

        const taxEl = document.getElementById('taxDisplay');
        if (taxEl) taxEl.textContent = '$' + tax.toFixed(2);
        
        const shippingEl = document.getElementById('shippingDisplay');
        if (shippingEl) {
            if (count === 0) {
                shippingEl.textContent = '$0.00';
                shippingEl.classList.remove('free');
            } else if (baseShipping === 0) {
                shippingEl.textContent = 'FREE';
                shippingEl.classList.add('free');
            } else {
                shippingEl.textContent = '$' + baseShipping.toFixed(2);
                shippingEl.classList.remove('free');
            }
        }

        const grandTotalEl = document.getElementById('grandTotalDisplay');
        if (grandTotalEl) grandTotalEl.textContent = '$' + total.toFixed(2);

        const ctaAmountEl = document.getElementById('ctaAmount');
        if (ctaAmountEl) ctaAmountEl.textContent = '$' + total.toFixed(2);

        const ctaBtn = document.getElementById('ctaButton');
        if (ctaBtn) ctaBtn.disabled = (count === 0);

        // Shipping progress
        const progress = document.getElementById('shippingProgress');
        if (progress) {
            if (count === 0 || expressShipping) {
                progress.style.display = 'none';
            } else {
                progress.style.display = 'block';
                const fillEl = document.getElementById('shippingFill');
                const progText = document.getElementById('shippingProgressText');

                if (subtotal >= 50) {
                    if (fillEl) {
                        fillEl.style.width = '100%';
                        fillEl.style.backgroundColor = '#059669';
                    }
                    if (progText) {
                        progText.innerHTML = '<strong>🎉 Congratulations! You\'ve unlocked FREE Shipping!</strong>';
                    }
                } else {
                    const pct = Math.min(100, (subtotal / 50) * 100);
                    if (fillEl) {
                        fillEl.style.width = pct + '%';
                        fillEl.style.backgroundColor = '';
                    }
                    if (progText) {
                        progText.textContent = 'Add $' + (50 - subtotal).toFixed(2) + ' more for free shipping';
                    }
                }
            }
        }

        // Update cart data hidden input
        const cartInput = document.getElementById('cartData');
        if (cartInput) cartInput.value = JSON.stringify(cart || []);
    }

    // ── Delivery Options ──
    function setupDeliveryOptions() {
        document.querySelectorAll('.delivery-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.querySelectorAll('.delivery-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
                expressShipping = (this.querySelector('input').value === 'express');
                
                const deliveryEl = document.getElementById('deliveryRange');
                if (expressShipping) {
                    deliveryEl.textContent = '2–3 business days';
                } else {
                    deliveryEl.textContent = '5–8 business days';
                }
                updateTotals();
            });
        });
    }

    // ── Billing Toggle ──
    function setupBillingToggle() {
        const checkbox = document.getElementById('sameBilling');
        const billingFields = document.getElementById('billingFields');

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                billingFields.style.display = 'none';
            } else {
                billingFields.style.display = 'block';
            }
        });
    }




    // ── Coupon ──
    window.applyCoupon = function() {
        const code = document.getElementById('couponCode').value.trim().toUpperCase();
        const btn = document.getElementById('applyCouponBtn');
        if (!code) return;

        btn.textContent = '...';
        btn.disabled = true;

        // Demo: hardcoded promo for now
        setTimeout(() => {
            if (code === 'WELCOME10') {
                discountAmount = 10;
                document.getElementById('discountLine').style.display = 'flex';
                document.getElementById('discountDisplay').textContent = '−$10.00';
                updateTotals();
                btn.textContent = '✓ Applied';
                btn.style.background = 'var(--success)';
                btn.style.color = 'white';
            } else {
                btn.textContent = 'Invalid';
                btn.style.background = 'var(--error)';
                btn.style.color = 'white';
                setTimeout(() => {
                    btn.textContent = 'Apply';
                    btn.style.background = '';
                    btn.style.color = '';
                    btn.disabled = false;
                }, 1500);
            }
        }, 600);
    };

    // ── Form Submission ──
    function setupFormSubmit() {
        const form = document.getElementById('checkoutForm');
        const btn = document.getElementById('ctaButton');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!validateForm()) return;

            // Sync cart data
            const savedCart = sessionStorage.getItem('lvb_cart');
            if (savedCart) document.getElementById('cartData').value = savedCart;

            // Ensure full_name is set
            const first = document.getElementById('firstName').value.trim();
            const last = document.getElementById('lastName').value.trim();
            
            // Add hidden full name field
            let fullNameInput = form.querySelector('input[name="full_name"]');
            if (!fullNameInput) {
                fullNameInput = document.createElement('input');
                fullNameInput.type = 'hidden';
                fullNameInput.name = 'full_name';
                form.appendChild(fullNameInput);
            }
            fullNameInput.value = first + ' ' + last;

            if (currentPayment === 'stripe') {
                await handleStripePayment(btn, form);
            } else if (currentPayment === 'cod') {
                // COD — submit directly
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Placing Order...';
                form.submit();
            } else {
                // PayPal — hardcoded redirect for now
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Redirecting to PayPal...';
                form.submit();
            }
        });
    }

    async function handleStripePayment(btn, form) {
        if (!stripe) {
            alert('Stripe is not available. Please try again or use PayPal.');
            return;
        }

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Processing...';

        try {
            const formData = new FormData(form);
            const cart = sessionStorage.getItem('lvb_cart') || document.getElementById('cartData').value;
            formData.set('cart', cart);

            var baseApiUrl = (typeof window.basePath !== 'undefined') ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '');
            const res = await fetch(baseApiUrl + '/api/stripe/create-checkout-session', {
                method: 'POST',
                body: formData
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch(e) { throw new Error('Invalid server response'); }

            if (data.id) {
                await stripe.redirectToCheckout({ sessionId: data.id });
            } else {
                throw new Error(data.error || 'No session created');
            }
        } catch (err) {
            console.error('Stripe error:', err);
            alert('Payment error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }

    // ── Validation ──
    function validateForm() {
        let valid = true;

        const required = ['email', 'firstName', 'lastName', 'address', 'city', 'state', 'zip', 'country'];
        required.forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.value.trim()) {
                el.classList.add('error');
                valid = false;
            } else if (el) {
                el.classList.remove('error');
            }
        });

        const email = document.getElementById('email');
        if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            email.classList.add('error');
            document.getElementById('emailError').style.display = 'block';
            valid = false;
        } else if (email) {
            document.getElementById('emailError').style.display = 'none';
        }

        if (!valid) {
            const firstError = document.querySelector('.error');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return valid;
    }

    // ── Utilities ──
    function esc(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[m]));
    }

    // ── Boot ──
    document.addEventListener('DOMContentLoaded', init);
})();
</script>
</body>
</html>