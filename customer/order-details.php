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


if ($_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}


$customerId = (int) $_SESSION['user_id'];


// =====================================================
// GET ORDER ID
// =====================================================

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($orderId <= 0) {
    die("Invalid order ID.");
}


// =====================================================
// GET ORDER
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        o.*,

        r.name AS restaurant_name,
        r.logo AS restaurant_logo,
        r.phone AS restaurant_phone,
        r.address AS restaurant_address,
        r.city AS restaurant_city

    FROM orders o

    INNER JOIN restaurants r
        ON o.restaurant_id = r.id

    WHERE o.id = ?
    AND o.customer_id = ?

    LIMIT 1
");

$stmt->execute([
    $orderId,
    $customerId
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
        id,
        menu_item_id,
        item_name,
        unit_price,
        quantity,
        subtotal,
        special_instructions

    FROM order_items

    WHERE order_id = ?

    ORDER BY id ASC
");

$stmt->execute([
    $orderId
]);

$orderItems = $stmt->fetchAll();


// =====================================================
// STATUS
// =====================================================

$status = $order['status'];

$statusLabel = ucwords(
    str_replace(
        '_',
        ' ',
        $status
    )
);


// =====================================================
// STATUS TIMELINE
// =====================================================

$statusSteps = [
    'pending' => [
        'title' => 'Order Placed',
        'description' => 'Your order has been sent to the restaurant.',
        'icon' => 'bi-receipt'
    ],

    'accepted' => [
        'title' => 'Order Accepted',
        'description' => 'The restaurant has accepted your order.',
        'icon' => 'bi-check-circle'
    ],

    'preparing' => [
        'title' => 'Preparing Your Food',
        'description' => 'The restaurant is preparing your food.',
        'icon' => 'bi-fire'
    ],

    'ready' => [
        'title' => 'Order Ready',
        'description' => 'Your food is ready for pickup or delivery.',
        'icon' => 'bi-check2-circle'
    ],

    'out_for_delivery' => [
        'title' => 'Out for Delivery',
        'description' => 'Your order is on its way to you.',
        'icon' => 'bi-truck'
    ],

    'completed' => [
        'title' => 'Order Completed',
        'description' => 'Your order has been completed successfully.',
        'icon' => 'bi-house-check'
    ]
];


// =====================================================
// STATUS ORDER
// =====================================================

$statusOrder = [
    'pending' => 1,
    'accepted' => 2,
    'preparing' => 3,
    'ready' => 4,
    'out_for_delivery' => 5,
    'completed' => 6
];

$currentStep =
    $statusOrder[$status]
    ?? 0;

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
        - MloGo
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
                rgba(0,0,0,.05);

        }


        .brand {

            font-size: 27px;

            font-weight: 800;

            text-decoration: none;

            color: #17202a;

        }


        .brand span {

            color: #20c997;

        }


        .page {

            max-width: 1100px;

            margin: auto;

            padding: 35px 15px 60px;

        }


        .card-box {

            background: white;

            border-radius: 18px;

            padding: 25px;

            box-shadow:
                0 5px 25px
                rgba(0,0,0,.05);

            margin-bottom: 20px;

        }


        .restaurant-logo {

            width: 65px;

            height: 65px;

            border-radius: 14px;

            object-fit: cover;

            background: #eee;

        }


        .status-badge {

            display: inline-block;

            padding: 8px 15px;

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


        /* TIMELINE */

        .timeline {

            position: relative;

            margin-top: 25px;

        }


        .timeline::before {

            content: "";

            position: absolute;

            left: 22px;

            top: 10px;

            bottom: 10px;

            width: 3px;

            background: #e9ecef;

        }


        .timeline-item {

            position: relative;

            display: flex;

            gap: 18px;

            margin-bottom: 30px;

        }


        .timeline-icon {

            position: relative;

            z-index: 2;

            width: 46px;

            height: 46px;

            min-width: 46px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #e9ecef;

            color: #6c757d;

        }


        .timeline-item.completed .timeline-icon {

            background: #20c997;

            color: white;

        }


        .timeline-item.current .timeline-icon {

            background: #20c997;

            color: white;

            box-shadow:
                0 0 0 6px
                rgba(32,201,151,.15);

        }


        .timeline-title {

            font-weight: 700;

            margin-bottom: 4px;

        }


        .timeline-description {

            color: #6c757d;

            font-size: 14px;

        }


        .order-item {

            display: flex;

            justify-content: space-between;

            padding: 17px 0;

            border-bottom: 1px solid #eee;

        }


        .order-item:last-child {

            border-bottom: none;

        }


        .item-name {

            font-weight: 700;

        }


        .item-meta {

            font-size: 14px;

            color: #6c757d;

        }


        .item-total {

            font-weight: 700;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 8px 0;

        }


        .total-row {

            border-top: 2px solid #eee;

            margin-top: 10px;

            padding-top: 15px;

            font-size: 20px;

            font-weight: 800;

        }


        .info-box {

            background: #f8f9fa;

            border-radius: 12px;

            padding: 15px;

            margin-bottom: 12px;

        }


        .info-box i {

            color: #20c997;

            margin-right: 8px;

        }


        .denied-box {

            background: #fff1f2;

            border: 1px solid #f5c2c7;

            border-radius: 14px;

            padding: 20px;

            color: #842029;

        }


        @media(max-width: 576px) {

            .page {

                padding-top: 20px;

            }

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


        <div class="d-flex align-items-center gap-3">


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

                Logout

            </a>


        </div>

    </div>

</nav>



<!-- =====================================================
     PAGE
===================================================== -->

<div class="page">


    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">


        <div>

            <a
                href="orders.php"
                class="text-decoration-none text-muted"
            >

                <i class="bi bi-arrow-left"></i>

                Back to My Orders

            </a>


            <h2 class="fw-bold mt-3 mb-1">

                Order #<?= htmlspecialchars(
                    $order['order_number']
                ) ?>

            </h2>


            <div class="text-muted">

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

            <?= htmlspecialchars($statusLabel) ?>

        </span>


    </div>



    <!-- =====================================================
         DENIED ORDER
    ===================================================== -->

    <?php if ($status === 'denied'): ?>


        <div class="denied-box mb-4">


            <h5 class="fw-bold">

                <i class="bi bi-x-circle-fill"></i>

                Order Denied

            </h5>


            <p class="mb-0">

                Unfortunately, the restaurant could not accept
                your order.

            </p>


            <?php if (
                !empty(
                    $order['denial_reason']
                )
            ): ?>

                <hr>

                <strong>
                    Reason:
                </strong>

                <p class="mb-0 mt-1">

                    <?= nl2br(
                        htmlspecialchars(
                            $order['denial_reason']
                        )
                    ) ?>

                </p>

            <?php endif; ?>


        </div>


    <?php endif; ?>



    <div class="row g-4">


        <!-- =================================================
             LEFT
        ================================================== -->

        <div class="col-lg-7">


            <!-- RESTAURANT -->

            <div class="card-box">


                <h5 class="fw-bold mb-4">

                    Restaurant

                </h5>


                <div class="d-flex align-items-center gap-3">


                    <?php if (
                        !empty(
                            $order[
                                'restaurant_logo'
                            ]
                        )
                    ): ?>


                        <img
                            src="../<?= htmlspecialchars(
                                $order[
                                    'restaurant_logo'
                                ]
                            ) ?>"
                            class="restaurant-logo"
                            alt="Restaurant"
                        >


                    <?php else: ?>


                        <div class="restaurant-logo d-flex align-items-center justify-content-center">

                            <i class="bi bi-shop fs-3 text-muted"></i>

                        </div>


                    <?php endif; ?>


                    <div>

                        <h5 class="fw-bold mb-1">

                            <?= htmlspecialchars(
                                $order[
                                    'restaurant_name'
                                ]
                            ) ?>

                        </h5>


                        <div class="text-muted">

                            <i class="bi bi-geo-alt"></i>

                            <?= htmlspecialchars(
                                $order[
                                    'restaurant_city'
                                ]
                            ) ?>

                        </div>

                    </div>


                </div>


            </div>



            <!-- ORDER ITEMS -->

            <div class="card-box">


                <h5 class="fw-bold mb-3">

                    <i class="bi bi-basket"></i>

                    Your Food

                </h5>


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


                            <div class="item-meta">

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


                                <small class="text-muted">

                                    <i class="bi bi-chat"></i>

                                    <?= htmlspecialchars(
                                        $item[
                                            'special_instructions'
                                        ]
                                    ) ?>

                                </small>


                            <?php endif; ?>


                        </div>


                        <div class="item-total">

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

                        Your Notes

                    </h5>


                    <p class="text-muted mb-0">

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

        <div class="col-lg-5">


            <!-- TRACKING -->

            <div class="card-box">


                <h5 class="fw-bold">

                    Track Your Order

                </h5>


                <?php if (
                    $status !== 'denied'
                    && $status !== 'cancelled'
                ): ?>


                    <div class="timeline">


                        <?php

                        $stepNumber = 0;

                        foreach (
                            $statusSteps
                            as $stepStatus => $step
                        ):

                            $stepNumber++;

                            $stepPosition =
                                $statusOrder[
                                    $stepStatus
                                ];

                            $isCompleted =
                                $stepPosition
                                < $currentStep;

                            $isCurrent =
                                $stepPosition
                                === $currentStep;

                        ?>


                            <div
                                class="
                                    timeline-item
                                    <?= $isCompleted
                                        ? 'completed'
                                        : '' ?>
                                    <?= $isCurrent
                                        ? 'current'
                                        : '' ?>
                                "
                            >


                                <div class="timeline-icon">

                                    <i
                                        class="bi <?= htmlspecialchars(
                                            $step['icon']
                                        ) ?>"
                                    ></i>

                                </div>


                                <div>


                                    <div class="timeline-title">

                                        <?= htmlspecialchars(
                                            $step['title']
                                        ) ?>


                                        <?php if (
                                            $isCurrent
                                        ): ?>

                                            <span
                                                class="badge bg-success ms-2"
                                            >

                                                Current

                                            </span>

                                        <?php endif; ?>


                                    </div>


                                    <div class="timeline-description">

                                        <?= htmlspecialchars(
                                            $step[
                                                'description'
                                            ]
                                        ) ?>

                                    </div>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </div>



            <!-- DELIVERY/PICKUP -->

            <div class="card-box">


                <h5 class="fw-bold mb-3">

                    <i class="bi bi-truck"></i>

                    <?= $order['order_type'] === 'delivery'
                        ? 'Delivery Information'
                        : 'Pickup Information' ?>

                </h5>


                <div class="info-box">


                    <?php if (
                        $order[
                            'order_type'
                        ] === 'delivery'
                    ): ?>


                        <i class="bi bi-geo-alt-fill"></i>

                        <strong>
                            Delivery Address
                        </strong>


                        <div class="mt-2 text-muted">

                            <?= nl2br(
                                htmlspecialchars(
                                    $order[
                                        'delivery_address'
                                    ]
                                    ?? 'Not provided'
                                )
                            ) ?>

                        </div>


                        <?php if (
                            !empty(
                                $order[
                                    'delivery_city'
                                ]
                            )
                        ): ?>

                            <div class="text-muted">

                                <?= htmlspecialchars(
                                    $order[
                                        'delivery_city'
                                    ]
                                ) ?>

                            </div>

                        <?php endif; ?>


                    <?php else: ?>


                        <i class="bi bi-bag-check-fill"></i>

                        <strong>

                            Pickup from Restaurant

                        </strong>


                        <div class="mt-2 text-muted">

                            <?= htmlspecialchars(
                                $order[
                                    'restaurant_address'
                                ]
                            ) ?>

                        </div>


                    <?php endif; ?>


                </div>


            </div>



            <!-- ORDER TOTAL -->

            <div class="card-box">


                <h5 class="fw-bold mb-3">

                    Payment Summary

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


                <div class="summary-row total-row">

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


                <hr>


                <div class="d-flex justify-content-between">

                    <span class="text-muted">

                        Payment Method

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


            </div>


        </div>


    </div>

</div>


</body>

</html>