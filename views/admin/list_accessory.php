<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../db.php';

// Handle accessory deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $accessory_id = (int)$_GET['delete'];
    
    // Get image filename before deleting
    $img_query = $conn->prepare("SELECT image FROM accessory WHERE accessory_id = ?");
    $img_query->bind_param("i", $accessory_id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    if ($img_row = $img_result->fetch_assoc()) {
        $image_name = $img_row['image'];
        if (!empty($image_name)) {
            // Try to delete image file from all possible locations
            $paths_to_check = [
                $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/' . $image_name,
                dirname(__DIR__, 2) . '/public/assets/img/' . $image_name,
                __DIR__ . '/../public/assets/img/' . $image_name
            ];

            foreach ($paths_to_check as $path) {
                if (file_exists($path)) {
                    unlink($path);
                    break;
                }
            }
        }
    }
    $img_query->close();
    
    // Delete accessory from database
    $delete_stmt = $conn->prepare("DELETE FROM accessory WHERE accessory_id = ?");
    $delete_stmt->bind_param("i", $accessory_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    echo "<script>window.location.href='/list_accessory?deleted=1';</script>";
    exit();
}

// Get success message
$success_message = '';
if (isset($_GET['deleted'])) {
    $success_message = 'Accessory deleted successfully!';
}
if (isset($_GET['added'])) {
    $success_message = 'Accessory added successfully!';
}
if (isset($_GET['updated'])) {
    $success_message = 'Accessory updated successfully!';
}

// Fetch all accessories
$query = "SELECT * FROM accessory ORDER BY created_at DESC";
$accessories = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessory List — Candle Shop</title>
    <style>
        :root {
            --blue:        #2563eb;
            --blue-h:      #1d4ed8;
            --blue-l:      #eff6ff;
            --bg:          #f1f5f9;
            --card:        #ffffff;
            --border:      #e2e8f0;
            --text:        #1e293b;
            --muted:       #64748b;
            --danger:      #ef4444;
            --danger-h:    #dc2626;
            --success:     #10b981;
            --radius:      10px;
            --radius-lg:   14px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        .page-main-content {
            padding: 28px 32px;
            margin-left: 0px;
        }

        @media (max-width: 960px) {
            .page-main-content { 
                margin-left: 0; 
                padding: 16px; 
                padding-top: 70px;
            }
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        /* Buttons */
        .btn-primary {
            background: var(--blue);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--blue-h);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
        }

        .btn-danger:hover {
            background: var(--danger-h);
        }

        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: var(--blue-l);
            color: var(--blue);
        }

        .btn-edit:hover {
            background: var(--blue);
            color: white;
        }

        .btn-delete {
            background: #fef2f2;
            color: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

        /* Alert */
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }

        /* Table */
        .accessories-table {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 16px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            color: var(--muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #fafbfc;
        }

        /* Image */
        .accessory-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }

        .no-image {
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 12px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            background: #e0e7ff;
            color: #3730a3;
            margin: 2px;
        }

        .price-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            th, td {
                padding: 12px;
            }
        }
    </style>
</head>
<body>

<div class="page-main-content">
    <div class="page-header">
        <h2>🛍️ Accessory Management</h2>
        <div class="header-actions">
            <a href="<?php echo $base; ?>/admin/add_accessory" class="btn-primary">+ Add New Accessory</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="accessories-table">
        <?php if ($accessories && $accessories->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $accessories->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['accessory_id'] ?></td>
                            
                            <!-- Image Column -->
                            <td>
                                <?php 
                                $image_file = $row['image'] ?? '';
                                if (!empty($image_file)) {
                                    $clean_name = ltrim(preg_replace('#^/?(img/)?#i', '', $image_file), '/');
                                    $disk_path = dirname(__DIR__, 2) . '/public/assets/img/' . $clean_name;
                                    
                                    if (file_exists($disk_path)) {
                                        $img_url = base_url('/public/assets/img/' . $clean_name);

                                        echo '<img src="' . htmlspecialchars($img_url) . '" class="accessory-image" alt="' . htmlspecialchars($row['name'] ?? '') . '">';
                                    } else if (preg_match('#^https?://#i', $image_file)) {
                                        echo '<img src="' . htmlspecialchars($image_file) . '" class="accessory-image" alt="' . htmlspecialchars($row['name'] ?? '') . '">';
                                    } else {
                                        echo '<div class="no-image">No image</div>';
                                    }
                                } else {
                                    echo '<div class="no-image">No image</div>';
                                }
                                ?>
                            </td>
                            
                            <td>
                                <strong><?= htmlspecialchars($row['name']) ?></strong>
                            </td>
                            
                            <td>
                                <span class="badge"><?= htmlspecialchars($row['sku']) ?></span>
                            </td>
                            
                            <td>
                                <span class="price-badge">$<?= number_format($row['price'], 2) ?></span>
                            </td>
                            
                            <td>
                                <span class="badge" style="background: #dbeafe; color: #1e40af;">
                                    <?= (int)$row['quantity'] ?> units
                                </span>
                            </td>
                            
                            <td style="font-size: 12px; color: var(--muted);">
                                <?= date('M d, Y', strtotime($row['created_at'])) ?>
                            </td>
                            
                            <td>
                                <div class="action-buttons">
                                    <a href="<?php echo $base; ?>/admin/edit_accessory?id=<?= $row['accessory_id'] ?>" class="btn-edit">✏️ Edit</a>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this accessory?');">
                                        <input type="hidden" name="delete_id" value="<?= $row['accessory_id'] ?>">
                                        <button type="submit" class="btn-delete">🗑️ Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>📦 No accessories found</p>
                <a href="<?php echo $base; ?>/admin/add_accessory" style="display: inline-block; margin-top: 16px;" class="btn-primary">Add Your First Accessory</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(accessoryId, accessoryName) {
    if (confirm(`Are you sure you want to delete "${accessoryName}"?\n\nThis action cannot be undone.`)) {
        window.location.href = `?delete=${accessoryId}`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.setAttribute('title', 'Edit accessory');
    });
    
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.setAttribute('title', 'Delete accessory');
    });
});
</script>

</body>
</html>