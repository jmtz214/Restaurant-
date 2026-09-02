<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/database.php";

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';

    header("Location: login.php");
    exit;
}

// =====================================================
// CHECK CART
// =====================================================

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {

    header("Location: cart.php");
    exit;

}


// =====================================================
// CART TOTALS
// =====================================================

$subtotal = 0;
$cartCount = 0;

foreach ($cart as $item) {

    $subtotal +=
        (float)$item['price'] * (int)$item['quantity'];

    $cartCount +=
        (int)$item['quantity'];
}


// =====================================================
// RESTAURANT
// =====================================================

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

$stmt->execute([$restaurantId]);

$restaurant = $stmt->fetch();

if (!$restaurant) {

    $_SESSION['checkout_error'] =
        "Restaurant not found.";

    header("Location: cart.php");
    exit;

}


// =====================================================
// DELIVERY FEE
// =====================================================

$deliveryFee = 0;

$total = $subtotal + $deliveryFee;


// =====================================================
// USER
// =====================================================

$customerName =
    trim(
        ($_SESSION['first_name'] ?? '')
        . ' '
        . ($_SESSION['last_name'] ?? '')
    );

$customerEmail =
    $_SESSION['email'] ?? '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout - MloGo</title>


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

        .checkout-page {

            background: #f8f9fa;
            min-height: 75vh;

        }


        .checkout-card {

            background: #fff;
            border: 1px solid #eee;
            border-radius: 18px;
            padding: 25px;

        }


        .checkout-title {

            font-weight: 700;

        }


        .order-type-card {

            border: 2px solid #e5e5e5;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: .2s;

        }


        .order-type-card:hover {

            border-color: #198754;

        }


        .order-type-card.active {

            border-color: #198754;
            background: #f0fff7;

        }


        .order-type-card input {

            display: none;

        }


        .order-type-icon {

            font-size: 35px;

        }


        .summary-item {

            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;

        }


        .place-order-btn {

            padding: 14px;
            font-size: 17px;
            font-weight: 600;

        }


        #deliveryFields {

            display: none;

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
                        class="nav-link"
                        href="cart.php"
                    >

                        Cart

                        <span class="badge bg-success">

                            <?= $cartCount ?>

                        </span>

                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>



<!-- =====================================================
     CHECKOUT
===================================================== -->

<section class="checkout-page py-5">

    <div class="container">


        <div class="mb-4">

            <h1 class="checkout-title">

                Checkout

            </h1>

            <p class="text-muted">

                Complete your order from
                <strong>
                    <?= htmlspecialchars($restaurant['name']) ?>
                </strong>

            </p>

        </div>



        <form
            action="place-order.php"
            method="POST"
            id="checkoutForm"
        >

            <input
                type="hidden"
                name="restaurant_id"
                value="<?= (int)$restaurantId ?>"
            >


            <div class="row g-4">


                <!-- =================================================
                     LEFT SIDE
                ================================================== -->

                <div class="col-lg-8">


                    <!-- ORDER TYPE -->

                    <div class="checkout-card mb-4">

                        <h4 class="mb-4">

                            <i class="bi bi-truck"></i>

                            How would you like to receive your order?

                        </h4>


                        <div class="row g-3">


                            <?php if (
                                !empty($restaurant['pickup_available'])
                            ): ?>


                                <div class="col-md-6">


                                    <label
                                        class="order-type-card active d-block"
                                        id="pickupCard"
                                    >

                                        <input
                                            type="radio"
                                            name="order_type"
                                            value="pickup"
                                            checked
                                            onchange="selectOrderType('pickup')"
                                        >


                                        <div class="order-type-icon">

                                            🥡

                                        </div>


                                        <h5 class="mt-2">

                                            Pickup

                                        </h5>


                                        <p class="text-muted mb-0">

                                            Pick up your order from
                                            the restaurant.

                                        </p>

                                    </label>


                                </div>


                            <?php endif; ?>



                            <?php if (
                                !empty($restaurant['delivery_available'])
                            ): ?>


                                <div class="col-md-6">


                                    <label
                                        class="order-type-card d-block"
                                        id="deliveryCard"
                                    >

                                        <input
                                            type="radio"
                                            name="order_type"
                                            value="delivery"
                                            onchange="selectOrderType('delivery')"
                                        >


                                        <div class="order-type-icon">

                                            🚚

                                        </div>


                                        <h5 class="mt-2">

                                            Delivery

                                        </h5>


                                        <p class="text-muted mb-0">

                                            Get your food delivered
                                            to your location.

                                        </p>

                                    </label>


                                </div>


                            <?php endif; ?>


                        </div>

                    </div>



                    <!-- DELIVERY INFORMATION -->

                    <div
                        class="checkout-card mb-4"
                        id="deliveryFields"
                    >

                        <h4 class="mb-4">

                            <i class="bi bi-geo-alt"></i>

                            Delivery Information

                        </h4>


                        <div class="mb-3">

                            <label class="form-label">

                                Delivery Address

                            </label>


                            <textarea
                                name="delivery_address"
                                id="delivery_address"
                                class="form-control"
                                rows="3"
                                placeholder="Example: Mikocheni B, near Shoppers Plaza"
                            ></textarea>

                        </div>


                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <label class="form-label">

                                    City

                                </label>


                                <input
                                    type="text"
                                    name="delivery_city"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $restaurant['city'] ?? 'Dar es Salaam'
                                    ) ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">

                                    Location

                                </label>


                                <button
                                    type="button"
                                    class="btn btn-outline-success w-100"
                                    onclick="getLocation()"
                                >

                                    <i class="bi bi-geo-alt"></i>

                                    Use My Current Location

                                </button>


                                <input
                                    type="hidden"
                                    name="delivery_latitude"
                                    id="delivery_latitude"
                                >


                                <input
                                    type="hidden"
                                    name="delivery_longitude"
                                    id="delivery_longitude"
                                >


                                <small
                                    class="text-muted"
                                    id="locationStatus"
                                >

                                    Location not selected

                                </small>

                            </div>

                        </div>

                    </div>



                    <!-- CUSTOMER NOTES -->

                    <div class="checkout-card mb-4">

                        <h4 class="mb-4">

                            <i class="bi bi-chat-left-text"></i>

                            Additional Information

                        </h4>


                        <label class="form-label">

                            Order Notes

                        </label>


                        <textarea
                            name="customer_notes"
                            class="form-control"
                            rows="4"
                            placeholder="Example: Please don't add onions..."
                        ></textarea>

                    </div>



                    <!-- PAYMENT -->

                    <div class="checkout-card">

                        <h4 class="mb-4">

                            <i class="bi bi-wallet2"></i>

                            Payment Method

                        </h4>


                        <div class="form-check mb-3">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="payment_method"
                                value="cash"
                                id="cash"
                                checked
                            >


                            <label
                                class="form-check-label"
                                for="cash"
                            >

                                💵 Cash

                                <div class="text-muted small">

                                    Pay when receiving or
                                    picking up your order.

                                </div>

                            </label>

                        </div>


                        <div class="alert alert-info mb-0">

                            <i class="bi bi-info-circle"></i>

                            Online mobile-money payments
                            will be added in the next stage.

                        </div>

                    </div>


                </div>



                <!-- =================================================
                     RIGHT SIDE
                ================================================== -->

                <div class="col-lg-4">


                    <div
                        class="checkout-card"
                        style="position: sticky; top: 100px;"
                    >


                        <h4 class="mb-4">

                            Your Order

                        </h4>


                        <div class="mb-3">

                            <strong>

                                <?= htmlspecialchars(
                                    $restaurant['name']
                                ) ?>

                            </strong>

                        </div>


                        <?php foreach ($cart as $item): ?>


                            <div class="summary-item">

                                <span>

                                    <?= (int)$item['quantity'] ?>
                                    ×
                                    <?= htmlspecialchars(
                                        $item['name']
                                    ) ?>

                                </span>


                                <span>

                                    TZS
                                    <?= number_format(
                                        (float)$item['price']
                                        *
                                        (int)$item['quantity']
                                    ) ?>

                                </span>

                            </div>


                        <?php endforeach; ?>


                        <hr>


                        <div class="summary-item">

                            <span>

                                Subtotal

                            </span>


                            <strong>

                                TZS
                                <?= number_format($subtotal) ?>

                            </strong>

                        </div>


                        <div
                            class="summary-item"
                            id="deliveryFeeRow"
                            style="display:none;"
                        >

                            <span>

                                Delivery

                            </span>


                            <strong id="deliveryFee">

                                TZS 0

                            </strong>

                        </div>


                        <hr>


                        <div class="summary-item fs-5">

                            <strong>

                                Total

                            </strong>


                            <strong
                                class="text-success"
                                id="grandTotal"
                            >

                                TZS
                                <?= number_format($total) ?>

                            </strong>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-success w-100 place-order-btn mt-3"
                        >

                            <i class="bi bi-check-circle"></i>

                            Place Order

                        </button>


                        <a
                            href="cart.php"
                            class="btn btn-outline-secondary w-100 mt-2"
                        >

                            Back to Cart

                        </a>


                    </div>


                </div>


            </div>

        </form>

    </div>

</section>



<script>

const subtotal =
    <?= json_encode($subtotal) ?>;

const restaurantDeliveryFee =
    <?= json_encode(
        (float)($restaurant['delivery_fee'] ?? 0)
    ) ?>;


// =====================================================
// ORDER TYPE
// =====================================================

function selectOrderType(type) {


    const pickupCard =
        document.getElementById('pickupCard');

    const deliveryCard =
        document.getElementById('deliveryCard');

    const deliveryFields =
        document.getElementById('deliveryFields');

    const deliveryFeeRow =
        document.getElementById('deliveryFeeRow');

    const deliveryFee =
        document.getElementById('deliveryFee');

    const grandTotal =
        document.getElementById('grandTotal');


    if (type === 'delivery') {


        if (deliveryCard) {

            deliveryCard.classList.add('active');

        }


        if (pickupCard) {

            pickupCard.classList.remove('active');

        }


        deliveryFields.style.display =
            'block';


        deliveryFeeRow.style.display =
            'flex';


        deliveryFee.innerText =
            'TZS ' +
            numberFormat(restaurantDeliveryFee);


        grandTotal.innerText =
            'TZS ' +
            numberFormat(
                subtotal +
                restaurantDeliveryFee
            );


    } else {


        if (pickupCard) {

            pickupCard.classList.add('active');

        }


        if (deliveryCard) {

            deliveryCard.classList.remove('active');

        }


        deliveryFields.style.display =
            'none';


        deliveryFeeRow.style.display =
            'none';


        grandTotal.innerText =
            'TZS ' +
            numberFormat(subtotal);

    }

}



// =====================================================
// NUMBER FORMAT
// =====================================================

function numberFormat(number) {

    return new Intl.NumberFormat(
        'en-US'
    ).format(number);

}



// =====================================================
// GET LOCATION
// =====================================================

function getLocation() {


    const status =
        document.getElementById(
            'locationStatus'
        );


    if (!navigator.geolocation) {

        status.innerText =
            'Geolocation is not supported by your browser.';

        return;

    }


    status.innerText =
        'Getting your location...';


    navigator.geolocation.getCurrentPosition(

        function(position) {


            const latitude =
                position.coords.latitude;


            const longitude =
                position.coords.longitude;


            document.getElementById(
                'delivery_latitude'
            ).value = latitude;


            document.getElementById(
                'delivery_longitude'
            ).value = longitude;


            status.innerText =
                '✓ Location captured successfully';


        },


        function(error) {


            status.innerText =
                'Unable to get your location. Please enter your address manually.';

        }

    );

}



// =====================================================
// FORM VALIDATION
// =====================================================

document
    .getElementById('checkoutForm')
    .addEventListener(
        'submit',
        function(event) {


            const orderType =
                document.querySelector(
                    'input[name="order_type"]:checked'
                );


            if (!orderType) {

                event.preventDefault();

                alert(
                    'Please select Pickup or Delivery.'
                );

                return;

            }


            if (
                orderType.value === 'delivery'
            ) {


                const address =
                    document
                        .getElementById(
                            'delivery_address'
                        )
                        .value
                        .trim();


                if (!address) {

                    event.preventDefault();

                    alert(
                        'Please enter your delivery address.'
                    );

                    return;

                }

            }

        }
    );

</script>


</body>

</html>