<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";


// =====================================================
// CHECK SUPER ADMIN
// =====================================================

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'super_admin'
) {

    redirect('../login.php');

    exit;
}


// =====================================================
// GET ORDER ID
// =====================================================

$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$orderId) {

    redirect('orders.php');

    exit;
}


// =====================================================
// GET ORDER
// =====================================================

$orderStmt = $pdo->prepare("
    SELECT

        o.id,
        o.order_number,
        o.customer_id,
        o.restaurant_id,
        o.order_type,
        o.status,

        o.subtotal,
        o.delivery_fee,
        o.discount_amount,
        o.total_amount,

        o.payment_method,
        o.payment_status,

        o.delivery_address_id,
        o.delivery_address,
        o.delivery_city,

        o.delivery_latitude,
        o.delivery_longitude,

        o.customer_notes,
        o.restaurant_notes,

        o.denial_reason,
        o.cancellation_reason,

        o.estimated_preparation_minutes,

        o.placed_at,
        o.accepted_at,
        o.preparing_at,
        o.ready_at,
        o.completed_at,
        o.cancelled_at,

        o.created_at,
        o.updated_at,

        u.first_name AS customer_first_name,
        u.last_name AS customer_last_name,
        u.email AS customer_email,
        u.phone AS customer_phone,

        r.name AS restaurant_name,
        r.phone AS restaurant_phone,
        r.email AS restaurant_email,
        r.address AS restaurant_address,
        r.city AS restaurant_city

    FROM orders o

    INNER JOIN users u
        ON u.id = o.customer_id

    INNER JOIN restaurants r
        ON r.id = o.restaurant_id

    WHERE o.id = ?

    LIMIT 1
");

$orderStmt->execute([
    $orderId
]);

$order = $orderStmt->fetch();


if (!$order) {

    redirect('orders.php');

    exit;
}


// =====================================================
// GET ORDER ITEMS
// =====================================================

$itemStmt = $pdo->prepare("
    SELECT

        oi.id,
        oi.order_id,
        oi.menu_item_id,
        oi.item_name,
        oi.unit_price,
        oi.quantity,
        oi.subtotal,
        oi.special_instructions,

        mi.image

    FROM order_items oi

    LEFT JOIN menu_items mi
        ON mi.id = oi.menu_item_id

    WHERE oi.order_id = ?

    ORDER BY oi.id ASC
");

$itemStmt->execute([
    $orderId
]);

$orderItems = $itemStmt->fetchAll();


// =====================================================
// STATUS HELPERS
// =====================================================

function orderStatusClass($status)
{

    switch ($status) {

        case 'pending':
            return 'status-pending';

        case 'accepted':
            return 'status-accepted';

        case 'denied':
            return 'status-denied';

        case 'preparing':
            return 'status-preparing';

        case 'ready':
            return 'status-ready';

        case 'out_for_delivery':
            return 'status-delivery';

        case 'completed':
            return 'status-completed';

        case 'cancelled':
            return 'status-cancelled';

        default:
            return 'status-default';
    }
}


function orderStatusLabel($status)
{

    return ucwords(
        str_replace(
            '_',
            ' ',
            $status
        )
    );
}


// =====================================================
// FORMAT DATE
// =====================================================

function formatOrderDate($date)
{

    if (empty($date)) {

        return '—';

    }

    return date(
        'd M Y, H:i',
        strtotime($date)
    );
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
        Order Details - MloGo Admin
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f5f7fb;

        }


        .page-header {

            background: white;

            border-radius: 16px;

            padding: 22px 25px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .content-card {

            background: white;

            border: none;

            border-radius: 16px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.05);

        }


        .card-title {

            font-weight: 700;

        }


        .order-number {

            font-size: 24px;

            font-weight: 700;

        }


        .status-badge {

            display: inline-block;

            padding: 7px 13px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .status-pending {

            background: #fff3cd;

            color: #664d03;

        }


        .status-accepted {

            background: #cff4fc;

            color: #055160;

        }


        .status-denied {

            background: #f8d7da;

            color: #842029;

        }


        .status-preparing {

            background: #cfe2ff;

            color: #084298;

        }


        .status-ready {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-delivery {

            background: #e2d9f3;

            color: #432874;

        }


        .status-completed {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-cancelled {

            background: #f8d7da;

            color: #842029;

        }


        .status-default {

            background: #e2e3e5;

            color: #41464b;

        }


        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom: 1px solid #eee;

        }


        .info-row:last-child {

            border-bottom: none;

        }


        .info-label {

            color: #6c757d;

        }


        .info-value {

            text-align: right;

            font-weight: 600;

        }


        .item-image {

            width: 60px;

            height: 60px;

            object-fit: cover;

            border-radius: 10px;

        }


        .item-placeholder {

            width: 60px;

            height: 60px;

            border-radius: 10px;

            background: #f1f3f5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 9px 0;

        }


        .total-row {

            border-top: 2px solid #eee;

            margin-top: 10px;

            padding-top: 15px;

            font-size: 20px;

            font-weight: 700;

        }


        .timeline {

            position: relative;

            padding-left: 35px;

        }


        .timeline-item {

            position: relative;

            padding-bottom: 25px;

        }


        .timeline-item:last-child {

            padding-bottom: 0;

        }


        .timeline-item::before {

            content: "";

            position: absolute;

            left: -25px;

            top: 8px;

            width: 12px;

            height: 12px;

            border-radius: 50%;

            background: #198754;

        }


        .timeline-item:not(:last-child)::after {

            content: "";

            position: absolute;

            left: -20px;

            top: 20px;

            width: 2px;

            height: calc(100% - 8px);

            background: #dee2e6;

        }


        .timeline-label {

            font-weight: 600;

        }


        .timeline-date {

            color: #6c757d;

            font-size: 13px;

        }


        .note-box {

            background: #f8f9fa;

            border-radius: 10px;

            padding: 15px;

        }


        .danger-note {

            background: #fff1f2;

            border-left: 4px solid #dc3545;

        }


        .success-note {

            background: #f0fdf4;

            border-left: 4px solid #198754;

        }


        .location-link {

            text-decoration: none;

        }

    </style>

</head>


<body>


<div class="container-fluid p-4">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="page-header">


        <div
            class="d-flex justify-content-between align-items-center flex-wrap gap-3"
        >


            <div>


                <div class="d-flex align-items-center gap-2 mb-1">

                    <span class="order-number">

                        #<?= htmlspecialchars(
                            $order['order_number']
                        ) ?>

                    </span>


                    <span
                        class="status-badge <?= htmlspecialchars(
                            orderStatusClass(
                                $order['status']
                            )
                        ) ?>"
                    >

                        <?= htmlspecialchars(
                            orderStatusLabel(
                                $order['status']
                            )
                        ) ?>

                    </span>

                </div>


                <p class="text-muted mb-0">

                    Complete order information and activity.

                </p>


            </div>


            <div class="d-flex gap-2">


                <a
                    href="orders.php"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Back to Orders

                </a>


                <a
                    href="dashboard.php"
                    class="btn btn-success"
                >

                    <i class="bi bi-speedometer2 me-1"></i>

                    Admin Dashboard

                </a>


            </div>


        </div>


    </div>



    <!-- =================================================
         CUSTOMER + RESTAURANT
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- CUSTOMER -->

        <div class="col-lg-6">


            <div class="content-card p-4 h-100">


                <h5 class="card-title mb-3">

                    <i class="bi bi-person text-success me-2"></i>

                    Customer Information

                </h5>


                <div class="info-row">

                    <span class="info-label">

                        Name

                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            $order['customer_first_name']
                        ) ?>

                        <?= htmlspecialchars(
                            $order['customer_last_name']
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Email

                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            $order['customer_email']
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Phone

                    </span>

                    <span class="info-value">

                        <?= !empty($order['customer_phone'])
                            ? htmlspecialchars($order['customer_phone'])
                            : 'Not provided'
                        ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Customer ID

                    </span>

                    <span class="info-value">

                        #<?= (int)$order['customer_id'] ?>

                    </span>

                </div>


            </div>


        </div>



        <!-- RESTAURANT -->

        <div class="col-lg-6">


            <div class="content-card p-4 h-100">


                <h5 class="card-title mb-3">

                    <i class="bi bi-shop text-success me-2"></i>

                    Restaurant Information

                </h5>


                <div class="info-row">

                    <span class="info-label">

                        Restaurant

                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            $order['restaurant_name']
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Phone

                    </span>

                    <span class="info-value">

                        <?= !empty($order['restaurant_phone'])
                            ? htmlspecialchars($order['restaurant_phone'])
                            : 'Not provided'
                        ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Email

                    </span>

                    <span class="info-value">

                        <?= !empty($order['restaurant_email'])
                            ? htmlspecialchars($order['restaurant_email'])
                            : 'Not provided'
                        ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Address

                    </span>

                    <span class="info-value">

                        <?= !empty($order['restaurant_address'])
                            ? htmlspecialchars($order['restaurant_address'])
                            : 'Not provided'
                        ?>

                    </span>

                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         ORDER ITEMS + SUMMARY
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- ORDER ITEMS -->

        <div class="col-lg-8">


            <div class="content-card">


                <div class="p-4 border-bottom">


                    <h5 class="card-title mb-0">

                        <i class="bi bi-basket text-success me-2"></i>

                        Ordered Items

                    </h5>


                </div>


                <div class="table-responsive">


                    <table class="table align-middle mb-0">


                        <thead class="table-light">

                            <tr>

                                <th>Item</th>

                                <th>Unit Price</th>

                                <th>Quantity</th>

                                <th>Subtotal</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (count($orderItems) > 0): ?>


                            <?php foreach ($orderItems as $item): ?>


                                <tr>


                                    <td>


                                        <div class="d-flex align-items-center gap-3">


                                            <?php if (!empty($item['image'])): ?>

                                                <img
                                                    src="../uploads/foods/<?= htmlspecialchars(
                                                        $item['image']
                                                    ) ?>"
                                                    class="item-image"
                                                    alt="<?= htmlspecialchars(
                                                        $item['item_name']
                                                    ) ?>"
                                                >

                                            <?php else: ?>

                                                <div class="item-placeholder">

                                                    🍽️

                                                </div>

                                            <?php endif; ?>


                                            <div>


                                                <div class="fw-semibold">

                                                    <?= htmlspecialchars(
                                                        $item['item_name']
                                                    ) ?>

                                                </div>


                                                <?php if (
                                                    !empty(
                                                        $item['special_instructions']
                                                    )
                                                ): ?>

                                                    <small class="text-muted">

                                                        <i class="bi bi-chat-left-text me-1"></i>

                                                        <?= htmlspecialchars(
                                                            $item['special_instructions']
                                                        ) ?>

                                                    </small>

                                                <?php endif; ?>


                                            </div>


                                        </div>


                                    </td>


                                    <td>

                                        TZS
                                        <?= number_format(
                                            (float)$item['unit_price']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= (int)$item['quantity'] ?>

                                    </td>


                                    <td>

                                        <strong>

                                            TZS
                                            <?= number_format(
                                                (float)$item['subtotal']
                                            ) ?>

                                        </strong>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="4"
                                    class="text-center text-muted py-5"
                                >

                                    No order items found.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>



        <!-- ORDER SUMMARY -->

        <div class="col-lg-4">


            <div class="content-card p-4">


                <h5 class="card-title mb-3">

                    Order Summary

                </h5>


                <div class="summary-row">

                    <span class="text-muted">

                        Subtotal

                    </span>

                    <span>

                        TZS
                        <?= number_format(
                            (float)$order['subtotal']
                        ) ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span class="text-muted">

                        Delivery Fee

                    </span>

                    <span>

                        TZS
                        <?= number_format(
                            (float)$order['delivery_fee']
                        ) ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span class="text-muted">

                        Discount

                    </span>

                    <span class="text-success">

                        - TZS
                        <?= number_format(
                            (float)$order['discount_amount']
                        ) ?>

                    </span>

                </div>


                <div class="summary-row total-row">

                    <span>

                        Total

                    </span>

                    <span class="text-success">

                        TZS
                        <?= number_format(
                            (float)$order['total_amount']
                        ) ?>

                    </span>

                </div>


            </div>



            <!-- PAYMENT -->

            <div class="content-card p-4 mt-4">


                <h5 class="card-title mb-3">

                    <i class="bi bi-credit-card text-success me-2"></i>

                    Payment

                </h5>


                <div class="info-row">

                    <span class="info-label">

                        Method

                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $order['payment_method']
                                )
                            )
                        ) ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="info-label">

                        Status

                    </span>


                    <?php if (
                        strtolower(
                            $order['payment_status']
                        ) === 'paid'
                    ): ?>

                        <span class="text-success fw-semibold">

                            <i class="bi bi-check-circle-fill me-1"></i>

                            Paid

                        </span>

                    <?php else: ?>

                        <span class="text-warning fw-semibold">

                            <i class="bi bi-clock me-1"></i>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $order['payment_status']
                                )
                            ) ?>

                        </span>

                    <?php endif; ?>


                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         DELIVERY INFORMATION
    ================================================== -->

    <div class="row g-4 mb-4">


        <div class="col-lg-7">


            <div class="content-card p-4 h-100">


                <h5 class="card-title mb-3">

                    <i class="bi bi-geo-alt text-danger me-2"></i>

                    Delivery Information

                </h5>


                <div class="info-row">

                    <span class="info-label">

                        Order Type

                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $order['order_type']
                                )
                            )
                        ) ?>

                    </span>

                </div>


                <?php if (
                    !empty(
                        $order['delivery_address']
                    )
                ): ?>

                    <div class="info-row">

                        <span class="info-label">

                            Address

                        </span>

                        <span class="info-value">

                            <?= htmlspecialchars(
                                $order['delivery_address']
                            ) ?>

                        </span>

                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $order['delivery_city']
                    )
                ): ?>

                    <div class="info-row">

                        <span class="info-label">

                            City

                        </span>

                        <span class="info-value">

                            <?= htmlspecialchars(
                                $order['delivery_city']
                            ) ?>

                        </span>

                    </div>

                <?php endif; ?>


                <?php if (
                    $order['delivery_latitude'] !== null &&
                    $order['delivery_longitude'] !== null
                ): ?>


                    <div class="info-row">

                        <span class="info-label">

                            Location

                        </span>

                        <span class="info-value">


                            <a
                                href="https://www.google.com/maps?q=<?= urlencode(
                                    $order['delivery_latitude']
                                    . ','
                                    . $order['delivery_longitude']
                                ) ?>"
                                target="_blank"
                                class="location-link"
                            >

                                <i class="bi bi-map me-1"></i>

                                View on Map

                            </a>


                        </span>

                    </div>


                <?php endif; ?>


                <?php if (
                    !empty(
                        $order['estimated_preparation_minutes']
                    )
                ): ?>

                    <div class="info-row">

                        <span class="info-label">

                            Estimated Preparation

                        </span>

                        <span class="info-value">

                            <?= (int)$order[
                                'estimated_preparation_minutes'
                            ] ?>

                            minutes

                        </span>

                    </div>

                <?php endif; ?>


            </div>


        </div>



        <!-- NOTES -->

        <div class="col-lg-5">


            <div class="content-card p-4 h-100">


                <h5 class="card-title mb-3">

                    <i class="bi bi-chat-left-text text-success me-2"></i>

                    Order Notes

                </h5>


                <?php if (
                    !empty(
                        $order['customer_notes']
                    )
                ): ?>

                    <div class="note-box mb-3">


                        <strong>

                            Customer Note

                        </strong>


                        <p class="mb-0 mt-2">

                            <?= nl2br(
                                htmlspecialchars(
                                    $order['customer_notes']
                                )
                            ) ?>

                        </p>


                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $order['restaurant_notes']
                    )
                ): ?>

                    <div class="note-box success-note mb-3">


                        <strong>

                            Restaurant Note

                        </strong>


                        <p class="mb-0 mt-2">

                            <?= nl2br(
                                htmlspecialchars(
                                    $order['restaurant_notes']
                                )
                            ) ?>

                        </p>


                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $order['denial_reason']
                    )
                ): ?>

                    <div class="note-box danger-note mb-3">


                        <strong class="text-danger">

                            Denial Reason

                        </strong>


                        <p class="mb-0 mt-2">

                            <?= nl2br(
                                htmlspecialchars(
                                    $order['denial_reason']
                                )
                            ) ?>

                        </p>


                    </div>

                <?php endif; ?>


                <?php if (
                    !empty(
                        $order['cancellation_reason']
                    )
                ): ?>

                    <div class="note-box danger-note">


                        <strong class="text-danger">

                            Cancellation Reason

                        </strong>


                        <p class="mb-0 mt-2">

                            <?= nl2br(
                                htmlspecialchars(
                                    $order['cancellation_reason']
                                )
                            ) ?>

                        </p>


                    </div>

                <?php endif; ?>


                <?php if (
                    empty($order['customer_notes']) &&
                    empty($order['restaurant_notes']) &&
                    empty($order['denial_reason']) &&
                    empty($order['cancellation_reason'])
                ): ?>

                    <p class="text-muted mb-0">

                        No notes or special messages for this order.

                    </p>

                <?php endif; ?>


            </div>


        </div>


    </div>



    <!-- =================================================
         ORDER TIMELINE
    ================================================== -->

    <div class="content-card p-4 mb-4">


        <h5 class="card-title mb-4">

            <i class="bi bi-clock-history text-success me-2"></i>

            Order Timeline

        </h5>


        <div class="timeline">


            <!-- PLACED -->

            <div class="timeline-item">

                <div class="timeline-label">

                    Order Placed

                </div>

                <div class="timeline-date">

                    <?= formatOrderDate(
                        $order['placed_at']
                    ) ?>

                </div>

            </div>


            <!-- ACCEPTED -->

            <?php if (
                !empty(
                    $order['accepted_at']
                )
            ): ?>

                <div class="timeline-item">

                    <div class="timeline-label">

                        Order Accepted

                    </div>

                    <div class="timeline-date">

                        <?= formatOrderDate(
                            $order['accepted_at']
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- PREPARING -->

            <?php if (
                !empty(
                    $order['preparing_at']
                )
            ): ?>

                <div class="timeline-item">

                    <div class="timeline-label">

                        Preparation Started

                    </div>

                    <div class="timeline-date">

                        <?= formatOrderDate(
                            $order['preparing_at']
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- READY -->

            <?php if (
                !empty(
                    $order['ready_at']
                )
            ): ?>

                <div class="timeline-item">

                    <div class="timeline-label">

                        Order Ready

                    </div>

                    <div class="timeline-date">

                        <?= formatOrderDate(
                            $order['ready_at']
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- COMPLETED -->

            <?php if (
                !empty(
                    $order['completed_at']
                )
            ): ?>

                <div class="timeline-item">

                    <div class="timeline-label text-success">

                        Order Completed

                    </div>

                    <div class="timeline-date">

                        <?= formatOrderDate(
                            $order['completed_at']
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- CANCELLED -->

            <?php if (
                !empty(
                    $order['cancelled_at']
                )
            ): ?>

                <div class="timeline-item">

                    <div class="timeline-label text-danger">

                        Order Cancelled

                    </div>

                    <div class="timeline-date">

                        <?= formatOrderDate(
                            $order['cancelled_at']
                        ) ?>

                    </div>

                </div>

            <?php endif; ?>


        </div>


    </div>



    <!-- =================================================
         FOOTER BUTTONS
    ================================================== -->

    <div class="d-flex justify-content-between mb-4">


        <a
            href="orders.php"
            class="btn btn-outline-secondary"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Back to Orders

        </a>


        <a
            href="dashboard.php"
            class="btn btn-success"
        >

            <i class="bi bi-speedometer2 me-1"></i>

            Admin Dashboard

        </a>


    </div>


</div>


</body>

</html>