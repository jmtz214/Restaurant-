<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";


// =====================================================
// ONLY POST REQUESTS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: restaurants.php");
    exit;

}


// =====================================================
// CHECK CART
// =====================================================

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {

    $_SESSION['checkout_error'] =
        "Your cart is empty.";

    header("Location: cart.php");
    exit;

}


// =====================================================
// CUSTOMER
// =====================================================

$customerId =
    $_SESSION['user_id']
    ?? null;


// For now login is required.
if (!$customerId) {

    $_SESSION['checkout_error'] =
        "Please login before placing an order.";

    header("Location: login.php");
    exit;

}


// =====================================================
// FORM DATA
// =====================================================

$restaurantId =
    filter_input(
        INPUT_POST,
        'restaurant_id',
        FILTER_VALIDATE_INT
    );


$orderType =
    $_POST['order_type']
    ?? '';


$paymentMethod =
    $_POST['payment_method']
    ?? 'cash';


$deliveryAddress =
    trim(
        $_POST['delivery_address']
        ?? ''
    );


$deliveryCity =
    trim(
        $_POST['delivery_city']
        ?? ''
    );


$deliveryLatitude =
    $_POST['delivery_latitude']
    ?? null;


$deliveryLongitude =
    $_POST['delivery_longitude']
    ?? null;


$customerNotes =
    trim(
        $_POST['customer_notes']
        ?? ''
    );


// =====================================================
// VALIDATION
// =====================================================

if (!$restaurantId) {

    die("Invalid restaurant.");

}


if (!in_array(
    $orderType,
    ['pickup', 'delivery'],
    true
)) {

    die("Invalid order type.");

}


if (!in_array(
    $paymentMethod,
    ['cash'],
    true
)) {

    die("Invalid payment method.");

}


if (
    $orderType === 'delivery'
    &&
    $deliveryAddress === ''
) {

    die("Delivery address is required.");

}


// =====================================================
// GET RESTAURANT
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        delivery_available,
        pickup_available,
        delivery_fee
    FROM restaurants
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $restaurantId
]);

$restaurant =
    $stmt->fetch();


if (!$restaurant) {

    die("Restaurant not found.");

}


// =====================================================
// CHECK ORDER TYPE AVAILABILITY
// =====================================================

if (
    $orderType === 'delivery'
    &&
    empty($restaurant['delivery_available'])
) {

    die(
        "This restaurant does not currently offer delivery."
    );

}


if (
    $orderType === 'pickup'
    &&
    empty($restaurant['pickup_available'])
) {

    die(
        "This restaurant does not currently offer pickup."
    );

}


// =====================================================
// VERIFY MENU ITEMS
// =====================================================

$menuIds = [];

foreach ($cart as $item) {

    $menuIds[] =
        (int)$item['menu_item_id'];

}


$placeholders =
    implode(
        ',',
        array_fill(
            0,
            count($menuIds),
            '?'
        )
    );


$stmt = $pdo->prepare("
    SELECT
        id,
        restaurant_id,
        name,
        price,
        is_available
    FROM menu_items
    WHERE id IN ($placeholders)
");


$stmt->execute($menuIds);

$databaseItems =
    $stmt->fetchAll();


$verifiedItems = [];

foreach ($databaseItems as $item) {

    $verifiedItems[
        (int)$item['id']
    ] = $item;

}


// =====================================================
// CALCULATE ORDER TOTALS
// =====================================================

$subtotal = 0;

$orderItems = [];


foreach ($cart as $cartItem) {


    $menuItemId =
        (int)$cartItem['menu_item_id'];


    if (!isset(
        $verifiedItems[$menuItemId]
    )) {

        die(
            htmlspecialchars(
                $cartItem['name']
            )
            .
            " is no longer available."
        );

    }


    $dbItem =
        $verifiedItems[$menuItemId];


    // Make sure food belongs to same restaurant.

    if (
        (int)$dbItem['restaurant_id']
        !==
        (int)$restaurantId
    ) {

        die(
            "Invalid restaurant item detected."
        );

    }


    // Make sure food is still available.

    if (
        empty($dbItem['is_available'])
    ) {

        die(
            htmlspecialchars(
                $dbItem['name']
            )
            .
            " is currently unavailable."
        );

    }


    $quantity =
        max(
            1,
            (int)$cartItem['quantity']
        );


    // IMPORTANT:
    // Use current database price,
    // not the session price.

    $unitPrice =
        (float)$dbItem['price'];


    $itemSubtotal =
        $unitPrice * $quantity;


    $subtotal +=
        $itemSubtotal;


    $orderItems[] = [

        'menu_item_id' =>
            (int)$dbItem['id'],

        'item_name' =>
            $dbItem['name'],

        'unit_price' =>
            $unitPrice,

        'quantity' =>
            $quantity,

        'subtotal' =>
            $itemSubtotal,

        'special_instructions' =>
            ''

    ];

}


// =====================================================
// DELIVERY FEE
// =====================================================

$deliveryFee = 0;

if ($orderType === 'delivery') {

    $deliveryFee =
        (float)$restaurant['delivery_fee'];

}


// =====================================================
// TOTAL
// =====================================================

$discountAmount = 0;

$totalAmount =
    $subtotal
    +
    $deliveryFee
    -
    $discountAmount;


// =====================================================
// ORDER NUMBER
// =====================================================

$orderNumber =
    'MLG-' .
    date('Ymd') .
    '-' .
    strtoupper(
        substr(
            bin2hex(
                random_bytes(4)
            ),
            0,
            6
        )
    );


// =====================================================
// START TRANSACTION
// =====================================================

try {


    $pdo->beginTransaction();


    // =================================================
    // INSERT ORDER
    // =================================================

    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number,
            customer_id,
            restaurant_id,
            order_type,
            status,
            subtotal,
            delivery_fee,
            discount_amount,
            total_amount,
            payment_method,
            payment_status,
            delivery_address,
            delivery_city,
            delivery_latitude,
            delivery_longitude,
            customer_notes,
            placed_at,
            created_at,
            updated_at
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            'pending',
            ?,
            ?,
            ?,
            ?,
            ?,
            'pending',
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW(),
            NOW(),
            NOW()
        )
    ");


    $stmt->execute([

        $orderNumber,

        $customerId,

        $restaurantId,

        $orderType,

        $subtotal,

        $deliveryFee,

        $discountAmount,

        $totalAmount,

        $paymentMethod,

        $orderType === 'delivery'
            ? $deliveryAddress
            : null,

        $orderType === 'delivery'
            ? $deliveryCity
            : null,

        $orderType === 'delivery'
            ? $deliveryLatitude
            : null,

        $orderType === 'delivery'
            ? $deliveryLongitude
            : null,

        $customerNotes

    ]);


    $orderId =
        $pdo->lastInsertId();


    // =================================================
    // INSERT ORDER ITEMS
    // =================================================

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            menu_item_id,
            item_name,
            unit_price,
            quantity,
            subtotal,
            special_instructions,
            created_at
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW()
        )
    ");


    foreach ($orderItems as $item) {

        $stmtItem->execute([

            $orderId,

            $item['menu_item_id'],

            $item['item_name'],

            $item['unit_price'],

            $item['quantity'],

            $item['subtotal'],

            $item['special_instructions']

        ]);

    }


    // =================================================
    // COMMIT
    // =================================================

    $pdo->commit();


    // =================================================
    // CLEAR CART
    // =================================================

    $_SESSION['cart'] = [];


    // =================================================
    // SAVE SUCCESS MESSAGE
    // =================================================

    $_SESSION['order_success'] = [

        'order_id' =>
            $orderId,

        'order_number' =>
            $orderNumber

    ];


    // =================================================
    // REDIRECT
    // =================================================

    header(
        "Location: order-success.php"
    );

    exit;


} catch (Exception $e) {


    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    die(
        "Unable to place your order: "
        .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}