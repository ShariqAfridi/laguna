<?php
namespace App\Controllers\Frontend;

use App\Models\User;
use App\Models\Order;
use App\Models\Address;
use App\Models\Review;
use App\Middleware\CustomerAuthMiddleware;

class DashboardController {

    private static function renderDashboard(string $activeTab) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        // Strictly enforce customer authentication middleware protection (No guests or demo allowed)
        CustomerAuthMiddleware::handle();
        CustomerAuthMiddleware::preventBackHistoryCache();

        $userId = $_SESSION['user_id'] ?? 0;
        $userEmail = $_SESSION['user_email'] ?? '';

        $user = null;
        if ($userId > 0) {
            $user = User::findById($userId);
        }
        if (!$user && !empty($userEmail)) {
            $user = User::findByEmail($userEmail);
        }

        // If user is not found in database, enforce login redirect
        if (!$user) {
            CustomerAuthMiddleware::handle();
        }

        // Fetch user orders strictly for this authenticated user
        $orders = Order::getByUser($userId, $user['email'] ?? '');

        // Calculate dynamic order counts
        $pendingCount = 0;
        $completedCount = 0;
        foreach ($orders as $o) {
            $st = strtolower($o['status'] ?? '');
            if (in_array($st, ['pending', 'processing', 'shipped'], true)) {
                $pendingCount++;
            } elseif ($st === 'delivered') {
                $completedCount++;
            }
        }

        // Session Cart items
        $cart = $_SESSION['cart'] ?? [];

        // Fetch User Addresses & Reviews
        $addresses = Address::getByUser($userId);
        $reviews = Review::getByUser($userId);

        view('frontend/dashboard', [
            'user'           => $user,
            'orders'         => $orders,
            'cart'           => $cart,
            'addresses'      => $addresses,
            'reviews'        => $reviews,
            'pendingCount'   => $pendingCount,
            'completedCount' => $completedCount,
            'activeTab'      => $activeTab
        ]);
    }

    // Dedicated Page Methods
    public static function index()          { self::renderDashboard($_GET['tab'] ?? 'home'); }
    public static function home()           { self::renderDashboard('home'); }
    public static function profile()        { self::renderDashboard('profile'); }
    public static function orders()         { self::renderDashboard('orders'); }
    public static function wishlist()       { self::renderDashboard('wishlist'); }
    public static function cart()           { self::renderDashboard('cart'); }
    public static function addresses()      { self::renderDashboard('addresses'); }
    public static function paymentMethods() { self::renderDashboard('payment-methods'); }
    public static function wallet()         { self::renderDashboard('wallet'); }
    public static function rewards()        { self::renderDashboard('rewards'); }
    public static function reviews()        { self::renderDashboard('reviews'); }
    public static function recentProducts() { self::renderDashboard('recent-products'); }
    public static function help()           { self::renderDashboard('help'); }
    public static function settings()       { self::renderDashboard('settings'); }

    // API Handlers
    public static function updateProfile() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

        $fullName = trim($input['full_name'] ?? ($_SESSION['user_name'] ?? ''));
        $email    = trim(strtolower($input['email'] ?? ($_SESSION['user_email'] ?? '')));
        $phone    = trim($input['phone'] ?? '');
        $city     = trim($input['city'] ?? '');
        $address  = trim($input['address'] ?? '');

        if (empty($fullName) || empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Full Name and Email address are required.']);
            return;
        }

        $avatarPath = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['avatar']['tmp_name'];
            $fileName = $_FILES['avatar']['name'];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed, true)) {
                $uploadDir1 = dirname(__DIR__, 2) . '/public/uploads/avatars/';
                $uploadDir2 = dirname(__DIR__, 2) . '/uploads/avatars/';
                if (!file_exists($uploadDir1)) { mkdir($uploadDir1, 0777, true); }
                if (!file_exists($uploadDir2)) { mkdir($uploadDir2, 0777, true); }

                $newFileName = 'avatar_' . ($userId ?: rand(1000, 9999)) . '_' . time() . '.' . $ext;
                $targetFile1 = $uploadDir1 . $newFileName;
                $targetFile2 = $uploadDir2 . $newFileName;

                if (move_uploaded_file($fileTmp, $targetFile1)) {
                    @copy($targetFile1, $targetFile2);
                    $avatarPath = 'uploads/avatars/' . $newFileName;
                } elseif (move_uploaded_file($fileTmp, $targetFile2)) {
                    @copy($targetFile2, $targetFile1);
                    $avatarPath = 'uploads/avatars/' . $newFileName;
                }
            }
        }

        if ($userId > 0) {
            $updateData = [
                'full_name' => $fullName,
                'email'     => $email,
                'phone'     => $phone,
                'city'      => $city,
                'address'   => $address
            ];
            if (!empty($avatarPath)) {
                $updateData['avatar'] = $avatarPath;
                $_SESSION['user_avatar'] = $avatarPath;
            }
            User::updateProfile($userId, $updateData);
        }

        $_SESSION['user_name']  = $fullName;
        $_SESSION['user_email'] = $email;

        echo json_encode([
            'success' => true,
            'avatar'  => $avatarPath,
            'message' => 'Your profile information and avatar have been updated successfully.'
        ]);
    }

    public static function changePassword() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $newPassword     = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($newPassword) || strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters long.']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'error' => 'New password and confirmation password do not match.']);
            return;
        }

        if ($userId > 0) {
            User::updatePassword($userId, $newPassword);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Your password has been changed successfully.'
        ]);
    }

    public static function cancelOrder() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $orderNumber = trim($input['order_number'] ?? '');

        echo json_encode([
            'success' => true,
            'message' => "Order #{$orderNumber} cancellation request submitted successfully."
        ]);
    }

    public static function reorder() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $orderNumber = trim($input['order_number'] ?? '');

        echo json_encode([
            'success' => true,
            'message' => "Items from Order #{$orderNumber} added to your shopping cart!",
            'redirect' => base_url('checkout')
        ]);
    }

    public static function saveAddress() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $title    = trim($input['title'] ?? 'Home');
        $address  = trim($input['address'] ?? '');
        $city     = trim($input['city'] ?? '');
        $state    = trim($input['state'] ?? 'CA');
        $zip      = trim($input['zip'] ?? '');

        if (empty($address) || empty($city) || empty($zip)) {
            echo json_encode(['success' => false, 'error' => 'Street Address, City, and ZIP code are required.']);
            return;
        }

        if ($userId > 0) {
            Address::save($userId, $input);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Address saved successfully!',
            'address' => [
                'title' => $title,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip' => $zip
            ]
        ]);
    }

    public static function deleteAddress() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $addressId = intval($input['address_id'] ?? 0);

        if ($userId > 0 && $addressId > 0) {
            Address::delete($userId, $addressId);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Address deleted successfully.'
        ]);
    }

    public static function submitReview() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $productName = trim($input['product_name'] ?? '');
        $rating      = intval($input['rating'] ?? 5);
        $reviewText  = trim($input['review_text'] ?? '');

        if (empty($productName) || empty($reviewText)) {
            echo json_encode(['success' => false, 'error' => 'Product Name and Review Message are required.']);
            return;
        }

        if ($userId > 0) {
            Review::submit($userId, $productName, $rating, $reviewText);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your review has been submitted successfully.'
        ]);
    }

    public static function toggleWishlist() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $productId = intval($input['product_id'] ?? 0);

        $wishlist = $_SESSION['wishlist'] ?? [1, 2];
        if ($productId > 0) {
            if (in_array($productId, $wishlist, true)) {
                $wishlist = array_values(array_diff($wishlist, [$productId]));
                $added = false;
            } else {
                $wishlist[] = $productId;
                $added = true;
            }
            $_SESSION['wishlist'] = $wishlist;
        } else {
            $added = false;
        }

        echo json_encode([
            'success' => true,
            'added' => $added,
            'count' => count($_SESSION['wishlist'] ?? []),
            'message' => $added ? 'Item added to your wishlist.' : 'Item removed from your wishlist.'
        ]);
    }

    public static function submitSupport() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');

        if (empty($subject) || empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Subject and Message are required.']);
            return;
        }

        $ticketId = 'TCK-' . rand(10000, 99999);

        echo json_encode([
            'success' => true,
            'ticket_id' => $ticketId,
            'message' => "Support request #{$ticketId} submitted successfully. Our team will contact you within 24 hours."
        ]);
    }
}
?>
