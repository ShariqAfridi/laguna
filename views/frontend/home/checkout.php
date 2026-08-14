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
$shipping = ($subtotal >= 75) ? 0 : 12.00;
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
<title>Checkout — Laguna Vibe</title>
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
    
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    select,
    textarea {
        font-size: 16px; /* Prevents unwanted iOS auto-zoom */
    }
}

@media (max-width: 480px) {
    .section-card { padding: 20px 16px; }
    .checkout-header { padding: 14px 16px; }
    .breadcrumb { font-size: 11px; gap: 6px; }
    .order-summary { padding: 20px 16px; }
}
/* ── Order Confirmation Popup Modal (Clean Normal Popup) ── */
.order-success-overlay {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: rgba(15, 23, 42, 0.68);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px 16px;
    overflow-y: auto;
    animation: lvbModalFadeIn 0.25s ease forwards;
}

@keyframes lvbModalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes lvbCardPopIn {
    from { opacity: 0; transform: scale(0.95) translateY(12px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.thankyou-card {
    max-width: 820px;
    width: 100%;
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    animation: lvbCardPopIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    margin: auto;
    position: relative;
}

.thankyou-card .card-header {
    background: #0F4C5C;
    padding: 2.2rem 2.5rem 1.8rem;
    text-align: center;
    color: white;
    position: relative;
}

.thankyou-card .modal-close-btn {
    position: absolute;
    top: 16px;
    right: 20px;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #ffffff;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    cursor: pointer;
    transition: all 0.2s ease;
    line-height: 1;
}

.thankyou-card .modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.06);
}

.thankyou-card .card-header i.header-icon {
    font-size: 3.2rem;
    color: #e9c46a;
    margin-bottom: 0.75rem;
    display: inline-block;
}

.thankyou-card .card-header h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 2.3rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    letter-spacing: 0.5px;
    color: #ffffff;
}

.thankyou-card .order-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.28);
    color: #ffffff;
    padding: 0.38rem 1.3rem;
    border-radius: 20px;
    font-size: 0.85rem;
    letter-spacing: 1.5px;
    font-weight: 700;
}

.thankyou-card .card-body {
    padding: 2.2rem 3rem 2rem;
}

.thankyou-card .thankyou-message {
    text-align: center;
    margin-bottom: 1.5rem;
}

.thankyou-card .thankyou-message p:first-child {
    font-size: 1.15rem;
    color: #1E2F3A;
    margin-bottom: 0.35rem;
}

.thankyou-card .thankyou-message p:last-child {
    font-size: 0.92rem;
    color: #6D8491;
    margin: 0;
    line-height: 1.55;
}

.thankyou-card .order-details {
    background: #f8fafc;
    border-radius: 18px;
    padding: 1.4rem 1.8rem;
    margin-bottom: 1.3rem;
    border: 1px solid #e2e8f0;
}

.thankyou-card .detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid #edf2f7;
    font-size: 0.92rem;
}

.thankyou-card .detail-row:last-of-type {
    border-bottom: none;
}

.thankyou-card .detail-label {
    color: #64748b;
    font-weight: 500;
}

.thankyou-card .detail-value {
    color: #1e293b;
    font-weight: 600;
}

.thankyou-card .detail-value.highlight {
    color: #d97706;
    font-size: 1.25rem;
    font-weight: 700;
}

.thankyou-card .items-preview summary {
    cursor: pointer;
    list-style: none;
}

.thankyou-card .items-preview summary::-webkit-details-marker {
    display: none;
}

.thankyou-card .btn-modal-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.85rem 1.8rem;
    border-radius: 40px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9rem;
    background: #1f5446;
    color: white;
    border: none;
    transition: 0.2s;
}

.thankyou-card .btn-modal-primary:hover {
    background: #154237;
    transform: translateY(-1px);
}

.thankyou-card .btn-modal-outline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.85rem 1.8rem;
    border-radius: 40px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9rem;
    background: white;
    border: 1px solid #cbdcd0;
    color: #2d5a47;
    transition: 0.2s;
}

.thankyou-card .btn-modal-outline:hover {
    background: #f5f1ea;
    transform: translateY(-1px);
}

@media(max-width: 768px) {
    .thankyou-card .card-body { padding: 1.5rem 1.4rem 1.6rem; }
    .thankyou-card .card-header { padding: 1.8rem 1.4rem 1.4rem; }
    .thankyou-card .card-header h1 { font-size: 1.85rem; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="checkout-header">
    <a href="/" class="logo">Laguna Vibe</a>
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
    <input type="hidden" name="promo_code" id="promoCodeInput" value="">

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
            <div class="delivery-price" id="standardShippingPrice"><?php echo ($subtotal >= 75) ? '<span style="color:#059669; font-weight:700;">FREE</span>' : '$12.00'; ?></div>
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
<div class="section-card">
    <h2 class="section-title">
        <span class="step-num">5</span> Payment Details
    </h2>

    <!-- Stripe Test / Sandbox Mode Banner -->
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px 16px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#22c55e;"></span>
            <span style="font-size:12.5px; font-weight:600; color:#15803d;">Stripe Test / Sandbox Mode Active</span>
        </div>
        <button type="button" onclick="fillTestCard()" style="background:#15803d; color:white; border:none; border-radius:6px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            ⚡ Auto-Fill Test Card
        </button>
    </div>

    <!-- Embedded Card Fields -->
    <div style="background:#ffffff; border:1.5px solid var(--border); border-radius:10px; padding:20px; box-shadow:0 1px 4px rgba(0,0,0,0.03);">
        <div class="field" style="margin-bottom:16px;">
            <label for="cardNumber">Card Number <span class="required">*</span></label>
            <div style="position:relative;">
                <input type="text" id="cardNumber" name="card_number" placeholder="4242  4242  4242  4242" maxlength="19" autocomplete="cc-number" required style="padding-right:45px; font-family:'Courier New', monospace; font-size:15px; letter-spacing:1.5px; font-weight:600;">
                <span id="cardBrandIcon" style="position:absolute; right:12px; top:11px; font-size:22px; color:#635bff;">
                    <i class="fab fa-cc-visa"></i>
                </span>
            </div>
            <div class="field-error" id="cardNumberError">Please enter a valid 16-digit card number.</div>
        </div>

        <div class="field-row" style="margin-bottom:16px;">
            <div class="field">
                <label for="cardExpiry">Expiration Date <span class="required">*</span></label>
                <input type="text" id="cardExpiry" name="card_expiry" placeholder="MM / YY" maxlength="7" autocomplete="cc-exp" required style="font-family:'Courier New', monospace; font-size:14px; font-weight:600;">
                <div class="field-error" id="cardExpiryError">Enter valid expiration (MM/YY).</div>
            </div>
            <div class="field">
                <label for="cardCvc">Security Code (CVC) <span class="required">*</span></label>
                <input type="text" id="cardCvc" name="card_cvc" placeholder="123" maxlength="4" autocomplete="cc-csc" required style="font-family:'Courier New', monospace; font-size:14px; font-weight:600;">
                <div class="field-error" id="cardCvcError">Enter 3 or 4 digit CVC.</div>
            </div>
        </div>

        <div class="field" style="margin-bottom:0;">
            <label for="cardName">Name on Card <span class="required">*</span></label>
            <input type="text" id="cardName" name="card_name" placeholder="Full Name as shown on card" autocomplete="cc-name" required>
            <div class="field-error" id="cardNameError">Please enter the cardholder's name.</div>
        </div>
    </div>

    <!-- PCI-DSS Compliance & Security Guarantee -->
    <div class="pci-compliance-box" style="margin-top: 16px; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-shield-check" style="font-size: 22px; color: #16a34a; flex-shrink: 0;"></i>
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #15803d; display: flex; align-items: center; gap: 6px;">
                <span>Instant Test Order Simulator · Full Workflow</span>
            </div>
            <div style="font-size: 11px; color: #475569; margin-top: 2px; line-height: 1.4;">
                Submitting creates real orders in the database, generates invoice numbers, updates coupon counts, and shows in Admin Orders.
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="is_mock_payment" id="isMockPayment" value="1">
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
                                <div class="item-variant">
                                    <?php 
                                        $scentName = !empty($item['scent']) ? $item['scent'] : (!empty($item['fragrance_name']) ? $item['fragrance_name'] : 'Standard');
                                        echo '<span style="font-weight:600; color:#0f4c5c;">Scent: ' . htmlspecialchars($scentName) . '</span>';
                                        if (!empty($item['size_name']) && strpos($item['name'] ?? '', $item['size_name']) === false) {
                                            echo ' · <span style="color:#6D8491; font-size:12px;">' . htmlspecialchars($item['size_name']) . '</span>';
                                        }
                                    ?>
                                </div>
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
            <div class="coupon-row" id="couponInputGroup">
                <input type="text" class="coupon-input" id="couponCode" placeholder="Promo / Coupon code" autocomplete="off">
                <button type="button" class="coupon-btn" id="applyCouponBtn" onclick="applyCoupon()">Apply</button>
            </div>
            <div id="couponAppliedBadge" style="display:none; margin: 14px 0; padding: 10px 14px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; font-size: 13px; color: #065f46; justify-content: space-between; align-items: center;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-tag" style="color:#059669;"></i>
                    <span><strong id="appliedCouponCode"></strong> <span id="appliedCouponDesc" style="color:#047857; font-size:12px;"></span></span>
                </div>
                <button type="button" onclick="removeCoupon()" style="background:none; border:none; color:#dc2626; font-weight:600; cursor:pointer; font-size:12px; padding:2px 6px;">Remove</button>
            </div>
            <div id="couponFeedback" style="display:none; font-size:12px; margin-top:-6px; margin-bottom:12px; font-weight:500;"></div>

            <!-- Shipping Progress -->
            <div class="shipping-progress" id="shippingProgress" style="<?php echo ($totalItems === 0) ? 'display:none;' : ''; ?>">
                <span id="shippingProgressText">
                    <?php if ($subtotal >= 75): ?>
                        <strong>🎉 Congratulations! You've unlocked FREE Shipping!</strong>
                    <?php else: ?>
                        Spend <strong>$<?php echo number_format(75 - $subtotal, 2); ?></strong> more to unlock <strong>FREE Shipping</strong>!
                    <?php endif; ?>
                </span>
                <div class="shipping-bar">
                    <div class="shipping-fill" id="shippingFill" style="width:<?php echo min(100, ($subtotal / 75) * 100); ?>%; <?php echo ($subtotal >= 75) ? 'background-color:#059669;' : ''; ?>"></div>
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

<!-- ═══════════════ ORDER CONFIRMATION POPUP MODAL ═══════════════ -->
<div id="orderSuccessModal" class="order-success-overlay" style="display: none;">
    <div class="thankyou-card">
        <div class="card-header">
            <button type="button" class="modal-close-btn" id="closeOrderModal" title="Close" aria-label="Close">&times;</button>
            <i class="fa-regular fa-circle-check header-icon"></i>
            <h1>You're all set!</h1>
            <div class="order-badge">
                ORDER #<span id="succOrderNumber"></span>
            </div>
        </div>

        <div class="card-body">
            <div class="thankyou-message">
                <p><strong>Thank you for shopping with Laguna Vibe.</strong></p>
                <p>We've received your order and it's being prepared with care. A confirmation email has been sent to your inbox.</p>
            </div>

            <!-- ORDER DETAILS FROM DATABASE -->
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Order total</span>
                    <span class="detail-value highlight" id="succTotal">$0.00</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment method</span>
                    <span class="detail-value" id="succPayment">Credit Card (Stripe)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Shipping to</span>
                    <span class="detail-value" id="succAddress" style="text-align: right; max-width: 60%; word-break: break-word;"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estimated delivery</span>
                    <span class="detail-value" id="succDelivery">3–5 business days ✨</span>
                </div>
                
                <!-- Show ordered items -->
                <details class="items-preview" open style="margin-top: 10px; border-top: 1px dashed #e2e8f0; padding-top: 10px;">
                    <summary style="font-size: 0.84rem; font-weight: 700; color: #0F4C5C; cursor: pointer; display: flex; align-items: center; gap: 6px; user-select: none;">
                        <i class="fa-regular fa-receipt"></i> Ordered Items (<span id="succItemCount">1</span>)
                    </summary>
                    <div class="items-list" id="succItemsList" style="margin-top: 8px; max-height: 180px; overflow-y: auto;"></div>
                </details>
            </div>

            <div class="pci-notice" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.8rem; padding:0.7rem 1rem; display:flex; align-items:center; gap:0.7rem; margin:1rem 0; font-size:0.78rem; color:#166534; line-height: 1.4;">
                <i class="fa-solid fa-shield-check" style="font-size:1.2rem; color:#16a34a; flex-shrink: 0;"></i>
                <span><strong>PCI-DSS Compliant Transaction</strong> — Processed via Stripe Level 1 PCI Gateway. Card details are fully encrypted and zero card data is retained on our servers.</span>
            </div>

            <div class="email-sent" style="background:#ecf6ef; border-radius:1rem; padding:0.75rem 1rem; display:flex; align-items:center; gap:0.7rem; margin:1rem 0; font-size:0.8rem; color:#2b5e3b; line-height: 1.4;">
                <i class="fa-regular fa-envelope-open" style="font-size:1.2rem; flex-shrink: 0;"></i>
                <span><strong>Instant confirmation</strong> — We've emailed your order details & receipt.<br><small style="color: #4b7a5a;">(Customer & admin copies sent to <span id="succEmail"></span>)</small></span>
            </div>

            <div class="action-buttons" style="display:flex; flex-wrap:wrap; gap:0.8rem; justify-content:center; margin:1.5rem 0 1rem;">
                <a href="<?php echo $base; ?>/shop" class="btn-modal-primary">
                    <i class="fa-solid fa-shop"></i> Continue shopping
                </a>
                <a href="<?php echo $base; ?>/contact" class="btn-modal-outline">
                    <i class="fa-regular fa-message"></i> Support
                </a>
            </div>

            <div class="support-links" style="text-align:center; font-size:0.72rem; color:#6f7e72; border-top:1px solid #ede3d6; padding-top:1.2rem; margin-top:0.5rem; line-height: 1.5;">
                Need help? Email <a href="mailto:support@lagunavibe.com" style="color:#3a6b58; text-decoration:none; font-weight:500;">support@lagunavibe.com</a> or call +1 (888) 420-1965<br>
                <span style="font-size: 0.7rem;">A portion of every order supports sustainable artisans. © Laguna Vibe <?php echo date('Y'); ?> | ethical fragrance</span>
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

        container.innerHTML = cart.map((item, idx) => {
            const scentName = item.scent || item.fragrance_name || 'Standard Scent';
            const sizeDetail = (item.size_name && (!item.name || !item.name.includes(item.size_name))) ? ` · <span style="color:#6D8491; font-size:12px;">${esc(item.size_name)}</span>` : '';
            return `
            <div class="cart-item" data-index="${idx}" data-price="${parseFloat(item.price)||0}">
                <img class="item-img" src="${esc(item.image || '/img/placeholder.jpg')}" alt="${esc(item.name||'')}">
                <div class="item-details">
                    <div class="item-name">${esc(item.name||'Product')}</div>
                    <div class="item-variant"><span style="font-weight:600; color:#0f4c5c;">Scent: ${esc(scentName)}</span>${sizeDetail}</div>
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
            </div>`;
        }).join('');

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

        const baseShipping = (count === 0) ? 0 : (expressShipping ? 18.00 : (subtotal >= 75 ? 0 : 12.00));
        const tax = (count === 0) ? 0 : subtotal * 0.08;
        const total = (count === 0) ? 0 : Math.max(0, subtotal + baseShipping + tax - discountAmount);

        // Update DOM
        const countLabel = document.getElementById('itemCountLabel');
        if (countLabel) countLabel.textContent = `(${count} ${count === 1 ? 'item' : 'items'})`;

        const stdPriceEl = document.getElementById('standardShippingPrice');
        if (stdPriceEl) {
            if (subtotal >= 75) {
                stdPriceEl.innerHTML = '<span style="color:#059669; font-weight:700;">FREE</span>';
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

                if (subtotal >= 75) {
                    if (fillEl) {
                        fillEl.style.width = '100%';
                        fillEl.style.backgroundColor = '#059669';
                    }
                    if (progText) {
                        progText.innerHTML = '<strong>🎉 Congratulations! You\'ve unlocked FREE Shipping!</strong>';
                    }
                } else {
                    const pct = Math.min(100, (subtotal / 75) * 100);
                    if (fillEl) {
                        fillEl.style.width = pct + '%';
                        fillEl.style.backgroundColor = '';
                    }
                    if (progText) {
                        const remaining = (75 - subtotal).toFixed(2);
                        progText.innerHTML = 'Spend <strong>$' + remaining + '</strong> more to unlock <strong>FREE Shipping</strong>!';
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




    // ── Coupon Logic ──
    let appliedCouponCode = '';

    window.applyCoupon = async function(manualCode = null) {
        const input = document.getElementById('couponCode');
        const code = (manualCode || (input ? input.value : '')).trim().toUpperCase();
        const btn = document.getElementById('applyCouponBtn');
        const feedback = document.getElementById('couponFeedback');
        const badge = document.getElementById('couponAppliedBadge');
        const inputGroup = document.getElementById('couponInputGroup');
        const promoInput = document.getElementById('promoCodeInput');

        if (!code) {
            if (feedback) {
                feedback.style.display = 'block';
                feedback.style.color = 'var(--error)';
                feedback.textContent = 'Please enter a coupon code.';
            }
            return;
        }

        if (btn) {
            btn.textContent = 'Checking...';
            btn.disabled = true;
        }
        if (feedback) feedback.style.display = 'none';

        // Calculate current subtotal
        let currentSubtotal = 0;
        if (Array.isArray(cart)) {
            cart.forEach(item => {
                currentSubtotal += (parseFloat(item.price) || 0) * (parseInt(item.qty) || 1);
            });
        }
        if (currentSubtotal <= 0) {
            currentSubtotal = subtotal || 0;
        }

        try {
            var baseApiUrl = (typeof window.basePath !== 'undefined') ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '');
            const res = await fetch(baseApiUrl + '/api/validate-coupon', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    code: code,
                    subtotal: currentSubtotal
                })
            });

            const data = await res.json();

            if (data.valid) {
                appliedCouponCode = data.code;
                discountAmount = parseFloat(data.discount || 0);

                if (promoInput) promoInput.value = appliedCouponCode;

                const discountLine = document.getElementById('discountLine');
                const discountDisplay = document.getElementById('discountDisplay');
                if (discountLine) discountLine.style.display = 'flex';
                if (discountDisplay) discountDisplay.textContent = '−$' + discountAmount.toFixed(2);
                
                if (badge) {
                    badge.style.display = 'flex';
                    const codeEl = document.getElementById('appliedCouponCode');
                    const descEl = document.getElementById('appliedCouponDesc');
                    if (codeEl) codeEl.textContent = data.code;
                    if (descEl) descEl.textContent = `— ${data.type === 'percentage' ? data.value + '%' : '$' + data.value} Off`;
                }
                if (inputGroup) inputGroup.style.display = 'none';

                updateTotals();

                if (btn) {
                    btn.textContent = '✓ Applied';
                    btn.style.background = 'var(--success)';
                    btn.style.color = 'white';
                }
            } else {
                appliedCouponCode = '';
                discountAmount = 0;
                if (promoInput) promoInput.value = '';
                const discountLine = document.getElementById('discountLine');
                if (discountLine) discountLine.style.display = 'none';
                updateTotals();

                if (btn) {
                    btn.textContent = 'Invalid';
                    btn.style.background = 'var(--error)';
                    btn.style.color = 'white';
                }
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.style.color = 'var(--error)';
                    feedback.textContent = data.message || 'Invalid coupon code.';
                }
                setTimeout(() => {
                    if (btn) {
                        btn.textContent = 'Apply';
                        btn.style.background = '';
                        btn.style.color = '';
                        btn.disabled = false;
                    }
                }, 2200);
            }
        } catch (e) {
            console.error('Coupon validation error:', e);
            if (btn) {
                btn.textContent = 'Error';
                btn.disabled = false;
            }
        }
    };

    window.removeCoupon = function() {
        appliedCouponCode = '';
        discountAmount = 0;
        const promoInput = document.getElementById('promoCodeInput');
        if (promoInput) promoInput.value = '';

        const badge = document.getElementById('couponAppliedBadge');
        if (badge) badge.style.display = 'none';

        const inputGroup = document.getElementById('couponInputGroup');
        if (inputGroup) inputGroup.style.display = 'flex';

        const input = document.getElementById('couponCode');
        if (input) input.value = '';

        const btn = document.getElementById('applyCouponBtn');
        if (btn) {
            btn.textContent = 'Apply';
            btn.style.background = '';
            btn.style.color = '';
            btn.disabled = false;
        }

        const feedback = document.getElementById('couponFeedback');
        if (feedback) feedback.style.display = 'none';

        const discountLine = document.getElementById('discountLine');
        if (discountLine) discountLine.style.display = 'none';
        
        updateTotals();
    };

    // Auto-apply promo from URL param if present (e.g. ?promo=WELCOME20 or ?coupon=WELCOME20)
    const urlParams = new URLSearchParams(window.location.search);
    const queryPromo = urlParams.get('promo') || urlParams.get('coupon') || urlParams.get('code');
    if (queryPromo) {
        setTimeout(() => {
            const input = document.getElementById('couponCode');
            if (input) input.value = queryPromo.toUpperCase();
            applyCoupon(queryPromo.toUpperCase());
        }, 500);
    }

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
            } else {
                // COD / other payment method — process asynchronously
                await submitOrderAsync(btn, form);
            }
        });
    }

    // ── Test Card Fill Helper ──
    window.fillTestCard = function() {
        const num = document.getElementById('cardNumber');
        const exp = document.getElementById('cardExpiry');
        const cvc = document.getElementById('cardCvc');
        const name = document.getElementById('cardName');
        if (num) num.value = '4242  4242  4242  4242';
        if (exp) exp.value = '12 / 28';
        if (cvc) cvc.value = '123';
        if (name) {
            const fName = document.getElementById('firstName')?.value || 'Jane';
            const lName = document.getElementById('lastName')?.value || 'Doe';
            name.value = `${fName} ${lName}`.trim() || 'Jane Doe';
        }
        ['cardNumber', 'cardExpiry', 'cardCvc', 'cardName'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('error');
            const err = document.getElementById(id + 'Error');
            if (err) err.style.display = 'none';
        });
        const icon = document.getElementById('cardBrandIcon');
        if (icon) icon.innerHTML = '<i class="fab fa-cc-visa" style="color:#1A1F71;"></i>';
    };

    function setupCardFormatters() {
        const num = document.getElementById('cardNumber');
        const exp = document.getElementById('cardExpiry');
        const cvc = document.getElementById('cardCvc');
        const icon = document.getElementById('cardBrandIcon');

        if (num) {
            num.addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '').substring(0, 16);
                let formatted = v.match(/.{1,4}/g)?.join('  ') || v;
                e.target.value = formatted;

                if (icon) {
                    if (v.startsWith('4')) {
                        icon.innerHTML = '<i class="fab fa-cc-visa" style="color:#1A1F71;"></i>';
                    } else if (v.startsWith('5')) {
                        icon.innerHTML = '<i class="fab fa-cc-mastercard" style="color:#EB001B;"></i>';
                    } else if (v.startsWith('3')) {
                        icon.innerHTML = '<i class="fab fa-cc-amex" style="color:#007CC3;"></i>';
                    } else if (v.startsWith('6')) {
                        icon.innerHTML = '<i class="fab fa-cc-discover" style="color:#FF6000;"></i>';
                    } else {
                        icon.innerHTML = '<i class="fas fa-credit-card" style="color:#cbd5e1;"></i>';
                    }
                }
            });
        }

        if (exp) {
            exp.addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '').substring(0, 4);
                if (v.length >= 2) {
                    e.target.value = v.substring(0, 2) + ' / ' + v.substring(2);
                } else {
                    e.target.value = v;
                }
            });
        }

        if (cvc) {
            cvc.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
            });
        }
    }

    async function handleStripePayment(btn, form) {
        const cardNum = document.getElementById('cardNumber');
        const cardExp = document.getElementById('cardExpiry');
        const cardCvc = document.getElementById('cardCvc');
        const cardName = document.getElementById('cardName');

        let cardValid = true;
        const cleanNum = cardNum ? cardNum.value.replace(/\D/g, '') : '';
        if (cleanNum.length < 14) {
            if (cardNum) cardNum.classList.add('error');
            const err = document.getElementById('cardNumberError');
            if (err) err.style.display = 'block';
            cardValid = false;
        } else {
            if (cardNum) cardNum.classList.remove('error');
            const err = document.getElementById('cardNumberError');
            if (err) err.style.display = 'none';
        }

        if (cardExp && cardExp.value.trim().length < 5) {
            cardExp.classList.add('error');
            const err = document.getElementById('cardExpiryError');
            if (err) err.style.display = 'block';
            cardValid = false;
        } else if (cardExp) {
            cardExp.classList.remove('error');
            const err = document.getElementById('cardExpiryError');
            if (err) err.style.display = 'none';
        }

        if (cardCvc && cardCvc.value.trim().length < 3) {
            cardCvc.classList.add('error');
            const err = document.getElementById('cardCvcError');
            if (err) err.style.display = 'block';
            cardValid = false;
        } else if (cardCvc) {
            cardCvc.classList.remove('error');
            const err = document.getElementById('cardCvcError');
            if (err) err.style.display = 'none';
        }

        if (cardName && !cardName.value.trim()) {
            cardName.classList.add('error');
            const err = document.getElementById('cardNameError');
            if (err) err.style.display = 'block';
            cardValid = false;
        } else if (cardName) {
            cardName.classList.remove('error');
            const err = document.getElementById('cardNameError');
            if (err) err.style.display = 'none';
        }

        if (!cardValid) {
            const firstErr = document.querySelector('.section-card .error');
            if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Processing Payment...';

        // Simulate seamless payment verification and submit order asynchronously
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-check"></i> Payment Approved! Placing Order...';
            btn.style.background = 'var(--success)';
            submitOrderAsync(btn, form);
        }, 800);
    }

    // ── Async Order Submission ──
    async function submitOrderAsync(btn, form) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner spinner"></i> Confirming Order...';

        const formData = new FormData(form);
        formData.set('is_ajax', '1');
        formData.set('place_order', '1');

        const savedCart = sessionStorage.getItem('lvb_cart');
        if (savedCart) {
            formData.set('cart_data', savedCart);
        } else if (Array.isArray(cart)) {
            formData.set('cart_data', JSON.stringify(cart));
        }

        try {
            var baseApiUrl = (typeof window.basePath !== 'undefined') ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '<?php echo $base; ?>');
            const placeOrderUrl = form.action || (baseApiUrl + '/logic/place_order.php');
            const res = await fetch(placeOrderUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await res.json();

            if (data && data.success) {
                // 1. Clear cart
                try {
                    sessionStorage.removeItem('lvb_cart');
                    sessionStorage.removeItem('cart_synced');
                    localStorage.removeItem('lvb_cart');
                } catch(e) {}

                if (window.LVBCart && typeof window.LVBCart.clear === 'function') {
                    window.LVBCart.clear();
                }
                cart = [];
                renderCart();

                // 2. Display order confirmation modal popup
                displayOrderSuccessModal(data);

                // 3. Update CTA button
                btn.innerHTML = '<i class="fas fa-check"></i> Order Confirmed!';
                btn.style.background = 'var(--success)';
                btn.disabled = true;
            } else {
                alert((data && data.error) ? data.error : 'Failed to place order. Please check your details and try again.');
                btn.disabled = false;
                btn.innerHTML = 'Place Order →';
                btn.style.background = '';
            }
        } catch (err) {
            console.error('Order submission error:', err);
            form.submit(); // fallback
        }
    }

    // ── Render Order Confirmation Modal ──
    function displayOrderSuccessModal(order) {
        const modal = document.getElementById('orderSuccessModal');
        if (!modal) return;

        // Order Number
        const ordNumEl = document.getElementById('succOrderNumber');
        if (ordNumEl) ordNumEl.textContent = order.order_number || '';

        // Order Total
        const totalEl = document.getElementById('succTotal');
        if (totalEl) totalEl.textContent = '$' + parseFloat(order.total || 0).toFixed(2);

        // Payment Method Display
        const paymentEl = document.getElementById('succPayment');
        if (paymentEl) paymentEl.textContent = order.payment_display || 'Credit Card (Stripe)';

        // Address Display
        const addressEl = document.getElementById('succAddress');
        if (addressEl) addressEl.textContent = order.full_address || order.address || 'Standard Delivery Address';

        // Delivery Estimate
        const deliveryEl = document.getElementById('succDelivery');
        if (deliveryEl) deliveryEl.textContent = order.delivery_est || '3–5 business days ✨';

        // Email Sent confirmation
        const emailEl = document.getElementById('succEmail');
        if (emailEl) emailEl.textContent = order.email || 'your email';

        // Itemized ordered items
        const itemsListEl = document.getElementById('succItemsList');
        const itemCountEl = document.getElementById('succItemCount');
        if (itemsListEl && Array.isArray(order.items)) {
            if (itemCountEl) itemCountEl.textContent = order.items.length;
            itemsListEl.innerHTML = order.items.map(item => `
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; padding-bottom:8px; border-bottom:1px dashed #e2e8f0; font-size:13px;">
                    <div>
                        <strong style="color:#1E2F3A;">${esc(item.product_name)}</strong>
                        ${item.scent ? `<br><span style="color:#0F4C5C; font-weight:600; font-size:12px;">Scent: ${esc(item.scent)}</span>` : ''}
                        <span style="color:#64748b; font-size:12px;"> × ${item.quantity}</span>
                    </div>
                    <div style="font-weight:700; color:#1E2F3A;">$${parseFloat(item.subtotal || (item.price * item.quantity)).toFixed(2)}</div>
                </div>
            `).join('');
        }

        // Show Modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        window.scrollTo({ top: 0, behavior: 'smooth' });
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

    // ── Modal Close Listeners ──
    function setupModalListeners() {
        const closeBtn = document.getElementById('closeOrderModal');
        const modal = document.getElementById('orderSuccessModal');
        if (closeBtn && modal) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        }
    }

    // ── Boot ──
    document.addEventListener('DOMContentLoaded', function() {
        init();
        setupCardFormatters();
        setupModalListeners();
    });
})();
</script>
</body>
</html>