<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";


// =====================================================
// GET CART
// =====================================================

$cart = $_SESSION['cart'] ?? [];


// =====================================================
// CALCULATE TOTALS
// =====================================================

$subtotal = 0;

$cartCount = 0;


foreach ($cart as $item) {

    $quantity =
        (int)$item['quantity'];

    $price =
        (float)$item['price'];

    $subtotal +=
        $price * $quantity;

    $cartCount +=
        $quantity;

}


// Delivery will be calculated during checkout.
$deliveryFee = 0;

$total = $subtotal + $deliveryFee;


// =====================================================
// RESTAURANT INFORMATION
// =====================================================

$restaurant = null;


if (!empty($cart)) {

    $restaurantId =
        $cart[array_key_first($cart)]['restaurant_id'];


    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            city,
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

}

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
        Shopping Cart - MloGo
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        .cart-page {

            background: #f8f9fa;

            min-height: 70vh;

        }


        .cart-item {

            background: white;

            border-radius: 16px;

            padding: 18px;

            border: 1px solid #eee;

        }


        .cart-item-image {

            width: 100px;

            height: 100px;

            object-fit: cover;

            border-radius: 12px;

        }


        .cart-summary {

            background: white;

            border-radius: 18px;

            padding: 25px;

            border: 1px solid #eee;

            position: sticky;

            top: 100px;

        }


        .quantity-control {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .quantity-control button {

            width: 34px;

            height: 34px;

            border-radius: 50%;

            border: 1px solid #ddd;

            background: white;

        }


        .quantity-value {

            min-width: 25px;

            text-align: center;

            font-weight: 600;

        }


        .empty-cart {

            padding: 80px 20px;

            text-align: center;

        }


        .empty-cart-icon {

            font-size: 70px;

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg main-navbar sticky-top">

    <div class="container">


        <a
            class="navbar-brand brand-logo"
            href="index.php"
        >

            Mlo<span>Go</span>

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >


            <ul class="navbar-nav mx-auto">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php"
                    >

                        Home

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="restaurants.php"
                    >

                        Restaurants

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="cart.php"
                    >

                        Cart

                        <?php if ($cartCount > 0): ?>

                            <span class="badge bg-success">

                                <?= $cartCount ?>

                            </span>

                        <?php endif; ?>

                    </a>

                </li>

            </ul>


            <div class="d-flex gap-2">

                <a
                    href="login.php"
                    class="btn btn-outline-dark"
                >

                    Login

                </a>

            </div>


        </div>

    </div>

</nav>



<!-- =====================================================
     CART
===================================================== -->

<section class="cart-page py-5">

    <div class="container">


        <div class="mb-4">

            <h1 class="fw-bold">

                Your Cart

            </h1>


            <p class="text-muted">

                Review your selected meals before checkout.

            </p>

        </div>



        <?php if (!empty($cart)): ?>


            <div class="row g-4">


                <!-- =================================================
                     CART ITEMS
                ================================================== -->

                <div class="col-lg-8">


                    <?php if ($restaurant): ?>

                        <div class="bg-white rounded-4 p-3 mb-3">

                            <div class="d-flex align-items-center gap-2">

                                <i class="bi bi-shop fs-4 text-success"></i>


                                <div>

                                    <small class="text-muted">

                                        Ordering from

                                    </small>


                                    <div class="fw-bold">

                                        <?= htmlspecialchars(
                                            $restaurant['name']
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>



                    <?php foreach ($cart as $item): ?>


                        <div
                            class="cart-item mb-3"
                            id="cart-item-<?= (int)$item['menu_item_id'] ?>"
                        >


                            <div class="row align-items-center g-3">


                                <!-- Image -->

                                <div class="col-4 col-md-2">


                                    <?php if (!empty($item['image'])): ?>

                                        <img
                                            src="uploads/foods/<?= htmlspecialchars($item['image']) ?>"
                                            class="cart-item-image"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                        >

                                    <?php else: ?>

                                        <img
                                            src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=300&q=80"
                                            class="cart-item-image"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                        >

                                    <?php endif; ?>


                                </div>



                                <!-- Information -->

                                <div class="col-8 col-md-4">


                                    <h5 class="mb-1">

                                        <?= htmlspecialchars(
                                            $item['name']
                                        ) ?>

                                    </h5>


                                    <p class="text-muted mb-0">

                                        TZS
                                        <?= number_format(
                                            (float)$item['price']
                                        ) ?>

                                        each

                                    </p>


                                </div>



                                <!-- Quantity -->

                                <div class="col-6 col-md-3">


                                    <div class="quantity-control">


                                        <button
                                            type="button"
                                            onclick="changeQuantity(
                                                <?= (int)$item['menu_item_id'] ?>,
                                                <?= (int)$item['quantity'] - 1 ?>
                                            )"
                                        >

                                            <i class="bi bi-dash"></i>

                                        </button>


                                        <span
                                            class="quantity-value"
                                            id="quantity-<?= (int)$item['menu_item_id'] ?>"
                                        >

                                            <?= (int)$item['quantity'] ?>

                                        </span>


                                        <button
                                            type="button"
                                            onclick="changeQuantity(
                                                <?= (int)$item['menu_item_id'] ?>,
                                                <?= (int)$item['quantity'] + 1 ?>
                                            )"
                                        >

                                            <i class="bi bi-plus"></i>

                                        </button>


                                    </div>


                                </div>



                                <!-- Total -->

                                <div class="col-5 col-md-2 text-md-end">


                                    <strong>

                                        TZS
                                        <?= number_format(
                                            (float)$item['price']
                                            *
                                            (int)$item['quantity']
                                        ) ?>

                                    </strong>


                                </div>



                                <!-- Remove -->

                                <div class="col-1 text-end">


                                    <button
                                        type="button"
                                        class="btn btn-link text-danger p-0"
                                        onclick="removeItem(
                                            <?= (int)$item['menu_item_id'] ?>
                                        )"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </button>


                                </div>


                            </div>

                        </div>


                    <?php endforeach; ?>



                    <div class="d-flex justify-content-between mt-4">


                        <a
                            href="restaurants.php"
                            class="btn btn-outline-dark"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Continue Shopping

                        </a>


                        <button
                            type="button"
                            class="btn btn-outline-danger"
                            onclick="clearCart()"
                        >

                            <i class="bi bi-trash"></i>

                            Clear Cart

                        </button>


                    </div>


                </div>



                <!-- =================================================
                     SUMMARY
                ================================================== -->

                <div class="col-lg-4">


                    <div class="cart-summary">


                        <h4 class="fw-bold mb-4">

                            Order Summary

                        </h4>


                        <div class="d-flex justify-content-between mb-3">

                            <span>

                                Subtotal

                            </span>


                            <strong>

                                TZS
                                <?= number_format($subtotal) ?>

                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-3">

                            <span>

                                Delivery

                            </span>


                            <span class="text-muted">

                                Calculated at checkout

                            </span>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between mb-4">

                            <strong>

                                Total

                            </strong>


                            <strong class="text-success fs-5">

                                TZS
                                <?= number_format($total) ?>

                            </strong>

                        </div>


                        <a
                            href="checkout.php"
                            class="btn btn-success w-100 py-3"
                        >

                            Proceed to Checkout

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    </div>


                </div>


            </div>


        <?php else: ?>


            <!-- =================================================
                 EMPTY CART
            ================================================== -->

            <div class="empty-cart bg-white rounded-4">


                <div class="empty-cart-icon">

                    🛒

                </div>


                <h2 class="mt-3">

                    Your cart is empty

                </h2>


                <p class="text-muted">

                    You haven't added any food yet.

                </p>


                <a
                    href="restaurants.php"
                    class="btn btn-success mt-2"
                >

                    Explore Restaurants

                </a>


            </div>


        <?php endif; ?>


    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div class="container">


        <div class="row g-4">


            <div class="col-lg-4">

                <h3 class="brand-logo text-white">

                    Mlo<span>Go</span>

                </h3>


                <p class="mt-3 text-white-50">

                    Connecting Tanzanian food lovers
                    with great local restaurants.

                </p>

            </div>


            <div class="col-6 col-lg-2">

                <h5>

                    Explore

                </h5>


                <ul class="list-unstyled mt-3">

                    <li class="mb-2">

                        <a href="index.php">

                            Home

                        </a>

                    </li>


                    <li>

                        <a href="restaurants.php">

                            Restaurants

                        </a>

                    </li>

                </ul>

            </div>


            <div class="col-6 col-lg-2">

                <h5>

                    Account

                </h5>


                <ul class="list-unstyled mt-3">

                    <li class="mb-2">

                        <a href="login.php">

                            Login

                        </a>

                    </li>


                    <li>

                        <a href="register.php">

                            Register

                        </a>

                    </li>

                </ul>

            </div>


        </div>


        <div class="footer-bottom text-center">

            © <?= date('Y') ?> MloGo.
            All rights reserved.

        </div>


    </div>

</footer>



<script>


// =====================================================
// CHANGE QUANTITY
// =====================================================

function changeQuantity(
    menuItemId,
    quantity
) {


    if (quantity < 1) {

        removeItem(menuItemId);

        return;

    }


    fetch('cart-action.php', {

        method: 'POST',

        headers: {

            'Content-Type':
                'application/x-www-form-urlencoded'

        },

        body:
            'action=update' +
            '&menu_item_id=' +
            encodeURIComponent(menuItemId) +
            '&quantity=' +
            encodeURIComponent(quantity)

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            location.reload();

        } else {

            alert(data.message);

        }

    });

}



// =====================================================
// REMOVE ITEM
// =====================================================

function removeItem(menuItemId) {

    if (!confirm(
        'Remove this item from your cart?'
    )) {

        return;

    }


    fetch('cart-action.php', {

        method: 'POST',

        headers: {

            'Content-Type':
                'application/x-www-form-urlencoded'

        },

        body:
            'action=remove' +
            '&menu_item_id=' +
            encodeURIComponent(menuItemId)

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            location.reload();

        } else {

            alert(data.message);

        }

    });

}



// =====================================================
// CLEAR CART
// =====================================================

function clearCart() {

    if (!confirm(
        'Are you sure you want to clear your cart?'
    )) {

        return;

    }


    fetch('cart-action.php', {

        method: 'POST',

        headers: {

            'Content-Type':
                'application/x-www-form-urlencoded'

        },

        body:
            'action=clear'

    })

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            location.reload();

        } else {

            alert(data.message);

        }

    });

}

</script>


</body>

</html>