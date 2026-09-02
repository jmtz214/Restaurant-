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
// ORDER ID
// =====================================================

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($orderId <= 0) {
    die("Invalid order ID.");
}


// =====================================================
// GET RESTAURANT
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM restaurants
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$restaurant = $stmt->fetch();


if (!$restaurant) {
    die("Restaurant not found.");
}


$restaurantId = (int) $restaurant['id'];


// =====================================================
// GET ORDER
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        o.*,

        u.first_name,
        u.last_name,
        u.email,
        u.phone

    FROM orders o

    INNER JOIN users u
        ON o.customer_id = u.id

    WHERE o.id = ?
    AND o.restaurant_id = ?

    LIMIT 1
");

$stmt->execute([
    $orderId,
    $restaurantId
]);

$order = $stmt->fetch();


if (!$order) {
    die("Order not found.");
}


// =====================================================
// GET ORDER ITEMS
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        oi.id,
        oi.menu_item_id,
        oi.item_name,
        oi.unit_price,
        oi.quantity,
        oi.subtotal,
        oi.special_instructions

    FROM order_items oi

    WHERE oi.order_id = ?

    ORDER BY oi.id ASC
");

$stmt->execute([$orderId]);

$orderItems = $stmt->fetchAll();


$adminName =
    trim(
        ($_SESSION['first_name'] ?? '')
        . ' '
        . ($_SESSION['last_name'] ?? '')
    );


// =====================================================
// STATUS DISPLAY
// =====================================================

$status = $order['status'];

$statusLabel = ucwords(
    str_replace(
        '_',
        ' ',
        $status
    )
);

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
        Order #<?= htmlspecialchars($order['order_number']) ?>
        | MloGo
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

            background: #f5f7fa;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        /* SIDEBAR */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            bottom: 0;

            width: 250px;

            background: #17202a;

            color: white;

            padding: 25px 15px;

            z-index: 1000;

        }


        .brand {

            font-size: 27px;

            font-weight: 800;

            text-decoration: none;

            color: white;

            padding-left: 15px;

            display: block;

            margin-bottom: 35px;

        }


        .brand span {

            color: #20c997;

        }


        .restaurant-name {

            background: rgba(
                255,
                255,
                255,
                .08
            );

            border-radius: 12px;

            padding: 15px;

            margin-bottom: 25px;

        }


        .restaurant-name small {

            color: #adb5bd;

        }


        .sidebar-menu {

            list-style: none;

            padding: 0;

            margin: 0;

        }


        .sidebar-menu li {

            margin-bottom: 7px;

        }


        .sidebar-menu a {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 15px;

            color: #ced4da;

            text-decoration: none;

            border-radius: 10px;

        }


        .sidebar-menu a:hover,
        .sidebar-menu a.active {

            background: #20c997;

            color: white;

        }


        .main {

            margin-left: 250px;

            min-height: 100vh;

        }


        /* TOPBAR */

        .topbar {

            background: white;

            padding: 18px 30px;

            border-bottom: 1px solid #e9ecef;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .topbar-title {

            font-size: 21px;

            font-weight: 700;

        }


        .admin-profile {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .profile-circle {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            background: #20c997;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

        }


        .content {

            padding: 30px;

        }


        /* CARDS */

        .card-box {

            background: white;

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.04);

            margin-bottom: 20px;

        }


        /* STATUS */

        .status-badge {

            padding: 8px 14px;

            border-radius: 30px;

            font-size: 13px;

            font-weight: 700;

        }


        .status-pending {

            background: #fff3cd;

            color: #856404;

        }


        .status-accepted {

            background: #cff4fc;

            color: #055160;

        }


        .status-preparing {

            background: #cfe2ff;

            color: #084298;

        }


        .status-ready {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-out_for_delivery {

            background: #e2d9f3;

            color: #59359a;

        }


        .status-completed {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-denied,
        .status-cancelled {

            background: #f8d7da;

            color: #842029;

        }


        /* ORDER ITEM */

        .order-item {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 18px 0;

            border-bottom: 1px solid #eee;

        }


        .order-item:last-child {

            border-bottom: none;

        }


        .item-name {

            font-weight: 700;

        }


        .item-details {

            color: #6c757d;

            font-size: 14px;

        }


        .item-price {

            font-weight: 700;

        }


        /* SUMMARY */

        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 8px 0;

        }


        .summary-total {

            font-size: 22px;

            font-weight: 800;

            border-top: 2px solid #eee;

            padding-top: 15px;

            margin-top: 10px;

        }


        /* CUSTOMER */

        .info-row {

            display: flex;

            gap: 12px;

            margin-bottom: 15px;

        }


        .info-icon {

            width: 38px;

            height: 38px;

            border-radius: 10px;

            background: #e9f8f3;

            color: #20c997;

            display: flex;

            justify-content: center;

            align-items: center;

        }


        /* ACTIONS */

        .action-box {

            background: #f8f9fa;

            border-radius: 14px;

            padding: 20px;

        }


        /* MOBILE */

        @media(max-width: 991px) {

            .sidebar {

                width: 75px;

            }


            .brand {

                font-size: 0;

                padding: 0;

                text-align: center;

            }


            .brand span {

                font-size: 25px;

            }


            .restaurant-name,
            .sidebar-menu span {

                display: none;

            }


            .sidebar-menu a {

                justify-content: center;

            }


            .main {

                margin-left: 75px;

            }

        }


        @media(max-width: 576px) {

            .content {

                padding: 15px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <a
        href="../index.php"
        class="brand"
    >

        Mlo<span>Go</span>

    </a>


    <div class="restaurant-name">

        <small>
            Restaurant
        </small>

        <div class="fw-bold mt-1">

            <?= htmlspecialchars(
                $restaurant['name']
            ) ?>

        </div>

    </div>


    <ul class="sidebar-menu">


        <li>

            <a href="dashboard.php">

                <i class="bi bi-grid-1x2-fill"></i>

                <span>
                    Dashboard
                </span>

            </a>

        </li>


        <li>

            <a
                href="orders.php"
                class="active"
            >

                <i class="bi bi-receipt"></i>

                <span>
                    Orders
                </span>

            </a>

        </li>


        <li>

            <a href="menu.php">

                <i class="bi bi-egg-fried"></i>

                <span>
                    Menu
                </span>

            </a>

        </li>


        <li>

            <a href="analytics.php">

                <i class="bi bi-bar-chart-fill"></i>

                <span>
                    Analytics
                </span>

            </a>

        </li>


        <li>

            <a href="restaurant-settings.php">

                <i class="bi bi-shop"></i>

                <span>
                    Restaurant Settings
                </span>

            </a>

        </li>


        <li class="mt-4">

            <a href="orders.php">

                <i class="bi bi-arrow-left"></i>

                <span>
                    Back to Orders
                </span>

            </a>

        </li>


        <li>

            <a href="../logout.php">

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Logout
                </span>

            </a>

        </li>

    </ul>

</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div>

            <div class="topbar-title">

                Order Details

            </div>

            <small class="text-muted">

                #<?= htmlspecialchars(
                    $order['order_number']
                ) ?>

            </small>

        </div>


        <div class="admin-profile">

            <div class="text-end d-none d-sm-block">

                <div class="fw-semibold">

                    <?= htmlspecialchars(
                        $adminName
                    ) ?>

                </div>

                <small class="text-muted">

                    Restaurant Admin

                </small>

            </div>


            <div class="profile-circle">

                <?= strtoupper(
                    substr(
                        $_SESSION['first_name']
                        ?? 'A',
                        0,
                        1
                    )
                ) ?>

            </div>

        </div>

    </header>



    <!-- CONTENT -->

    <div class="content">
<?php if (!empty($_GET['success'])): ?>

    <div class="alert alert-success alert-dismissible fade show">

        <i class="bi bi-check-circle-fill"></i>

        <?= htmlspecialchars($_GET['success']) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>

        <!-- PAGE HEADER -->

        <div class="d-flex justify-content-between align-items-center mb-4">


            <div>

                <h3 class="fw-bold mb-1">

                    Order
                    #<?= htmlspecialchars(
                        $order['order_number']
                    ) ?>

                </h3>

                <div class="text-muted">

                    Placed

                    <?= date(
                        'd M Y, H:i',
                        strtotime(
                            $order['placed_at']
                            ?? $order['created_at']
                        )
                    ) ?>

                </div>

            </div>


            <span
                class="status-badge status-<?= htmlspecialchars($status) ?>"
            >

                <?= htmlspecialchars(
                    $statusLabel
                ) ?>

            </span>


        </div>



        <div class="row g-4">


            <!-- =================================================
                 LEFT
            ================================================== -->

            <div class="col-lg-8">


                <!-- CUSTOMER -->

                <div class="card-box">


                    <h5 class="fw-bold mb-4">

                        <i class="bi bi-person-circle"></i>

                        Customer Information

                    </h5>


                    <div class="row">


                        <div class="col-md-6">


                            <div class="info-row">

                                <div class="info-icon">

                                    <i class="bi bi-person"></i>

                                </div>

                                <div>

                                    <small class="text-muted">
                                        Customer
                                    </small>

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $order[
                                                'first_name'
                                            ]
                                            . ' '
                                            . $order[
                                                'last_name'
                                            ]
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                        </div>


                        <div class="col-md-6">


                            <div class="info-row">

                                <div class="info-icon">

                                    <i class="bi bi-telephone"></i>

                                </div>

                                <div>

                                    <small class="text-muted">
                                        Phone
                                    </small>

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $order['phone']
                                            ?? 'Not provided'
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                        </div>


                        <div class="col-md-6">


                            <div class="info-row">

                                <div class="info-icon">

                                    <i class="bi bi-envelope"></i>

                                </div>

                                <div>

                                    <small class="text-muted">
                                        Email
                                    </small>

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $order['email']
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                        </div>


                        <div class="col-md-6">


                            <div class="info-row">

                                <div class="info-icon">

                                    <?php if (
                                        $order[
                                            'order_type'
                                        ]
                                        === 'delivery'
                                    ): ?>

                                        <i class="bi bi-truck"></i>

                                    <?php else: ?>

                                        <i class="bi bi-bag"></i>

                                    <?php endif; ?>

                                </div>

                                <div>

                                    <small class="text-muted">
                                        Order Type
                                    </small>

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $order[
                                                    'order_type'
                                                ]
                                            )
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                        </div>


                    </div>


                    <?php if (
                        $order['order_type']
                        === 'delivery'
                    ): ?>


                        <hr>


                        <div class="info-row mb-0">

                            <div class="info-icon">

                                <i class="bi bi-geo-alt"></i>

                            </div>

                            <div>

                                <small class="text-muted">
                                    Delivery Address
                                </small>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $order[
                                            'delivery_address'
                                        ]
                                        ?? 'Address not provided'
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $order[
                                            'delivery_city'
                                        ]
                                    )
                                ): ?>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $order[
                                                'delivery_city'
                                            ]
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </div>

                        </div>


                    <?php endif; ?>


                </div>



                <!-- ORDER ITEMS -->

                <div class="card-box">


                    <h5 class="fw-bold mb-3">

                        <i class="bi bi-basket"></i>

                        Ordered Items

                    </h5>


                    <?php if (
                        empty($orderItems)
                    ): ?>


                        <div class="text-muted">

                            No items found for this order.

                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $orderItems
                            as $item
                        ): ?>


                            <div class="order-item">


                                <div>

                                    <div class="item-name">

                                        <?= htmlspecialchars(
                                            $item[
                                                'item_name'
                                            ]
                                        ) ?>

                                    </div>


                                    <div class="item-details">

                                        TZS
                                        <?= number_format(
                                            (float)
                                            $item[
                                                'unit_price'
                                            ]
                                        ) ?>

                                        ×

                                        <?= (int)
                                            $item[
                                                'quantity'
                                            ] ?>

                                    </div>


                                    <?php if (
                                        !empty(
                                            $item[
                                                'special_instructions'
                                            ]
                                        )
                                    ): ?>

                                        <div
                                            class="small text-muted mt-1"
                                        >

                                            <i class="bi bi-chat"></i>

                                            <?= htmlspecialchars(
                                                $item[
                                                    'special_instructions'
                                                ]
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                </div>


                                <div class="item-price">

                                    TZS
                                    <?= number_format(
                                        (float)
                                        $item[
                                            'subtotal'
                                        ]
                                    ) ?>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>



                <!-- CUSTOMER NOTES -->

                <?php if (
                    !empty(
                        $order[
                            'customer_notes'
                        ]
                    )
                ): ?>


                    <div class="card-box">


                        <h5 class="fw-bold">

                            <i class="bi bi-chat-left-text"></i>

                            Customer Notes

                        </h5>


                        <p class="mb-0 text-muted">

                            <?= nl2br(
                                htmlspecialchars(
                                    $order[
                                        'customer_notes'
                                    ]
                                )
                            ) ?>

                        </p>


                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 RIGHT
            ================================================== -->

            <div class="col-lg-4">


                <!-- SUMMARY -->

                <div class="card-box">


                    <h5 class="fw-bold mb-4">

                        Order Summary

                    </h5>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong>

                            TZS
                            <?= number_format(
                                (float)
                                $order[
                                    'subtotal'
                                ]
                            ) ?>

                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Delivery Fee
                        </span>

                        <strong>

                            TZS
                            <?= number_format(
                                (float)
                                $order[
                                    'delivery_fee'
                                ]
                            ) ?>

                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Discount
                        </span>

                        <strong>

                            - TZS
                            <?= number_format(
                                (float)
                                $order[
                                    'discount_amount'
                                ]
                            ) ?>

                        </strong>

                    </div>


                    <div class="summary-row summary-total">

                        <span>
                            Total
                        </span>

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


                </div>



                <!-- PAYMENT -->

                <div class="card-box">


                    <h5 class="fw-bold mb-3">

                        Payment

                    </h5>


                    <div class="d-flex justify-content-between mb-2">

                        <span class="text-muted">
                            Method
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $order[
                                        'payment_method'
                                    ]
                                )
                            ) ?>

                        </strong>

                    </div>


                    <div class="d-flex justify-content-between">

                        <span class="text-muted">
                            Status
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $order[
                                        'payment_status'
                                    ]
                                )
                            ) ?>

                        </strong>

                    </div>


                </div>



                <!-- ACTIONS -->

                <div class="card-box">


                    <h5 class="fw-bold mb-3">

                        Order Actions

                    </h5>


                    <div class="action-box">


                        <?php if (
                            $status === 'pending'
                        ): ?>


                            <form
                                method="POST"
                                action="update-order.php"
                                class="mb-2"
                            >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= $orderId ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="accept"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success w-100"
                                >

                                    <i class="bi bi-check-circle"></i>

                                    Accept Order

                                </button>

                            </form>


                       <button
    type="button"
    class="btn btn-outline-danger w-100"
    data-bs-toggle="modal"
    data-bs-target="#denyOrderModal"
>

    <i class="bi bi-x-circle"></i>

    Deny Order

</button>


                        <?php elseif (
                            $status === 'accepted'
                        ): ?>


                            <form
                                method="POST"
                                action="update-order.php"
                            >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= $orderId ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="preparing"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100"
                                >

                                    <i class="bi bi-fire"></i>

                                    Start Preparing

                                </button>

                            </form>


                        <?php elseif (
                            $status === 'preparing'
                        ): ?>


                            <form
                                method="POST"
                                action="update-order.php"
                            >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= $orderId ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="ready"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success w-100"
                                >

                                    <i class="bi bi-check2-circle"></i>

                                    Mark as Ready

                                </button>

                            </form>


                        <?php elseif (
                            $status === 'ready'
                        ): ?>


                            <?php if (
                                $order[
                                    'order_type'
                                ]
                                === 'delivery'
                            ): ?>


                                <form
                                    method="POST"
                                    action="update-order.php"
                                >

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= $orderId ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="out_for_delivery"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-primary w-100"
                                    >

                                        <i class="bi bi-truck"></i>

                                        Out for Delivery

                                    </button>

                                </form>


                            <?php else: ?>


                                <form
                                    method="POST"
                                    action="update-order.php"
                                >

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= $orderId ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="completed"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-success w-100"
                                    >

                                        <i class="bi bi-check-circle"></i>

                                        Complete Pickup

                                    </button>

                                </form>


                            <?php endif; ?>


                        <?php elseif (
                            $status === 'out_for_delivery'
                        ): ?>


                            <form
                                method="POST"
                                action="update-order.php"
                            >

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= $orderId ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="completed"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success w-100"
                                >

                                    <i class="bi bi-check-circle"></i>

                                    Mark as Completed

                                </button>

                            </form>


                        <?php elseif (
                            $status === 'completed'
                        ): ?>


                            <div
                                class="alert alert-success mb-0"
                            >

                                <i class="bi bi-check-circle"></i>

                                This order has been completed.

                            </div>


                        <?php elseif (
                            $status === 'denied'
                        ): ?>


                            <div
                                class="alert alert-danger mb-0"
                            >

                                <i class="bi bi-x-circle"></i>

                                This order was denied.

                                <?php if (
                                    !empty(
                                        $order[
                                            'denial_reason'
                                        ]
                                    )
                                ): ?>

                                    <hr>

                                    <strong>
                                        Reason:
                                    </strong>

                                    <br>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $order[
                                                'denial_reason'
                                            ]
                                        )
                                    ) ?>

                                <?php endif; ?>

                            </div>


                        <?php elseif (
                            $status === 'cancelled'
                        ): ?>


                            <div
                                class="alert alert-secondary mb-0"
                            >

                                <i class="bi bi-info-circle"></i>

                                This order has been cancelled.

                            </div>


                        <?php endif; ?>


                    </div>


                </div>



                <!-- PREPARATION TIME -->

                <?php if (
                    !empty(
                        $order[
                            'estimated_preparation_minutes'
                        ]
                    )
                ): ?>


                    <div class="card-box">


                        <h6 class="fw-bold">

                            <i class="bi bi-clock"></i>

                            Estimated Preparation

                        </h6>


                        <div class="display-6 fw-bold">

                            <?= (int)
                                $order[
                                    'estimated_preparation_minutes'
                                ] ?>

                            <small class="fs-6">

                                minutes

                            </small>

                        </div>


                    </div>


                <?php endif; ?>


            </div>


        </div>


    </div>

</main>


<!-- =====================================================
     DENY ORDER MODAL
===================================================== -->

<div
    class="modal fade"
    id="denyOrderModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title fw-bold">

                    <i class="bi bi-exclamation-triangle text-danger"></i>

                    Deny Order

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>



            <form
                method="POST"
                action="update-order.php"
            >


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= $orderId ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="deny"
                    >


                    <p class="text-muted">

                        Are you sure you want to deny this order?

                        Please provide a reason so the customer
                        understands why the order could not be accepted.

                    </p>


                    <div class="mb-3">

                        <label
                            for="denial_reason"
                            class="form-label fw-semibold"
                        >

                            Reason for denial

                        </label>


                        <select
                            class="form-select mb-3"
                            id="denial_reason_select"
                        >

                            <option value="">

                                Select a reason

                            </option>

                            <option value="Food is currently unavailable">

                                Food is currently unavailable

                            </option>

                            <option value="Restaurant is currently too busy">

                                Restaurant is currently too busy

                            </option>

                            <option value="Restaurant is temporarily closed">

                                Restaurant is temporarily closed

                            </option>

                            <option value="Delivery is currently unavailable">

                                Delivery is currently unavailable

                            </option>

                            <option value="Other">

                                Other

                            </option>

                        </select>


                        <textarea
                            name="denial_reason"
                            id="denial_reason"
                            class="form-control"
                            rows="4"
                            placeholder="Enter the reason..."
                            required
                        ></textarea>


                    </div>


                </div>



                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-danger"
                    >

                        <i class="bi bi-x-circle"></i>

                        Deny Order

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>

<script>

document
    .getElementById('denial_reason_select')
    .addEventListener('change', function () {

        const reason = this.value;

        const textarea =
            document.getElementById('denial_reason');


        if (reason === 'Other') {

            textarea.value = '';

            textarea.focus();

        } else {

            textarea.value = reason;

        }

    });

</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>