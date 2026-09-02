<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// AUTHENTICATION
// =====================================================

if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['user_role'])
) {
    header("Location: ../login.php");
    exit;
}


if ($_SESSION['user_role'] !== 'restaurant_admin') {
    header("Location: ../index.php");
    exit;
}


$userId = (int) $_SESSION['user_id'];


// =====================================================
// ONLY POST REQUESTS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}


// =====================================================
// GET FORM DATA
// =====================================================

$orderId = isset($_POST['order_id'])
    ? (int) $_POST['order_id']
    : 0;

$action = trim(
    $_POST['action'] ?? ''
);


if ($orderId <= 0) {
    die("Invalid order ID.");
}


// =====================================================
// VALID ACTIONS
// =====================================================

$allowedActions = [
    'accept',
    'deny',
    'preparing',
    'ready',
    'out_for_delivery',
    'completed'
];


if (!in_array($action, $allowedActions, true)) {
    die("Invalid order action.");
}


// =====================================================
// GET RESTAURANT OWNED BY CURRENT ADMIN
// =====================================================

$stmt = $pdo->prepare("
    SELECT id, name
    FROM restaurants
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->execute([
    $userId
]);

$restaurant = $stmt->fetch();


if (!$restaurant) {
    die("Restaurant not found.");
}


$restaurantId = (int) $restaurant['id'];


// =====================================================
// GET ORDER
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
    AND restaurant_id = ?
    LIMIT 1
");

$stmt->execute([
    $orderId,
    $restaurantId
]);

$order = $stmt->fetch();


if (!$order) {
    die("Order not found or you do not have permission to modify it.");
}


$currentStatus = $order['status'];


// =====================================================
// DETERMINE NEW STATUS
// =====================================================

$newStatus = null;

switch ($action) {

    case 'accept':

        if ($currentStatus !== 'pending') {
            die("This order can only be accepted while it is pending.");
        }

        $newStatus = 'accepted';

        break;


    case 'deny':

        if ($currentStatus !== 'pending') {
            die("This order can only be denied while it is pending.");
        }

        $newStatus = 'denied';

        break;


    case 'preparing':

        if ($currentStatus !== 'accepted') {
            die("This order must be accepted before preparation starts.");
        }

        $newStatus = 'preparing';

        break;


    case 'ready':

        if ($currentStatus !== 'preparing') {
            die("This order must be preparing before it can be marked ready.");
        }

        $newStatus = 'ready';

        break;


    case 'out_for_delivery':

        if ($currentStatus !== 'ready') {
            die("This order must be ready before it can go out for delivery.");
        }

        if ($order['order_type'] !== 'delivery') {
            die("Only delivery orders can be marked as out for delivery.");
        }

        $newStatus = 'out_for_delivery';

        break;


    case 'completed':

        if ($currentStatus !== 'ready' && $currentStatus !== 'out_for_delivery') {
            die("This order cannot be completed from its current status.");
        }

        $newStatus = 'completed';

        break;
}


// =====================================================
// UPDATE ORDER
// =====================================================

try {

    $pdo->beginTransaction();


    /*
     * ACCEPTED
     */

    if ($newStatus === 'accepted') {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = ?,
                accepted_at = NOW(),
                updated_at = NOW()

            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $newStatus,
            $orderId,
            $restaurantId
        ]);
    }


    /*
     * PREPARING
     */

    elseif ($newStatus === 'preparing') {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = ?,
                preparing_at = NOW(),
                updated_at = NOW()

            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $newStatus,
            $orderId,
            $restaurantId
        ]);
    }


    /*
     * READY
     */

    elseif ($newStatus === 'ready') {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = ?,
                ready_at = NOW(),
                updated_at = NOW()

            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $newStatus,
            $orderId,
            $restaurantId
        ]);
    }


    /*
     * OUT FOR DELIVERY
     */

    elseif ($newStatus === 'out_for_delivery') {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = ?,
                updated_at = NOW()

            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $newStatus,
            $orderId,
            $restaurantId
        ]);
    }


    /*
     * COMPLETED
     */

    elseif ($newStatus === 'completed') {

        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = ?,
                completed_at = NOW(),
                updated_at = NOW()

            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $newStatus,
            $orderId,
            $restaurantId
        ]);
    }


    /*
     * DENIED
     */

    elseif ($newStatus === 'denied') {

        $denialReason = trim(
            $_POST['denial_reason'] ?? ''
        );


        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                status = ?,
                denial_reason = ?,
                updated_at = NOW()

            WHERE id = ?
            AND restaurant_id = ?
        ");

        $stmt->execute([
            $newStatus,
            $denialReason !== ''
                ? $denialReason
                : null,
            $orderId,
            $restaurantId
        ]);
    }


    $pdo->commit();


    // =================================================
    // REDIRECT BACK TO ORDER
    // =================================================

    header(
        "Location: order-details.php?id="
        . $orderId
        . "&success="
        . urlencode(
            "Order updated successfully."
        )
    );

    exit;


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    die(
        "Unable to update order: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );
}