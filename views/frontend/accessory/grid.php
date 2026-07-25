<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../../db.php';

// Fetch all accessories from database
$query = "SELECT * FROM accessory WHERE quantity > 0 ORDER BY created_at DESC";
$accessories = $conn->query($query);
?>

<div class="custom-store-wrapper">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #F7FBFC;
        }

        .custom-store-wrapper {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .custom-store-wrapper * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .product-grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            column-gap: 40px;
            row-gap: 50px;
            justify-items: center;
        }

        @media (max-width: 992px) {
            .product-grid-container {
                grid-template-columns: repeat(2, 1fr);
                column-gap: 30px;
            }
        }

        @media (max-width: 680px) {
            .product-grid-container {
                grid-template-columns: 1fr;
            }
        }

        .exact-product-card {
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
            border-radius: 24px;
            border: 1px solid #B3BEC5;
            box-shadow: 0 15px 35px rgba(210, 222, 230, 0.45);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease;
            overflow: hidden;
        }

        .exact-product-card:hover {
            transform: translateY(-3px);
        }

        /* ── Image container — fixed height, uniform across all cards ── */
        .exact-card-top {
            width: 100%;
            height: 280px;
            position: relative;
            overflow: hidden;
            background-color: #ede8e1;
        }

        /* Blurred duplicate fills gaps behind the main image */
        .img-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: blur(20px) brightness(1.05) saturate(0.75);
            transform: scale(1.15);
            z-index: 0;
        }

        /* Main image — contained, never cropped */
        .exact-product-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            z-index: 1;
            display: block;
        }

        /* ── No image placeholder ── */
        .no-image-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            height: 100%;
        }

        .no-image-placeholder svg {
            width: 60px;
            height: 60px;
            opacity: 0.3;
        }

        .no-image-placeholder span {
            font-size: 12px;
            color: #8c969a;
            font-family: 'Inter', sans-serif;
        }

        /* ── Card bottom ── */
        .exact-card-bottom {
            padding: 16px 24px 28px 24px;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .exact-sku {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #8c969a;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .exact-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 17px;
            color: #1a2b36;
            line-height: 1.4;
            flex-grow: 1;
            min-height: 40px;
        }

        .exact-action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }

        .exact-price {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 19px;
            color: #1a2b36;
            font-weight: 500;
        }

        .exact-cart-btn {
            background-color: #004767;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .exact-cart-btn:hover {
            background-color: #00334d;
        }

        .exact-cart-btn.disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .exact-cart-btn.disabled:hover {
            background-color: #95a5a6;
        }

        /* ── Empty state ── */
        .empty-accessories {
            text-align: center;
            padding: 80px 20px;
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #B3BEC5;
            grid-column: 1 / -1;
        }

        .empty-accessories svg {
            width: 80px;
            height: 80px;
            opacity: 0.4;
            margin-bottom: 20px;
        }

        .empty-accessories h3 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 22px;
            color: #1a2b36;
            margin-bottom: 10px;
        }

        .empty-accessories p {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #8c969a;
        }
    </style>

    <div class="product-grid-container">
        <?php if ($accessories && $accessories->num_rows > 0): ?>
            <?php while ($row = $accessories->fetch_assoc()): ?>
                <?php
                $image_path = '';
                $has_image = false;

                if (!empty($row['image'])) {
                    $img_file = $row['image'];
                    if (strpos($img_file, 'http') === 0) {
                        $image_path = $img_file;
                    } else {
                        $image_path = $base . '/img/' . ltrim($img_file, '/');
                    }
                    $has_image = true;
                }
                ?>
                <div class="exact-product-card" data-accessory-id="<?= $row['accessory_id'] ?>">
                    <div class="exact-card-top">
                        <?php if ($has_image && !empty($row['image'])): ?>
                            <img src="<?= htmlspecialchars($image_path) ?>"
                                 class="img-bg"
                                 aria-hidden="true"
                                 alt="">
                            <img src="<?= htmlspecialchars($image_path) ?>"
                                 class="exact-product-image"
                                 alt="<?= htmlspecialchars($row['name']) ?>">
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#6b7c85" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
                                </svg>
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="exact-card-bottom">
                        <div class="exact-sku"><?= htmlspecialchars($row['sku']) ?></div>
                        <h3 class="exact-title"><?= htmlspecialchars($row['name']) ?></h3>

                        <?php $quantity = (int)$row['quantity']; ?>

                        <div class="exact-action-row">
                            <span class="exact-price">$<?= number_format($row['price'], 2) ?></span>
                            <button class="exact-cart-btn <?= $quantity <= 0 ? 'disabled' : '' ?>"
                                type="button"
                                data-id="<?= $row['accessory_id'] ?>"
                                data-sku="<?= htmlspecialchars($row['sku']) ?>"
                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                data-price="<?= $row['price'] ?>"
                                data-image="<?= htmlspecialchars($image_path) ?>"
                                <?= $quantity <= 0 ? 'disabled' : '' ?>>
                                <?= $quantity <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-accessories">
                <svg viewBox="0 0 24 24" fill="none" stroke="#6b7c85" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 7h-4.18A3 3 0 0 0 16 5.18V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                    <path d="M16 5v4h4"/>
                    <path d="M9 13h6"/>
                    <path d="M9 17h3"/>
                </svg>
                <h3>No Accessories Available</h3>
                <p>Check back soon for new accessories!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.exact-cart-btn');
    if (!btn) return;
    if (btn.classList.contains('disabled')) return;

    e.preventDefault();

    const sku        = btn.getAttribute('data-sku');
    const name       = btn.getAttribute('data-name');
    const price      = parseFloat(btn.getAttribute('data-price'));
    const accessoryId = btn.getAttribute('data-id');
    const image      = btn.getAttribute('data-image');

    if (window.LVBCart) {
        LVBCart.addItem({
            id: sku,
            name: name,
            scent: 'Accessory',
            price: price,
            qty: 1,
            accessory_id: accessoryId,
            image: image
        });

        showCartSuccess(name + ' added to cart');

        btn.style.transform = 'scale(0.95)';
        setTimeout(() => { btn.style.transform = ''; }, 150);
    } else {
        console.warn('LVBCart not available');
        showCartSuccess(name + ' added to cart (demo mode)');
    }
});

function showCartSuccess(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #002C4C;
        color: #fff;
        padding: 14px 22px;
        border-radius: 12px;
        z-index: 99999;
        font-family: Inter, sans-serif;
        font-size: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,.15);
        opacity: 0;
        transform: translateY(10px);
        transition: .25s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 250);
    }, 2200);
}

document.querySelectorAll('.exact-cart-btn:not(.disabled)').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (this.dataset.processing === 'true') {
            e.preventDefault();
            return;
        }
        this.dataset.processing = 'true';
        setTimeout(() => { delete this.dataset.processing; }, 1000);
    });
});
</script>