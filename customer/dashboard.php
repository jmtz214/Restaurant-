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


$customerId = (int) $_SESSION['user_id'];

$firstName = $_SESSION['first_name'] ?? 'Customer';


// =====================================================
// GET CUSTOMER ORDERS
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.restaurant_id,
        o.order_type,
        o.status,
        o.total_amount,
        o.placed_at,
        o.created_at,

        r.name AS restaurant_name

    FROM orders o

    INNER JOIN restaurants r
        ON o.restaurant_id = r.id

    WHERE o.customer_id = ?

    ORDER BY o.created_at DESC

    LIMIT 5
");

$stmt->execute([
    $customerId
]);

$recentOrders = $stmt->fetchAll();


// =====================================================
// COUNT ORDERS
// =====================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE customer_id = ?
");

$stmt->execute([
    $customerId
]);

$totalOrders = (int) $stmt->fetchColumn();


// =====================================================
// ACTIVE ORDER
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.status,
        o.total_amount,
        o.order_type,

        r.name AS restaurant_name

    FROM orders o

    INNER JOIN restaurants r
        ON o.restaurant_id = r.id

    WHERE o.customer_id = ?

    AND o.status IN (
        'pending',
        'accepted',
        'preparing',
        'ready',
        'out_for_delivery'
    )

    ORDER BY o.created_at DESC

    LIMIT 1
");

$stmt->execute([
    $customerId
]);

$activeOrder = $stmt->fetch();

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
        Customer Dashboard - MloGo
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
                0 2px 10px
                rgba(0,0,0,.06);

        }


        .brand {

            font-size: 28px;

            font-weight: 800;

            text-decoration: none;

            color: #17202a;

        }


        .brand span {

            color: #20c997;

        }


        .hero {

            background:
                linear-gradient(
                    135deg,
                    #20c997,
                    #159f7a
                );

            color: white;

            border-radius: 22px;

            padding: 35px;

            margin-top: 30px;

            position: relative;

            overflow: hidden;

        }


        .hero::after {

            content: "🍛";

            position: absolute;

            right: 40px;

            bottom: -20px;

            font-size: 130px;

            opacity: .18;

        }


        .hero h1 {

            font-weight: 800;

        }


        .search-box {

            background: white;

            border-radius: 15px;

            padding: 8px;

            margin-top: 25px;

            max-width: 600px;

            display: flex;

        }


        .search-box input {

            border: none;

            outline: none;

            flex: 1;

            padding: 12px;

        }


        .section-title {

            font-weight: 800;

            margin-bottom: 20px;

        }


        .feature-card {

            background: white;

            border-radius: 18px;

            padding: 25px;

            height: 100%;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

            transition: .2s;

        }


        .feature-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 10px 30px
                rgba(0,0,0,.08);

        }


        .feature-icon {

            width: 52px;

            height: 52px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e8f8f3;

            color: #20c997;

            font-size: 24px;

            margin-bottom: 15px;

        }


        .active-order {

            background: white;

            border-radius: 18px;

            padding: 25px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

        }


        .status {

            padding: 7px 13px;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 700;

        }


        .pending {

            background: #fff3cd;

            color: #856404;

        }


        .accepted,
        .preparing {

            background: #cff4fc;

            color: #055160;

        }


        .ready {

            background: #d1e7dd;

            color: #0f5132;

        }


        .out_for_delivery {

            background: #e2d9f3;

            color: #59359a;

        }


        .order-row {

            background: white;

            border-radius: 15px;

            padding: 18px;

            margin-bottom: 12px;

            box-shadow:
                0 3px 15px
                rgba(0,0,0,.03);

        }


        .empty-state {

            background: white;

            border-radius: 18px;

            padding: 45px 20px;

            text-align: center;

        }


        footer {

            background: #17202a;

            color: #adb5bd;

            margin-top: 60px;

            padding: 35px 0;

        }


        footer strong {

            color: white;

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg">

    <div class="container">

        <a
            href="dashboard.php"
            class="brand"
        >

            Mlo<span>Go</span>

        </a>


        <div class="d-flex align-items-center gap-2">


            <a
                href="../restaurants.php"
                class="btn btn-success btn-sm"
            >

                <i class="bi bi-shop"></i>

                Restaurants

            </a>


            <a
                href="orders.php"
                class="btn btn-outline-secondary btn-sm"
            >

                <i class="bi bi-receipt"></i>

                My Orders

            </a>


            <a
                href="../logout.php"
                class="btn btn-outline-danger btn-sm"
            >

                <i class="bi bi-box-arrow-right"></i>

                Logout

            </a>

        </div>

    </div>

</nav>



<div class="container">


    <!-- =================================================
         HERO
    ================================================== -->

    <section class="hero">


        <h1>

            Welcome back,
            <?= htmlspecialchars($firstName) ?>! 👋

        </h1>


        <p class="mb-0">

            What delicious Tanzanian food are you craving today?

        </p>


        <form
            action="../restaurants.php"
            method="GET"
            class="search-box"
        >

            <input
                type="text"
                name="search"
                placeholder="Search for restaurants or food..."
            >


            <button
                type="submit"
                class="btn btn-success"
            >

                <i class="bi bi-search"></i>

                Search

            </button>

        </form>


    </section>



    <!-- =================================================
         QUICK ACTIONS
    ================================================== -->

    <section class="mt-5">


        <h3 class="section-title">

            What would you like to do?

        </h3>


        <div class="row g-4">


            <div class="col-md-4">

                <a
                    href="../restaurants.php"
                    class="text-decoration-none text-dark"
                >

                    <div class="feature-card">

                        <div class="feature-icon">

                            <i class="bi bi-shop"></i>

                        </div>


                        <h5 class="fw-bold">

                            Browse Restaurants

                        </h5>


                        <p class="text-muted mb-0">

                            Discover restaurants and explore
                            their delicious menus.

                        </p>

                    </div>

                </a>

            </div>



            <div class="col-md-4">

                <a
                    href="orders.php"
                    class="text-decoration-none text-dark"
                >

                    <div class="feature-card">

                        <div class="feature-icon">

                            <i class="bi bi-receipt"></i>

                        </div>


                        <h5 class="fw-bold">

                            My Orders

                        </h5>


                        <p class="text-muted mb-0">

                            View your previous orders and track
                            your current orders.

                        </p>

                    </div>

                </a>

            </div>



            <div class="col-md-4">

                <a
                    href="../restaurants.php"
                    class="text-decoration-none text-dark"
                >

                    <div class="feature-card">

                        <div class="feature-icon">

                            <i class="bi bi-basket"></i>

                        </div>


                        <h5 class="fw-bold">

                            Order Food

                        </h5>


                        <p class="text-muted mb-0">

                            Choose your favourite Tanzanian meals
                            and place an order.

                        </p>

                    </div>

                </a>

            </div>


        </div>

    </section>



    <!-- =================================================
         ACTIVE ORDER
    ================================================== -->

    <?php if ($activeOrder): ?>


        <section class="mt-5">


            <div class="d-flex justify-content-between align-items-center mb-3">


                <h3 class="section-title mb-0">

                    Your Active Order

                </h3>


                <a
                    href="orders.php"
                    class="text-success text-decoration-none"
                >

                    View all

                    <i class="bi bi-arrow-right"></i>

                </a>


            </div>


            <div class="active-order">


                <div class="row align-items-center">


                    <div class="col-md-4">


                        <small class="text-muted">

                            Order Number

                        </small>


                        <h5 class="fw-bold">

                            #<?= htmlspecialchars(
                                $activeOrder[
                                    'order_number'
                                ]
                            ) ?>

                        </h5>


                        <div class="text-muted">

                            <?= htmlspecialchars(
                                $activeOrder[
                                    'restaurant_name'
                                ]
                            ) ?>

                        </div>

                    </div>



                    <div class="col-md-3 mt-3 mt-md-0">


                        <small class="text-muted d-block">

                            Status

                        </small>


                        <span
                            class="status <?= htmlspecialchars(
                                $activeOrder['status']
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $activeOrder[
                                            'status'
                                        ]
                                    )
                                )
                            ) ?>

                        </span>


                    </div>



                    <div class="col-md-2 mt-3 mt-md-0">


                        <small class="text-muted d-block">

                            Type

                        </small>


                        <strong>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $activeOrder[
                                        'order_type'
                                    ]
                                )
                            ) ?>

                        </strong>

                    </div>



                    <div class="col-md-3 mt-3 mt-md-0 text-md-end">


                        <a
                            href="order-details.php?id=<?= (int) $activeOrder['id'] ?>"
                            class="btn btn-success"
                        >

                            Track Order

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    </div>


                </div>


            </div>


        </section>


    <?php endif; ?>



    <!-- =================================================
         RECENT ORDERS
    ================================================== -->

    <section class="mt-5">


        <div class="d-flex justify-content-between align-items-center mb-3">


            <div>

                <h3 class="section-title mb-1">

                    Recent Orders

                </h3>


                <p class="text-muted mb-0">

                    You have placed
                    <strong>
                        <?= $totalOrders ?>
                    </strong>
                    order(s).

                </p>

            </div>


            <?php if ($totalOrders > 0): ?>

                <a
                    href="orders.php"
                    class="text-success text-decoration-none"
                >

                    View all

                    <i class="bi bi-arrow-right"></i>

                </a>

            <?php endif; ?>


        </div>



        <?php if (empty($recentOrders)): ?>


            <div class="empty-state">


                <i
                    class="bi bi-basket fs-1 text-muted"
                ></i>


                <h4 class="mt-3">

                    No orders yet

                </h4>


                <p class="text-muted">

                    Your delicious food journey starts here.

                </p>


                <a
                    href="../restaurants.php"
                    class="btn btn-success"
                >

                    Browse Restaurants

                </a>


            </div>


        <?php else: ?>


            <?php foreach (
                $recentOrders
                as $order
            ): ?>


                <div class="order-row">


                    <div class="row align-items-center">


                        <div class="col-md-4">


                            <strong>

                                #<?= htmlspecialchars(
                                    $order[
                                        'order_number'
                                    ]
                                ) ?>

                            </strong>


                            <div class="text-muted">

                                <?= htmlspecialchars(
                                    $order[
                                        'restaurant_name'
                                    ]
                                ) ?>

                            </div>


                        </div>



                        <div class="col-md-2 mt-2 mt-md-0">


                            <small class="text-muted d-block">

                                Type

                            </small>


                            <?= htmlspecialchars(
                                ucfirst(
                                    $order[
                                        'order_type'
                                    ]
                                )
                            ) ?>


                        </div>



                        <div class="col-md-2 mt-2 mt-md-0">


                            <small class="text-muted d-block">

                                Total

                            </small>


                            <strong>

                                TZS
                                <?= number_format(
                                    (float)
                                    $order[
                                        'total_amount'
                                    ]
                                ) ?>

                            </strong>


                        </div>



                        <div class="col-md-2 mt-2 mt-md-0">


                            <span
                                class="status <?= htmlspecialchars(
                                    $order['status']
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $order[
                                                'status'
                                            ]
                                        )
                                    )
                                ) ?>

                            </span>


                        </div>



                        <div class="col-md-2 mt-2 mt-md-0 text-md-end">


                            <a
                                href="order-details.php?id=<?= (int) $order['id'] ?>"
                                class="btn btn-outline-success btn-sm"
                            >

                                View

                            </a>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </section>


</div>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div class="container">


        <div class="row">


            <div class="col-md-6">


                <h4>

                    <strong>MloGo</strong>

                </h4>


                <p class="mb-0">

                    Your home for delicious Tanzanian food.

                    Discover restaurants, order your favourite
                    meals and enjoy convenient pickup or delivery.

                </p>


            </div>


            <div class="col-md-6 text-md-end mt-3 mt-md-0">


                <p class="mb-1">

                    <i class="bi bi-telephone"></i>

                    Customer Support

                </p>


                <p class="mb-0">

                    © <?= date('Y') ?> MloGo

                </p>


            </div>


        </div>


    </div>

</footer>



</body>

</html>