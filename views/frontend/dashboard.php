<?php
/**
 * views/frontend/dashboard.php — Modular Master User Dashboard
 */
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}

$user = $user ?? [
    'id' => $_SESSION['user_id'] ?? 0,
    'full_name' => $_SESSION['user_name'] ?? 'Eleanor Vance',
    'username' => 'eleanor',
    'email' => $_SESSION['user_email'] ?? 'eleanor@lagunavibe.com',
    'phone' => '+1 (555) 234-5678',
    'city' => 'Laguna Beach',
    'address' => '124 Ocean Avenue, Suite 400',
    'dob' => '1995-08-14',
    'gender' => 'Female',
    'role' => 'customer',
    'status' => 'active',
    'created_at' => date('Y-m-d H:i:s', strtotime('-180 days'))
];

$orders = $orders ?? [];
$cart = $cart ?? [];
$addresses = $addresses ?? [];
$reviews = $reviews ?? [];
$pendingCount = $pendingCount ?? 0;
$completedCount = $completedCount ?? 0;
$activeTab = $activeTab ?? ($_GET['tab'] ?? 'home');
?>

<style>
/* ── LVB Customer Dashboard Master Styles ── */
.dashboard-wrapper {
    background: linear-gradient(180deg, #F7FCFD 0%, #FFFFFF 300px);
    min-height: calc(100vh - 65px);
    padding: 30px 20%;
    color: #1E2F3A;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

@media (max-width: 1440px) {
    .dashboard-wrapper { padding: 24px 4%; }
}
@media (max-width: 1024px) {
    .dashboard-wrapper { padding: 16px 12px; }
}

/* Guest Banner */
.guest-banner {
    background: #D6E8F0;
    border: 1px solid #C4DCE6;
    border-radius: 12px;
    padding: 14px 20px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.guest-banner p { font-size: 13px; color: #1E2F3A; margin: 0; font-weight: 500; }

/* Topbar Header & Breadcrumbs */
.dash-topbar {
    background: #FFFFFF;
    border: 1px solid #EEF3F6;
    border-radius: 16px;
    padding: 20px 28px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.breadcrumbs {
    font-size: 12px;
    color: #6D8491;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.breadcrumbs a { color: #1E2F3A; text-decoration: none; font-weight: 500; }
.breadcrumbs span { color: #A0ABB3; }

.topbar-user {
    display: flex;
    align-items: center;
    gap: 16px;
}
.topbar-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #1E2F3A;
    color: #FFFFFF;
    font-family: 'Cinzel', serif;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #D6E8F0;
    text-transform: uppercase;
}

/* Dashboard Grid Layout */
.dashboard-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 28px;
    align-items: start;
}
@media (max-width: 1024px) {
    .dashboard-layout { grid-template-columns: 1fr; }
}

/* Sidebar Styling */
.dash-sidebar {
    background: #FFFFFF;
    border: 1px solid #EEF3F6;
    border-radius: 16px;
    padding: 16px 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    gap: 3px;
}
@media (max-width: 1024px) {
    .dash-sidebar {
        flex-direction: row;
        overflow-x: auto;
        padding: 10px;
        white-space: nowrap;
        border-radius: 12px;
    }
}

.dash-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #5A6D7A;
    text-decoration: none;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    text-align: left;
}
.dash-nav-item svg {
    width: 17px;
    height: 17px;
    stroke: #6D8491;
    stroke-width: 1.7;
    fill: none;
    transition: stroke 0.2s ease;
}
.dash-nav-item:hover {
    background: #F4F8FA;
    color: #1E2F3A;
}
.dash-nav-item:hover svg { stroke: #1E2F3A; }
.dash-nav-item.active {
    background: #D6E8F0;
    color: #14222B;
    font-weight: 600;
}
.dash-nav-item.active svg { stroke: #14222B; }

/* Panels & Cards */
.dash-panel {
    display: none;
    background: #FFFFFF;
    border: 1px solid #EEF3F6;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    animation: fadeIn 0.25s ease-out;
}
.dash-panel.active { display: block; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.panel-title {
    border-bottom: 1px solid #EEF3F6;
    padding-bottom: 18px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.panel-title h2 {
    font-family: 'Cinzel', serif;
    font-size: 22px;
    font-weight: 600;
    color: #1E2F3A;
    margin: 0;
}
.panel-title p { font-size: 13px; color: #6D8491; margin-top: 4px; }

/* KPI Grid Cards */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.kpi-box {
    background: #F8FBFD;
    border: 1px solid #E4EFF4;
    border-radius: 12px;
    padding: 18px;
    transition: transform 0.2s ease;
}
.kpi-box:hover { transform: translateY(-2px); }
.kpi-box-title {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    color: #6D8491;
    margin-bottom: 6px;
}
.kpi-box-num {
    font-family: 'Cinzel', serif;
    font-size: 24px;
    font-weight: 600;
    color: #1E2F3A;
}

/* Forms */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
}
.form-grp { margin-bottom: 20px; }
.form-lbl {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #1E2F3A;
    margin-bottom: 8px;
}
.form-inp {
    width: 100%;
    padding: 11px 14px;
    font-size: 13px;
    color: #1E2F3A;
    background: #FFFFFF;
    border: 1px solid #DCE6ED;
    border-radius: 8px;
    outline: none;
    transition: border-color 0.2s ease;
}
.form-inp:focus {
    border-color: #6D8491;
    box-shadow: 0 0 0 3px rgba(214, 232, 240, 0.4);
}

/* Buttons */
.btn-lvb {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #1E2F3A;
    color: #FFFFFF;
    padding: 11px 24px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-lvb:hover { background: #14222B; }
.btn-lvb-outline {
    background: transparent;
    color: #1E2F3A;
    border: 1px solid #1E2F3A;
}
.btn-lvb-outline:hover {
    background: #D6E8F0;
    border-color: #D6E8F0;
    color: #14222B;
}

/* Badges */
.badge-st {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.badge-del { background: #E6F4EA; color: #137333; }
.badge-ship { background: #E8F0FE; color: #1A73E8; }
.badge-proc { background: #FEF7E0; color: #B06000; }
.badge-can { background: #FCE8E6; color: #C5221F; }

/* Timeline bar */
.order-timeline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 20px 0;
    position: relative;
}
.timeline-step {
    text-align: center;
    font-size: 10px;
    font-weight: 600;
    color: #6D8491;
    letter-spacing: 1px;
    text-transform: uppercase;
    flex: 1;
    position: relative;
    z-index: 2;
}
.timeline-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #D6E8F0;
    border: 2px solid #FFFFFF;
    margin: 0 auto 6px auto;
}
.timeline-step.active .timeline-dot {
    background: #1E2F3A;
}

/* Modals */
.modal-bg {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 25, 35, 0.45);
    backdrop-filter: blur(4px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-bg.active { display: flex; }
.modal-box {
    background: #FFFFFF;
    border-radius: 16px;
    max-width: 520px;
    width: 100%;
    padding: 32px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-close-btn {
    position: absolute;
    top: 20px; right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6D8491;
}

/* Toast Notification */
#dashToast {
    position: fixed;
    bottom: 24px; right: 24px;
    background: #1E2F3A;
    color: #FFFFFF;
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    z-index: 3000;
    display: none;
}
</style>

<div class="dashboard-wrapper">
    <!-- Topbar Header -->
    <?php include __DIR__ . '/dashboard/topbar.php'; ?>

    <!-- Main Dashboard Grid Layout -->
    <div class="dashboard-layout">
        <!-- Sidebar Component -->
        <?php include __DIR__ . '/dashboard/sidebar.php'; ?>

        <!-- Dynamic Content Modules (Modular Files) -->
        <main>
            <?php include __DIR__ . '/dashboard/home.php'; ?>
            <?php include __DIR__ . '/dashboard/profile.php'; ?>
            <?php include __DIR__ . '/dashboard/orders.php'; ?>
            <?php include __DIR__ . '/dashboard/cart.php'; ?>
            <?php include __DIR__ . '/dashboard/addresses.php'; ?>
            <?php include __DIR__ . '/dashboard/payment_methods.php'; ?>
            <?php include __DIR__ . '/dashboard/help.php'; ?>
            <?php include __DIR__ . '/dashboard/settings.php'; ?>
        </main>
    </div>
</div>

<!-- Modal: Add Address -->
<div id="modalAddress" class="modal-bg">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeModal('modalAddress')">&times;</button>
        <h3 style="font-family:'Cinzel', serif; margin-bottom:20px;">Add New Address</h3>
        <form onsubmit="handleAddAddress(event)">
            <div class="form-grp">
                <label class="form-lbl">Title (e.g. Home, Office)</label>
                <input type="text" name="title" class="form-inp" value="Home" required>
            </div>
            <div class="form-grp">
                <label class="form-lbl">Full Name</label>
                <input type="text" name="full_name" class="form-inp" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-grp">
                <label class="form-lbl">Street Address</label>
                <input type="text" name="address" class="form-inp" placeholder="123 Ocean Blvd" required>
            </div>
            <div class="form-row">
                <div class="form-grp">
                    <label class="form-lbl">City</label>
                    <input type="text" name="city" class="form-inp" placeholder="Laguna Beach" required>
                </div>
                <div class="form-grp">
                    <label class="form-lbl">Zip Code</label>
                    <input type="text" name="zip" class="form-inp" placeholder="92651" required>
                </div>
            </div>
            <div class="form-grp">
                <label style="font-size:12px; display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_default" value="1" checked> Set as Primary Shipping Address
                </label>
            </div>
            <button type="submit" class="btn-lvb" style="width:100%;">Save Address</button>
        </form>
    </div>
</div>

<!-- Modal: Product Review -->
<div id="modalReview" class="modal-bg">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeModal('modalReview')">&times;</button>
        <h3 style="font-family:'Cinzel', serif; margin-bottom:16px;">Write a Product Review</h3>
        <form onsubmit="handleReviewSubmit(event)">
            <div class="form-grp">
                <label class="form-lbl">Product Name</label>
                <input type="text" id="reviewProdName" name="product_name" class="form-inp" required placeholder="Product Name">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Rating</label>
                <select name="rating" class="form-inp">
                    <option value="5">★★★★★ (5 Stars - Excellent)</option>
                    <option value="4">★★★★☆ (4 Stars - Very Good)</option>
                    <option value="3">★★★☆☆ (3 Stars - Average)</option>
                    <option value="2">★★☆☆☆ (2 Stars - Poor)</option>
                    <option value="1">★☆☆☆☆ (1 Star - Terrible)</option>
                </select>
            </div>
            <div class="form-grp">
                <label class="form-lbl">Your Review</label>
                <textarea name="review_text" class="form-inp" rows="4" required placeholder="Share details about the fragrance, quality, and your overall experience..."></textarea>
            </div>
            <button type="submit" class="btn-lvb" style="width:100%;">Submit Product Review</button>
        </form>
    </div>
</div>

<!-- Modal: Order Detail Breakdown -->
<div id="modalOrderDetail" class="modal-bg">
    <div class="modal-box" style="max-width:600px;">
        <button class="modal-close-btn" onclick="closeModal('modalOrderDetail')">&times;</button>
        <h3 style="font-family:'Cinzel', serif; margin-bottom:4px;" id="ordModalNum">Order Details</h3>
        <p style="font-size:12px; color:#6D8491; margin-bottom:20px;" id="ordModalSub">Fulfillment Breakdown & Courier Tracking</p>
        <div id="ordModalContent"></div>
    </div>
</div>

<!-- Toast notification element -->
<div id="dashToast">Notification Message</div>

<script>
function openTab(tabId) {
    document.querySelectorAll('.dash-nav-item').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.dash-panel').forEach(panel => panel.classList.remove('active'));

    const btn = document.querySelector(`.dash-nav-item[href*="${tabId}"], .dash-nav-item[onclick*="'${tabId}'"]`);
    const panel = document.getElementById(`tab-${tabId}`);

    if (btn) btn.classList.add('active');
    if (panel) panel.classList.add('active');

    const breadcrumb = document.getElementById('breadcrumb-current');
    if (breadcrumb) breadcrumb.textContent = tabId.replace('-', ' ');

    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.pushState({}, '', url);
}

function showToast(msg) {
    const toast = document.getElementById('dashToast');
    if (!toast) return;
    toast.textContent = msg;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3000);
}

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('active');
}

function openReviewModal(prodName) {
    document.getElementById('reviewProdName').value = prodName;
    openModal('modalReview');
}

function filterDashboardOrders(status, btn) {
    const filters = document.querySelectorAll('.btn-lvb-filter');
    filters.forEach(b => {
        b.classList.remove('active');
        b.classList.add('btn-lvb-outline');
    });
    if (btn) {
        btn.classList.add('active');
        btn.classList.remove('btn-lvb-outline');
    }

    const cards = document.querySelectorAll('.user-order-card');
    cards.forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        if (status === 'all' || cardStatus === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function openOrderModalFromBtn(btn) {
    try {
        const orderData = JSON.parse(btn.getAttribute('data-order'));
        renderOrderDetailsModal(orderData);
    } catch(e) {
        console.error('Failed to parse order data:', e);
    }
}

function renderOrderDetailsModal(o) {
    document.getElementById('ordModalNum').textContent = `Order #${o.order_number}`;
    document.getElementById('ordModalSub').textContent = `Placed on ${o.date} • Total: $${parseFloat(o.total || 0).toFixed(2)}`;

    const st = (o.status || 'processing').toLowerCase();
    const isProc = ['processing', 'shipped', 'delivered'].includes(st);
    const isShip = ['shipped', 'delivered'].includes(st);
    const isDel = st === 'delivered';

    let trackingDesc = 'Your order has been confirmed and our artisans are hand-pouring your candles.';
    if (st === 'shipped') trackingDesc = 'Your order is currently in transit with FedEx Express Priority.';
    if (st === 'delivered') trackingDesc = 'Package has been delivered to your front door / porch.';

    let html = `
        <!-- Live Visual Stepper -->
        <div style="background:#F8FBFD; border:1px solid #D6E8F0; border-radius:12px; padding:18px; margin-bottom:18px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-size:11px; font-weight:700; color:#1E2F3A; text-transform:uppercase; letter-spacing:1px;">Live Fulfillment Status</span>
                <span class="badge-st badge-${st === 'delivered' ? 'del' : (st === 'shipped' ? 'ship' : 'proc')}">${st.toUpperCase()}</span>
            </div>
            <div class="order-timeline" style="margin:14px 0 8px;">
                <div class="timeline-step active"><div class="timeline-dot"></div><span style="font-size:10px;">Confirmed</span></div>
                <div class="timeline-step ${isProc ? 'active' : ''}"><div class="timeline-dot"></div><span style="font-size:10px;">Crafting</span></div>
                <div class="timeline-step ${isShip ? 'active' : ''}"><div class="timeline-dot"></div><span style="font-size:10px;">In Transit</span></div>
                <div class="timeline-step ${isDel ? 'active' : ''}"><div class="timeline-dot"></div><span style="font-size:10px;">Delivered</span></div>
            </div>
            <div style="font-size:12px; color:#475569; margin-top:10px; line-height:1.4;">
                <strong>Tracking Reference:</strong> <span style="font-family:monospace; color:#0f4c5c; font-weight:700;">LVB-TRK-${o.order_number}</span><br>
                <span style="color:#64748b;">${trackingDesc}</span>
            </div>
        </div>

        <!-- Ordered Items -->
        <div style="margin-bottom:18px;">
            <h4 style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#1E2F3A; margin-bottom:10px;">Items in this Order</h4>
            <div style="background:#FFFFFF; border:1px solid #EEF3F6; border-radius:10px; padding:6px 14px;">
    `;

    if (o.items && o.items.length > 0) {
        o.items.forEach((it, idx) => {
            const pName = it.product_name || it.name || 'Luxury Candle';
            const qty = parseInt(it.quantity || it.qty || 1);
            const price = parseFloat(it.price || 0);
            const scent = it.scent || '';
            const border = (idx < o.items.length - 1) ? 'border-bottom:1px solid #F4F8FA;' : '';

            html += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; ${border}">
                    <div>
                        <div style="font-size:13px; font-weight:600; color:#1E2F3A;">${pName}</div>
                        <div style="font-size:11px; color:#6D8491;">
                            Qty: ${qty} ${scent ? ' · Scent: ' + scent : ''}
                        </div>
                    </div>
                    <div style="font-size:13px; font-weight:700; color:#1E2F3A;">$${(price * qty).toFixed(2)}</div>
                </div>
            `;
        });
    } else {
        html += '<p style="font-size:12px; color:#6D8491; padding:8px 0;">Handcrafted Scented Candle (x1)</p>';
    }

    html += `
            </div>
        </div>

        <!-- Shipping & Cost Breakdown -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">
            <div style="background:#F8FBFD; border:1px solid #E2EDF3; border-radius:10px; padding:14px;">
                <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#6D8491; margin-bottom:6px;">Delivery Destination</div>
                <div style="font-size:12.5px; color:#1E2F3A; line-height:1.5;">
                    <strong>${o.name || 'Customer'}</strong><br>
                    ${o.address || ''}<br>
                    ${o.city ? o.city + ', ' : ''}${o.state || ''} ${o.zip || ''}<br>
                    ${o.country || 'US'}
                </div>
            </div>
            <div style="background:#F8FBFD; border:1px solid #E2EDF3; border-radius:10px; padding:14px;">
                <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#6D8491; margin-bottom:6px;">Payment Summary</div>
                <div style="font-size:12px; color:#475569; line-height:1.6;">
                    <div style="display:flex; justify-content:space-between;"><span>Subtotal:</span><span>$${(o.subtotal || o.total).toFixed(2)}</span></div>
                    ${o.discount > 0 ? `<div style="display:flex; justify-content:space-between; color:#059669; font-weight:600;"><span>Promo Discount:</span><span>−$${o.discount.toFixed(2)}</span></div>` : ''}
                    <div style="display:flex; justify-content:space-between;"><span>Shipping:</span><span>${o.shipping === 0 ? '<strong style=\"color:#059669;\">FREE</strong>' : '$' + o.shipping.toFixed(2)}</span></div>
                    <div style="display:flex; justify-content:space-between; border-top:1px solid #D6E8F0; padding-top:4px; margin-top:4px; font-weight:700; color:#1E2F3A; font-size:13px;">
                        <span>Total Paid:</span><span>$${o.total.toFixed(2)}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Footer -->
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="${typeof window.basePath !== 'undefined' ? window.basePath : (window.location.pathname.startsWith('/laguna') ? '/laguna' : '')}/thankyou?order_id=${o.id}&order_number=${encodeURIComponent(o.order_number)}" target="_blank" class="btn-lvb btn-lvb-outline" style="flex:1; font-size:11px; padding:10px; text-decoration:none; text-align:center;">
                <i class="fas fa-receipt"></i> Full Web Receipt
            </a>
            <button onclick="reorder('${o.order_number}')" class="btn-lvb" style="flex:1; font-size:11px; padding:10px; cursor:pointer;">
                <i class="fas fa-redo"></i> Reorder Items
            </button>
        </div>
    `;

    document.getElementById('ordModalContent').innerHTML = html;
    openModal('modalOrderDetail');
}

function openOrderModal(orderNum, date, total, status, itemsJson) {
    const fallbackData = {
        order_number: orderNum,
        date: date,
        total: parseFloat(total || 0),
        status: status,
        items: []
    };
    try { fallbackData.items = JSON.parse(itemsJson); } catch(e){}
    renderOrderDetailsModal(fallbackData);
}

function downloadInvoice(orderNum) {
    showToast(`Downloading tax invoice PDF for Order #${orderNum}...`);
}

function reorder(orderNum) {
    fetch('<?php echo $base; ?>/api/dashboard/reorder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_number: orderNum })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message || 'Items added to your bag!');
    });
}

function previewAvatar(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('avatarImage');
        const initials = document.getElementById('avatarInitials');
        if (img) {
            img.src = e.target.result;
            img.style.display = 'block';
        }
        if (initials) {
            initials.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);

    handleProfileSubmit();
}

function handleProfileSubmit(e) {
    if (e && e.preventDefault) e.preventDefault();
    const formElement = document.getElementById('profileForm');
    if (!formElement) return;
    const formData = new FormData(formElement);

    fetch('<?php echo $base; ?>/api/dashboard/update-profile', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message || 'Profile saved successfully.');
        if (res.success) {
            setTimeout(() => window.location.reload(), 600);
        }
    })
    .catch(err => {
        showToast('Profile updated successfully.');
    });
}

function handleChangePassword(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    fetch('<?php echo $base; ?>/api/dashboard/change-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message);
            e.target.reset();
        } else {
            showToast(res.error || 'Password update failed.');
        }
    });
}

function handleAddAddress(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    fetch('<?php echo $base; ?>/api/dashboard/save-address', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        closeModal('modalAddress');
        showToast(res.message || 'Address saved successfully.');
        setTimeout(() => window.location.reload(), 1000);
    });
}

function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to remove this address?')) return;
    fetch('<?php echo $base; ?>/api/dashboard/delete-address', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ address_id: addressId })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message || 'Address deleted.');
        setTimeout(() => window.location.reload(), 1000);
    });
}

function handleReviewSubmit(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    fetch('<?php echo $base; ?>/api/dashboard/submit-review', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        closeModal('modalReview');
        showToast(res.message || 'Review submitted!');
        setTimeout(() => window.location.reload(), 1000);
    });
}

function handleSupportSubmit(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    fetch('<?php echo $base; ?>/api/dashboard/support', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message || 'Support ticket submitted.');
        e.target.reset();
    });
}
</script>
