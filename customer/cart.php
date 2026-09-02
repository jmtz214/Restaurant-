<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// AUTHENTICATION
// =====================================================

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// INITIALIZE CART
// =====================================================

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// =====================================================
// REMOVE ITEM
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_item'])
) {

    $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);

    if ($menuItemId > 0) {

        unset(
            $_SESSION['cart'][$menuItemId]
        );
    }

    header("Location: cart.php");
    exit;
}


// =====================================================
// UPDATE QUANTITY
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_cart'])
) {

    $quantities =
        $_POST['quantity'] ?? [];


    foreach (
        $quantities as $menuItemId => $quantity
    ) {

        $menuItemId = (int) $menuItemId;

        $quantity = (int) $quantity;


        if ($quantity <= 0) {

            unset(
                $_SESSION['cart'][$menuItemId]
            );

        } else {

            if (
                isset(
                    $_SESSION['cart'][$menuItemId]
                )
            ) {

                $_SESSION['cart'][$menuItemId] =
                    $quantity;

            }

        }

    }

    header("Location: cart.php");
    exit;
}


// =====================================================
// LOAD CART ITEMS
// =====================================================

$cart = $_SESSION['cart'];

$cartItems = [];

$subtotal = 0;

$restaurantId = null;

$restaurant = null;


if (!empty($cart)) {

    $ids = array_keys($cart);

    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count($ids),
                '?'
            )
        );


    $stmt = $pdo->prepare("
        SELECT
            m.*,
            r.name AS restaurant_name,
            r.delivery_available,
            r.pickup_available,
            r.delivery_fee,
            r.minimum_order_amount

        FROM menu_items m

        INNER JOIN restaurants r
            ON r.id = m.restaurant_id

        WHERE m.id IN ($placeholders)

        AND m.is_available = 1

        AND r.status = 'approved'
    ");


    $stmt->execute($ids);

    $items = $stmt->fetchAll();


    foreach ($items as $item) {

        $id =
            (int) $item['id'];


        $quantity =
            (int) $cart[$id];


        $itemSubtotal =
            (float) $item['price'] *
            $quantity;


        $subtotal +=
            $itemSubtotal;


        $cartItems[] = [

            'id' => $id,

            'restaurant_id' =>
                (int) $item['restaurant_id'],

            'restaurant_name' =>
                $item['restaurant_name'],

            'name' =>
                $item['name'],

            'price' =>
                (float) $item['price'],

            'image' =>
                $item['image'],

            'quantity' =>
                $quantity,

            'subtotal' =>
                $itemSubtotal,

            'delivery_available' =>
                (int) $item['delivery_available'],

            'pickup_available' =>
                (int) $item['pickup_available'],

            'delivery_fee' =>
                (float) $item['delivery_fee'],

            'minimum_order_amount' =>
                (float) $item['minimum_order_amount']

        ];


        if ($restaurantId === null) {

            $restaurantId =
                (int) $item['restaurant_id'];

        }

    }


    // Get restaurant

    if ($restaurantId !== null) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM restaurants
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $restaurantId
        ]);

        $restaurant =
            $stmt->fetch();

    }

}


// =====================================================
// CART VALIDATION
// =====================================================

// A cart cannot contain food from multiple restaurants.

$multipleRestaurants = false;

foreach ($cartItems as $item) {

    if (
        $restaurantId !== null &&
        $item['restaurant_id'] !== $restaurantId
    ) {

        $multipleRestaurants = true;

        break;
    }

}


// =====================================================
// PAGE
// =====================================================

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        My Cart - MloGo
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f7f8fa;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        .navbar {

            background: white;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,.05);

        }


        .brand {

            font-size: 28px;

            font-weight: 800;

            color: #212529;

            text-decoration: none;

        }


        .brand span {

            color: #20c997;

        }


        .cart-card {

            background: white;

            border-radius: 16px;

            box-shadow:
                0 4px 20px
                rgba(0,0,0,.05);

        }


        .food-image {

            width: 100px;

            height: 80px;

            object-fit: cover;

            border-radius: 12px;

            background: #eee;

        }


        .food-placeholder {

            width: 100px;

            height: 80px;

            border-radius: 12px;

            background: #eee;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            color: #aaa;

        }


        .price {

            color: #20c997;

            font-weight: 700;

        }


        .summary {

            position: sticky;

            top: 20px;

        }


        .summary-total {

            font-size: 26px;

            font-weight: 800;

        }


        .empty-cart {

            text-align: center;

            padding: 90px 20px;

            background: white;

            border-radius: 18px;

        }


        .empty-cart i {

            font-size: 70px;

            color: #20c997;

        }


        .quantity-input {

            width: 75px;

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg">

    <div class="container py-3">


        <a
            href="../index.php"
            class="brand"
        >

            Mlo<span>Go</span>

        </a>


        <div class="d-flex align-items-center gap-3">


            <a
                href="dashboard.php"
                class="btn btn-outline-secondary"
            >

                <i class="bi bi-house"></i>

                Dashboard

            </a>


            <a
                href="cart.php"
                class="btn btn-success"
            >

                <i class="bi bi-cart3"></i>

                Cart

                <span class="badge bg-white text-success">

                    <?= array_sum($_SESSION['cart']) ?>

                </span>

            </a>


        </div>


    </div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-5">


    <div class="mb-4">

        <h2 class="fw-bold">

            Your Cart

        </h2>


        <p class="text-muted">

            Review your food before checkout.

        </p>

    </div>



    <?php if (empty($cartItems)): ?>


        <div class="empty-cart">


            <i class="bi bi-cart-x"></i>


            <h3 class="fw-bold mt-4">

                Your cart is empty

            </h3>


            <p class="text-muted">

                You haven't added any food yet.

            </p>


            <a
                href="../restaurants.php"
                class="btn btn-success px-4"
            >

                <i class="bi bi-shop"></i>

                Browse Restaurants

            </a>


        </div>


    <?php else: ?>


        <?php if ($multipleRestaurants): ?>


            <div class="alert alert-warning">

                <i class="bi bi-exclamation-triangle"></i>

                Your cart contains food from different
                restaurants. Please order from one restaurant
                at a time.

            </div>


        <?php endif; ?>


        <div class="row g-4">


            <!-- =================================================
                 CART ITEMS
            ================================================== -->

            <div class="col-lg-8">


                <div class="cart-card p-4">


                    <div class="d-flex justify-content-between mb-4">

                        <h5 class="fw-bold mb-0">

                            <?= htmlspecialchars(
                                $restaurant['name'] ?? 'Restaurant'
                            ) ?>

                        </h5>


                        <span class="text-muted">

                            <?= count($cartItems) ?>

                            item(s)

                        </span>

                    </div>


                    <form method="POST">


                        <?php foreach (
                            $cartItems
                            as $item
                        ): ?>


                            <div
                                class="border-bottom pb-4 mb-4"
                            >


                                <div class="row align-items-center g-3">


                                    <!-- IMAGE -->

                                    <div class="col-auto">


                                        <?php if (
                                            !empty(
                                                $item['image']
                                            )
                                        ): ?>


                                            <img
                                                src="../<?= htmlspecialchars(
                                                    $item['image']
                                                ) ?>"
                                                class="food-image"
                                                alt="<?= htmlspecialchars(
                                                    $item['name']
                                                ) ?>"
                                            >


                                        <?php else: ?>


                                            <div
                                                class="food-placeholder"
                                            >

                                                <i
                                                    class="bi bi-egg-fried"
                                                ></i>

                                            </div>


                                        <?php endif; ?>


                                    </div>



                                    <!-- NAME -->

                                    <div class="col">


                                        <h6 class="fw-bold mb-1">

                                            <?= htmlspecialchars(
                                                $item['name']
                                            ) ?>

                                        </h6>


                                        <div class="price">

                                            TZS
                                            <?= number_format(
                                                $item['price']
                                            ) ?>

                                        </div>


                                    </div>



                                    <!-- QUANTITY -->

                                    <div class="col-auto">


                                        <label
                                            class="small text-muted"
                                        >

                                            Quantity

                                        </label>


                                        <input
                                            type="number"
                                            name="quantity[<?= $item['id'] ?>]"
                                            value="<?= $item['quantity'] ?>"
                                            min="1"
                                            max="99"
                                            class="form-control quantity-input"
                                        >

                                    </div>



                                    <!-- SUBTOTAL -->

                                    <div
                                        class="col-auto text-end"
                                    >

                                        <div class="fw-bold">

                                            TZS
                                            <?= number_format(
                                                $item['subtotal']
                                            ) ?>

                                        </div>


                                        <button
                                            type="submit"
                                            name="remove_item"
                                            value="1"
                                            class="btn btn-link text-danger btn-sm p-0 mt-2"
                                            formaction="cart.php"
                                            onclick="setRemoveItem(<?= $item['id'] ?>)"
                                        >

                                            <i class="bi bi-trash"></i>

                                            Remove

                                        </button>


                                    </div>


                                </div>


                            </div>


                        <?php endforeach; ?>


                        <input
                            type="hidden"
                            name="menu_item_id"
                            id="removeItemId"
                            value=""
                        >


                        <div class="text-end">


                            <button
                                type="submit"
                                name="update_cart"
                                class="btn btn-outline-success"
                            >

                                <i class="bi bi-arrow-repeat"></i>

                                Update Cart

                            </button>


                        </div>


                    </form>


                </div>


            </div>



            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="col-lg-4">


                <div class="summary">


                    <div class="cart-card p-4">


                        <h5 class="fw-bold mb-4">

                            Order Summary

                        </h5>


                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">

                                Subtotal

                            </span>


                            <strong>

                                TZS
                                <?= number_format(
                                    $subtotal
                                ) ?>

                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">

                                Delivery

                            </span>


                            <span>

                                Calculated at checkout

                            </span>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between align-items-center mb-4">


                            <span class="fw-bold">

                                Total

                            </span>


                            <span class="summary-total">

                                TZS
                                <?= number_format(
                                    $subtotal
                                ) ?>

                            </span>


                        </div>


                        <?php if (
                            $restaurant &&
                            $restaurant['minimum_order_amount'] > 0
                        ): ?>


                            <div class="alert alert-light small">

                                <i class="bi bi-info-circle"></i>

                                Minimum order:

                                <strong>

                                    TZS
                                    <?= number_format(
                                        $restaurant[
                                            'minimum_order_amount'
                                        ]
                                    ) ?>

                                </strong>

                            </div>


                        <?php endif; ?>


                        <a
                            href="checkout.php"
                            class="btn btn-success btn-lg w-100"
                        >

                            Proceed to Checkout

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    </div>


                </div>


            </div>


        </div>


    <?php endif; ?>


</div>



<script>

function setRemoveItem(id) {

    document.getElementById(
        'removeItemId'
    ).value = id;

}

</script>


</body>

</html>