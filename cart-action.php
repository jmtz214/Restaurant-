<?php

session_start();

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";


// =====================================================
// GET ACTION
// =====================================================

$action = $_POST['action'] ?? '';


// =====================================================
// ADD ITEM
// =====================================================

if ($action === 'add') {

    $menuItemId = filter_input(
        INPUT_POST,
        'menu_item_id',
        FILTER_VALIDATE_INT
    );

    $restaurantId = filter_input(
        INPUT_POST,
        'restaurant_id',
        FILTER_VALIDATE_INT
    );


    if (!$menuItemId || !$restaurantId) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid food item.'
        ]);

        exit;
    }


    // =================================================
    // GET FOOD ITEM
    // =================================================

    $stmt = $pdo->prepare("
        SELECT
            id,
            restaurant_id,
            name,
            price,
            image,
            is_available
        FROM menu_items
        WHERE id = ?
          AND restaurant_id = ?
          AND is_available = 1
        LIMIT 1
    ");

    $stmt->execute([
        $menuItemId,
        $restaurantId
    ]);

    $item = $stmt->fetch();


    if (!$item) {

        echo json_encode([
            'success' => false,
            'message' => 'This food item is currently unavailable.'
        ]);

        exit;
    }


    // =================================================
    // INITIALIZE CART
    // =================================================

    if (!isset($_SESSION['cart'])) {

        $_SESSION['cart'] = [];

    }


    // =================================================
    // PREVENT MULTIPLE RESTAURANTS
    // =================================================

    if (!empty($_SESSION['cart'])) {

        $existingRestaurantId =
            $_SESSION['cart'][0]['restaurant_id'];


        if ((int)$existingRestaurantId !== (int)$restaurantId) {

            echo json_encode([
                'success' => false,
                'different_restaurant' => true,
                'message' =>
                    'Your cart contains food from another restaurant. Please complete or clear your current cart first.'
            ]);

            exit;
        }

    }


    // =================================================
    // CHECK IF ITEM ALREADY EXISTS
    // =================================================

    if (isset($_SESSION['cart'][$menuItemId])) {

        $_SESSION['cart'][$menuItemId]['quantity']++;

    } else {

        $_SESSION['cart'][$menuItemId] = [

            'menu_item_id' =>
                (int)$item['id'],

            'restaurant_id' =>
                (int)$item['restaurant_id'],

            'name' =>
                $item['name'],

            'price' =>
                (float)$item['price'],

            'image' =>
                $item['image'],

            'quantity' =>
                1

        ];

    }


    // =================================================
    // CART COUNT
    // =================================================

    $cartCount = 0;

    foreach ($_SESSION['cart'] as $cartItem) {

        $cartCount +=
            (int)$cartItem['quantity'];

    }


    echo json_encode([

        'success' => true,

        'message' =>
            $item['name'] . ' added to your cart.',

        'cart_count' =>
            $cartCount

    ]);

    exit;
}



// =====================================================
// UPDATE QUANTITY
// =====================================================

if ($action === 'update') {

    $menuItemId = filter_input(
        INPUT_POST,
        'menu_item_id',
        FILTER_VALIDATE_INT
    );

    $quantity = filter_input(
        INPUT_POST,
        'quantity',
        FILTER_VALIDATE_INT
    );


    if (
        !$menuItemId ||
        !$quantity ||
        $quantity < 1
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid quantity.'
        ]);

        exit;
    }


    if (
        isset(
            $_SESSION['cart'][$menuItemId]
        )
    ) {

        $_SESSION['cart'][$menuItemId]['quantity'] =
            $quantity;

    }


    echo json_encode([

        'success' => true,

        'message' =>
            'Cart updated successfully.'

    ]);

    exit;
}



// =====================================================
// REMOVE ITEM
// =====================================================

if ($action === 'remove') {

    $menuItemId = filter_input(
        INPUT_POST,
        'menu_item_id',
        FILTER_VALIDATE_INT
    );


    if ($menuItemId) {

        unset(
            $_SESSION['cart'][$menuItemId]
        );

    }


    echo json_encode([

        'success' => true,

        'message' =>
            'Item removed from cart.'

    ]);

    exit;
}



// =====================================================
// CLEAR CART
// =====================================================

if ($action === 'clear') {

    $_SESSION['cart'] = [];


    echo json_encode([

        'success' => true,

        'message' =>
            'Cart cleared successfully.'

    ]);

    exit;
}



// =====================================================
// INVALID ACTION
// =====================================================

echo json_encode([

    'success' => false,

    'message' =>
        'Invalid cart action.'

]);

exit;