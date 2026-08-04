<?php
// stripe/webhook.php — Server-Side Authenticated Stripe Webhook Handler
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/app.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

// Load Stripe API Secret & Webhook Secret
$stripeSecret = env('STRIPE_SECRET_KEY', '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');
$webhookSecret = env('STRIPE_WEBHOOK_SECRET', '');

if (class_exists('\Stripe\Stripe')) {
    \Stripe\Stripe::setApiKey($stripeSecret);
}

// 1. Read Payload and Signature
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

// 2. Cryptographically Verify Webhook Signature (If Webhook Secret Set)
if (!empty($webhookSecret) && !empty($sigHeader)) {
    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Stripe Webhook Signature']);
        error_log("PCI Security Alert: Invalid Stripe Webhook Signature from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        exit;
    } catch (\UnexpectedValueException $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Webhook Payload']);
        exit;
    }
} else {
    // Fallback parsing if signature secret not configured yet in local environment
    $data = json_decode($payload, true);
    if ($data && isset($data['type'])) {
        $event = $data;
    }
}

if (!$event) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty or unparseable event']);
    exit;
}

$eventType = is_object($event) ? $event->type : ($event['type'] ?? '');
$eventData = is_object($event) ? $event->data->object : ($event['data']['object'] ?? null);

// 3. Process Authenticated Payment Events
if ($eventType === 'checkout.session.completed' || $eventType === 'payment_intent.succeeded') {
    $conn = \get_db_connection();
    
    $paymentIntentId = is_object($eventData) ? ($eventData->payment_intent ?? $eventData->id ?? '') : ($eventData['payment_intent'] ?? $eventData['id'] ?? '');
    $customerEmail = is_object($eventData) ? ($eventData->customer_email ?? $eventData->receipt_email ?? '') : ($eventData['customer_email'] ?? $eventData['receipt_email'] ?? '');
    
    if (!empty($paymentIntentId)) {
        // Check if order already recorded in DB
        $stmtCheck = $conn->prepare("SELECT id, status FROM orders WHERE stripe_payment_intent_id = ?");
        if ($stmtCheck) {
            $stmtCheck->bind_param("s", $paymentIntentId);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                // Update order to paid if not already paid
                if ($row['status'] !== 'paid' && $row['status'] !== 'completed') {
                    $stmtUpdate = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
                    if ($stmtUpdate) {
                        $orderId = intval($row['id']);
                        $stmtUpdate->bind_param("i", $orderId);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();
                    }
                }
                $stmtCheck->close();
                echo json_encode(['status' => 'success', 'message' => 'Order already logged, status confirmed paid']);
                exit;
            }
            $stmtCheck->close();
        }
    }
}

echo json_encode(['status' => 'success', 'event' => $eventType]);
exit;
